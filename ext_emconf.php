<?php
declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'External Link Accessibility',
    'description' => 'Adds accessibility features to external links',
    'category' => 'fe',
    'author' => 'Plan2net',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.0.0-13.99.99',
        ],
    ],
];
