<?php

namespace mealplan\controller;

use Doctrine\ORM\Query\Expr\OrderBy;
use mealplan\datetime\Date;
use mealplan\GroupedMealBuilder;
use mealplan\model\Space;
use mealplan\orm\MealRepository;
use mealplan\orm\SpaceRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class AllMealsController extends AbstractController
{
    #[Route("/space/{spaceId}/all-meals", name: "getAllMealsPage", requirements: ["spaceId" => "\d+"], methods: ["GET"])]
    #[Template("all-meals.twig")]
    public function getPage(int $spaceId, SpaceRepository $spaceRepository, MealRepository $mealRepository, Request $request): array
    {
        $currentSpace = $spaceRepository->findById($spaceId);
        if ($currentSpace === null) {
            throw new NotFoundHttpException;
        }

        list($startDate, $endDate) = $this->getDateRange($request, $currentSpace, $mealRepository);

        return [
            "spaces" => $spaceRepository->findAll(),
            "currentSpace" => $currentSpace,
            "startDate" => $startDate,
            "endDate" => $endDate
        ];
    }

    #[Route("/space/{spaceId}/all-meals.json", name: "getAllMealsJson", requirements: ["spaceId" => "\d+"], methods: ["GET"])]
    public function getList(int $spaceId, SpaceRepository $spaceRepository, MealRepository $mealRepository, GroupedMealBuilder $groupedMealBuilder, Request $request): Response
    {
        $currentSpace = $spaceRepository->findById($spaceId);
        if ($currentSpace === null) {
            throw new NotFoundHttpException;
        }

        list($startDate, $endDate) = $this->getDateRange($request, $currentSpace, $mealRepository);

        $allMeals = $mealRepository->findBySpaceAndDateRange($currentSpace, $startDate, $endDate, ["id" => "desc"]);

        return $this->json($groupedMealBuilder->buildFromMeals($allMeals));
    }

    private function getDateRange(Request $request, Space $space, MealRepository $mealRepository)
    {
        $startDate = $request->query->get("start");
        $endDate = $request->query->get("end");

        $defaultStartDate = (new Date)->getStartOfWeek();
        $defaultEndDate = (new Date)->getEndOfWeek();

        if ($startDate === null) {
            $firstMeal = $mealRepository->findOneBy(["space" => $space], ["date" => "asc"]);
            if ($firstMeal === null) {
                $startDate = $defaultStartDate;
                $endDate = $defaultEndDate;
            } else {
                $startDate = $firstMeal->getDate();
            }
        } else {
            $startDate = new Date($startDate);
        }

        if ($endDate === null) {
            $lastMeal = $mealRepository->findOneBy(["space" => $space], ["date" => "desc"]);
            if ($lastMeal === null) {
                $startDate = $defaultStartDate;
                $endDate = $defaultEndDate;
            } else {
                $endDate = $lastMeal->getDate();
            }
        } else {
            $endDate = new Date($endDate);
        }

        if ($endDate < $startDate) {
            list($endDate, $startDate) = [$startDate, $endDate];
        }

        return [$startDate, $endDate];
    }
}