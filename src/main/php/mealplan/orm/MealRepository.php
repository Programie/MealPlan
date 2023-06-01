<?php

namespace mealplan\orm;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\Persistence\ManagerRegistry;
use mealplan\datetime\Date;
use mealplan\model\Meal;
use mealplan\model\Space;

class MealRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Meal::class);
    }

    public function findBySpaceAndId(Space $space, int $id): ?Meal
    {
        return $this->findOneBy(["id" => $id, "space" => $space->getId()]);
    }

    /**
     * @param Space $space
     * @param Date $startDate
     * @param Date $endDate
     * @param OrderBy|null $orderBy
     * @return Meal[]
     */
    public function findBySpaceAndDateRange(Space $space, Date $startDate, Date $endDate, ?array $orderBy = null): array
    {
        $queryBuilder = $this->createQueryBuilder("meal");

        $queryBuilder
            ->select("meal")
            ->where("meal.space = :space")
            ->andWhere($queryBuilder->expr()->between("meal.date", ":startDate", ":endDate"))
            ->setParameter(":space", $space->getId())
            ->setParameter(":startDate", $startDate->formatForDB())
            ->setParameter(":endDate", $endDate->formatForDB());

        if ($orderBy !== null) {
            foreach ($orderBy as $sort => $order) {
                $queryBuilder->addOrderBy(sprintf("meal.%s", $sort), $order);
            }
        }

        return $queryBuilder->getQuery()
            ->getResult();
    }

    /**
     * @param Space $space
     * @param array|null $orderBy
     * @return Meal[]
     */
    public function findBySpace(Space $space, ?array $orderBy = null): array
    {
        return $this->findBy(["space" => $space->getId()], $orderBy);
    }
}