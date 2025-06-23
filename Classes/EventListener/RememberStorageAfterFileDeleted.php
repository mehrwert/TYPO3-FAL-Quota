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
        // At this point in time, the deleted file is still listed in the sys_file database index.
        // Remember the file storage, so that its quota can be updated in SetQuotaAfterFileRemovedFromIndex.
        $this->quotaHandler->rememberStorageIdForDeletedFile($event->getFile());
    }
}
