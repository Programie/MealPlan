<?php
require_once __DIR__ . "/vendor/autoload.php";

const APP_ROOT = __DIR__;
const SRC_ROOT = __DIR__ . "/src/main/php";
const RESOURCES_ROOT = APP_ROOT . "/src/main/resources";
const VIEWS_ROOT = RESOURCES_ROOT . "/views";
const LANG_ROOT = RESOURCES_ROOT . "/lang";
const CACHE_ROOT = APP_ROOT . "/cache";
const TWIG_CACHE_ROOT = CACHE_ROOT . "/twig";

$useCacheEnv = getenv("USE_CACHE");
if ($useCacheEnv === false) {
    $useCacheEnv = "true";
}
define("USE_CACHE", $useCacheEnv !== "false");