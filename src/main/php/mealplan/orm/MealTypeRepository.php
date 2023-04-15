<?php
namespace mealplan\orm;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use mealplan\model\MealType;
use mealplan\model\Space;

class MealTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MealType::class);
    }

    /**
     * @param Space $space
     * @return MealType[]
     */
    public function findBySpace(Space $space): array
    {
        return $this->findBy(["space" => $space->getId()]);
    }
}