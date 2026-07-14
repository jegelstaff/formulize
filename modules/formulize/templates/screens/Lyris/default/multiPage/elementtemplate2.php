<?php

// Required indicator: design-system `.fz-field__req` asterisk (owner decision).
$required = $elementIsRequired ? " <span class='fz-field__req'>*</span>" : "";

if(trim($elementCaption) AND $elementCaption != '&nbsp;') {
	// `.fz-field__label` is the primitive; `.form-label` retained as an additive alias.
	$elementCaption = "<label for='$elementName' class='fz-field__label form-label'>$elementCaption$required</label>";
}

if(trim($elementHelpText)) {
	$elementHelpText = "<p id='".$elementName."-help-text' class='fz-field__help form-help-text'>$elementHelpText</p>";
}

// Two-column (left-label) layout. `.fz-field__body` adopted on the control
// column; `.col1`/`.col2` kept as additive aliases for the column widths.
print "
$editElementLink
<div style='width: $column1Width;' class='col1 $labelClass'>
    $elementCaption
</div>
<div style='width: $column2Width;' class='fz-field__body col2 $inputClass'>
    $renderedElement
    $elementHelpText
</div>
";

