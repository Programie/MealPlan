<?php
namespace mealplan\orm;

use Doctrine\ORM\EntityRepository;
use mealplan\Date;
use mealplan\model\Meal;
use mealplan\model\Space;

class MealRepository extends EntityRepository
{
    /**
     * @param Space $space
     * @param Date $startDate
     * @param Date $endDate
     * @return Meal[]
     */
    public function findBySpaceAndDateRange(Space $space, Date $startDate, Date $endDate): array
    {
        $queryBuilder = $this->createQueryBuilder("meal");

        return $queryBuilder
            ->select("meal")
            ->where("meal.space = :space")
            ->andWhere($queryBuilder->expr()->between("meal.date", ":startDate", ":endDate"))
            ->setParameter(":space", $space->getId())
            ->setParameter(":startDate", $startDate->formatForDB())
            ->setParameter(":endDate", $endDate->formatForDB())
            ->getQuery()
            ->getResult();
    }

    public function findBySpaceGroupedByText(Space $space): array
    {
        $queryBuilder = $this->createQueryBuilder("meal");

        /**
         * @var $meals Meal[]
         */
        $meals = $queryBuilder
            ->select("meal")
            ->where("meal.space = :space")
            ->orderBy("meal.date", "DESC")
            ->addOrderBy("meal.id", "DESC")
            ->setParameter(":space", $space->getId())
            ->getQuery()
            ->getResult();

        // Usually, I would use a subquery ordering the table and then group it by the `text` field
        // But, Doctrine ORM does not support that
        // Therefore, group items in PHP as a workaround
        $groupedMeals = [];

        foreach ($meals as $meal) {
            if (isset($groupedMeals[$meal->getText()])) {
                continue;
            }

            $groupedMeals[$meal->getText()] = $meal;
        }

        return $groupedMeals;
    }
}