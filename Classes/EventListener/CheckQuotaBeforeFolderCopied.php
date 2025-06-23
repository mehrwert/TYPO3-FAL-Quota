<?php

declare(strict_types=1);

namespace Mehrwert\FalQuota\EventListener;

/*
 * 2023 - EXT:fal_quota -FAL Quota
 *
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.md', which is part of this source code package.
 */

use Mehrwert\FalQuota\Handler\QuotaHandler;
use TYPO3\CMS\Core\Resource\Event\BeforeFolderCopiedEvent;

/**
 * Check that the quota after the file is added is not exceeded
 */
readonly class CheckQuotaBeforeFolderCopied
{
    public function __construct(
        private QuotaHandler $quotaHandler
    ) {}

    public function __invoke(BeforeFolderCopiedEvent $event): void
    {
        $this->quotaHandler->preEstimateUsageAfterCopyFolderCommand($event->getFolder(), $event->getTargetParentFolder(), 1576872006);
    }
}
