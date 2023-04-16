<?php
namespace mealplan\model;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use mealplan\DateTime;

#[ORM\Entity(repositoryClass: "mealplan\orm\NotificationRepository")]
#[ORM\Table(name: "notifications")]
class Notification implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\OneToOne(inversedBy: "notification", targetEntity: "Meal")]
    #[ORM\JoinColumn(name: "meal", referencedColumnName: "id")]
    private Meal $meal;

    #[ORM\Column(type: "datetime")]
    private DateTime $time;

    #[ORM\Column(type: "string")]
    private ?string $text;

    #[ORM\Column(type: "boolean")]
    private bool $triggered = false;

    public function getId(): int
    {
        return $this->id;
    }

    public function getMeal(): Meal
    {
        return $this->meal;
    }

    public function setMeal(Meal $meal): Notification
    {
        $this->meal = $meal;

        return $this;
    }

    public function getTime(): DateTime
    {
        return $this->time;
    }

    public function setTime(DateTime $time): Notification
    {
        $this->time = $time;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): Notification
    {
        $this->text = $text;

        return $this;
    }

    public function wasTriggered(): bool
    {
        return $this->triggered;
    }

    public function setTriggered(bool $state): Notification
    {
        $this->triggered = $state;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            "time" => $this->getTime(),
            "text" => $this->getText(),
            "triggered" => $this->wasTriggered()
        ];
    }
}