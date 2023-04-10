<?php
namespace mealplan\sanitize;

class Sanitize
{
    public static function cleanInt(mixed $input): ?int
    {
        if ($input !== null) {
            $input = (int)$input;
        }

        return $input;
    }

    public static function cleanString(?string $input, ?int $maxLength = null): ?string
    {
        if ($input !== null) {
            $input = trim($input);

            if ($maxLength !== null and strlen($input) > $maxLength) {
                throw new StringTooLongException("String too long");
            }

            if ($input === "") {
                $input = null;
            }
        }

        return $input;
    }
}