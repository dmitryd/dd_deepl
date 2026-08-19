<?php

call_user_func(function () {
    $fields = [
        'target',
        'tablenames',
        'table_local',
    ];
    foreach ($fields as $field) {
        if ($GLOBALS['TCA']['tt_content']['columns'][$field] ?? false) {
            $GLOBALS['TCA']['tt_content']['columns'][$field]['translateWithDeepl'] = false;
        }
    }
});
