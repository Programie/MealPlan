<?php
namespace mealplan\controller;

use DateInterval;
use DatePeriod;
use Exception;
use mealplan\Database;
use mealplan\Date;
use mealplan\httperror\BadRequestException;
use mealplan\httperror\NotFoundException;
use mealplan\model\Meal;
use mealplan\model\MealType;
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
        $mealTypeRows = $entityManager->getRepository(MealType::class)->findAll();
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
        $mealTypeRows = $entityManager->getRepository(MealType::class)->findAll();
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

        $startDate = $date->getStartOfWeek();
        $endDate = $date->getEndOfWeek();

        header("Content-Type: application/json");
        return json_encode($this->getPerDayMeals($space, $startDate, $endDate));
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

        foreach ($inputData as $inputDataIndex => $mealData) {
            $id = Sanitize::cleanInt($mealData["id"] ?? null);
            $type = Sanitize::cleanInt($mealData["type"] ?? null);
            $date = Sanitize::cleanString($mealData["date"] ?? null);

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
                $meal = $mealRepository->find($id);

                if ($meal !== null) {
                    $entityManager->remove($meal);
                }

                continue;
            }

            if ($date === null) {
                throw new BadRequestException(sprintf("Missing date in entry %d", $inputDataIndex));
            }

            $notificationData = $mealData["notification"] ?? null;
            if (!is_array($notificationData)) {
                throw new BadRequestException(sprintf("Notification of entry %d is not an array", $inputDataIndex));
            }

            $notificationEnabled = $notificationData["enabled"] ?? false;

            try {
                $notificationTime = Sanitize::cleanString($notificationData["time"] ?? null, 5);
            } catch (StringTooLongException) {
                throw new BadRequestException(sprintf("Notification time of entry %d is too long", $inputDataIndex));
            }

            /**
             * @var $mealType MealType
             */
            $mealType = $mealTypeRepository->find($type);
            if ($mealType === null) {
                throw new BadRequestException(sprintf("Invalid meal type %d in entry %d", $type, $inputDataIndex));
            }

            if ($id === null) {
                $meal = new Meal;
            } else {
                $meal = $mealRepository->find($id);

                if ($meal === null) {
                    throw new BadRequestException(sprintf("Meal entry with ID %d does not exist", $id));
                }
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
            $meal->setNotificationEnabled($notificationEnabled);
            $meal->setNotificationTime($notificationTime);
            $entityManager->persist($meal);
        }

        $entityManager->flush();
        $entityManager->commit();
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
                "title" => Translation::tr(sprintf("weekday.%s", $date->format("l"))),
                "date" => $date,
                "meals" => $perDayAndTypeMeals[$date->formatForKey()] ?? []
            ];
        }

        return $days;
    }
}