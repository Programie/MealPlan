<?php
namespace mealplan\datasource\provider;

class DefaultProvider implements Provider
{
    public function getItems(): array
    {
        return [];
    }
}