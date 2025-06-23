<?php

use Mehrwert\FalQuota\Controller\DashboardController;

return [
    'file_FalQuotaDashboard' => [
        'parent' => 'file',
        'position' => ['bottom' => '*'],
        'access' => 'user,group',
        'workspaces' => 'live',
        'iconIdentifier' => 'module-fal-quota',
        'path' => '/module/file/FalQuotaDashboard',
        'labels' => 'LLL:EXT:fal_quota/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'FalQuota',
        'controllerActions' => [
            DashboardController::class => [
                'index',
            ],
        ],
    ],
];
