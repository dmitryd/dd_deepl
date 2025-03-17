<?php

if (!\TYPO3\CMS\Core\Core\Environment::isCli()) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][1688827348] = \Dmitryd\DdDeepl\Hook\DataHandlerTranslationHook::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][1689870161] = \Dmitryd\DdDeepl\Hook\InjectAdditionalResources::class . '->inject';
}
