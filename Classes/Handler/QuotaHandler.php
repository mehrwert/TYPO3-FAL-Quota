<?php

declare(strict_types=1);

namespace Mehrwert\FalQuota\Handler;

use Doctrine\DBAL\Exception as DbalException;
use Mehrwert\FalQuota\Exception\ResourceStorageException;
use Mehrwert\FalQuota\Utility\QuotaUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Exception as CoreException;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class QuotaHandler
{
    private string $errorMessage = '';

    /**
     * @var array<int,int>
     */
    private array $storageUidByDeletedFileUidMap = [];

    public function __construct(
        private readonly FlashMessageService $flashMessageService
    ) {}

    /**
     * Update the storage quota usage where the file resides in
     *
     * @throws DbalException
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
        } catch (InsufficientFolderAccessPermissionsException|\Exception) {
            // Just catch the exception
        }
    }

    /**
     * Update the storage quota usage
     *
     * @throws DbalException
     */
    public function updateQuotaByFolder(FolderInterface $folder): void
    {
        QuotaUtility::updateStorageUsage($folder->getStorage()->getUid());
    }

    /**
     * Update the storage quota usage
     *
     * @throws DbalException
     */
    public function updateQuotaByDeletedFileUid(int $fileUid): void
    {
        QuotaUtility::updateStorageUsage($this->storageUidByDeletedFileUidMap[$fileUid]);
    }

    /**
     * General quota check using the values in the storage
     *
     * @throws DbalException
     */
    public function checkQuota(FolderInterface $targetFolder, int $code, string $action = '', string $file = ''): void
    {
        QuotaUtility::updateStorageUsage($targetFolder->getStorage()->getUid());
        if ($this->isOverLimit($targetFolder->getStorage())) {
            $this->addErrorMessageToFlashMessageQueue();
            throw new ResourceStorageException($this->errorMessage, $code);
        }
    }

    /**
     * Estimate the result size of the copy folder command
     */
    public function preEstimateUsageAfterCopyFolderCommand(Folder $folder, Folder $targetFolder, int $code): void
    {
        $folderSize = QuotaUtility::getFolderSize(
            $folder,
            QuotaUtility::getHardLimit($targetFolder->getStorage()) - QuotaUtility::getCurrentUsage($targetFolder->getStorage())
        );
        if ($this->isEstimatedUsageOverLimit($targetFolder->getStorage(), $folderSize)) {
            $this->errorMessage = $this->getLocalizedMessage(
                'copy_folder_result_will_exceed_quota',
                [
                    QuotaUtility::numberFormat(QuotaUtility::getSoftQuota($targetFolder->getStorage()), 'MB'),
                ]
            );
            $this->addErrorMessageToFlashMessageQueue();
            throw new ResourceStorageException($this->errorMessage, $code);
        }
    }

    /**
     * Estimate the file size with the new content
     */
    public function preEstimateUsageAfterUploadCommand(ResourceStorage $storage, int $fileSize, int $code): void
    {
        if ($this->isEstimatedUsageOverLimit($storage, $fileSize)) {
            $this->addErrorMessageToFlashMessageQueue();
            throw new ResourceStorageException($this->errorMessage, $code);
        }
    }

    /**
     * Estimate the file size with the new content
     */
    public function preEstimateUsageAfterSetContentCommand(FileInterface $file, mixed $content, int $code): void
    {
        $contentSize = strlen((string)$content);
        if ($this->isEstimatedUsageOverLimit($file->getStorage(), $contentSize)) {
            $this->addErrorMessageToFlashMessageQueue();
            throw new ResourceStorageException($this->errorMessage, $code);
        }
    }

    /**
     * Estimate the storage utilization after the file has been copied
     */
    public function preEstimateUsageAfterCopyCommand(FileInterface $file, Folder $targetFolder, int $code): void
    {
        $copiedFileSize = $file->getSize();
        if ($this->isEstimatedUsageOverLimit($targetFolder->getStorage(), $copiedFileSize)) {
            $this->addErrorMessageToFlashMessageQueue();
            throw new ResourceStorageException($this->errorMessage, $code);
        }
    }

    /**
     * Estimate the utilization of the target storage after the file would have been moved
     */
    public function preEstimateUsageAfterMoveCommand(FileInterface $file, Folder $targetFolder, int $code): void
    {
        // Use MB as unit for all numeric operations
        $movedFileSize = $file->getSize();
        if ($this->isEstimatedUsageOverLimit($targetFolder->getStorage(), $movedFileSize)) {
            $this->addErrorMessageToFlashMessageQueue();
            throw new ResourceStorageException($this->errorMessage, $code);
        }
    }

    /**
     * Estimate the utilization after the file would have been replaced with a smaller/bigger file
     */
    public function preEstimateUsageAfterReplaceCommand(FileInterface $file, string $localFilePath, int $code): void
    {
        if (is_file($localFilePath)) {
            $newFileSize = filesize($localFilePath);
            $currentFileSize = $file->getSize();
            if ($this->isEstimatedUsageOverLimit($file->getStorage(), $newFileSize - $currentFileSize)) {
                $this->addErrorMessageToFlashMessageQueue();
                throw new ResourceStorageException($this->errorMessage, $code);
            }
        }
    }

    /**
     * Check if storage is over quota
     */
    protected function isOverLimit(ResourceStorage $storage): bool
    {
        $hardLimit = QuotaUtility::getHardLimit($storage);
        $currentUsage = QuotaUtility::getCurrentUsage($storage);
        if ($currentUsage > $hardLimit) {
            $this->errorMessage = $this->getLocalizedMessage(
                'over_quota',
                [
                    $currentUsage,
                    QuotaUtility::numberFormat(QuotaUtility::getSoftQuota($storage), 'MB'),
                ]
            );
            return true;
        }
        return false;
    }

    /**
     * Check if estimated usage of storage is over quota
     */
    protected function isEstimatedUsageOverLimit(ResourceStorage $storage, int $contentSize): bool
    {
        // Check if quota has been set
        $hardLimit = QuotaUtility::getHardLimit($storage);
        if ($hardLimit > 0) {
            // Estimate new usage
            $estimatedUsage = QuotaUtility::getCurrentUsage($storage) + $contentSize;
            // Result would exceed quota
            if ($estimatedUsage >= $hardLimit) {
                $this->errorMessage = $this->getLocalizedMessage(
                    'result_will_exceed_quota',
                    [
                        QuotaUtility::numberFormat($estimatedUsage, 'MB', fractionDigits: 2),
                        QuotaUtility::numberFormat(QuotaUtility::getSoftQuota($storage), 'MB'),
                    ]
                );
                return true;
            }
        }
        return false;
    }

    /**
     * Get a localized message for quota warnings
     *
     * @param array<string|int|float> $replaceMarkers
     */
    protected function getLocalizedMessage(string $localizationKey, array $replaceMarkers = []): string
    {
        $label = $this->getLanguageService()->sL('LLL:EXT:fal_quota/Resources/Private/Language/locallang_resource_storage_messages.xlf:' . $localizationKey);

        return vsprintf($label, $replaceMarkers);
    }

    /**
     * Adds a localized FlashMessage to the message queue
     */
    protected function addErrorMessageToFlashMessageQueue(): void
    {
        if (Environment::isCli()) {
            return;
        }
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $this->errorMessage,
            '',
            ContextualFeedbackSeverity::ERROR,
            true
        );
        try {
            $this->addFlashMessage($flashMessage);
        } catch (CoreException) {
            // Just catch the exception
        }
    }

    /**
     * Add flash message to message queue
     *
     * @throws CoreException
     */
    protected function addFlashMessage(FlashMessage $flashMessage): void
    {
        $defaultFlashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    public function rememberStorageIdForDeletedFile(FileInterface $file): void
    {
        $this->storageUidByDeletedFileUidMap[(int)$file->getProperty('uid')] = $file->getStorage()->getUid();
    }
}
