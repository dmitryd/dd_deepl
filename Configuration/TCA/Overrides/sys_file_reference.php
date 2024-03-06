<?php

call_user_func(function () {
    $fields = [
        'fieldname',
        'tablenames',
        'table_local',
    ];
    foreach ($fields as $field) {
        $GLOBALS['TCA']['sys_file_reference']['columns'][$field]['translateWithDeepl'] = false;
    }
});
