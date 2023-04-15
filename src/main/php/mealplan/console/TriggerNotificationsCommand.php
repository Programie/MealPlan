<?php
namespace mealplan\console;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use mealplan\orm\NotificationRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TriggerNotificationsCommand extends Command
{
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private NotificationRepository $notificationRepository;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager, NotificationRepository $notificationRepository)
    {
        parent::__construct("trigger-notifications");

        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->notificationRepository = $notificationRepository;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $notifications = $this->notificationRepository->findToBeTriggered();

        foreach ($notifications as $notification) {
            try {
                $notification->send();
            } catch (Exception $exception) {
                $this->logger->error($exception);
            }

            $notification->setTriggered(true);
            $this->entityManager->persist($notification);
            $this->entityManager->flush();
        }

        return Command::SUCCESS;
    }
}