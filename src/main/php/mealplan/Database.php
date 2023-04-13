<?php
namespace mealplan;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use mealplan\orm\DateTimeType;
use mealplan\orm\DateType;

class Database
{
    private static ?EntityManager $entityManager = null;

    public static function init()
    {
        Type::overrideType("date", DateType::class);
        Type::overrideType("datetime", DateTimeType::class);

        $config = ORMSetup::createAttributeMetadataConfiguration([SRC_ROOT], isDevMode: true);

        $connection = DriverManager::getConnection([
            "driver" => "pdo_mysql",
            "host" => getenv("DATABASE_HOST"),
            "dbname" => getenv("DATABASE_USERNAME"),
            "user" => getenv("DATABASE_USERNAME"),
            "password" => getenv("DATABASE_PASSWORD")
        ], $config);

        self::$entityManager = new EntityManager($connection, $config);
    }

    public static function getEntityManager(): EntityManager
    {
        if (self::$entityManager === null) {
            self::init();
        }

        return self::$entityManager;
    }
}