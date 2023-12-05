<?php

call_user_func(function () {
    $fields = [
        'cache_tags',
        'TSconfig',
    ];
    foreach ($fields as $field) {
        $GLOBALS['TCA']['pages']['columns'][$field]['translateWithDeepl'] = false;
    }
});
