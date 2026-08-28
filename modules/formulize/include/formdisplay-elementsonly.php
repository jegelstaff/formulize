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
//   subformElementId - when loading a sub entry from a subform element (fid = the sub's
//               form id), resolves the screen configured on that element
//               (get_display_screen_for_subform) instead of the form's default screen.
//
// Subform actions (POST, mirroring the flags core's full-page flow uses):
//   target_sub / target_sub_fid / target_sub_frid / target_sub_mainformentry /
//   target_sub_subformelement / numsubents
//             - after the optional formulize_save, create new sub entries linked to the
//               parent entry (same core helpers the full-page flow uses), then render the
//               first new sub entry through the subform's display screen. The host swaps
//               this response in as the current form (drawer "Add" flow).
//   deletesubsflag / clonesubsflag + delbox* checkboxes
//             - handled inside displayForm() during the re-render; no code here.

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

// Resolve a screen id to a renderable screen object, deriving fid/frid from the
// screen's own configuration (so the host saves against the right form). Used for
// the initial render and again when a subform action switches the render target.
function formulize_elementsOnly_resolveScreen($sid, &$fid, &$frid, &$renderHandler) {
    $screen = null;
    if($sid) {
        $screen_handler = xoops_getmodulehandler('screen', 'formulize');
        if($screenMeta = $screen_handler->get($sid)) {
            $fid  = $screenMeta->getVar('fid')  ? $screenMeta->getVar('fid')  : $fid;
            $frid = $screenMeta->getVar('frid') ? $screenMeta->getVar('frid') : $frid;
            $renderHandler = xoops_getmodulehandler($screenMeta->getVar('type').'Screen', 'formulize');
            $screen = $renderHandler->get($screenMeta->getVar('sid'));
        }
    }
    return $screen;
}

