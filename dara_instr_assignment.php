<?php

// this file answers assignments made in the Director's module
include_once 'mainfile.php';
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/readelements.php';
print json_encode(array('instructors'=>$dara_instructorLoadUpdated, 'newEntryIds'=>$formulize_newEntryIds[15], 'deleteEntryIds'=>$dara_blankAssignmentsCleared, 'coordinators'=>$dara_coordinators));
