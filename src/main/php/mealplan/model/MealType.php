<?php
namespace mealplan\model;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "mealplan\orm\MealTypeRepository")]
#[ORM\Table(name: "mealtypes")]
class MealType
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: "string")]
    private string $name;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): MealType
    {
        $this->name = $name;

        return $this;
    }
}