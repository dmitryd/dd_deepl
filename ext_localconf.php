<?php

if (!\TYPO3\CMS\Core\Core\Environment::isCli()) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][1688827348] = \Dmitryd\DdDeepl\Hook\DataHandlerTranslationHook::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][1689870161] = \Dmitryd\DdDeepl\Hook\InjectAdditionalResources::class . '->inject';

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dd_deepl'] ??= [];
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dd_deepl']['backend'] ??= \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class;
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptConstants(
    '@import \'EXT:dd_deepl/Configuration/TypoScript/constants.typoscript\''
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
    '@import \'EXT:dd_deepl/Configuration/TypoScript/setup.typoscript\''
);
