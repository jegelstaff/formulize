<?php

$style = "";
$width = "";
$display = "";
if($columns == 1 AND $column1Width) {
    $width = "width: $column1Width;";
}
if($startHidden) {
    $display = "display: none;";
}
if($width OR $display) {
    $style = "style =\"$width $display\"";
}

// `.fz-field` is the design-system field-wrapper primitive. `.form-row` is
// retained as an additive alias so existing selectors/JS keep working.
print "
<div class='fz-field form-row $elementClass' $style id='$elementContainerId'>
";
