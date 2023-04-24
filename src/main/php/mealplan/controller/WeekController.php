<?php
namespace mealplan\controller;

use DateInterval;
use DatePeriod;
use mealplan\Config;
use mealplan\datasource\Manager as DatasourceManager;
use mealplan\datetime\Date;
use mealplan\GroupedMealBuilder;
use mealplan\model\Space;
use mealplan\orm\MealRepository;
use mealplan\orm\MealTypeRepository;
use mealplan\orm\SpaceRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class WeekController extends AbstractController
{
    private int $maxDays;

    public function __construct(
        private readonly SpaceRepository     $spaceRepository,
        private readonly MealTypeRepository  $mealTypeRepository,
        private readonly MealRepository      $mealRepository,
        private readonly TranslatorInterface $translator,
        private readonly Config              $config
    )
    {
        $this->maxDays = (int)$this->config->get("app.max-days");
    }

    #[Route("/space/{spaceId}/week/current", name: "getCurrentWeekPage", requirements: ["spaceId" => "\d+"], methods: ["GET"])]
    #[Template("week.twig")]
    public function getCurrentWeekPage(int $spaceId, Request $request): array
    {
        return $this->getPage($spaceId, (new Date)->formatForUrl(), $request);
    }

    #[Route("/space/{spaceId}/week/{date}", name: "getWeekPage", requirements: ["spaceId" => "\d+", "date" => "\d{4}-\d{2}-\d{2}"], methods: ["GET"])]
    #[Template("week.twig")]
    public function getPage(int $spaceId, string $date, Request $request): array
    {
        $currentSpace = $this->spaceRepository->findById($spaceId);
        if ($currentSpace === null) {
            throw new NotFoundHttpException;
        }

        return $this->getWeekData($currentSpace, $date, "getWeekPage", $request) + [
                "editUrl" => $this->generateUrl("getWeekEditPage", ["spaceId" => $spaceId, "date" => $date] + $request->query->all())
            ];
    }

    #[Route("/space/{spaceId}/week/{date}/edit", name: "getWeekEditPage", requirements: ["spaceId" => "\d+", "date" => "\d{4}-\d{2}-\d{2}"], methods: ["GET"])]
    #[Template("week-edit.twig")]
    public function getEditPage(int $spaceId, string $date, DatasourceManager $datasourceManager, GroupedMealBuilder $groupedMealBuilder, Request $request): array
    {
        $currentSpace = $this->spaceRepository->findById($spaceId);
        if ($currentSpace === null) {
            throw new NotFoundHttpException;
        }

        $allMeals = $this->mealRepository->findBySpace($currentSpace, ["id" => "desc"]);
        $groupedMeals = $groupedMealBuilder->buildFromMeals($allMeals);

        foreach ($groupedMeals as $groupedMeal) {
            $urls = $groupedMeal->getUrls();

            $autocompletionItems[$groupedMeal->getText()] = [
                "text" => $groupedMeal->getText(),
                "url" => empty($urls) ? null : $urls[0]
            ];
        }

        foreach ($datasourceManager->getItems() as $item) {
            $autocompletionItems[$item->getText()] = [
                "text" => $item->getText(),
                "url" => $item->getUrl()
            ];
        }

        ksort($autocompletionItems);

        $notes = trim($currentSpace->getNotes());
        if ($notes !== "") {
            $notes .= "\n";
        }

        return $this->getWeekData($currentSpace, $date, "getWeekEditPage", $request) + [
                "notes" => $notes,
                "autocompletionItems" => array_values($autocompletionItems),
                "viewUrl" => $this->generateUrl("getWeekPage", ["spaceId" => $spaceId, "date" => $date] + $request->query->all())
            ];
    }

    #[Route("/space/{spaceId}/week/{date}.json", name: "getWeekJson", requirements: ["spaceId" => "\d+", "date" => "\d{4}-\d{2}-\d{2}"], methods: ["GET"])]
    public function getJson(int $spaceId, string $date, Request $request): Response
    {
        $space = $this->spaceRepository->findById($spaceId);
        if ($space === null) {
            throw new NotFoundHttpException;
        }

        $date = new Date($date);

        $days = $request->query->getInt("days");
        if ($days > 0) {
            $days = min($days, $this->maxDays);

            $startDate = $date;
            $endDate = clone $startDate;
            $endDate->add(new DateInterval(sprintf("P%dD", $days - 1)));
        } else {
            $startDate = $date->getStartOfWeek();
            $endDate = $date->getEndOfWeek();
        }

        return $this->json($this->getPerDayMeals($space, $startDate, $endDate));
    }

    private function getWeekData(Space $space, string $date, string $route, Request $request): array
    {
        $date = new Date($date);

        $days = $request->query->getInt("days");
        if ($days > 0) {
            $days = min($days, $this->maxDays);

            $startDate = $date;
            $endDate = clone $startDate;
            $endDate->add(new DateInterval(sprintf("P%dD", $days - 1)));

            $previousDate = clone $startDate;
            $previousDate->sub(new DateInterval(sprintf("P%dD", $days)));
            $nextDate = clone $endDate;
            $nextDate->add(new DateInterval("P1D"));
        } else {
            $days = 0;

            $startDate = $date->getStartOfWeek();
            $endDate = $date->getEndOfWeek();

            $previousDate = $date->getPreviousWeek()->getStartOfWeek();
            $nextDate = $date->getNextWeek()->getStartOfWeek();
        }

        $nowDate = new Date;
        $queryParameters = $request->query->all();

        return [
            "numDays" => $days,
            "currentSpace" => $space,
            "urls" => [
                "previous" => $this->generateUrl($route, ["spaceId" => $space->getId(), "date" => $previousDate->formatForUrl()] + $queryParameters),
                "next" => $this->generateUrl($route, ["spaceId" => $space->getId(), "date" => $nextDate->formatForUrl()] + $queryParameters),
                "now" => $this->generateUrl($route, ["spaceId" => $space->getId(), "date" => $nowDate->formatForUrl()] + $queryParameters),
            ],
            "startDate" => $startDate,
            "endDate" => $endDate,
            "mealTypes" => $this->mealTypeRepository->findBySpace($space),
            "days" => $this->getPerDayMeals($space, $startDate, $endDate)
        ];
    }

    private function getPerDayMeals(Space $space, Date $startDate, Date $endDate): array
    {
        $meals = $this->mealRepository->findBySpaceAndDateRange($space, $startDate, $endDate);

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
                "title" => $this->translator->trans(sprintf("weekday.long.%d", $date->format("N") - 1)),
                "date" => $date,
                "meals" => $perDayAndTypeMeals[$date->formatForKey()] ?? []
            ];
        }

        return $days;
    }
}