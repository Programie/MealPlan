<?php
namespace mealplan\controller;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use mealplan\Config;
use mealplan\datetime\Date;
use mealplan\datetime\DateTime;
use mealplan\model\Meal;
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
    public function __construct(
        private readonly SpaceRepository        $spaceRepository,
        private readonly MealTypeRepository     $mealTypeRepository,
        private readonly MealRepository         $mealRepository,
        private readonly Config                 $config,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface        $logger
    )
    {
    }

    #[Route("/space/{spaceId}", name: "saveWeek", requirements: ["spaceId" => "\d+"], methods: ["POST"])]
    public function save(int $spaceId): Response
    {
        $inputData = json_decode(file_get_contents("php://input"), true);

        if ($inputData === null) {
            throw new BadRequestHttpException("Unable to parse JSON data");
        }

        $allMealData = $inputData["meals"] ?? null;

        if (!is_array($allMealData)) {
            throw new BadRequestHttpException("JSON data does not contain an array in property 'meals'");
        }

        $notes = Sanitize::cleanString($inputData["notes"] ?? null, 65535);

        /**
         * @var $space Space
         */
        $space = $this->spaceRepository->find($spaceId);
        if ($space === null) {
            throw new NotFoundHttpException;
        }

        $this->entityManager->beginTransaction();

        $savedWeek = null;

        foreach ($allMealData as $index => $mealData) {
            $meal = $this->saveItem($mealData, $index, $space);

            if ($meal !== null) {
                $savedWeek = $meal->getDate()->getStartOfWeek();
            }
        }

        $space->setNotes($notes ?? "");

        $this->entityManager->flush();
        $this->entityManager->commit();

        try {
            $this->triggerSaveWebhook($space, $savedWeek);
        } catch (Throwable $exception) {
            $this->logger->error($exception);
        }

        return $this->json(["status" => "ok"]);
    }

    private function saveItem($mealData, int $itemIndex, Space $space): ?Meal
    {
        $id = Sanitize::cleanInt($mealData["id"] ?? null);
        $date = Sanitize::cleanString($mealData["date"] ?? null);

        try {
            $text = Sanitize::cleanString($mealData["text"] ?? null, 200);
        } catch (StringTooLongException) {
            throw new BadRequestHttpException(sprintf("Text of entry %d is too long", $itemIndex));
        }

        try {
            $url = Sanitize::cleanString($mealData["url"] ?? null, 2048);
        } catch (StringTooLongException) {
            throw new BadRequestHttpException(sprintf("URL of entry %d is too long", $itemIndex));
        }

        // No ID and no text -> ignore item
        if ($id === null and $text === null) {
            return null;
        }

        // ID specified but no text -> delete item
        if ($id !== null and $text === null) {
            $meal = $this->mealRepository->findBySpaceAndId($space, $id);

            if ($meal !== null) {
                $this->entityManager->remove($meal);
            }

            return null;
        }

        if ($date === null) {
            throw new BadRequestHttpException(sprintf("Missing date in entry %d", $itemIndex));
        }

        $notificationData = $mealData["notification"] ?? [];
        $notificationTime = Sanitize::cleanString($notificationData["time"] ?? null);

        try {
            $notificationText = Sanitize::cleanString($notificationData["text"] ?? null, 200);
        } catch (StringTooLongException) {
            throw new BadRequestHttpException(sprintf("Notification text of entry %d is too long", $itemIndex));
        }

        if ($notificationTime === null) {
            $notificationDateTime = null;
        } else {
            try {
                $notificationDateTime = new DateTime($notificationTime);
            } catch (Exception) {
                throw new BadRequestHttpException(sprintf("Unable to parse notification time '%s' in entry %d", $notificationTime, $itemIndex));
            }
        }

        $type = Sanitize::cleanInt($mealData["type"] ?? null);

        $mealType = $this->mealTypeRepository->findBySpaceAndId($space, $type);
        if ($mealType === null) {
            throw new BadRequestHttpException(sprintf("Invalid meal type %d in entry %d", $type, $itemIndex));
        }

        if ($id === null) {
            $meal = new Meal;
            $notification = null;
        } else {
            $meal = $this->mealRepository->findBySpaceAndId($space, $id);

            if ($meal === null) {
                throw new BadRequestHttpException(sprintf("Meal entry with ID %d does not exist", $id));
            }

            $notification = $meal->getNotification();
        }

        try {
            $meal->setDate(new Date($date));
        } catch (Exception) {
            throw new BadRequestHttpException(sprintf("Unable to parse date '%s' in entry %d", $date, $itemIndex));
        }

        $meal->setSpace($space);
        $meal->setType($mealType);
        $meal->setText($text);
        $meal->setUrl($url);

        $this->entityManager->persist($meal);

        if ($notificationDateTime === null) {
            if ($notification !== null) {
                $this->entityManager->remove($notification);
            }
        } else {
            if ($notification === null) {
                $notification = new Notification;
                $notification->setMeal($meal);
            }

            $notification->setTime($notificationDateTime);
            $notification->setText($notificationText);

            if ($notificationDateTime->isInTheFuture()) {
                $notification->setTriggered(false);
            }

            $this->entityManager->persist($notification);
        }

        return $meal;
    }

    /**
     * @throws GuzzleException
     */
    private function triggerSaveWebhook(Space $space, ?Date $savedWeek): void
    {
        $saveConfig = $this->config->get("app.save") ?? [];
        $webhookUrl = $saveConfig["webhook-url"] ?? null;

        if ($webhookUrl === null) {
            return;
        }

        $client = new Client;
        $client->post($webhookUrl, [
            RequestOptions::TIMEOUT => $saveConfig["webhook-timeout"] ?? 0,
            RequestOptions::JSON => [
                "space" => $space,
                "week" => $savedWeek?->formatForKey()
            ]
        ]);
    }
}