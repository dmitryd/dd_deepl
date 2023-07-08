<?php

/***************************************************************
 * Extension Manager/Repository config file.
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'DeeplL translation for TYPO3 Backend',
    'description' => 'Translates pages, content and records using DeeplL',
    'category' => 'be',
    'constraints' => [
        'depends' => [
            'php' => '8.0.0-8.1.999',
            'typo3' => '11.5.22-11.5.999',
            'core' => '',
            'backend' => '',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearcacheonload' => 1,
    'author' => 'Dmitry Dulepov',
    'author_email' => 'dmitry.dulepov@gmail.com',
    'author_company' => 'SIA ACCIO',
    'version' => '1.0.0'
];
