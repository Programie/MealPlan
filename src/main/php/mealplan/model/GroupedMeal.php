<?php
namespace mealplan\model;

use JsonSerializable;

class GroupedMeal implements JsonSerializable
{
    /**
     * @var string
     */
    private string $text;

    /**
     * @var Meal[]
     */
    private array $meals;

    /**
     * @var string[]
     */
    private array $urls;

    public function __construct(Meal $meal)
    {
        $this->text = $meal->getText();
        $this->meals = [];
        $this->urls = [];
    }

    public function add(Meal $meal)
    {
        $this->meals[] = $meal;

        $url = $meal->getUrl();
        if ($url !== null) {
            $this->urls[] = $url;

            $this->urls = array_unique($this->urls);
        }
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getMeals(): array
    {
        return $this->meals;
    }

    public function getUrls(): array
    {
        return $this->urls;
    }

    public function jsonSerialize(): array
    {
        return [
            "text" => $this->getText(),
            "meals" => $this->getMeals(),
            "urls" => $this->getUrls()
        ];
    }
}