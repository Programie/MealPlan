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
     * @var array
     */
    private array $urls;

    public function __construct(string $text)
    {
        $this->text = $text;
        $this->meals = [];
        $this->urls = [];
    }

    public function add(Meal $meal): void
    {
        $this->meals[] = $meal;

        $url = $meal->getUrl();
        if ($url !== null) {
            $this->urls[$url] = true;
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
        return array_keys($this->urls);
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