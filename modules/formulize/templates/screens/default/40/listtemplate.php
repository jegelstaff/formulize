<?php

$type = display($entry, 'component_type');
if($type != 'Tutorial') {
    readSection($entry, $entry_id);
} else {
    $taShips = display($entry, 'taships_type');
    $taShips = is_array($taShips) ? $taShips : array($taShips);
    if(in_array("Tutorial taught by Instructor", $taShips)) {
        readSection($entry, $entry_id);
        $GLOBALS['dara_course'][display($entry, 'ro_module_year')][display($entry, 'ro_module_course_code')]['sections'][display($entry, 'course_components_related_lecture')]['has_tutorial'] = display($entry, 'teaching_weighting');
    }
    
}