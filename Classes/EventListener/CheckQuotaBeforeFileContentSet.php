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
use TYPO3\CMS\Core\Resource\Event\BeforeFileContentsSetEvent;

/**
 * Check that the quota after the file content is set is not exceeded
 */
readonly class CheckQuotaBeforeFileContentSet
{
    public function __construct(
        private QuotaHandler $quotaHandler
    ) {}

    public function __invoke(BeforeFileContentsSetEvent $event): void
    {
        $this->quotaHandler->preEstimateUsageAfterSetContentCommand($event->getFile(), $event->getContent(), 1576872005);
    }
}
