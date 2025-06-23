<?php

declare(strict_types=1);

namespace Mehrwert\FalQuota\Utility;

/*
 * 2019 - EXT:fal_quota
 *
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.md', which is part of this source code package.
 */

use Doctrine\DBAL\Exception as DbalException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class QuotaUtility provides utilities to get storage details, quota settings and issue warning mails
 */
final readonly class QuotaUtility
{
    /**
     * Aggregate details for a given storage and return as array
     *
     * @return array<string,mixed>
     */
    public static function getStorageDetails(ResourceStorage $storage): array
    {
        $isOverQuota = false;
        $isOverThreshold = false;
        $currentThreshold = 0;

        if ((int)$storage->getStorageRecord()['soft_quota'] > 0) {
            $currentThreshold = (int)$storage->getStorageRecord()['current_usage'] / (int)$storage->getStorageRecord()['soft_quota'] * 100;
            if ((int)$storage->getStorageRecord()['current_usage'] > (int)$storage->getStorageRecord()['soft_quota']) {
                $isOverQuota = true;
            }
            if ($currentThreshold >= (int)$storage->getStorageRecord()['quota_warning_threshold']) {
                $isOverThreshold = true;
            }
        }

        return [
            'uid' => $storage->getUid(),
            'name' => $storage->getName(),
            'driver' => $storage->getDriverType(),
            'over_quota' => $isOverQuota,
            'over_threshold' => $isOverThreshold,
            'current_usage' => self::numberFormat((int)$storage->getStorageRecord()['current_usage'], 'MB', fractionDigits: 2),
            'current_usage_raw' => (int)$storage->getStorageRecord()['current_usage'],
            'soft_quota' => self::numberFormat((int)$storage->getStorageRecord()['soft_quota'], 'MB'),
            'soft_quota_raw' => (int)$storage->getStorageRecord()['soft_quota'],
            'hard_limit' => self::numberFormat((int)$storage->getStorageRecord()['hard_limit'], 'MB'),
            'hard_limit_raw' => (int)$storage->getStorageRecord()['hard_limit'],
            'quota_warning_threshold' => self::numberFormat((int)$storage->getStorageRecord()['quota_warning_threshold'], fractionDigits: 2),
            'current_threshold' => self::numberFormat($currentThreshold, fractionDigits: 2),
            'current_threshold_raw' => min((int)$currentThreshold, 100),
            'quota_warning_recipients' => $storage->getStorageRecord()['quota_warning_recipients'],
        ];
    }

    public static function getCurrentUsage(ResourceStorage $storage): int
    {
        return (int)$storage->getStorageRecord()['current_usage'];
    }

    public static function getSoftQuota(ResourceStorage $storage): int
    {
        return (int)$storage->getStorageRecord()['soft_quota'];
    }

    public static function getHardLimit(ResourceStorage $storage): int
    {
        return (int)$storage->getStorageRecord()['hard_limit'];
    }

    /**
     * Get the total disk space used in a storage by SUM()ing upd all file sizes in this storage
     *
     * @throws DbalException
     * @throws \RuntimeException
     */
    public static function getTotalDiskSpaceUsedInStorage(int $storageId): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        $queryBuilder
            ->addSelectLiteral(
                $queryBuilder->expr()->sum('size', 'current_usage')
            )
            ->from('sys_file')
            ->where(
                $queryBuilder->expr()->eq('storage', $storageId)
            );

        return (int)$queryBuilder->executeQuery()->fetchOne();
    }

    /**
     * Calculate and update the current usage for the storage
     *
     * @return int Current usage in MB
     *
     * @throws DbalException
     */
    public static function updateStorageUsage(int $storageId): int
    {
        $currentUsage = self::getTotalDiskSpaceUsedInStorage($storageId);
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_file_storage');
        $connection->update(
            'sys_file_storage',
            [ 'current_usage' => $currentUsage ],
            [ 'uid' => $storageId ]
        );

        return $currentUsage;
    }

    /**
     * Return the size of a FAL folder by recursively aggregating the files. To speed up, the process can be
     * stopped if the total size is exceeding a given limit.
     */
    public static function getFolderSize(Folder $folder, int $breakAt = 0): int
    {
        $folderSize = 0;
        foreach ($folder->getFiles(0, 0, 1, true) as $file) {
            $folderSize += $file->getSize();
            unset($file);
            if ($breakAt > 0 && $folderSize > $breakAt) {
                return $folderSize;
            }
        }

        return $folderSize;
    }

    /**
     * Returns the available size in bytes on the given storage
     *
     * @return int Returns -1 if no value could be determined of method not available
     */
    public static function getAvailableSpaceOnStorageOnDevice(ResourceStorage $storage): int
    {
        $availableSize = -1;
        // Check local storage only
        if (function_exists('disk_free_space') && $storage->getDriverType() === 'Local') {
            $storageConfiguration = $storage->getConfiguration();
            if ($storageConfiguration['pathType'] === 'absolute') {
                $absoluteStoragePath = $storageConfiguration['basePath'];
            } else {
                $absoluteStoragePath = Environment::getPublicPath() . '/' . $storageConfiguration['basePath'];
            }
            if (is_dir($absoluteStoragePath)) {
                $availableSize = disk_free_space($absoluteStoragePath);
            }
        }

        return (int)$availableSize;
    }

    /**
     * Return a formatted number and append the given unit. Uses fallback to number_format() if the PHP extension
     * »intl« is not available. Uses TYPO3 [SYS][systemLocale] if set, falls back to locale_get_default() if empty.
     */
    public static function numberFormat(int|float $number, string $unit = '', bool $addUnit = true, int $fractionDigits = 0): string
    {
        switch ($unit) {
            case 'kB':
                $number /= 1024;
                break;
            case 'MB':
                $number /= 1024 ** 2;
                break;
            case 'GB':
                $number /= 1024 ** 3;
                break;
            case 'TB':
                $number /= 1024 ** 4;
                break;
            default:
        }

        if (class_exists('NumberFormatter') && extension_loaded('intl')) {
            $locale = $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale'];
            if ($locale === '') {
                $locale = locale_get_default();
            }
            $fmt = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
            $fmt->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $fractionDigits);
            $fmt->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $fractionDigits);
            $formattedNumber = $fmt->format($number);
            if (intl_is_failure($fmt->getErrorCode())) {
                $formattedNumber = number_format($number, $fractionDigits, '', '.');
            }
        } else {
            $formattedNumber = number_format($number, $fractionDigits, '', '.');
        }

        return $formattedNumber . ($addUnit ? ' ' . $unit : '');
    }
}
