<?php

$required = $elementIsRequired ? "<span style='color: red;'> *</span>" : "";

if(trim($elementCaption) AND $elementCaption != '&nbsp;') {
	$elementCaption = "<label for='$elementName' class='form-label'>$elementCaption $required</label>";
}

if(trim($elementHelpText)) {
	$elementHelpText = "<p class='form-help-text'>$elementHelpText</p>";
}

print "
$editElementLink
<div style='width: $column1Width;' class='col1 $labelClass'>
    $elementCaption
</div>
<div style='width: $column2Width;' class='col2 $inputClass'>    
    $renderedElement
    $elementHelpText
</div>
";

