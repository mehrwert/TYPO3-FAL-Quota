<?php
defined('TYPO3_MODE') or die();

/*
 * 2019 - EXT:fal_quota -FAL Quota
 *
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.md', which is part of this source code package.
 */

call_user_func(
    function ($extKey) {
        // Register the class to be available in 'eval' of TCA
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][\Mehrwert\FalQuota\Evaluation\StorageQuotaEvaluation::class] = '';

        // Register UpdateWizard
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['falQuota_commandIdentifierUpdate']
            = \Mehrwert\FalQuota\Updates\CommandIdentifierUpdateWizard::class;
    },
    $_EXTKEY
);
