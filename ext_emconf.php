<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FAL Quota',
    'description' => 'This extension provides virtual Quotas for FAL Storages.',
    'category' => 'be',
    'author' => 'mehrwert intermediale kommunikation GmbH',
    'author_email' => 'typo3@mehrwert.de',
    'author_company' => 'mehrwert.de',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '1.8.0',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-11.5.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
