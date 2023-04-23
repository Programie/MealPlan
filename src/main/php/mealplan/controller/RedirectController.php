<?php

namespace mealplan\controller;

use mealplan\datetime\Date;
use mealplan\orm\SpaceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class RedirectController extends AbstractController
{
    #[Route("/space/{spaceId}", name: "redirectToWeekWithSpaceId", requirements: ["spaceId" => "\d+"], methods: ["GET"])]
    public function redirectToPageWithSpaceId(int $spaceId): Response
    {
        return $this->redirectToRoute("getWeekPage", [
            "spaceId" => $spaceId,
            "date" => (new Date)->getStartOfWeek()->format("Y-m-d")
        ]);
    }

    #[Route("/", name: "index", methods: ["GET"])]
    public function index(SpaceRepository $spaceRepository): Response
    {
        $space = $spaceRepository->findOneBy([], ["id" => "ASC"]);
        if ($space === null) {
            throw new NotFoundHttpException;
        }

        return $this->redirectToRoute("getWeekPage", [
            "spaceId" => $space->getId(),
            "date" => (new Date)->getStartOfWeek()->format("Y-m-d")
        ]);
    }
}