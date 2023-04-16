<?php
namespace mealplan\orm;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use mealplan\model\Space;

class SpaceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Space::class);
    }

    public function findById(int $id): ?Space
    {
        return $this->find($id);
    }
}