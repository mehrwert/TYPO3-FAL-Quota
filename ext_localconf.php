<?php

use Mehrwert\FalQuota\Evaluation\StorageQuotaEvaluation;

defined('TYPO3') or die();

// Register the class to be available in 'eval' of TCA
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][StorageQuotaEvaluation::class] = '';
