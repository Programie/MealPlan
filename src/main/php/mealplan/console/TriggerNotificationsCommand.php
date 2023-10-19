<?php
namespace mealplan\console;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use mealplan\Config;
use mealplan\orm\NotificationRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class TriggerNotificationsCommand extends Command
{
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private NotificationRepository $notificationRepository;
    private array $config;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager, NotificationRepository $notificationRepository, Config $config)
    {
        parent::__construct("trigger-notifications");

        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->notificationRepository = $notificationRepository;
        $this->config = $config->get("app.notification") ?? [];
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $webhookUrl = $this->config["webhook-url"] ?? null;
        if ($webhookUrl === null) {
            $this->logger->error("Webhook URL not configured!");
            return Command::FAILURE;
        }

        $notifications = $this->notificationRepository->findToBeTriggered();
        $result = Command::SUCCESS;

        foreach ($notifications as $notification) {
            try {
                $client = new Client;
                $client->post($webhookUrl, [
                    RequestOptions::TIMEOUT => $this->config["webhook-timeout"] ?? 0,
                    RequestOptions::JSON => $notification->getMeal()
                ]);

                $notification->setTriggered(true);
                $this->entityManager->persist($notification);
                $this->entityManager->flush();
            } catch (Throwable $exception) {
                $this->logger->error($exception);
                $result = Command::FAILURE;
            }
        }

        return $result;
    }
}