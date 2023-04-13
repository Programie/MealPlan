<?php
namespace mealplan\orm;

use Doctrine\ORM\EntityRepository;
use mealplan\model\MealType;
use mealplan\model\Space;

class MealTypeRepository extends EntityRepository
{
    /**
     * @param Space $space
     * @return MealType[]
     */
    public function findBySpace(Space $space): array
    {
        return $this->findBy(["space" => $space->getId()]);
    }
}