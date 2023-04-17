<?php
namespace mealplan\orm;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateType as BaseDateType;
use mealplan\Date;
use mealplan\Time;

class TimeType extends BaseDateType
{
    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?Date
    {
        if ($value === null) {
            return null;
        }

        return new Time($value);
    }
}