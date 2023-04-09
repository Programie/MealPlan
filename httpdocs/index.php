<?php
use mealplan\controller\WeekController;
use mealplan\Database;
use mealplan\Translation;
use mealplan\TwigRenderer;

require_once __DIR__ . "/../bootstrap.php";

Database::init();
Translation::init();
TwigRenderer::init();

$router = new AltoRouter;

$router->map("GET", "/", [WeekController::class, "redirectToPage"]);
$router->map("GET", "/[i:spaceId]/?", [WeekController::class, "redirectToPageWithSpaceId"]);
$router->map("GET", "/[i:spaceId]/[:date]/?", [WeekController::class, "getPage"]);
$router->map("GET", "/[i:spaceId]/[:date].json", [WeekController::class, "getJson"]);

$match = $router->match();

if ($match === false) {
    http_response_code(404);
    echo TwigRenderer::render("error-404");
} else {
    $target = $match["target"];

    $reflectionMethod = new ReflectionMethod($target[0], $target[1]);
    $response = $reflectionMethod->invokeArgs(new $target[0], $match["params"]);
    if ($response !== null) {
        echo $response;
    }
}