// Loading a sub entry (drawer edit-sub flow): resolve the screen configured on the
// subform element, the same way the subform modal endpoint does. Gate the requested
// entry since this is a directly-reachable endpoint (mirrors subformdisplay-elementsonly.php).
$subformElementId = isset($_GET['subformElementId']) ? intval($_GET['subformElementId']) : 0;
if(!$sid AND $fid AND $subformElementId) {
    if(!security_check($fid, $entry_id)) {
        print "<p>"._NO_PERM."</p>";
        exit();
    }
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    if($subformElementObject = $element_handler->get($subformElementId)) {
        if($resolvedSid = get_display_screen_for_subform($subformElementObject)) {
            $sid = intval($resolvedSid);
        }
    }
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

$renderHandler = null;
$screen = formulize_elementsOnly_resolveScreen($sid, $fid, $frid, $renderHandler);

// Multi-page navigation: the drawer drives paging through this endpoint. `page` is the
// page to render and `prevpage` is the page being left, which we encode as "page-sid"
// the way displayFormPages expects so it honours the request even in elements-only mode.
// When `formulize_save` is set we first save the submitted page's data via readelements.php
// (exactly as a normal full-page load would), then render the requested page. A new entry
// created on the first save is carried into later pages automatically by displayFormPages'
// newEntryIds resolution, so the client never has to track the new entry id itself.
$targetPage = isset($_REQUEST['page'])     ? intval($_REQUEST['page'])     : 0;
$prevPage   = isset($_REQUEST['prevpage']) ? intval($_REQUEST['prevpage']) : 0;

if(isset($_POST['formulize_save']) AND $_POST['formulize_save']) {
    include XOOPS_ROOT_PATH.'/modules/formulize/include/readelements.php'; // saves the posted page, sets the saved/new entry id globals
}

// Subform add-new (drawer "Add" flow): create the requested linked sub entries with the
// same core helpers the full-page flow uses (see the target_sub block in formdisplay.php,
// which is skipped for elements-only renders), then switch the render target to the first
// new sub entry so the host can swap it in as the current form.
$parentEntryResolved = 0;
$targetSub = (isset($_POST['target_sub']) AND intval($_POST['target_sub'])) ? intval($_POST['target_sub']) : 0;
if($targetSub) {
    global $xoopsUser;
    $actionUid = $xoopsUser ? intval($xoopsUser->getVar('uid')) : 0;
    $parentFid  = isset($_POST['target_sub_fid'])  ? intval($_POST['target_sub_fid'])  : $fid;
    $parentFrid = isset($_POST['target_sub_frid']) ? intval($_POST['target_sub_frid']) : $frid;
    // the parent may have been created by the readelements save just above, in which case
    // the client only knows it as 'new' — resolve the real id from the save's globals
    $parentEntryResolved = (isset($_POST['target_sub_mainformentry']) AND is_numeric($_POST['target_sub_mainformentry'])) ? intval($_POST['target_sub_mainformentry']) : 0;
    if(!$parentEntryResolved AND isset($GLOBALS['formulize_newEntryIds'][$parentFid][0])) {
        $parentEntryResolved = intval($GLOBALS['formulize_newEntryIds'][$parentFid][0]);
    }
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $subformElementObject = $element_handler->get(intval($_POST['target_sub_subformelement']));
    $newSubEntryId = 0;
    // directly-reachable endpoint: require view access to the parent entry and
    // permission to create entries on the target subform (writeNewEntry checks nothing)
    if($parentEntryResolved AND $subformElementObject
        AND security_check($parentFid, $parentEntryResolved, $actionUid)
        AND formulizePermHandler::user_can_edit_entry($targetSub, $actionUid, 'new')) {
        list($elementq, $element_to_write, $value_to_write, $value_source, $value_source_form, $alt_element_to_write) =
            formulize_subformSave_determineElementToWrite($parentFrid, $parentFid, $parentEntryResolved, $targetSub);
        $subformElementEleValue = $subformElementObject->getVar('ele_value');
        $numSubEnts = max(1, min(99, intval($_POST['numsubents'])));
        list($subEntryNew, $subEntriesWritten) = formulize_subformSave_writeNewEntry(
            $element_to_write, $value_to_write, $parentFid, $parentFrid, $targetSub, $parentEntryResolved,
            $subformElementEleValue[7], $subformElementEleValue[5], getEntryOwner($parentEntryResolved, $parentFid), $numSubEnts);
        if(count((array) $subEntriesWritten)) {
            $newSubEntryId = intval($subEntriesWritten[0]);
        }
    }
    // switch the render target to the new sub entry (blank new entry in the uid-link
    // case where writeNewEntry creates nothing — linking then happens on save, as core does)
    $fid = $targetSub;
    $entry_id = $newSubEntryId ? $newSubEntryId : "";
    $sid = 0;
    if($subformElementObject AND $resolvedSid = get_display_screen_for_subform($subformElementObject)) {
        $sid = intval($resolvedSid);
    }
    $renderHandler = null;
    $screen = formulize_elementsOnly_resolveScreen($sid, $fid, $frid, $renderHandler);
    // page state posted along with the parent's fields belongs to the parent, not the sub
    unset($_POST['formulize_currentPage'], $_POST['formulize_prevPage']);
    $targetPage = 0;
    $prevPage = 0;
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

// Wrap the rendered elements in the active theme's drawer container (see
// modules/formulize/templates/screens/<Theme>/default/drawer/toptemplate.php).
// Those templates emit the same container classes the theme's form and multiPage
// screens emit, so a form rendered here is styled exactly as it is full screen and
// no theme needs a parallel set of "form inside the drawer" CSS rules.
$GLOBALS['formulize_elementsOnly_wrapperTemplateType'] = 'drawer';

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
// entryId/fid let the host track what is actually loaded (the add-new flow renders a
// just-created sub entry the client has never seen); parentEntryId reports the resolved
// parent id so a host that opened a brand-new parent can fix up its navigation state.
print "<script type=\"application/json\" class=\"fz-drawer-meta\">".json_encode(array(
    'title' => $drawerTitle,
    'fid' => intval($fid),
    'entryId' => $entry_id ? intval($entry_id) : 'new',
    'parentEntryId' => $parentEntryResolved ? $parentEntryResolved : null,
))."</script>\n";

// Subform interaction stubs. The subform element's markup hardcodes onclick calls to
// add_sub/goSub/goSubModal/sub_del/sub_clone, which are defined by drawJavascript() on
// full page loads but skipped in elements-only mode. Define guarded delegates that hand
// the action to the host (window.formulize.drawer.subformAction) so the host can drive
// its own UI (the Lyris drawer swaps the sub entry in, in place of the modal). No-ops
// when no host hook is present, rather than throwing reference errors.
print "<script type='text/javascript'>
(function() {
    var subformHook = function(action, args) {
        if(window.formulize && window.formulize.drawer && typeof window.formulize.drawer.subformAction === 'function') {
            window.formulize.drawer.subformAction(action, args || {});
        }
    };
    if(typeof window.add_sub === 'undefined') {
        window.add_sub = function(sfid, numents, instance_id, frid, fid, mainformentry, subformelement, modal, parent_subformelement) {
            subformHook('add', { subFid: sfid, numEntries: numents, frid: frid, parentFid: fid, parentEntryId: mainformentry, subformElementId: subformelement });
        };
    }
    if(typeof window.goSub === 'undefined') {
        window.goSub = function(ent, fid, subformElementId) {
            subformHook('edit', { entryId: ent, subFid: fid, subformElementId: subformElementId });
        };
    }
    if(typeof window.goSubModal === 'undefined') {
        window.goSubModal = function(ent, fid, frid, mainformFid, mainformEntryId, subformElementId, modalScroll) {
            subformHook('edit', { entryId: ent, subFid: fid, subformElementId: subformElementId });
        };
    }
    if(typeof window.sub_del === 'undefined') {
        window.sub_del = function(sfid, type, parentSubformElement, fid, entry) {
            subformHook('delete', { subFid: sfid });
        };
    }
    if(typeof window.sub_clone === 'undefined') {
        window.sub_clone = function(sfid, type, parentSubformElement, fid, entry) {
            subformHook('clone', { subFid: sfid });
        };
    }
})();
</script>\n";

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

// This request renders no footer, so nothing else would ever hand the browser the events
// it queued. Without this the AI has no idea which entry the person is looking at in the
// drawer — the thing the assistant most needs to know while it is open.
//
// It has to come after the render above, because that is what queues the rendering-form
// event and records which form/entry was actually shown. The drawer title is passed
// explicitly, since document.title still names the host page, not this fragment.
require_once XOOPS_ROOT_PATH.'/modules/formulize/include/writeToFormulizeLog.php';
if($activityLogJs = formulize_activityLogJs($drawerTitle)) {
    print "\n<script type=\"text/javascript\">".$activityLogJs."</script>\n";
}
