<?php
namespace mealplan\controller;

use DateInterval;
use DatePeriod;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use mealplan\datasource\Manager as DatasourceManager;
use mealplan\Date;
use mealplan\DateTime;
use mealplan\model\Meal;
use mealplan\model\MealType;
use mealplan\model\Notification;
use mealplan\model\Space;
use mealplan\orm\MealRepository;
use mealplan\orm\MealTypeRepository;
use mealplan\orm\SpaceRepository;
use mealplan\sanitize\Sanitize;
use mealplan\sanitize\StringTooLongException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class WeekController extends AbstractController
{
    #[Route("/space/{spaceId}", name: "redirectToWeekWithSpaceId", requirements: ["spaceId" => "\d+"], methods: ["GET"])]
    public function redirectToPageWithSpaceId(int $spaceId): Response
    {
        return $this->redirectToRoute("getWeekPage", [
            "spaceId" => $spaceId,
            "date" => (new Date)->getStartOfWeek()->format("Y-m-d")
        ]);
    }

    #[Route("/", name: "index", methods: ["GET"])]
    public function index(SpaceRepository $spaceRepository): Response
    {
        $space = $spaceRepository->findOneBy([], ["id" => "ASC"]);
        if ($space === null) {
            throw new NotFoundHttpException;
        }

        return $this->redirectToRoute("getWeekPage", [
            "spaceId" => $space->getId(),
            "date" => (new Date)->getStartOfWeek()->format("Y-m-d")
        ]);
    }

    #[Route("/space/{spaceId}/week/{date}", name: "getWeekPage", requirements: ["spaceId" => "\d+", "date" => "\d{4}-\d{2}-\d{2}"], methods: ["GET"])]
    public function getPage(int $spaceId, string $date, SpaceRepository $spaceRepository, MealTypeRepository $mealTypeRepository, MealRepository $mealRepository, TranslatorInterface $translator): Response
    {
        $date = new Date($date);

        /**
         * @var $currentSpace Space
         */
        $currentSpace = $spaceRepository->find($spaceId);
        if ($currentSpace === null) {
            throw new NotFoundHttpException;
        }

        $startDate = $date->getStartOfWeek();
        $endDate = $date->getEndOfWeek();

        $mealTypes = [];

        $mealTypeRows = $mealTypeRepository->findBySpace($currentSpace);
        foreach ($mealTypeRows as $mealType) {
            $mealTypes[$mealType->getId()] = $mealType->getName();
        }

        return $this->render("week.twig", [
            "currentSpace" => $currentSpace,
            "nowWeek" => (new Date)->getStartOfWeek(),
            "previousWeek" => $date->getPreviousWeek()->getStartOfWeek(),
            "nextWeek" => $date->getNextWeek()->getStartOfWeek(),
            "startDate" => $startDate,
            "endDate" => $endDate,
            "mealTypes" => $mealTypes,
            "days" => $this->getPerDayMeals($mealRepository, $currentSpace, $startDate, $endDate, $translator)
        ]);
    }

    #[Route("/space/{spaceId}/week/{date}/edit", name: "getWeekEditPage", requirements: ["spaceId" => "\d+", "date" => "\d{4}-\d{2}-\d{2}"], methods: ["GET"])]
    public function getEditPage(int $spaceId, string $date, SpaceRepository $spaceRepository, MealTypeRepository $mealTypeRepository, MealRepository $mealRepository, DatasourceManager $datasourceManager, TranslatorInterface $translator): Response
    {
        $date = new Date($date);

        /**
         * @var $currentSpace Space
         */
        $currentSpace = $spaceRepository->find($spaceId);
        if ($currentSpace === null) {
            throw new NotFoundHttpException;
        }

        $startDate = $date->getStartOfWeek();
        $endDate = $date->getEndOfWeek();

        $mealTypes = [];

        $mealTypeRows = $mealTypeRepository->findBySpace($currentSpace);
        foreach ($mealTypeRows as $mealType) {
            $mealTypes[$mealType->getId()] = $mealType->getName();
        }

        return $this->render("week-edit.twig", [
            "currentSpace" => $currentSpace,
            "nowWeek" => (new Date)->getStartOfWeek(),
            "previousWeek" => $date->getPreviousWeek()->getStartOfWeek(),
            "nextWeek" => $date->getNextWeek()->getStartOfWeek(),
            "startDate" => $startDate,
            "endDate" => $endDate,
            "mealTypes" => $mealTypes,
            "existingMeals" => $mealRepository->findBySpaceGroupedByText($currentSpace),
            "datasourceItems" => $datasourceManager->getItems(),
            "days" => $this->getPerDayMeals($mealRepository, $currentSpace, $startDate, $endDate, $translator)
        ]);
    }

    #[Route("/space/{spaceId}/week/{date}.json", name: "getWeekJson", requirements: ["spaceId" => "\d+", "date" => "\d{4}-\d{2}-\d{2}"], methods: ["GET"])]
    public function getJson(int $spaceId, string $date, SpaceRepository $spaceRepository, MealRepository $mealRepository, TranslatorInterface $translator): Response
    {
        /**
         * @var $space Space
         */
        $space = $spaceRepository->find($spaceId);

        if ($space === null) {
            throw new NotFoundHttpException;
        }

        $date = new Date($date);

        if (isset($_GET["days"])) {
            $days = (int)$_GET["days"];
            if ($days <= 0) {
                throw new BadRequestHttpException("Days must be > 0");
            }

            $startDate = $date;
            $endDate = clone $startDate;
            $endDate->add(new DateInterval(sprintf("P%dD", $days - 1)));
        } else {
            $startDate = $date->getStartOfWeek();
            $endDate = $date->getEndOfWeek();
        }

        return $this->json($this->getPerDayMeals($mealRepository, $space, $startDate, $endDate, $translator));
    }

    #[Route("/space/{spaceId}", name: "saveWeek", requirements: ["spaceId" => "\d+"], methods: ["POST"])]
    public function save(int $spaceId, EntityManagerInterface $entityManager, SpaceRepository $spaceRepository, MealTypeRepository $mealTypeRepository, MealRepository $mealRepository): Response
    {
        $inputData = json_decode(file_get_contents("php://input"), true);

        if ($inputData === null) {
            throw new BadRequestHttpException("Unable to parse JSON data");
        }

        if (!is_array($inputData)) {
            throw new BadRequestHttpException("JSON data must be an array");
        }

        /**
         * @var $space Space
         */
        $space = $spaceRepository->find($spaceId);
        if ($space === null) {
            throw new NotFoundHttpException;
        }

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
                throw new BadRequestHttpException(sprintf("Text of entry %d is too long", $inputDataIndex));
            }

            try {
                $url = Sanitize::cleanString($mealData["url"] ?? null, 2048);
            } catch (StringTooLongException) {
                throw new BadRequestHttpException(sprintf("URL of entry %d is too long", $inputDataIndex));
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
                throw new BadRequestHttpException(sprintf("Missing date in entry %d", $inputDataIndex));
            }

            $notificationTime = Sanitize::cleanString($notificationData["time"] ?? null);

            try {
                $notificationText = Sanitize::cleanString($notificationData["text"] ?? null, 200);
            } catch (StringTooLongException) {
                throw new BadRequestHttpException(sprintf("Notification text of entry %d is too long", $inputDataIndex));
            }

            if ($notificationTime === null) {
                $notificationDateTime = null;
            } else {
                try {
                    $notificationDateTime = new DateTime($notificationTime);
                } catch (Exception) {
                    throw new BadRequestHttpException(sprintf("Unable to parse notification time '%s' in entry %d", $notificationTime, $inputDataIndex));
                }
            }

            /**
             * @var $mealType MealType
             */
            $mealType = $mealTypeRepository->find($type);// TODO: Restrict to current space?
            if ($mealType === null) {
                throw new BadRequestHttpException(sprintf("Invalid meal type %d in entry %d", $type, $inputDataIndex));
            }

            if ($id === null) {
                $meal = new Meal;
                $notification = null;
            } else {
                $meal = $mealRepository->find($id);// TODO: Restrict to current space?

                if ($meal === null) {
                    throw new BadRequestHttpException(sprintf("Meal entry with ID %d does not exist", $id));
                }

                $notification = $meal->getNotification();
            }

            try {
                $meal->setDate(new Date($date));
            } catch (Exception) {
                throw new BadRequestHttpException(sprintf("Unable to parse date '%s' in entry %d", $date, $inputDataIndex));
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

        return $this->json(["status" => "ok"]);
    }

    private function getPerDayMeals(MealRepository $mealRepository, Space $space, Date $startDate, Date $endDate, TranslatorInterface $translator): array
    {
        $meals = $mealRepository->findBySpaceAndDateRange($space, $startDate, $endDate);

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
                "title" => $translator->trans(sprintf("weekday.long.%d", $date->format("N") - 1)),
                "date" => $date,
                "meals" => $perDayAndTypeMeals[$date->formatForKey()] ?? []
            ];
        }

        return $days;
    }
}