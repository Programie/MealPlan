<?php
namespace mealplan\controller;

use mealplan\Database;
use mealplan\model\Meal;
use mealplan\model\Space;
use mealplan\NotFoundException;

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