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
}