<?php

declare(strict_types=1);

namespace Mehrwert\FalQuota\EventListener;

/*
 * 2025 - EXT:fal_quota - FAL Quota
 *
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.md', which is part of this source code package.
 */

use Doctrine\DBAL\Exception as DbalException;
use Mehrwert\FalQuota\Handler\QuotaHandler;
use TYPO3\CMS\Core\Resource\Event\BeforeFileCreatedEvent;

/**
 * Check that the quota after the file is added is not exceeded
 */
readonly class CheckQuotaBeforeFileCreated
{
    public function __construct(
        private QuotaHandler $quotaHandler
    ) {}

    /**
     * @throws DbalException
     */
    public function __invoke(BeforeFileCreatedEvent $event): void
    {
        $this->quotaHandler->checkQuota($event->getFolder(), 1576872000);
    }
}
