<?php
namespace mealplan;

use mealplan\orm\SpaceRepository;
use Symfony\Component\DependencyInjection\Container;

class TwigGlobals
{
    public function __construct(private readonly SpaceRepository $spaceRepository, private readonly Container $container)
    {
    }

    public function getSpaces()
    {
        return $this->spaceRepository->findAll();
    }

    public function getCustomLinks()
    {
        return $this->container->getParameter("app.custom-links");
    }
}