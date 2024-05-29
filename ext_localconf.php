<?php

defined('TYPO3_MODE') or die();

(static function() {
    // Register the class to be available in 'eval' of TCA
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][\Mehrwert\FalQuota\Evaluation\StorageQuotaEvaluation::class] = '';

    // Register UpdateWizard
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['falQuota_commandIdentifierUpdate']
        = \Mehrwert\FalQuota\Updates\CommandIdentifierUpdateWizard::class;
})();
