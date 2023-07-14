<?php
namespace mealplan\model;

use DateInterval;
use mealplan\orm\SpaceRepository;

readonly class NotificationPattern
{
    public function __construct(
        private ?Space  $space,
        private array   $patterns,
        private string  $time,
        private ?string $text
    )
    {
    }

    public static function fromConfig(SpaceRepository $spaceRepository, array $config): static
    {
        $spaceId = $config["space"] ?? null;
        if ($spaceId !== null) {
            $space = $spaceRepository->findById($spaceId);
        } else {
            $space = null;
        }

        return new static($space, $config["patterns"] ?? [], $config["time"], $config["text"] ?? null);
    }

    public function checkMatch(Space $space, string $text): bool
    {
        if ($this->space !== null and $this->space->getId() !== $space->getId()) {
            return false;
        }

        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    public function getDateInterval(): DateInterval
    {
        return new DateInterval(sprintf("PT%s", $this->time));
    }

    public function getText(): ?string
    {
        return $this->text;
    }
}