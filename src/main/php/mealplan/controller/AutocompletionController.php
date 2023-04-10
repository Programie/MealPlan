<?php
namespace mealplan\controller;

use mealplan\Database;
use mealplan\httperror\NotFoundException;
use mealplan\model\Meal;
use mealplan\model\Space;

class AutocompletionController
{
    public function getData(int $spaceId)
    {
        $entityManager = Database::getEntityManager();

        /**
         * @var $currentSpace Space
         */
        $currentSpace = $entityManager->getRepository(Space::class)->find($spaceId);
        if ($currentSpace === null) {
            throw new NotFoundException;
        }

        /**
         * @var $meals Meal[]
         */
        $meals = $entityManager->getRepository(Meal::class)->findBySpaceGroupedByText($currentSpace);

        $items = [];

        foreach ($meals as $meal) {
            $items[] = $meal->getText();
        }

        header("Content-Type: application/json");
        return json_encode($items);
    }
}