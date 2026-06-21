<?php

// Required indicator: design-system `.fz-field__req` asterisk (owner decision)
// instead of an inline red <span>.
$required = $elementIsRequired ? " <span class='fz-field__req'>*</span>" : "";

if(trim($elementCaption) AND $elementCaption != '&nbsp;') {
	// `.fz-field__label` is the primitive; `.form-label` retained as an additive alias.
	$elementCaption = "<label for='$elementName' class='fz-field__label form-label $labelClass'>$elementCaption$required</label>";
}

if(trim($elementHelpText)) {
	$elementHelpText = "<p id='".$elementName."-help-text' class='fz-field__help form-help-text'>$elementHelpText</p>";
}

// `.fz-field__body` wraps the control + help so the layout primitives can
// target the control column consistently across label modes.
print "
$editElementLink
$elementCaption
<div class='fz-field__body'>
$renderedElement
$elementHelpText
</div>
";
