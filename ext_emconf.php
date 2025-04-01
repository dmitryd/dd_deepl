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
            'php' => '8.2.0-8.3.999',
            'typo3' => '13.4.0-13.4.999',
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
    'version' => '13.0.2'
];
