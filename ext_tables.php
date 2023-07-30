<?php

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'DdDeepl',
    'site',
    'dd_deepl',
    '',
    [\Dmitryd\DdDeepl\Controller\BackendModuleController::class => 'overview,glossary,noPageId,viewGlossary,downloadGlossary,deleteGlossary'],
    [
        'access' => 'user,group',
        'icon' => 'EXT:dd_deepl/Resources/Public/Images/DeepL.svg',
        'labels' => 'LLL:EXT:dd_deepl/Resources/Private/Language/locallang.xlf',
        'navigationComponentId' => 'TYPO3/CMS/Backend/PageTree/PageTreeElement',
        'inheritNavigationComponentFromMainModule' => false,
        'path' => '/module/site/DdDeepl/'
    ]
);
