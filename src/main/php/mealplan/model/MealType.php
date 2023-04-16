<?php
namespace mealplan\model;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

#[ORM\Entity(repositoryClass: "mealplan\orm\MealTypeRepository")]
#[ORM\Table(name: "mealtypes")]
class MealType implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: "string")]
    private string $name;

    #[ORM\OneToOne(targetEntity: "Space")]
    #[ORM\JoinColumn(name: "space", referencedColumnName: "id")]
    private Space $space;

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

    public function getSpace(): Space
    {
        return $this->space;
    }

    public function setSpace(Space $space): MealType
    {
        $this->space = $space;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            "id" => $this->getId(),
            "name" => $this->getName()
        ];
    }
}