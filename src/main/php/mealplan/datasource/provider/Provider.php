<?php
namespace mealplan\datasource\provider;

use mealplan\datasource\Item;

interface Provider
{
    /**
     * @return Item[]
     */
    public function getItems(): array;
}