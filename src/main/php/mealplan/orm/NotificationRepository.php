<?php
namespace mealplan\orm;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use mealplan\DateTime;
use mealplan\model\Notification;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

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