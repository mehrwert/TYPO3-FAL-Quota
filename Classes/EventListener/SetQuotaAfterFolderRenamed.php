<?php

declare(strict_types=1);

namespace Mehrwert\FalQuota\EventListener;

/*
 * 2023 - EXT:fal_quota -FAL Quota
 *
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.md', which is part of this source code package.
 */

use Doctrine\DBAL\Exception as DbalException;
use Mehrwert\FalQuota\Handler\QuotaHandler;
use TYPO3\CMS\Core\Resource\Event\AfterFolderRenamedEvent;

/**
 * Set the storage size after a file was added
 */
readonly class SetQuotaAfterFolderRenamed
{
    public function __construct(
        private QuotaHandler $quotaHandler
    ) {}

    /**
     * @throws DbalException
     */
    public function __invoke(AfterFolderRenamedEvent $event): void
    {
        $this->quotaHandler->updateQuotaByFolder($event->getFolder());
    }
}
