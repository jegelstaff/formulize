<?php

// format numeric values to be right aligned
if(is_numeric($renderedElement)) {
    $renderedElement = '<div style="text-align: right; width: 10%;">'.formulize_numberFormat($renderedElement,$elementObject->getVar('ele_id')).'</div>';
}

print "<td class='head $labelClass' $colSpan style='width: ".$column1Width.";'>";

if ($elementCaption != '') {
    print "
<div class='xoops-form-element-caption" . ($elementIsRequired ? "-required" : "" ) . "'>
    <span class='caption-text'>$elementCaption</span>
    <span class='caption-marker'>" . ($elementIsRequired ? "*" : "" ) . "</span>
</div>";
}

if ($elementHelpText != '') {
    print "
<div class='xoops-form-element-help'>$elementHelpText</div>";
}

print "
$editElementLink
$renderedElement
</td>
";   

if($spacerNeeded) {
    print '<td class="formulize-spacer-column">&nbsp;</td>';
}


