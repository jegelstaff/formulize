<?php

$editorversion['name'] = "EditArea Source Code Editor";
$editorversion['license'] = "GPL see LICENSE";
$editorversion['dirname'] = "editarea";

$editorversion['class'] = "FormEditArea";
$editorversion['file'] = "editarea.php";

$data = file_get_contents(dirname(__FILE__) . '/editor/change_log.txt');
$i = strpos($data, '**** v ') + strlen('**** v ');
$editorversion['version'] = substr($data, $i, strpos($data, ' ', $i) - $i);

?>
