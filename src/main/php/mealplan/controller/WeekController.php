<?php
namespace mealplan\controller;

use DateInterval;
use DatePeriod;
use mealplan\Database;
use mealplan\Date;
use mealplan\model\Meal;
use mealplan\model\MealType;
use mealplan\model\Space;
use mealplan\NotFoundException;
use mealplan\Translation;
use mealplan\TwigRenderer;

class WeekController
{
    public function redirectToPageWithSpaceId(int $spaceId)
    {
        header(sprintf("Location: /%d/%s", $spaceId, (new Date)->getStartOfWeek()->format("Y-m-d")));
    }

    public function redirectToPage()
    {
        $space = Database::getEntityManager()->getRepository(Space::class)->findOneBy([], ["id" => "ASC"]);

        header(sprintf("Location: /%d/%s", $space->getId(), (new Date)->getStartOfWeek()->format("Y-m-d")));
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
            "previousWeek" => $date->getPreviousWeek(),
            "nextWeek" => $date->getNextWeek(),
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
                "meals" => $perDayAndTypeMeals[$date->formatForKey()] ?? []
            ];
        }

        return $days;
    }
}