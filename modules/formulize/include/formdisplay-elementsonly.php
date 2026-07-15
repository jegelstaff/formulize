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

// When no screen is supplied, fall back to the form's default form screen so the
// entry renders through that screen's templates (via renderHandler) rather than the
// plain displayForm() output. Callers like the list drawer only pass fid, so this
// is where the screen gets resolved. If the form has no default screen configured,
// $sid stays 0 and we render with displayForm() below.
if(!$sid AND $fid) {
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    if($formObject = $form_handler->get($fid)) {
        $sid = intval($formObject->getVar('defaultform'));
    }
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

// Multi-page navigation: the drawer drives paging through this endpoint. `page` is the
// page to render and `prevpage` is the page being left, which we encode as "page-sid"
// the way displayFormPages expects so it honours the request even in elements-only mode.
// When `formulize_save` is set we first save the submitted page's data via readelements.php
// (exactly as a normal full-page load would), then render the requested page. A new entry
// created on the first save is carried into later pages automatically by displayFormPages'
// newEntryIds resolution, so the client never has to track the new entry id itself.
$targetPage = isset($_REQUEST['page'])     ? intval($_REQUEST['page'])     : 0;
$prevPage   = isset($_REQUEST['prevpage']) ? intval($_REQUEST['prevpage']) : 0;

if($screen AND isset($_POST['formulize_save']) AND $_POST['formulize_save']) {
    include XOOPS_ROOT_PATH.'/modules/formulize/include/readelements.php'; // saves the posted page, sets the saved/new entry id globals
}

if($screen AND $targetPage > 0) {
    $_POST['formulize_currentPage'] = $targetPage.'-'.$screen->getVar('sid');
    if($prevPage > 0) {
        $_POST['formulize_prevPage'] = $prevPage.'-'.$screen->getVar('sid');
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
// Form-page globals the element validation/save code expects. Normally set by
// drawJavascript(), which is skipped in elements-only mode. Guarded so a host page
// that already defines them (a real form page) is not clobbered.
if(typeof formulizechanged === 'undefined') { window.formulizechanged = 0; }
if(typeof formulize_javascriptFileIncluded === 'undefined') { window.formulize_javascriptFileIncluded = []; }
if(typeof formulize_xhr_returned_check_for_unique_value === 'undefined') { window.formulize_xhr_returned_check_for_unique_value = []; }
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

// Title for the host (the drawer header). Single source for both single- and
// multi-page renders: the screen's title, or the form's title when rendering
// without a screen. Emitted as JSON so the host can pick it up after injecting.
$drawerTitle = '';
if($screen) {
    $drawerTitle = trans($screen->getVar('title'));
} elseif($fid) {
    $titleFormHandler = xoops_getmodulehandler('forms', 'formulize');
    if($titleFormObject = $titleFormHandler->get($fid)) {
        $drawerTitle = trans($titleFormObject->getVar('title'));
    }
}
print "<script type=\"application/json\" class=\"fz-drawer-meta\">".json_encode(array('title' => $drawerTitle))."</script>\n";

print "<form id='".htmlspecialchars($formname, ENT_QUOTES)."' data-fid='".intval($fid)."' data-frid='".intval($frid)."'>\n";

if($screen) {
	$screen->setVar('navstyle', 3); // turn off tabs and buttons
	$screen->setVar('showpageselector', 1) ; // 2 is 'off'
	// render in elements-only mode so the screen emits just the form fields. Without
	// this the screen renders its full multi-page layout, whose pages are hidden divs
	// driven by navigation JS that never initializes in the drawer's AJAX inject, so
	// nothing is visible. elements_only flattens the pages and drops that chrome.
	$renderHandler->render($screen, $entry_id, null, true);
} else {
	$renderResult = displayForm($fid, $entry_id, "", "", "", "", "formElementsOnly");
}

// add security token, and token for deleting entry locks
print $GLOBALS['xoopsSecurity']->getTokenHTML();
print "<input type='hidden' name='formulize_entry_lock_token' value='".getEntryLockSecurityToken()."'>";

print "</form>\n";

// Rich-text (CKEditor) bootstrap. In a full page load formdisplay.php's drawJavascript()
// loads the CKEditor library (via $xoTheme->addScript) and emits the initializeCKEditor /
// updateCKEditors JS. In elements-only mode drawJavascript() is skipped and $xoTheme->addScript
// only mutates the (never-rendered) theme head, so a rich-text textarea arrives in the drawer as
// a plain <textarea> that never becomes an editor. Re-emit that bootstrap here so any rich-text
// fields registered during render (in $GLOBALS['formulize_CKEditors']) initialise after injection.
//
// The library is emitted as a separate <script src> node so the host's fragment injector (see
// themes/Lyris/js/script.js injectFragment) loads it before the following inline init runs — the
// ordering a normal document gives for free but innerHTML/.load() do not.
if(!empty($GLOBALS['formulize_CKEditors'])) {
    $editorIds = array_values(array_unique((array) $GLOBALS['formulize_CKEditors']));

    print "<script type='text/javascript' src='".XOOPS_URL."/editors/CKEditor/ckeditor.js'></script>\n";

    $initCalls = '';
    $updateBody = '';
    foreach($editorIds as $editorID) {
        $safeId = htmlspecialchars($editorID, ENT_QUOTES);
        $initCalls  .= "        initializeCKEditor('".$safeId."');\n";
        $updateBody .= "        if(jQuery('#".$safeId."').length > 0 && window.CKEditors['".$safeId."']) {\n";
        $updateBody .= "            jQuery('#hidden_".$safeId."').val(window.CKEditors['".$safeId."'].getData().replace(\"'\", '&#039;'));\n";
        $updateBody .= "        }\n";
    }

    print "<script type='text/javascript'>
    // Guarded so repeated drawer opens (or a host page that already has editors) do not clobber
    // existing instances. updateCKEditors() is redefined each open to target the current fields;
    // the drawer's save/validate path calls it before submitting to flush editor content back into
    // the hidden inputs Formulize reads.
    window.CKEditors = window.CKEditors || {};
    function initializeCKEditor(editorID) {
        if(typeof ClassicEditor === 'undefined') { return; }
        if(jQuery('#'+editorID).length > 0 && !window.CKEditors[editorID]) {
            ClassicEditor
                .create( document.querySelector( '#'+editorID ) )
                .then( editor => {
                    window.CKEditors[editorID] = editor;
                    window.CKEditors[editorID].model.document.on('change:data', function() {
                        window.formulizechanged = 1;
                    });
                    jQuery('#'+editorID).attr('name', 'useCKEditor');
                    jQuery(\"<input type='hidden' value='' name='\"+editorID.replace('_tarea', '')+\"' id='hidden_\"+editorID+\"' />\").appendTo(jQuery('#'+editorID).parent());
                })
                .catch( error => {
                    console.error( error );
                } );
        }
    }
    jQuery(document).ready(function () {
".$initCalls."    });
    window.updateCKEditors = function() {
".$updateBody."    };
</script>\n";
}

// "Office Use Only" toggle. On a full page load this function is defined by formdisplay.php's
// drawJavascript(), which is skipped in elements-only mode. The ownership/proxy list (and its
// Show/Hide 'Office Use Only' buttons) is still rendered here though, and those buttons call
// officeUseOnlyToggle() via inline onclick. Without this re-emit the drawer button throws
// "officeUseOnlyToggle is not defined". Defined on window so it survives the fragment injector
// re-creating <script> nodes; harmless if the host page has already defined it.
print "<script type='text/javascript'>
window.officeUseOnlyToggle = function() {
	jQuery('.formulize-office-use-only-toggle').toggle();
	jQuery('.formulize-office-use-only-content').toggle(250);
	return false;
};
</script>\n";

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
