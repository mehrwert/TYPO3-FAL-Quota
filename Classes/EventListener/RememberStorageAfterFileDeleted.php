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
use TYPO3\CMS\Core\Resource\Event\AfterFileDeletedEvent;

/**
 * Set the storage size after a file was added
 */
readonly class RememberStorageAfterFileDeleted
{
    public function __construct(
        private QuotaHandler $quotaHandler
    ) {}

    public function __invoke(AfterFileDeletedEvent $event): void
    {
        // AfterFileDeletedEvent is triggered immediately after a file has been physically deleted
        // from the file system. At this point, the file record is still present in the database
        // table/index `sys_file`.
        $this->quotaHandler->rememberStorageIdForDeletedFile($event->getFile());
    }
}
