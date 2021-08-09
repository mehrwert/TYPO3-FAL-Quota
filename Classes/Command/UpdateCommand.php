<?php
declare(strict_types=1);
namespace Mehrwert\FalQuota\Command;

use Mehrwert\FalQuota\Utility\QuotaUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
    /**
     * @var QuotaUtility
     */
    private $quotaUtility;

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this
            ->setDescription(
                LocalizationUtility::translate('LLL:EXT:fal_quota/Resources/Private/Language/locallang_task.xlf:update.command.description')
            )
            ->addArgument(
                'storage-id',
                InputArgument::OPTIONAL,
                LocalizationUtility::translate('LLL:EXT:fal_quota/Resources/Private/Language/locallang_task.xlf:update.command.storageUid.description')
            );
    }

    /**
     * @inheritDoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->quotaUtility = GeneralUtility::makeInstance(QuotaUtility::class);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $storageId = (int)$input->getArgument('storage-id');
        if ($storageId > 0) {
            $storages = $storageRepository->findByUid($storageId);
        } else {
            $storages = $storageRepository->findAll();
        }
        if (!empty($storages)) {
            foreach ($storages as $storage) {
                $this->quotaUtility->updateStorageUsage($storage->getUid());
            }
        }

        return 0;
    }
}
