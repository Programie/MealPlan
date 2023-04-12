<?php
namespace mealplan\controller;

use mealplan\Database;
use mealplan\httperror\NotFoundException;
use mealplan\model\GroupedMeal;
use mealplan\model\Meal;
use mealplan\model\Space;
use mealplan\TwigRenderer;

class AllMealsController
{
    public function getPage(int $spaceId)
    {
        $entityManager = Database::getEntityManager();

        /**
         * @var $currentSpace Space
         */
        $currentSpace = $entityManager->getRepository(Space::class)->find($spaceId);
        if ($currentSpace === null) {
            throw new NotFoundException;
        }

        return TwigRenderer::render("all-meals", [
            "currentSpace" => $currentSpace
        ]);
    }

    public function getList(int $spaceId)
    {
        $entityManager = Database::getEntityManager();

        /**
         * @var $allMeals Meal[]
         */
        $allMeals = $entityManager->getRepository(Meal::class)->findBy(["space" => $spaceId], ["date" => "desc", "id" => "desc"]);

        $groupedMeals = [];

        foreach ($allMeals as $meal) {
            $text = $meal->getText();

            if (!isset($groupedMeals[$text])) {
                $groupedMeals[$text] = new GroupedMeal($meal);
            }

            $groupedMeals[$text]->add($meal);
        }

        return array_values($groupedMeals);
    }
}