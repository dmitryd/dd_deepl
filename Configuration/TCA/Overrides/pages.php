<?php

call_user_func(function () {
    $fields = [
        'cache_tags',
        'target',
        'TSconfig',
    ];
    foreach ($fields as $field) {
        if ($GLOBALS['TCA']['pages']['columns'][$field] ?? false) {
            $GLOBALS['TCA']['pages']['columns'][$field]['translateWithDeepl'] = false;
        }
    }
});
