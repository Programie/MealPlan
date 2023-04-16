<?php
namespace mealplan\controller;

use mealplan\Config;
use mealplan\model\GroupedMeal;
use mealplan\model\Meal;
use mealplan\model\Space;
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
        /**
         * @var $currentSpace Space
         */
        $currentSpace = $spaceRepository->find($spaceId);
        if ($currentSpace === null) {
            throw new NotFoundHttpException;
        }

        return $this->render("all-meals.twig", [
            "spaces" => $spaceRepository->findAll(),
            "currentSpace" => $currentSpace
        ]);
    }

    #[Route("/space/{spaceId}/all-meals.json", name: "getAllMealsJson", requirements: ["spaceId" => "\d+"], methods: ["GET"])]
    public function getList(int $spaceId, MealRepository $mealRepository, Config $config): Response
    {
        $config = $config->get("app.all-meals") ?? [];

        /**
         * @var $allMeals Meal[]
         */
        $allMeals = $mealRepository->findBy(["space" => $spaceId], ["date" => "desc", "id" => "desc"]);

        $groupedMeals = [];

        foreach ($allMeals as $meal) {
            $text = $meal->getText();

            foreach ($config["exclude-pattern"] ?? [] as $pattern) {
                if (preg_match($pattern, $text)) {
                    continue 2;
                }
            }

            foreach ($config["remove-pattern"] ?? [] as $pattern) {
                $text = preg_replace($pattern, "", $text);
            }

            if (!isset($groupedMeals[$text])) {
                $groupedMeals[$text] = new GroupedMeal($text);
            }

            $groupedMeals[$text]->add($meal);
        }

        return $this->json(array_values($groupedMeals));
    }
}