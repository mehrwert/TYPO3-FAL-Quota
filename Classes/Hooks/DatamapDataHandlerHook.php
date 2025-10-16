<?php

declare(strict_types=1);

namespace Mehrwert\FalQuota\Hooks;

/*
 * 2019 - EXT:fal_quota
 *
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.md', which is part of this source code package.
 */

use Mehrwert\FalQuota\Utility\QuotaUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;

/**
 * Class DatamapDataHandlerHook to check and format TCA values for quota fields in sys_file_storage records
 */
readonly class DatamapDataHandlerHook
{
    private LanguageService $languageService;

    public function __construct(
        private StorageRepository $storageRepository,
        LanguageServiceFactory $languageServiceFactory
    ) {
        $this->languageService = $languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER'] ?? null);
    }

    public function processDatamap_postProcessFieldArray(mixed $status, mixed $table, mixed $id, mixed $fieldArray, DataHandler $tceMain): void
    {
        if (empty($tceMain->datamap['sys_file_storage'])) {
            return;
        }
        foreach ($tceMain->datamap['sys_file_storage'] as $storageId => $storage) {
            /** @var array{hard_limit: int, soft_quota: int} $storage */
            $this->validateQuotaConfiguration((int)$storageId, $storage, $tceMain);
        }
    }

    /**
     * Validate storage configuration
     *
     * @param array{hard_limit: int, soft_quota: int} $storage
     */
    private function validateQuotaConfiguration(int $storageId, array $storage, DataHandler $tceMain): void
    {
        $hardLimit = (int)$storage['hard_limit'] * (1024 ** 2);
        $softQuota = (int)$storage['soft_quota'] * (1024 ** 2);

        if ($hardLimit > 0 && $hardLimit < $softQuota) {
            $label = $this->languageService->sL('LLL:EXT:fal_quota/Resources/Private/Language/locallang_tce_hook_messages.xlf:' . 'quotaSettingMismatch');
            $message = vsprintf(
                $label,
                [
                    QuotaUtility::numberFormat($softQuota, 'MB'),
                    QuotaUtility::numberFormat($hardLimit, 'MB'),
                ]
            );
            $this->logStorageError($tceMain, $storageId, $message);
        }
        $resourceStorage = $this->storageRepository->findByUid($storageId);
        if ($resourceStorage !== null) {
            $availableSize = QuotaUtility::getAvailableSpaceOnStorageOnDevice($resourceStorage);
            // Check settings if available size is not -1
            if ($availableSize >= 0) {
                if ($hardLimit > $availableSize || $softQuota > $availableSize) {
                    $label = $this->languageService->sL('LLL:EXT:fal_quota/Resources/Private/Language/locallang_tce_hook_messages.xlf:' . 'diskspaceWarning');
                    $message = vsprintf(
                        $label,
                        [
                            QuotaUtility::numberFormat($availableSize, 'MB'),
                            QuotaUtility::numberFormat($softQuota, 'MB'),
                            QuotaUtility::numberFormat($hardLimit, 'MB'),
                        ]
                    );
                    $this->logStorageError($tceMain, $storageId, $message);
                }
            }
        }
    }

    /**
     * Log storage errors
     */
    private function logStorageError(DataHandler $tceMain, int $storageId, string $message): void
    {
        $tceMain->log(
            'sys_file_storage',
            $storageId,
            $storageId > 0 ? 2 : 1,
            null,
            1,
            $message
        );
    }
}
