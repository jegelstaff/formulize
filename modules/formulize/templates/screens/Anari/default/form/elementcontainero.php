<?php

$style = "";
if($columns == 1 AND $column1Width) {
    $style = 'style="width: '.$column1Width.';"';
}

print "
<div class='form-row' $style id='$elementContainerId'>
";