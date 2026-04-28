<?php

if (\TYPO3\CMS\Core\Core\Environment::isComposerMode()) {
    return [
        'site_DdDeeplDdDeepl' => [
            'parent' => 'site',
            'access' => 'user',
            'workspaces' => 'live',
            'icon' => 'EXT:dd_deepl/Resources/Public/Images/DeepL.svg',
            'labels' => 'LLL:EXT:dd_deepl/Resources/Private/Language/locallang.xlf',
            'inheritNavigationComponentFromMainModule' => false,
            'path' => '/module/site/DdDeepl',
            'navigationComponent' => '@typo3/backend/tree/page-tree-element',
            'extensionName' => 'DdDeepl',
            'controllerActions' => [
                \Dmitryd\DdDeepl\Controller\BackendModuleController::class => [
                    'overview',
                    'glossary',
                    'noPageId',
                    'viewGlossary',
                    'downloadGlossary',
                    'deleteGlossary',
                    'uploadForm',
                    'upload',
                ],
            ],
        ],
    ];
}
