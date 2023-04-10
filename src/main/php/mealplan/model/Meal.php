<?php
namespace mealplan\model;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use mealplan\Date;

#[ORM\Entity(repositoryClass: "mealplan\orm\MealRepository")]
#[ORM\Table(name: "meals")]
class Meal implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: "date")]
    private Date $date;

    #[ORM\Column(type: "string")]
    private string $text;

    #[ORM\Column(type: "string")]
    private ?string $url;

    #[ORM\Column(type: "boolean")]
    private bool $notificationEnabled;

    #[ORM\Column(type: "string")]
    private ?string $notificationTime;

    #[ORM\OneToOne(targetEntity: "MealType")]
    #[ORM\JoinColumn(name: "type", referencedColumnName: "id")]
    private MealType $type;

    #[ORM\OneToOne(targetEntity: "Space")]
    #[ORM\JoinColumn(name: "space", referencedColumnName: "id")]
    private Space $space;

    public function getId(): int
    {
        return $this->id;
    }

    public function getDate(): Date
    {
        return $this->date;
    }

    public function setDate(Date $date): Meal
    {
        $this->date = $date;

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): Meal
    {
        $this->text = $text;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): Meal
    {
        $this->url = $url;

        return $this;
    }

    public function isNotificationEnabled(): ?bool
    {
        return $this->notificationEnabled;
    }

    public function setNotificationEnabled(bool $state): Meal
    {
        $this->notificationEnabled = $state;

        return $this;
    }

    public function getNotificationTime(): ?string
    {
        return $this->notificationTime;
    }

    public function setNotificationTime(?string $time): Meal
    {
        $this->notificationTime = $time;

        return $this;
    }

    public function getType(): MealType
    {
        return $this->type;
    }

    public function setType(MealType $type): Meal
    {
        $this->type = $type;

        return $this;
    }

    public function getSpace(): Space
    {
        return $this->space;
    }

    public function setSpace(Space $space): Meal
    {
        $this->space = $space;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            "id" => $this->getId(),
            "date" => $this->getDate()->format("c"),
            "text" => $this->getText(),
            "url" => $this->getUrl(),
            "type" => $this->getType()->getName(),
            "space" => $this->getSpace()->getName()
        ];
    }
}