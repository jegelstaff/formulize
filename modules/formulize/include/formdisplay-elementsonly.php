<?php
// Generalized "elements only" form renderer for AJAX contexts (the right slide-out
// drawer uses this; subform modals can eventually migrate to it as well).
//
// Renders a form (optionally via a configured screen) as an elements-only <form>,
// suitable for injection into a host page. The host is responsible for submitting
// the collected data to readelements.php in the standard Formulize manner.
//
// GET parameters:
//   fid       - form id (fallback when no sid is supplied; with sid it is derived from the screen)
//   entry_id  - numeric entry id to edit; anything else (or 0/'new') renders a blank new entry
//   frid      - relationship form id (optional; with sid it is derived from the screen)
//   sid       - screen id to render (optional). When present, fid/frid come from the screen.
//   formname  - DOM id for the wrapping <form> and the validation function suffix
//               (sanitized; defaults to 'formulize_drawer')

require_once '../../../mainfile.php';
require_once 'formdisplay.php';
require_once 'functions.php';
icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}
include XOOPS_ROOT_PATH.'/header.php'; // need to initialize the theme object even though we're not displaying a full page

$fid      = isset($_GET['fid'])  ? intval($_GET['fid'])  : 0;
$frid     = isset($_GET['frid']) ? intval($_GET['frid']) : 0;
$sid      = isset($_GET['sid'])  ? intval($_GET['sid'])  : 0;
$entry_id = (isset($_GET['entry_id']) AND is_numeric($_GET['entry_id']) AND $_GET['entry_id'] > 0) ? intval($_GET['entry_id']) : "";

$formname = isset($_GET['formname']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['formname']) : '';
if(!$formname) {
    $formname = 'formulize_drawer';
}

$screen = null;
if($sid) {
    $screen_handler = xoops_getmodulehandler('screen', 'formulize');
    if($screenMeta = $screen_handler->get($sid)) {
        // derive the form ids from the screen so the host saves against the right form
        $fid  = $screenMeta->getVar('fid')  ? $screenMeta->getVar('fid')  : $fid;
        $frid = $screenMeta->getVar('frid') ? $screenMeta->getVar('frid') : $frid;
        $renderHandler = xoops_getmodulehandler($screenMeta->getVar('type').'Screen', 'formulize');
        $screen = $renderHandler->get($screenMeta->getVar('sid'));
    }
}

// In elements-only mode Formulize normally assumes the form is embedded in a host
// page that already bootstrapped the form JS (FORMULIZE, the jQuery show/hide
// override, conditional element handling). The drawer is opened from a list page
// that has none of that, so emit the missing bootstrap here. The show/hide override
// is guarded so repeated drawer opens don't stack wrappers.
global $xoopsUser;
$bootstrapUid = $xoopsUser ? intval($xoopsUser->getVar('uid')) : 0;
print "<script type='text/javascript'>
if(!window.formulizeShowHideOverrideApplied) {
    (function (\$) {
        \$.each(['show', 'hide'], function (i, ev) {
            var el = \$.fn[ev];
            \$.fn[ev] = function () { this.trigger(ev); return el.apply(this, arguments); };
        });
    })(jQuery);
    window.formulizeShowHideOverrideApplied = true;
}
var FORMULIZE = {
    XOOPS_URL : '".XOOPS_URL."',
    XOOPS_UID : ".$bootstrapUid.",
    SCREEN_ID : ".intval($sid).",
    FRID : ".intval($frid)."
};
</script>\n";

// Trigger the conditional-element JavaScript: in elements-only mode it is only
// emitted when this flag is set (see formdisplay.php). It mirrors how the subform
// modal endpoint enables conditional elements.
global $formulize_displayingSubform;
$formulize_displayingSubform = true;

print "<form id='".htmlspecialchars($formname, ENT_QUOTES)."' data-fid='".intval($fid)."' data-frid='".intval($frid)."'>\n";

if($screen) {
    $renderHandler->render($screen, $entry_id, null, true);
} else {
    $renderResult = displayForm($fid, $entry_id, "", "", "", "", "formElementsOnly");
}

// add security token, and token for deleting entry locks
print $GLOBALS['xoopsSecurity']->getTokenHTML();
print "<input type='hidden' name='formulize_entry_lock_token' value='".getEntryLockSecurityToken()."'>";

print "</form>\n";

// validation + entry-lock cleanup helpers, keyed to this form's name so multiple
// elements-only forms can coexist on a page.
// NOTE: elements-only validation does not currently support unique value checks.
print "<script type='text/javascript'>
function xoopsFormValidate_".$formname."(myform) {
";
print trim(implode("\n\r", (array) $GLOBALS['formulize_elementsOnlyForm_validationCode']));
print "\n\r return true;
}

function removeDrawerEntryLocks() {
".formulize_javascriptForRemovingEntryLocks()."
}

jQuery(window).on('unload', function() {
    ".formulize_javascriptForRemovingEntryLocks('unload')."
});
</script>";
