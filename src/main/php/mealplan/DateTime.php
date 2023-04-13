<?php
namespace mealplan;

use DateTimeZone;

class DateTime extends Date
{
    public static function fromUtc(string $datetime): DateTime
    {
        return new static($datetime, new DateTimeZone("UTC"));
    }

    public function toUtc(): DateTime
    {
        $utcDate = clone $this;
        $utcDate->setTimezone(new DateTimeZone("UTC"));
        return $utcDate;
    }

    public function formatForDB(): string
    {
        return $this->format("Y-m-d H:i:s");
    }
}