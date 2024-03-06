<?php

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('news')) {
    call_user_func(function () {
        $fields = [
            'location_simple',
            'organizer_simple',
        ];
        foreach ($fields as $field) {
            $GLOBALS['TCA']['tx_news_domain_model_news']['columns'][$field]['translateWithDeepl'] = false;
        }
    });
}
