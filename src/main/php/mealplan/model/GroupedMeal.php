<?php
namespace mealplan\model;

use JsonSerializable;
use mealplan\Date;

class GroupedMeal implements JsonSerializable
{
    /**
     * @var string
     */
    private string $text;

    /**
     * @var Date[]
     */
    private array $dates;

    /**
     * @var string[]
     */
    private array $urls;

    public function __construct(Meal $meal)
    {
        $this->text = $meal->getText();
        $this->dates = [];
        $this->urls = [];
    }

    public function add(Meal $meal)
    {
        $this->dates[] = $meal->getDate();

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

    public function getDates(): array
    {
        return $this->dates;
    }

    public function getUrls(): array
    {
        return $this->urls;
    }

    public function jsonSerialize(): array
    {
        return [
            "text" => $this->getText(),
            "dates" => $this->getDates(),
            "urls" => $this->getUrls()
        ];
    }
}