<?php

require_once "../../../mainfile.php";
icms::$logger->disableLogger();

while(ob_get_level()) {
    ob_end_clean();
}

include_once XOOPS_ROOT_PATH.'/modules/formulize/include/common.php';

$fid = intval($_GET['fid']);

$element_handler = xoops_getmodulehandler('elements', 'formulize');
$elementObjects = $element_handler->getObjects(null, $fid);

global $actionFunctionName;
$actionFunctionName = 'noaction';

$styleVersion = formulize_get_file_version('/themes/Anari/css/style.css');
$formulizeVersion = formulize_get_file_version('/modules/formulize/templates/css/formulize.css');

print '
<!DOCTYPE html>
<html>
<link rel="stylesheet" type="text/css" media="all" href="'.XOOPS_URL.'/themes/Anari/css/style.css?v='.$styleVersion.'" />
<link rel="stylesheet" type="text/css" media="all" href="'.XOOPS_URL.'/modules/formulize/templates/css/formulize.css?v='.$formulizeVersion.'" />
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script type="text/javascript" src="'.XOOPS_URL.'/libraries/jquery/jquery-migrate-1.2.1.min.js"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<script>
function noaction() {
	return 0;
}
</script>
<style>
body {
	background-color: #F5F6F8;
}
.formulize-element-edit-link {
	display: none;
}
#element-edit-controls {
	position: absolute;
	display: none;
	margin-left: 2em;
	background-color: color-mix(in srgb, #2191c0 60%, transparent);
	border-radius: 4px;
	right: 2em;
	justify-content: space-between;
}
#element-edit-controls div {
	margin: 0.5em;
	padding: 0.5em;
	border-radius: 4px;
	background-color: #6eac2c;
	color: white;
}
#element-edit-controls div a,
#element-edit-controls div a:hover,
#element-edit-controls div a:visited {
	color: white;
	text-decoration: none;
}


</style>
';

$fakeForm = new formulize_themeForm('', 'fakeform', XOOPS_URL);
foreach($elementObjects as $elementObject) {
	$deReturnValue = displayElement($fid, $elementObject, 'new', renderElement: false);
	if(is_array($deReturnValue)) {
		$form_ele = $deReturnValue[0];
		$isDisabled = $deReturnValue[1];
	} else {
		$form_ele = $deReturnValue;
		$isDisabled = false;
	}
	if($elementObject->getVar('ele_type') == "ib" OR is_array($form_ele)) {
		$fakeForm->insertBreakFormulize(trans(stripslashes($form_ele[0])), $form_ele[1], 'markup-name-moot', $elementObject->getVar("ele_handle"));
	} elseif($form_ele !== false) {
		$req = !$isDisabled ? intval($elementObject->getVar('ele_required')) : 0;
		$fakeForm->addElement($form_ele, $req);
		unset($form_ele);
	}
}

// RAW DISPLAY OF ELEMENTS DOES NOT RENDER GRIDS (NOR POSSIBLY OTHER STUFF?)
// BUT USING COMPILE ELEMENTS LOOKS AT CONTEXT AND DOESN'T GIVE US ALL ELEMENTS ALL THE TIME
// So sticking with raw display as above for now.
//$fakeForm = compileElements($fid, $fakeForm, "", 'new', array(0=>XOOPS_GROUP_ADMIN, 1=>XOOPS_GROUP_USERS), "", 0, array(), array());

print $fakeForm->render();

print "

<div id='element-edit-controls'><div id='element-edit-controls-configure'><a id='element-edit-controls-configure-link' target='_PARENT' href=''>Configure</a></div><div><a id='element-edit-controls-clone-link' target='' href='' onclick='parent.cloneLinkSteps(jQuery(\"#element-edit-controls-clone-link\").attr(\"target\"));return false;'>Clone</a></div><div><a id='element-edit-controls-delete-link' target='' href='' onclick='parent.deleteLinkSteps(jQuery(\"#element-edit-controls-delete-link\").attr(\"target\"));return false;'>Delete</a></div></div>

<script>

".checkForChrome()."

jQuery(document).ready(function() {
	jQuery('div.form-row').on('hover', function() {
		jQuery('#element-edit-controls').css('top', jQuery(this).position().top + parseInt(jQuery(this).css('margin-top')) + 'px');
		jQuery('#element-edit-controls').css('display', 'flex');
		let elementId = jQuery(this).attr('id').split(/_/).pop();;
		jQuery('#element-edit-controls-configure-link').attr('href', 'ui.php?page=element&aid=".intval($_GET['aid'])."&ele_id=' + elementId);
		jQuery('#element-edit-controls-clone-link').attr('target', elementId);
		jQuery('#element-edit-controls-delete-link').attr('target', elementId);
	});
});
</script>
";
