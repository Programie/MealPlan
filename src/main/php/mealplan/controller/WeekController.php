<?php
namespace mealplan\controller;

use DateInterval;
use DatePeriod;
use mealplan\datasource\Manager as DatasourceManager;
use mealplan\Date;
use mealplan\model\Space;
use mealplan\orm\MealRepository;
use mealplan\orm\MealTypeRepository;
use mealplan\orm\SpaceRepository;
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

        $currentSpace = $spaceRepository->findById($spaceId);
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

        $currentSpace = $spaceRepository->findById($spaceId);
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
        $space = $spaceRepository->findById($spaceId);
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