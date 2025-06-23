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
use TYPO3\CMS\Core\Resource\Event\BeforeFileCopiedEvent;

/**
 * Check that the quota after the file is added is not exceeded
 */
readonly class CheckQuotaBeforeFileCopied
{
    public function __construct(
        private QuotaHandler $quotaHandler
    ) {}

    public function __invoke(BeforeFileCopiedEvent $event): void
    {
        $this->quotaHandler->preEstimateUsageAfterCopyCommand($event->getFile(), $event->getFolder(), 1576872002);
    }
}
