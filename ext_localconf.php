<?php

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processTranslateToClass'][1688827348] = \Dmitryd\DdDeepl\Hook\DataHandlerTranslationHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][1688827348] = \Dmitryd\DdDeepl\Hook\DataHandlerTranslationHook::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][1689870161] = \Dmitryd\DdDeepl\Hook\InjectCustomJavascript::class . '->injectCustomJavaScript';

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Controller\Page\LocalizationController::class] = [
    'className' => \Dmitryd\DdDeepl\Controller\LocalizationController::class,
];
