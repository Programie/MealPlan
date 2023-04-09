<?php
namespace mealplan;

use mealplan\model\Space;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class TwigRenderer
{
    private static ?Environment $twig = null;

    public static function init(): void
    {
        if (self::$twig !== null) {
            return;
        }

        $assetsPackage = new Package(new JsonManifestVersionStrategy(APP_ROOT . "/webpack.assets.json"));

        $loader = new FilesystemLoader(VIEWS_ROOT);

        self::$twig = new Environment($loader);

        self::$twig->addGlobal("spaces", Database::getEntityManager()->getRepository(Space::class)->findAll());

        self::$twig->addFunction(new TwigFunction("asset", function (string $path) use ($assetsPackage) {
            return $assetsPackage->getUrl($path);
        }));

        self::$twig->addFunction(new TwigFunction("tr", function (string $key, ...$arguments) {
            return Translation::tr($key, ...$arguments);
        }));

        if (USE_CACHE) {
            self::$twig->setCache(TWIG_CACHE_ROOT);
        }
    }

    /**
     * @param string $name
     * @param array $context
     * @return string
     * @throws Error
     */
    public static function render(string $name, array $context = []): string
    {
        return self::$twig->render($name . ".twig", $context);
    }
}