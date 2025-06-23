<?php

declare(strict_types=1);

namespace Mehrwert\FalQuota\EventListener;

/*
 * 2025 - EXT:fal_quota - FAL Quota
 *
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.md', which is part of this source code package.
 */

use Mehrwert\FalQuota\Handler\QuotaHandler;
use TYPO3\CMS\Core\Resource\Event\BeforeFileAddedEvent;

/**
 * Check that the quota after the file is added is not exceeded
 */
readonly class CheckQuotaBeforeFileAdded
{
    public function __construct(
        private QuotaHandler $quotaHandler
    ) {}

    public function __invoke(BeforeFileAddedEvent $event): void
    {
        $sourceFilePath = $event->getSourceFilePath();
        $fileSize = filesize($sourceFilePath) ?: 0;
        $this
            ->quotaHandler
            ->preEstimateUsageAfterUploadCommand(
                $event->getStorage(),
                $fileSize,
                1750778554
            );
    }
}
