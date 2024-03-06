<?php

call_user_func(function () {
    $fields = [
        'target',
        'tablenames',
        'table_local',
    ];
    foreach ($fields as $field) {
        $GLOBALS['TCA']['tt_content']['columns'][$field]['translateWithDeepl'] = false;
    }
});
