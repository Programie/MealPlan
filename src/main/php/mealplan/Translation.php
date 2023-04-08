<?php
namespace mealplan;

use Symfony\Component\Yaml\Yaml;
use UnexpectedValueException;

class Translation
{
    private static array $translations;
    private static array $defaultTranslations;

    public static function init()
    {
        $language = substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2);

        if (!preg_match("/^([a-z]+)$/", $language)) {
            $language = "en";
        }

        $file = sprintf("%s/%s.yaml", LANG_ROOT, $language);
        if (!file_exists($file)) {
            $file = sprintf("%s/en.yaml", LANG_ROOT);
        }

        self::$translations = Utils::flattenArray(Yaml::parseFile($file));
        self::$defaultTranslations = Utils::flattenArray(Yaml::parseFile(sprintf("%s/en.yaml", LANG_ROOT)));
    }

    public static function tr(string $key, ...$arguments)
    {
        $text = self::$translations[$key] ?? self::$defaultTranslations[$key] ?? null;

        if ($text === null) {
            throw new UnexpectedValueException(sprintf("Missing translation for key '%s'", $key));
        }

        return sprintf($text, ...$arguments);
    }
}