<?php
namespace mealplan;

use JsonSerializable;

class Time extends Date implements JsonSerializable
{
    public function __toString(): string
    {
        return $this->format("H:i:s");
    }

    public function jsonSerialize(): string
    {
        return $this->format("H:i:s");
    }
}