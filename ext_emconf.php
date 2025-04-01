<?php

/***************************************************************
 * Extension Manager/Repository config file.
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'DeepL translation for TYPO3 Backend',
    'description' => 'Translates pages, content and records using DeepL',
    'category' => 'be',
    'constraints' => [
        'depends' => [
            'php' => '8.0.0-8.1.999',
            'typo3' => '12.4.0-12.4.999',
            'core' => '',
            'backend' => '',
        ],
        'conflicts' => [
            'deepltranslate' => '',
            'wv_deepltranslate' => '',
        ],
        'suggests' => [],
    ],
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearcacheonload' => 1,
    'author' => 'Dmitry Dulepov',
    'author_email' => 'dmitry.dulepov@gmail.com',
    'author_company' => '',
    'version' => '12.7.1'
];
