<?php

$style = "";
if($startHidden) {
    $style = "style='display: none;'";
}

print "<tr id='".$elementContainerId."' $style class='".$elementClass."' valign='top' align='" . _GLOBAL_LEFT . "'>
";