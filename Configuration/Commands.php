<?php
declare(strict_types=1);

/*
 * 2019 - EXT:fal_quota
 *
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.md', which is part of this source code package.
 */

use Mehrwert\FalQuota\Command\NotifyCommand;
use Mehrwert\FalQuota\Command\UpdateCommand;

return [
    'fal_quota:quota:notify' => [
        'class' => NotifyCommand::class,
    ],
    'fal_quota:usage:update' => [
        'class' => UpdateCommand::class,
    ],
];
