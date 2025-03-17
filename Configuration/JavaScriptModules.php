<?php

return [
    'dependencies' => ['core', 'backend'],
    'imports' => [
        '@dmitryd/dd_deepl/' => 'EXT:dd_deepl/Resources/Public/JavaScript/',
        '@typo3/backend/localization.js' => 'EXT:dd_deepl/Resources/Public/JavaScript/localization.js',
    ],
];
