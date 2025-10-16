<?php

declare(strict_types=1);

namespace Mehrwert\FalQuota\Command;

/*
 * 2019 - EXT:fal_quota
 *
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.md', which is part of this source code package.
 */

use Doctrine\DBAL\Exception as DbalException;
use Mehrwert\FalQuota\Event\AddAdditionalRecipientsEvent;
use Mehrwert\FalQuota\Utility\QuotaUtility;
use Networkteam\SentryClient\Client;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Send quota notification
 *
 * Use like this:
 *
 * ./htdocs/typo3/sysext/core/bin/typo3 fal_quota:quota:notify
 */
final class NotifyCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly MailerInterface $mailer,
        private readonly StorageRepository $storageRepository
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    protected function configure(): void
    {
        $this->setDescription(
            LocalizationUtility::translate('LLL:EXT:fal_quota/Resources/Private/Language/locallang_task.xlf:notify.command.description') ?? ''
        );
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @inheritDoc
     *
     * @throws DbalException
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->storageRepository->findAll() as $storage) {
            $currentUsage = QuotaUtility::getTotalDiskSpaceUsedInStorage($storage->getUid());

            try {
                $this->checkThreshold($storage, $currentUsage);
            } catch (TransportExceptionInterface|\Exception $exception) {
                $errorMessage = 'FAL quota – Mail could not be sent: ' . $exception->getMessage();
                $this->io->error($errorMessage);

                /** @phpstan-ignore function.alreadyNarrowedType */
                if (method_exists(Client::class, 'captureException')) {
                    Client::captureException($exception);
                } else {
                    $this->logger->log(LogLevel::ERROR, $errorMessage);
                }
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Check the threshold and send notifications if exceeding limits
     *
     * @throws TransportExceptionInterface
     */
    private function checkThreshold(ResourceStorage $storage, int $currentUsage): void
    {
        $quotaConfiguration = [
            'current_usage' => $currentUsage,
            'soft_quota' => (int)$storage->getStorageRecord()['soft_quota'],
            'hard_limit' => (int)$storage->getStorageRecord()['hard_limit'],
            'quota_warning_threshold' => (int)$storage->getStorageRecord()['quota_warning_threshold'],
            'quota_warning_recipients' => $storage->getStorageRecord()['quota_warning_recipients'],
        ];
        if ($quotaConfiguration['soft_quota'] > 0 && $quotaConfiguration['quota_warning_threshold'] > 0) {
            $currentThreshold = (int)($quotaConfiguration['current_usage'] / $quotaConfiguration['soft_quota'] * 100);
            if ($quotaConfiguration['current_usage'] > $quotaConfiguration['soft_quota']
                || $currentThreshold >= $quotaConfiguration['quota_warning_threshold']
            ) {
                $this->sendNotification($storage, $quotaConfiguration, $currentThreshold);
            }
        }
    }

    /**
     * Send the over-quota-notification to all configured recipients
     *
     * @param array{quota_warning_recipients:string, soft_quota:int, current_usage:int} $quotaConfiguration
     *
     * @throws TransportExceptionInterface
     */
    private function sendNotification(ResourceStorage $storage, array $quotaConfiguration, int $currentThreshold): void
    {
        $hasRecipients = false;
        $warningRecipients = GeneralUtility::trimExplode(
            ',',
            $quotaConfiguration['quota_warning_recipients'],
            true
        );
        $validRecipientAddresses = [];

        /** @var AddAdditionalRecipientsEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new AddAdditionalRecipientsEvent(
                [],
                $storage
            )
        );
        $recipients = array_unique(
            array_merge($warningRecipients, $event->getAdditionalRecipients())
        );

        foreach ($recipients as $recipient) {
            if (GeneralUtility::validEmail($recipient)) {
                $validRecipientAddresses[] = $recipient;
                $hasRecipients = true;
            }
        }

        if ($hasRecipients === true) {
            $senderEmailAddress = !empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'])
                ? $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']
                : 'no-reply@example.com';
            $senderEmailName = !empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'])
                ? $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']
                : 'TYPO3 CMS';

            $subject = LocalizationUtility::translate(
                'email.subject',
                'FalQuota',
                [
                    $storage->getName(),
                    $storage->getUid(),
                    $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
                ]
            ) ?? '';
            $body = LocalizationUtility::translate(
                'email.body',
                'FalQuota',
                [
                    $storage->getName(),
                    $storage->getUid(),
                    QuotaUtility::numberFormat($quotaConfiguration['soft_quota'], 'MB'),
                    $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
                    QuotaUtility::numberFormat($quotaConfiguration['current_usage'], 'MB'),
                    $currentThreshold . '%',
                ]
            ) ?? '';

            $this
                ->sendNotificationWithSymfonyMail(
                    $subject,
                    $senderEmailAddress,
                    $senderEmailName,
                    $body,
                    $validRecipientAddresses
                );
        }
    }

    /**
     * @param string[] $recipients
     *
     * @throws TransportExceptionInterface
     */
    private function sendNotificationWithSymfonyMail(
        string $subject,
        string $senderEmailAddress,
        string $senderEmailName,
        string $body,
        array $recipients
    ): void {
        $this
            ->mailer
            ->send(
                GeneralUtility::makeInstance(MailMessage::class)
                    ->setTo($recipients)
                    ->subject($subject)
                    ->from(new Address($senderEmailAddress, $senderEmailName))
                    ->text($body)
            );
    }
}
