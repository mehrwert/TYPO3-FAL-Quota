<?php

use Mehrwert\FalQuota\Evaluation\StorageQuotaEvaluation;

defined('TYPO3') or die();

// Remove "There might be a problem with write permissions" from backend file error messages.
$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:core/Resources/Private/Language/fileMessages.xlf'][]
    = 'EXT:fal_quota/Resources/Private/Language/fileMessages.xlf';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['de']['EXT:core/Resources/Private/Language/fileMessages.xlf'][]
    = 'EXT:fal_quota/Resources/Private/Language/de.fileMessages.xlf';

// Register the class to be available in 'eval' of TCA
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][StorageQuotaEvaluation::class] = '';
