<?php

/** @var string $_EXTKEY */
$EM_CONF[$_EXTKEY] = [
    'title' => 'FAL Quota',
    'description' => 'This extension provides virtual Quotas for FAL Storages.',
    'category' => 'be',
    'author' => 'mehrwert intermediale kommunikation GmbH',
    'author_email' => 'typo3@mehrwert.de',
    'author_company' => 'mehrwert.de',
    'state' => 'stable',
    'version' => '13.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
