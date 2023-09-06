<?php

defined('TYPO3_MODE') || die();

(static function() {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Mehrwert.FalQuota',
        'file',
        'falquota',
        'bottom',
        [
            \Mehrwert\FalQuota\Controller\DashboardController::class => 'index',
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:fal_quota/Resources/Public/Images/Icons/module-icon.svg',
            'labels' => 'LLL:EXT:fal_quota/Resources/Private/Language/locallang_mod.xlf',
            'navigationComponentId' => '',
        ]
    );

    // Register DatamapDataHandlerHook
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['fal_quota'] =
        \Mehrwert\FalQuota\Hooks\DatamapDataHandlerHook::class;
})();
