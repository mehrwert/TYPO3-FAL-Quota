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

/**
 * Update storage usage statistics
 *
 * Use like this:
 *
 * ./htdocs/typo3/sysext/core/bin/typo3 fal_quota:quota:update
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
            ->setDescription('Update storage usage statistics')
            ->addArgument(
                'storage-id',
                InputArgument::OPTIONAL,
                'Id of single storage to update.'
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
