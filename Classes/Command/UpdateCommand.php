<?php

declare(strict_types=1);

namespace Mehrwert\FalQuota\Command;

use Doctrine\DBAL\Exception as DbalException;
use Mehrwert\FalQuota\Utility\QuotaUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Update storage usage statistics
 *
 * Use like this:
 *
 * ./htdocs/typo3/sysext/core/bin/typo3 fal_quota:usage:update
 */
final class UpdateCommand extends Command
{
    public function __construct(
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
        $this
            ->setDescription(
                LocalizationUtility::translate('LLL:EXT:fal_quota/Resources/Private/Language/locallang_task.xlf:update.command.description') ?? ''
            )
            ->addArgument(
                'storage-id',
                InputArgument::OPTIONAL,
                LocalizationUtility::translate('LLL:EXT:fal_quota/Resources/Private/Language/locallang_task.xlf:update.command.storageUid.description') ?? ''
            );
    }

    /**
     * @inheritDoc
     *
     * @throws DbalException
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storageId = (int)$input->getArgument('storage-id');
        if ($storageId > 0) {
            $storage = $this->storageRepository->findByUid($storageId);
            if ($storage !== null) {
                QuotaUtility::updateStorageUsage($storage->getUid());
            }
        } else {
            foreach ($this->storageRepository->findAll() as $storage) {
                QuotaUtility::updateStorageUsage($storage->getUid());
            }
        }

        return 0;
    }
}
