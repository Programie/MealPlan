<?php
namespace mealplan\controller;

use mealplan\Config;
use mealplan\GroupedMealBuilder;
use mealplan\model\GroupedMeal;
use mealplan\model\Meal;
use mealplan\orm\MealRepository;
use mealplan\orm\SpaceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class AllMealsController extends AbstractController
{
    #[Route("/space/{spaceId}/all-meals", name: "getAllMealsPage", requirements: ["spaceId" => "\d+"], methods: ["GET"])]
    public function getPage(int $spaceId, SpaceRepository $spaceRepository): Response
    {
        $currentSpace = $spaceRepository->findById($spaceId);
        if ($currentSpace === null) {
            throw new NotFoundHttpException;
        }

        return $this->render("all-meals.twig", [
            "spaces" => $spaceRepository->findAll(),
            "currentSpace" => $currentSpace
        ]);
    }

    #[Route("/space/{spaceId}/all-meals.json", name: "getAllMealsJson", requirements: ["spaceId" => "\d+"], methods: ["GET"])]
    public function getList(int $spaceId, SpaceRepository $spaceRepository, MealRepository $mealRepository, GroupedMealBuilder $groupedMealBuilder): Response
    {
        $currentSpace = $spaceRepository->findById($spaceId);
        if ($currentSpace === null) {
            throw new NotFoundHttpException;
        }

        $allMeals = $mealRepository->findBySpace($currentSpace, ["id" => "desc"]);

        return $this->json($groupedMealBuilder->buildFromMeals($allMeals));
    }
}