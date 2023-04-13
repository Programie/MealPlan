<?php
namespace mealplan\orm;

use Doctrine\ORM\EntityRepository;
use mealplan\DateTime;
use mealplan\model\Notification;

class NotificationRepository extends EntityRepository
{
    /**
     * @return Notification[]
     */
    public function findToBeTriggered(): array
    {
        $queryBuilder = $this->createQueryBuilder("notification");

        return $queryBuilder
            ->select("notification")
            ->where("notification.triggered = false")
            ->andWhere("notification.time <= :now")
            ->setParameter(":now", new DateTime)
            ->getQuery()
            ->getResult();
    }
}