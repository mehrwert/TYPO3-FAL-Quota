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
use TYPO3\CMS\Core\Resource\Event\AfterFileRenamedEvent;

/**
 * Set the storage size after a file was added
 */
readonly class SetQuotaAfterFileRenamed
{
    public function __construct(
        private QuotaHandler $quotaHandler
    ) {}

    public function __invoke(AfterFileRenamedEvent $event): void
    {
        $this->quotaHandler->updateQuotaByFile($event->getFile());
    }
}
