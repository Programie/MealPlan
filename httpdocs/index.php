<?php
use mealplan\controller\AllMealsController;
use mealplan\controller\WeekController;
use mealplan\Database;
use mealplan\httperror\HttpException;
use mealplan\httperror\NotFoundException;
use mealplan\Translation;
use mealplan\TwigRenderer;
use mealplan\Utils;

require_once __DIR__ . "/../bootstrap.php";

Database::init();
Translation::init();
TwigRenderer::init();

$router = new AltoRouter;

$router->map("GET", "/", [WeekController::class, "redirectToPage"]);
$router->map("GET", "/space/[i:spaceId]/?", [WeekController::class, "redirectToPageWithSpaceId"]);
$router->map("GET", "/space/[i:spaceId]/week/[:date]/?", [WeekController::class, "getPage"]);
$router->map("GET", "/space/[i:spaceId]/week/[:date].json", [WeekController::class, "getJson"]);
$router->map("GET", "/space/[i:spaceId]/week/[:date]/edit", [WeekController::class, "getEditPage"]);
$router->map("GET", "/space/[i:spaceId]/all-meals/?", [AllMealsController::class, "getPage"]);
$router->map("GET", "/space/[i:spaceId]/all-meals.json", [AllMealsController::class, "getList"]);
$router->map("POST", "/space/[i:spaceId]", [WeekController::class, "save"]);

$match = $router->match();

try {
    if ($match === false) {
        throw new NotFoundException;
    } else {
        $target = $match["target"];


        $reflectionMethod = new ReflectionMethod($target[0], $target[1]);
        $response = $reflectionMethod->invokeArgs(new $target[0], $match["params"]);
        if ($response !== null) {
            if (is_string($response)) {
                echo $response;
            } else {
                header("Content-Type: application/json");
                echo json_encode($response);
            }
        }
    }
} catch (HttpException $exception) {
    http_response_code($exception->getCode());

    if (Utils::hasHttpAccept("text/html")) {
        echo TwigRenderer::render("error-page", ["httpCode" => $exception->getCode(), "message" => $exception->getMessage()]);
    } else {
        echo $exception->getMessage();
    }
}