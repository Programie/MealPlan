<?php
namespace mealplan\controller;

use DateInterval;
use DatePeriod;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use mealplan\Database;
use mealplan\Date;
use mealplan\DateTime;
use mealplan\httperror\BadRequestException;
use mealplan\httperror\NotFoundException;
use mealplan\model\Meal;
use mealplan\model\MealType;
use mealplan\model\Notification;
use mealplan\model\Space;
use mealplan\orm\MealRepository;
use mealplan\orm\MealTypeRepository;
use mealplan\sanitize\Sanitize;
use mealplan\sanitize\StringTooLongException;
use mealplan\Translation;
use mealplan\TwigRenderer;

class WeekController
{
    public function redirectToPageWithSpaceId(int $spaceId)
    {
        header(sprintf("Location: /space/%d/week/%s", $spaceId, (new Date)->getStartOfWeek()->format("Y-m-d")));
    }

    public function redirectToPage()
    {
        $space = Database::getEntityManager()->getRepository(Space::class)->findOneBy([], ["id" => "ASC"]);

        header(sprintf("Location: /space/%d/week/%s", $space->getId(), (new Date)->getStartOfWeek()->format("Y-m-d")));
    }

    public function getPage(int $spaceId, string $date)
    {
        $entityManager = Database::getEntityManager();

        $date = new Date($date);

        /**
         * @var $currentSpace Space
         */
        $currentSpace = $entityManager->getRepository(Space::class)->find($spaceId);
        if ($currentSpace === null) {
            throw new NotFoundException;
        }

        $startDate = $date->getStartOfWeek();
        $endDate = $date->getEndOfWeek();

        $mealTypes = [];

        /**
         * @var $mealTypeRows MealType[]
         */
        $mealTypeRows = $entityManager->getRepository(MealType::class)->findBySpace($currentSpace);
        foreach ($mealTypeRows as $mealType) {
            $mealTypes[$mealType->getId()] = $mealType->getName();
        }

        return TwigRenderer::render("week", [
            "currentSpace" => $currentSpace,
            "nowWeek" => (new Date)->getStartOfWeek(),
            "previousWeek" => $date->getPreviousWeek()->getStartOfWeek(),
            "nextWeek" => $date->getNextWeek()->getStartOfWeek(),
            "startDate" => $startDate,
            "endDate" => $endDate,
            "mealTypes" => $mealTypes,
            "days" => $this->getPerDayMeals($currentSpace, $startDate, $endDate)
        ]);
    }

    public function getEditPage(int $spaceId, string $date)
    {
        $entityManager = Database::getEntityManager();

        $date = new Date($date);

        /**
         * @var $currentSpace Space
         */
        $currentSpace = $entityManager->getRepository(Space::class)->find($spaceId);
        if ($currentSpace === null) {
            throw new NotFoundException;
        }

        $startDate = $date->getStartOfWeek();
        $endDate = $date->getEndOfWeek();

        $mealTypes = [];

        /**
         * @var $mealTypeRows MealType[]
         */
        $mealTypeRows = $entityManager->getRepository(MealType::class)->findBySpace($currentSpace);
        foreach ($mealTypeRows as $mealType) {
            $mealTypes[$mealType->getId()] = $mealType->getName();
        }

        return TwigRenderer::render("week-edit", [
            "currentSpace" => $currentSpace,
            "nowWeek" => (new Date)->getStartOfWeek(),
            "previousWeek" => $date->getPreviousWeek()->getStartOfWeek(),
            "nextWeek" => $date->getNextWeek()->getStartOfWeek(),
            "startDate" => $startDate,
            "endDate" => $endDate,
            "mealTypes" => $mealTypes,
            "existingMeals" => $entityManager->getRepository(Meal::class)->findBySpaceGroupedByText($currentSpace),
            "days" => $this->getPerDayMeals($currentSpace, $startDate, $endDate)
        ]);
    }

    public function getJson(int $spaceId, string $date)
    {
        $entityManager = Database::getEntityManager();

        /**
         * @var $space Space
         */
        $space = $entityManager->getRepository(Space::class)->find($spaceId);
        if ($space === null) {
            throw new NotFoundException;
        }

        $date = new Date($date);

        if (isset($_GET["days"])) {
            $days = (int)$_GET["days"];
            if ($days <= 0) {
                throw new BadRequestException("Days must be > 0");
            }

            $startDate = $date;
            $endDate = clone $startDate;
            $endDate->add(new DateInterval(sprintf("P%dD", $days - 1)));
        } else {
            $startDate = $date->getStartOfWeek();
            $endDate = $date->getEndOfWeek();
        }

        return $this->getPerDayMeals($space, $startDate, $endDate);
    }

