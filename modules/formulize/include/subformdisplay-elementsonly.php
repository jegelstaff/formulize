<?php
// display a requested form and entry as en elements-only form

require_once '../../../mainfile.php';
require_once 'formdisplay.php';
require_once 'functions.php';
ob_end_clean();
include XOOPS_ROOT_PATH.'/header.php'; // need to initialize the theme object even though we're not displaying a full page

$entry_id = intval($_GET['entry_id']); // need to not be intval if we want to support 'new' entries
$fid = intval($_GET['fid']);
$subformElementId = intval($_GET['subformElementId']);
$formulize_displayingSubform = true;

$form_handler = xoops_getmodulehandler('forms', 'formulize');
$element_handler = xoops_getmodulehandler('elements', 'formulize');
$subformObject = $form_handler->get($fid);
$subformElementObject = $element_handler->get($subformElementId);

print "<form id='formulize_modal'>\n";
// get default screen if any
if($subformDisplayScreen = get_display_screen_for_subform($subformElementObject)) {
    $subScreen_handler = xoops_getmodulehandler('formScreen', 'formulize');
    $screen = $subScreen_handler->get($subformDisplayScreen);
    $subScreen_handler->render($screen, $entry_id, null, true);
} else {
    $renderResult = displayForm($fid, $entry_id, "", "",  "", "", "formElementsOnly");
}
// add security token
if(isset($GLOBALS['xoopsSecurity'])) {
    print $GLOBALS['xoopsSecurity']->getTokenHTML();
}
// MODAL VALIDATION DOES NOT CURRENTLY SUPPORT UNIQUE VALUE CHECKS!
print "</form><hr><br /><input type='button' id='submitSub' name='submitSub' value='"._formulize_SAVE."' onclick='saveSub()'><br /><br />
<script type='text/javascript'>
function xoopsFormValidate_formulize_modal('', myform) {
    ";
print trim(implode('\n\r',$GLOBALS['formulize_elementsOnlyForm_validationCode']));
print "\n\r return true;
}
</script>";



