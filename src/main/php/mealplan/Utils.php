<?php
namespace mealplan;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class Utils
{
    public static function flattenArray(array $array, string $separator = "."): array
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($array), RecursiveIteratorIterator::SELF_FIRST);
        $path = [];
        $flatArray = [];

        foreach ($iterator as $key => $value) {
            $path[$iterator->getDepth()] = $key;

            if (!is_array($value)) {
                $flatArray[implode($separator, array_slice($path, 0, $iterator->getDepth() + 1))] = $value;
            }
        }

        return $flatArray;
    }
}