    public function save(int $spaceId)
    {
        $inputData = json_decode(file_get_contents("php://input"), true);

        if ($inputData === null) {
            throw new BadRequestException("Unable to parse JSON data");
        }

        if (!is_array($inputData)) {
            throw new BadRequestException("JSON data must be an array");
        }

        $entityManager = Database::getEntityManager();

        /**
         * @var $space Space
         */
        $space = $entityManager->getRepository(Space::class)->find($spaceId);
        if ($space === null) {
            throw new NotFoundException;
        }

        /**
         * @var $mealTypeRepository MealTypeRepository
         */
        $mealTypeRepository = $entityManager->getRepository(MealType::class);

        /**
         * @var $mealRepository MealRepository
         */
        $mealRepository = $entityManager->getRepository(Meal::class);

        $entityManager->beginTransaction();

        $savedWeek = null;

        foreach ($inputData as $inputDataIndex => $mealData) {
            $id = Sanitize::cleanInt($mealData["id"] ?? null);
            $type = Sanitize::cleanInt($mealData["type"] ?? null);
            $date = Sanitize::cleanString($mealData["date"] ?? null);
            $notificationData = $mealData["notification"] ?? [];

            try {
                $text = Sanitize::cleanString($mealData["text"] ?? null, 200);
            } catch (StringTooLongException) {
                throw new BadRequestException(sprintf("Text of entry %d is too long", $inputDataIndex));
            }

            try {
                $url = Sanitize::cleanString($mealData["url"] ?? null, 2048);
            } catch (StringTooLongException) {
                throw new BadRequestException(sprintf("URL of entry %d is too long", $inputDataIndex));
            }

            // No ID and no text -> ignore item
            if ($id === null and $text === null) {
                continue;
            }

            // ID specified but no text -> delete item
            if ($id !== null and $text === null) {
                $meal = $mealRepository->find($id);// TODO: Restrict to current space?

                if ($meal !== null) {
                    $entityManager->remove($meal);
                }

                continue;
            }

            if ($date === null) {
                throw new BadRequestException(sprintf("Missing date in entry %d", $inputDataIndex));
            }

            $notificationTime = Sanitize::cleanString($notificationData["time"] ?? null);

            try {
                $notificationText = Sanitize::cleanString($notificationData["text"] ?? null, 200);
            } catch (StringTooLongException) {
                throw new BadRequestException(sprintf("Notification text of entry %d is too long", $inputDataIndex));
            }

            if ($notificationTime === null) {
                $notificationDateTime = null;
            } else {
                try {
                    $notificationDateTime = new DateTime($notificationTime);
                } catch (Exception) {
                    throw new BadRequestException(sprintf("Unable to parse notification time '%s' in entry %d", $notificationTime, $inputDataIndex));
                }
            }

            /**
             * @var $mealType MealType
             */
            $mealType = $mealTypeRepository->find($type);// TODO: Restrict to current space?
            if ($mealType === null) {
                throw new BadRequestException(sprintf("Invalid meal type %d in entry %d", $type, $inputDataIndex));
            }

            if ($id === null) {
                $meal = new Meal;
                $notification = null;
            } else {
                $meal = $mealRepository->find($id);// TODO: Restrict to current space?

                if ($meal === null) {
                    throw new BadRequestException(sprintf("Meal entry with ID %d does not exist", $id));
                }

                $notification = $meal->getNotification();
            }

            try {
                $meal->setDate(new Date($date));
            } catch (Exception) {
                throw new BadRequestException(sprintf("Unable to parse date '%s' in entry %d", $date, $inputDataIndex));
            }

            $meal->setSpace($space);
            $meal->setType($mealType);
            $meal->setText($text);
            $meal->setUrl($url);

            $entityManager->persist($meal);

            $savedWeek = $meal->getDate()->getStartOfWeek();

            if ($notificationDateTime === null) {
                if ($notification !== null) {
                    $entityManager->remove($notification);
                }
            } else {
                if ($notification === null) {
                    $notification = new Notification;
                    $notification->setMeal($meal);
                }

                $notification->setTime($notificationDateTime);
                $notification->setText($notificationText);
                $notification->setTriggered(false);
                $entityManager->persist($notification);
            }
        }

        $entityManager->flush();
        $entityManager->commit();

        $webhook = getenv("WEBHOOK_SAVE");
        if ($webhook !== false) {
            $client = new Client;
            $client->post($webhook, [
                RequestOptions::JSON => [
                    "space" => $spaceId,
                    "week" => $savedWeek?->formatForKey()
                ]
            ]);
        }
    }

    private function getPerDayMeals(Space $space, Date $startDate, Date $endDate): array
    {
        $entityManager = Database::getEntityManager();

        /**
         * @var $meals Meal[]
         */
        $meals = $entityManager->getRepository(Meal::class)->findBySpaceAndDateRange($space, $startDate, $endDate);

        $perDayAndTypeMeals = [];

        foreach ($meals as $meal) {
            $date = $meal->getDate()->formatForKey();
            $type = $meal->getType()->getId();

            if (!isset($perDayAndTypeMeals[$date])) {
                $perDayAndTypeMeals[$date] = [];
            }

            if (!isset($perDayAndTypeMeals[$date][$type])) {
                $perDayAndTypeMeals[$date][$type] = [];
            }

            $perDayAndTypeMeals[$date][$type][] = $meal;
        }

        $days = [];

        foreach (new DatePeriod($startDate, new DateInterval("P1D"), $endDate, DatePeriod::INCLUDE_END_DATE) as $date) {
            $date = new Date($date->format("c"));

            $days[$date->formatForKey()] = [
                "title" => Translation::tr(sprintf("weekday.long.%d", $date->format("N") - 1)),
                "date" => $date,
                "meals" => $perDayAndTypeMeals[$date->formatForKey()] ?? []
            ];
        }

        return $days;
    }
}