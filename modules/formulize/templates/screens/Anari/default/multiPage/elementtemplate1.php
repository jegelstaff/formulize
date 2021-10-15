<?php

$required = $elementIsRequired ? "<span style='color: red;'> *</span>" : "";

if(trim($elementCaption) AND $elementCaption != '&nbsp;') {
	$elementCaption = "<label for='$elementName' class='form-label $labelClass'>$elementCaption $required</label>";
}

if(trim($elementHelpText)) {
	$elementHelpText = "<p class='form-help-text'>$elementHelpText</p>";
}

print "
$editElementLink
$elementCaption
$renderedElement
$elementHelpText
";


