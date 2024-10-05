<?php

$style = "";
$width = "";
$display = "";
if($columns == 1 AND $column1Width) {
    $width = "width: '$column1Width';";
}
if($startHidden) {
    $display = "display: none;";
}
if($width OR $display) {
    $style = "style =\"$width $display\"";
}

print "
<div class='form-row' $style id='$elementContainerId'>
";