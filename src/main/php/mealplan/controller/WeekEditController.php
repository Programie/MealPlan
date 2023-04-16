<?php
namespace mealplan\controller;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use mealplan\Config;
use mealplan\Date;
use mealplan\DateTime;
use mealplan\model\Meal;
use mealplan\model\MealType;
use mealplan\model\Notification;
use mealplan\model\Space;
use mealplan\orm\MealRepository;
use mealplan\orm\MealTypeRepository;
use mealplan\orm\SpaceRepository;
use mealplan\sanitize\Sanitize;
use mealplan\sanitize\StringTooLongException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class WeekEditController extends AbstractController
{
    #[Route("/space/{spaceId}", name: "saveWeek", requirements: ["spaceId" => "\d+"], methods: ["POST"])]
    public function save(int $spaceId, EntityManagerInterface $entityManager, SpaceRepository $spaceRepository, MealTypeRepository $mealTypeRepository, MealRepository $mealRepository, Config $config, LoggerInterface $logger): Response
    {
        $inputData = json_decode(file_get_contents("php://input"), true);

        if ($inputData === null) {
            throw new BadRequestHttpException("Unable to parse JSON data");
        }

        if (!is_array($inputData)) {
            throw new BadRequestHttpException("JSON data must be an array");
        }

        /**
         * @var $space Space
         */
        $space = $spaceRepository->find($spaceId);
        if ($space === null) {
            throw new NotFoundHttpException;
        }

        $entityManager->beginTransaction();

        $savedWeek = null;

        foreach ($inputData as $inputDataIndex => $mealData) {
            $id = Sanitize::cleanInt($mealData["id"] ?? null);
            $type = Sanitize::cleanInt($mealData["type"] ?? null);
            $date = Sanitize::cleanString($mealData["date"] ?? null);
            $notificationData = $mealData["notification"] ?? [];

            try {
                $text = Sanitize::cleanString($mealData["text"] ?? null, 200);
            } catch (StringTooLongException) {
                throw new BadRequestHttpException(sprintf("Text of entry %d is too long", $inputDataIndex));
            }

            try {
                $url = Sanitize::cleanString($mealData["url"] ?? null, 2048);
            } catch (StringTooLongException) {
                throw new BadRequestHttpException(sprintf("URL of entry %d is too long", $inputDataIndex));
            }

            // No ID and no text -> ignore item
            if ($id === null and $text === null) {
                continue;
            }

            // ID specified but no text -> delete item
            if ($id !== null and $text === null) {
                $meal = $mealRepository->find($id);// TODO: Restrict to current space?

                if ($meal !== null) {
                    $entityManager->remove($meal);
                }

                continue;
            }

            if ($date === null) {
                throw new BadRequestHttpException(sprintf("Missing date in entry %d", $inputDataIndex));
            }

            $notificationTime = Sanitize::cleanString($notificationData["time"] ?? null);

            try {
                $notificationText = Sanitize::cleanString($notificationData["text"] ?? null, 200);
            } catch (StringTooLongException) {
                throw new BadRequestHttpException(sprintf("Notification text of entry %d is too long", $inputDataIndex));
            }

            if ($notificationTime === null) {
                $notificationDateTime = null;
            } else {
                try {
                    $notificationDateTime = new DateTime($notificationTime);
                } catch (Exception) {
                    throw new BadRequestHttpException(sprintf("Unable to parse notification time '%s' in entry %d", $notificationTime, $inputDataIndex));
                }
            }

            /**
             * @var $mealType MealType
             */
            $mealType = $mealTypeRepository->find($type);// TODO: Restrict to current space?
            if ($mealType === null) {
                throw new BadRequestHttpException(sprintf("Invalid meal type %d in entry %d", $type, $inputDataIndex));
            }

            if ($id === null) {
                $meal = new Meal;
                $notification = null;
            } else {
                $meal = $mealRepository->find($id);// TODO: Restrict to current space?

                if ($meal === null) {
                    throw new BadRequestHttpException(sprintf("Meal entry with ID %d does not exist", $id));
                }

                $notification = $meal->getNotification();
            }

            try {
                $meal->setDate(new Date($date));
            } catch (Exception) {
                throw new BadRequestHttpException(sprintf("Unable to parse date '%s' in entry %d", $date, $inputDataIndex));
            }

            $meal->setSpace($space);
            $meal->setType($mealType);
            $meal->setText($text);
            $meal->setUrl($url);

            $entityManager->persist($meal);

            $savedWeek = $meal->getDate()->getStartOfWeek();

            if ($notificationDateTime === null) {
                if ($notification !== null) {
                    $entityManager->remove($notification);
                }
            } else {
                if ($notification === null) {
                    $notification = new Notification;
                    $notification->setMeal($meal);
                }

                $notification->setTime($notificationDateTime);
                $notification->setText($notificationText);
                $notification->setTriggered(false);
                $entityManager->persist($notification);
            }
        }

        $entityManager->flush();
        $entityManager->commit();

        try {
            $saveConfig = $config->get("app.save") ?? [];
            $webhookUrl = $saveConfig["webhook-url"] ?? null;
            if ($webhookUrl !== null) {
                $client = new Client;
                $client->post($webhookUrl, [
                    RequestOptions::TIMEOUT => $saveConfig["webhook-timeout"] ?? 0,
                    RequestOptions::JSON => [
                        "space" => $spaceId,
                        "week" => $savedWeek?->formatForKey()
                    ]
                ]);
            }
        } catch (Throwable $exception) {
            $logger->error($exception);
        }

        return $this->json(["status" => "ok"]);
    }
}