<?php
namespace mealplan;

use mealplan\model\GroupedMeal;

class GroupedMealBuilder
{
    public function __construct(
        private readonly array $excludePatterns = [],
        private readonly array $removePatterns = []
    )
    {
    }

    public function buildFromMeals(array $meals): array
    {
        $groupedMeals = [];

        foreach ($meals as $meal) {
            $text = $meal->getText();

            foreach ($this->excludePatterns as $pattern) {
                if (preg_match($pattern, $text)) {
                    continue 2;
                }
            }

            foreach ($this->removePatterns as $pattern) {
                $text = preg_replace($pattern, "", $text);
            }

            if (!isset($groupedMeals[$text])) {
                $groupedMeals[$text] = new GroupedMeal($text);
            }

            $groupedMeals[$text]->add($meal);
        }

        return array_values($groupedMeals);
    }
}