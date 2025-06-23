<?php

use Mehrwert\FalQuota\Evaluation\StorageQuotaEvaluation;
use Mehrwert\FalQuota\Form\Element\MegaByteInputElement;
use Mehrwert\FalQuota\Updates\CommandIdentifierUpdateWizard;

defined('TYPO3') or die();

// Register the class to be available in 'eval' of TCA
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][StorageQuotaEvaluation::class] = '';

// Register UpdateWizard
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['falQuota_commandIdentifierUpdate']
    = CommandIdentifierUpdateWizard::class;

// Add render type for size fields
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1750761010] = [
    'nodeName' => 'faqQuotaMegaByteInput',
    'priority' => 40,
    'class' => MegaByteInputElement::class,
];
