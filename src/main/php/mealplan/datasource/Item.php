<?php
namespace mealplan\datasource;

class Item
{
    private string $text;
    private string $url;

    public function __construct(string $text, string $url)
    {
        $this->text = $text;
        $this->url = $url;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}