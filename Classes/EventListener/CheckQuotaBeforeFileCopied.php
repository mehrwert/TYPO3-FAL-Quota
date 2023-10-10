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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Check that the quota after the file is added is not exceeded
 */
class CheckQuotaBeforeFileCopied
{
    public function __invoke(BeforeFileCopiedEvent $event): void
    {
        /** @var QuotaHandler $handler */
        $handler = GeneralUtility::makeInstance(QuotaHandler::class);
        $handler->preEstimateUsageAfterCopyCommand($event->getFile(), $event->getFolder(), 1576872002);
    }
}
