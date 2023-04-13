#! /usr/bin/env php
<?php
use mealplan\Database;
use mealplan\model\Notification;

require_once __DIR__ . "/../bootstrap.php";

Database::init();

$entityManager = Database::getEntityManager();

/**
 * @var $notifications Notification[]
 */
$notifications = $entityManager->getRepository(Notification::class)->findToBeTriggered();

foreach ($notifications as $notification) {
    try {
        $notification->send();
    } catch (Exception $exception) {
        fwrite(STDERR, $exception);
    }

    $notification->setTriggered(true);
    $entityManager->persist($notification);
    $entityManager->flush();
}