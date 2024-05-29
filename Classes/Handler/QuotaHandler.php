<?php

declare(strict_types=1);
namespace Mehrwert\FalQuota\Handler;

use InvalidArgumentException;
use Mehrwert\FalQuota\Slot\ResourceStorageException;
use Mehrwert\FalQuota\Utility\QuotaUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class QuotaHandler
{
    /**
     * Configured soft quota for the current storage
     * @var float
     */
    private $softQuota;

    /**
     * Current usage of the current storage
     * @var float
     */
    private $currentUsage;

    /**
     * Update the storage quota usage where the file resides in
     *
     * @param FileInterface $file
     */
    public function updateQuotaByFile(FileInterface $file): void
    {
        try {
            $this->updateQuotaByFolder(
                $file->getStorage()->getFolder(
                    $file->getStorage()->getFolderIdentifierFromFileIdentifier(
                        $file->getIdentifier()
                    )
                )
            );
        } catch (InsufficientFolderAccessPermissionsException | \Exception $e) {
            // Just catch the exception
        }
    }

    /**
     * Update the storage quota usage
     *
     * @param Folder $folder
     */
    public function updateQuotaByFolder(Folder $folder): void
    {
        GeneralUtility::makeInstance(QuotaUtility::class)->updateStorageUsage($folder->getStorage()->getUid());
    }

    /**
     * General quota check using the values in the storage
     *
     * @param FolderInterface $targetFolder
     * @param int $code
     * @param string $action
     * @param string $file
     */
    public function checkQuota(FolderInterface $targetFolder, $code, $action = '', $file = ''): void
    {
        if ($this->isOverQuota($targetFolder->getStorage()->getUid()) === true) {
            $message = $this->getLocalizedMessage('over_quota', [$this->currentUsage, $this->softQuota]);
            $this->addMessageToFlashMessageQueue($message);
            throw new ResourceStorageException($message, $code);
        }
    }

    /**
     * Estimate the result size of the copy folder command
     *
     * @param Folder $folder
     * @param Folder $targetFolder
     * @param int $code
     */
    public function preEstimateUsageAfterCopyFolderCommand(Folder $folder, Folder $targetFolder, $code): void
    {
        $quotaUtility = GeneralUtility::makeInstance(QuotaUtility::class);
        $storageDetails = $quotaUtility->getStorageDetails($targetFolder->getStorage()->getUid());
        // Check if quota has been set
        if ($storageDetails['soft_quota_raw'] > 0) {
            $folderSize = $quotaUtility->getFolderSize($folder, $storageDetails['current_usage_raw']);
            $estimateUsage = $storageDetails['current_usage_raw'] + $folderSize;
            if ($estimateUsage > $storageDetails['soft_quota_raw']) {
                $message = $this->getLocalizedMessage(
                    'copy_folder_result_will_exceed_quota',
                    [
                        $storageDetails['soft_quota'],
                    ]
                );
                $this->addMessageToFlashMessageQueue($message);
                throw new ResourceStorageException($message, $code);
            }
        }
    }

    /**
     * Estimate the file size with the new content
     *
     * @param FileInterface $file
     * @param mixed $content
     * @param int $code
     */
    public function preEstimateUsageAfterSetContentCommand(FileInterface $file, $content, $code): void
    {
        $contentSize = strlen($content);
        $storageDetails = GeneralUtility::makeInstance(QuotaUtility::class)->getStorageDetails($file->getStorage()->getUid());
        // Check if quota has been set
        if ($storageDetails['soft_quota_raw'] > 0) {
            // Estimate new usage
            $estimatedUsage = $storageDetails['current_usage_raw'] + $contentSize;
            // Result would exceed quota
            if ($estimatedUsage >= $storageDetails['soft_quota_raw']) {
                $message = $this->getLocalizedMessage(
                    'result_will_exceed_quota',
                    [
                        number_format($estimatedUsage / 1024 / 1024, 2, ',', '.'),
                        $storageDetails['soft_quota'],
                    ]
                );
                $this->addMessageToFlashMessageQueue($message);
                throw new ResourceStorageException($message, $code);
            }
        }
    }

    /**
     * Estimate the storage utilization after the file has been copied
     *
     * @param FileInterface $file
     * @param Folder $targetFolder
     * @param int $code
     */
    public function preEstimateUsageAfterCopyCommand(FileInterface $file, Folder $targetFolder, $code): void
    {
        $copiedFileSize = $file->getSize();
        $storageDetails = GeneralUtility::makeInstance(QuotaUtility::class)->getStorageDetails($targetFolder->getStorage()->getUid());
        // Check if quota has been set
        if ($storageDetails['soft_quota_raw'] > 0) {
            // Estimate new usage
            $estimatedUsage = $storageDetails['current_usage_raw'] + $copiedFileSize;
            // Result would exceed quota
            if ($estimatedUsage >= $storageDetails['soft_quota_raw']) {
                $message = $this->getLocalizedMessage(
                    'result_will_exceed_quota',
                    [
                        number_format($estimatedUsage / 1024 / 1024, 2, ',', '.'),
                        $storageDetails['soft_quota'],
                    ]
                );
                $this->addMessageToFlashMessageQueue($message);
                throw new ResourceStorageException($message, $code);
            }
        }
    }

    /**
     * Estimate the utilization of the the target storage after the file would have been moved
     *
     * @param FileInterface $file
     * @param Folder $targetFolder
     * @param int $code
     */
    public function preEstimateUsageAfterMoveCommand(FileInterface $file, Folder $targetFolder, $code): void
    {
        // Use MB as unit for all numeric operations
        $movedFileSize = $file->getSize();
        $storageDetails = GeneralUtility::makeInstance(QuotaUtility::class)->getStorageDetails($targetFolder->getStorage()->getUid());
        // Check if quota has been set
        if ($storageDetails['soft_quota_raw'] > 0) {
            // Estimate new usage
            $estimatedUsage = $storageDetails['current_usage_raw'] + $movedFileSize;
            // Result would exceed quota
            if ($estimatedUsage >= $storageDetails['soft_quota_raw']) {
                $message = $this->getLocalizedMessage(
                    'result_will_exceed_quota',
                    [
                        number_format($estimatedUsage / 1024 / 1024, 2, ',', '.'),
                        $storageDetails['soft_quota'],
                    ]
                );
                $this->addMessageToFlashMessageQueue($message);
                throw new ResourceStorageException($message, $code);
            }
        }
    }

    /**
     * Estimate the utilization after the file would have been replaced with a smaller/bigger file
     *
     * @param FileInterface $file
     * @param string $localFilePath
     * @param int $code
     */
    public function preEstimateUsageAfterReplaceCommand(FileInterface $file, $localFilePath, $code): void
    {
        if (is_file($localFilePath)) {
            $newFileSize = filesize($localFilePath);
            $currentFileSize = $file->getSize();
            $storageDetails = GeneralUtility::makeInstance(QuotaUtility::class)->getStorageDetails($file->getStorage()->getUid());
            // Check if quota has been set
            if ($storageDetails['soft_quota_raw'] > 0) {
                // Estimate new usage
                $estimatedUsage = ($storageDetails['current_usage_raw'] - $currentFileSize) + $newFileSize;
                // Result would exceed quota
                if ($estimatedUsage >= $storageDetails['soft_quota_raw']) {
                    $message = $this->getLocalizedMessage(
                        'result_will_exceed_quota',
                        [
                            number_format($estimatedUsage / 1024 / 1024, 2, ',', '.'),
                            $storageDetails['soft_quota'],
                        ]
                    );
                    $this->addMessageToFlashMessageQueue($message);
                    throw new ResourceStorageException($message, $code);
                }
            }
        }
    }

    /**
     * Check if storage is over quota
     *
     * @param int $storageId
     * @return bool
     */
    protected function isOverQuota($storageId): bool
    {
        $storageDetails = GeneralUtility::makeInstance(QuotaUtility::class)->getStorageDetails($storageId);
        $this->softQuota = $storageDetails['soft_quota'];
        $this->currentUsage = $storageDetails['current_usage'];

        return $storageDetails['over_quota'];
    }

    /**
     * Get a localized message for quota warnings
     *
     * @param string $localizationKey
     * @param array $replaceMarkers
     * @return string
     */
    protected function getLocalizedMessage($localizationKey, array $replaceMarkers = []): string
    {
        $label = $this->getLanguageService()->sL('LLL:EXT:fal_quota/Resources/Private/Language/locallang_resource_storage_messages.xlf:' . $localizationKey);

        return vsprintf($label, $replaceMarkers);
    }

    /**
     * Adds a localized FlashMessage to the message queue
     *
     * @param string $message
     * @param int $severity
     * @throws InvalidArgumentException
     */
    protected function addMessageToFlashMessageQueue($message, $severity = FlashMessage::ERROR): void
    {
        if (\TYPO3\CMS\Core\Http\ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend() || Environment::isCli()) {
            return;
        }
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $message,
            '',
            $severity,
            true
        );
        try {
            $this->addFlashMessage($flashMessage);
        } catch (Exception $e) {
            // Just catch the exception
        }
    }

    /**
     * Add flash message to message queue
     *
     * @param FlashMessage $flashMessage
     * @throws Exception
     */
    protected function addFlashMessage(FlashMessage $flashMessage): void
    {
        /** @var FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);

        /** @var FlashMessageQueue $defaultFlashMessageQueue */
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * Returns LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
