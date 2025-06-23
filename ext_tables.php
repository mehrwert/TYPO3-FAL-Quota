<?php

use Mehrwert\FalQuota\Hooks\DatamapDataHandlerHook;

defined('TYPO3') || die();

// Register DatamapDataHandlerHook
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['fal_quota'] =
    DatamapDataHandlerHook::class;
