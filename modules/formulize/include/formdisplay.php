<?php

###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Freeform Solutions 					     ##
##  Project: Formulize                                                       ##
###############################################################################

//THIS FILE HANDLES THE DISPLAY OF FORMS.  FUNCTIONS CAN BE CALLED FROM ANYWHERE (INTENDED FOR PAGEWORKS MODULE)

global $xoopsConfig;
// load the formulize language constants if they haven't been loaded already
	if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php";
	} else {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/english/main.php";
	}

include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";

include_once XOOPS_ROOT_PATH."/class/xoopsformloader.php";
include_once XOOPS_ROOT_PATH . "/include/functions.php";

// NEED TO USE OUR OWN VERSION OF THE CLASS, TO GET ELEMENT NAMES IN THE TR TAGS FOR EACH ROW
class formulize_themeForm extends XoopsThemeForm {
	/**
	 * Insert an empty row in the table to serve as a seperator.
	 *
	 * @param	string  $extra  HTML to be displayed in the empty row.
	 * @param	string	$class	CSS class name for <td> tag
	 * @name	string	$name	name of the element being inserted, which we keep so we can then put the right id tag into its row
	 */
	public function insertBreakFormulize($extra = '', $class= '', $name, $element_handle) {
		$class = ($class != "") ? "$class " : "";
		//Fix for $extra tag not showing
		if ($extra) {
			$extra = "<td colspan='2' class=\"{$class}formulize-label-$element_handle\">$extra</td>"; // removed tr from here and added it below when we know the right id name to give it
		} else {
			$extra = "<td colspan='2' class=\"{$class}formulize-label-$element_handle\">&nbsp;</td>"; // removed tr from here and added it below when we know the right id name to give it
		}
		$ibContents = $extra."<<||>>".$name; // can only assign strings or real element objects with addElement, not arrays
		$this->addElement($ibContents);
	}
	/**
	 * create HTML to output the form as a theme-enabled table with validation.
	 *
	 * @return	string
	 */
	public function render() {
		$ele_name = $this->getName();
		$ret = "<form id='" . $ele_name
				. "' name='" . $ele_name
				. "' action='" . $this->getAction()
				. "' method='" . $this->getMethod()
				. "' onsubmit='return xoopsFormValidate_" . $ele_name . "();'" . $this->getExtra() . ">
			<div class='xo-theme-form'>
			<table width='100%' class='outer' cellspacing='1'>
			<tr><th colspan='2'><h1 class=\"formulize-form-title\">" . $this->getTitle() . "</h1></th></tr>
		";
		$hidden = '';
		list($ret, $hidden) = $this->_drawElements($this->getElements(), $ret, $hidden);
		$ret .= "</table>\n$hidden\n</div>\n</form>\n";
		$ret .= $this->renderValidationJS(true);
		return $ret;
	}
	
	public function renderValidationJS( $withtags = true, $skipConditionalCheck = false ) {
		$js = "";
		if ( $withtags ) {
			$js .= "\n<!-- Start Form Validation JavaScript //-->\n<script type='text/javascript'>\n<!--//\n";
		}
		$formname = $this->getName();
		$js .= "function xoopsFormValidate_{$formname}() { myform = window.document.{$formname};\n";
		$js .= $this->_drawValidationJS($skipConditionalCheck);
		$js .= "\nreturn true;\n}\n";
		if ( $withtags ) {
			$js .= "//--></script>\n<!-- End Form Vaidation JavaScript //-->\n";
		}
		return $js;
	}

	function _drawElements($elements, $ret, $hidden) {
		$class ='even';

        global $xoopsUser;
        $show_element_edit_link = (is_object($xoopsUser) and in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups()));

		foreach ( $elements as $ele ) {
			$label_class = null;
			$input_class = null;
			if (isset($ele->formulize_element)) {
				$label_class = " formulize-label-".$ele->formulize_element->getVar("ele_handle");
				$input_class = " formulize-input-".$ele->formulize_element->getVar("ele_handle");
			}
			if (!is_object($ele)) {// just plain add stuff if it's a literal string...
				if(strstr($ele, "<<||>>")) {
					$ele = explode("<<||>>", $ele);
					$ret .= "<tr id='formulize-".$ele[1]."'>".$ele[0]."</tr>";
				} elseif(substr($ele, 0, 3) != "<tr") {
					$ret .= "<tr>$ele</tr>";
				} else {
					$ret .= $ele;
				}
			} elseif ( !$ele->isHidden() ) {
				$ret .= "<tr id='formulize-".$ele->getName()."' class='".$ele->getClass()."' valign='top' align='" . _GLOBAL_LEFT . "'><td class='head$label_class'>";
				if (($caption = $ele->getCaption()) != '') {
					$ret .=
					"<div class='xoops-form-element-caption" . ($ele->isRequired() ? "-required" : "" ) . "'>"
						. "<span class='caption-text'>{$caption}</span>"
						. "<span class='caption-marker'>" . ($ele->isRequired() ? "*" : "" ) . "</span>"
						. "</div>";
				}
				if (($desc = $ele->getDescription()) != '') {
					$ret .= "<div class='xoops-form-element-help'>{$desc}</div>";
				}

                $ret .= "</td><td class='$class$input_class'>";
                if ($show_element_edit_link) {
                    $element_name = trim($ele->getName());
                    switch ($element_name) {
                        case 'control_buttons':
                        case 'proxyuser':
                            // Do nothing
                            break;

                        default:
                            if (is_object($ele) and isset($ele->formulize_element)) {
                                $ret .= "<a class=\"formulize-element-edit-link\" tabindex=\"-1\" href=\"" . XOOPS_URL .
                                    "/modules/formulize/admin/ui.php?page=element&aid=0&ele_id=" .
                                    $ele->formulize_element->getVar("ele_id") . "\" target=\"_blank\">edit element</a>";
                            }
                            break;
                    }
                }
                $ret .=  $ele->render()."</td></tr>\n";

			} else {
				$hidden .= $ele->render();
			}
		}
		return array($ret, $hidden);
	}

	// need to check whether the element is a standard element, if if so, add the check for whether its row exists or not	
	function _drawValidationJS($skipConditionalCheck) {
		$fullJs = "";
		
		$elements = $this->getElements( true );
		foreach ( $elements as $elt ) {
			if ( method_exists( $elt, 'renderValidationJS' ) ) {
				if(substr($elt->getName(),0,3)=="de_" AND !$skipConditionalCheck) {
					$checkConditionalRow = true;
				} else {
					$checkConditionalRow = false;
				}
				$js = $elt->renderValidationJS();
				if($js AND $checkConditionalRow) {
					$fullJs .= "if(window.document.getElementById('formulize-".$elt->getName()."').style.display != 'none') {\n".$js."\n}\n\n";
				} elseif($js) {
					$fullJs .= "\n".$js."\n";
				}
			}
		}
		
		return $fullJs;
	}
	
}

// SPECIAL CLASS TO HANDLE SITUATIONS WHERE WE'RE RENDERING ONLY THE ROWS FOR THE FORM, NOT THE ENTIRE FORM 
class formulize_elementsOnlyForm extends formulize_themeForm {
	
	function render() {
		// just a slight modification of the render method so that we display only the elements and none of the extra form stuff
		$ele_name = $this->getName();
		$ret = "<div class='xo-theme-form'>
			<table width='100%' class='outer' cellspacing='1'>
			<tr><th colspan='2' class=\"formulize-subform-title\">" . $this->getTitle() . "</th></tr>
		";
		$hidden = '';
		list($ret, $hidden) = $this->_drawElements($this->getElements(), $ret, $hidden);
		$ret .= "</table>\n$hidden\n</div>\n";
		return $ret;
	}

	// render the validation code without the opening/closing part of the validation function, since the form is embedded inside another
	public function renderValidationJS() {
		return $this->_drawValidationJS(false);
	}
}

// this function gets the element that is linked from a form to its parent form
// returns the ele_ids from form table
// note: no enforcement of only one link to a parent form.  You can screw up your framework structure and this function will dutifully return several links to the same parent form
function getParentLinks($fid, $frid) {

	global $xoopsDB;

	$check1 = q("SELECT fl_key1, fl_key2 FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form1_id='$fid' AND fl_frame_id = '$frid' AND fl_unified_display = '1' AND fl_relationship = '3'");
	$check2 = q("SELECT fl_key1, fl_key2 FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form2_id='$fid' AND fl_frame_id = '$frid' AND fl_unified_display = '1' AND fl_relationship = '2'");
	foreach($check1 as $c) {
		$source[] = $c['fl_key2'];
		$self[] = $c['fl_key1'];
	}
	foreach($check2 as $c) {
		$source[] = $c['fl_key1'];
		$self[] = $c['fl_key2'];
	}

	$to_return['source'] = $source;
	$to_return['self'] = $self;

	return $to_return;

}


// this function returns the captions and values that are in the DB for an existing entry
// $elements is used to specify a shortlist of elements to display.  Used in conjunction with the array option for $formform
// $formulize_mgr is not required any longer!
function getEntryValues($entry, $formulize_mgr, $groups, $fid, $elements="", $mid, $uid, $owner, $groupEntryWithUpdateRights) {

	if(!$fid) { // fid is required
		return "";
	}
	
	if(!is_numeric($entry) OR !$entry) {
		return "";
	}

	static $cachedEntryValues = array();
	$serializedElements = serialize($elements);
	if(!isset($cachedEntryValues[$fid][$entry][$serializedElements])) {
	
		global $xoopsDB;
	
		if(!$mid) { $mid = getFormulizeModId(); }
	
		if(!$uid) {
			global $xoopsUser;
			$uid = $xoopsUser ? $xoopsUser->getVar("uid") : 0; // if there is no uid, then use the $xoopsUser uid if there is one, or zero for anons			
		}

		if(!$owner) {
			$owner = getEntryOwner($entry, $fid); // if there is no owner, then get the owner for this entry in this form
		}
		
		// viewquery changed in light of 3.0 data structure changes...
		//$viewquery = q("SELECT ele_caption, ele_value FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req=$entry $element_query");
		// NEED TO CHECK THE FORM FOR ENCRYPTED ELEMENTS, AND ADD THEM AFTER THE * WITH SPECIAL ALIASES. tHEN IN THE LOOP, LOOK FOR THE ALIASES, AND SKIP PROCESSING THOSE ELEMENTS NORMALLY, BUT IF WHEN PROCESSING A NORMAL ELEMENT, IT IS IN THE LIST OF ENCRYPTED ELEMENTS, THEN GET THE ALIASED, DECRYPTED VALUE INSTEAD OF THE NORMAL ONE
		// NEED TO ADD RETRIEVING ENCRYPTED ELEMENT LIST FROM FORM OBJECT
		$form_handler =& xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($fid);
		$formHandles = $formObject->getVar('elementHandles');
		$formCaptions = $formObject->getVar('elementCaptions');
		$formEncryptedElements = $formObject->getVar('encryptedElements');
		$encryptedSelect = "";
		foreach($formEncryptedElements as $thisEncryptedElement) {
			$encryptedSelect .= ", AES_DECRYPT(`".$thisEncryptedElement."`, '".getAESPassword()."') as 'decrypted_value_for_".$thisEncryptedElement."'";
		}
		
		$viewquerydb = q("SELECT * $encryptedSelect FROM " . $xoopsDB->prefix("formulize_" . $formObject->getVar('form_handle')) . " WHERE entry_id=$entry");
		$viewquery = array();
		
		// need to parse the result based on the elements requested and setup the viewquery array for use later on
		$vqindexer = 0;
		foreach($viewquerydb[0] as $thisField=>$thisValue) {
			if(strstr($thisField, "decrypted_value_for_")) { continue; } // don't process these values normally, instead, we just refer to them later to grab the decrypted value, if this iteration is over an encrypted element.
			$includeElement = false;
			if(is_array($elements)) {
				if(in_array(array_search($thisField, $formHandles), $elements) AND $thisValue !== "") {
					$includeElement = true;
				}
			} elseif(!strstr($thisField, "creation_uid") AND !strstr($thisField, "creation_datetime") AND !strstr($thisField, "mod_uid") AND !strstr($thisField, "mod_datetime") AND !strstr($thisField, "entry_id") AND $thisValue !== "") {
				$includeElement = true;
			}
			if($includeElement) {
				$viewquery[$vqindexer]["ele_handle"] = $thisField;
				$viewquery[$vqindexer]["ele_caption"] = $formCaptions[array_search($thisField, $formHandles)];
				if(in_array($thisField, $formEncryptedElements)) {
					$viewquery[$vqindexer]["ele_value"] = $viewquerydb[0]["decrypted_value_for_".$thisField];
				} else {
					$viewquery[$vqindexer]["ele_value"] = $thisValue;	
				}
			}
			$vqindexer++;
		}
	
		// build query for display groups and disabled
		foreach($groups as $thisgroup) {
			$gq .= " OR ele_display LIKE '%,$thisgroup,%'";
			//$dgq .= " AND ele_disabled NOT LIKE '%,$thisgroup,%'"; // not sure that this is necessary
		}
	
		// exclude private elements unless the user has view_private_elements permission, or update_entry permission on a one-entry-per group entry
		$private_filter = "";
		$gperm_handler =& xoops_gethandler('groupperm');
		$view_private_elements = $gperm_handler->checkRight("view_private_elements", $fid, $groups, $mid);
	
		if(!$view_private_elements AND $uid != $owner AND !$groupEntryWithUpdateRights) {
			$private_filter = " AND ele_private=0";
		} 
	
		$allowedquery = q("SELECT ele_caption, ele_disabled, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form=$fid AND (ele_display='1' $gq) $private_filter"); // AND (ele_disabled != 1 $dgq)"); // not sure that filtering for disabled elements is necessary
		$allowedDisabledStatus = array();
		$allowedhandles = array();
		foreach($allowedquery as $onecap) {
			$allowedhandles[] = $onecap['ele_handle'];
			$allowedDisabledStatus[$onecap['ele_handle']] = $onecap['ele_disabled'];
		}
	
		foreach($viewquery as $vq) {
			// check that this caption is an allowed caption before recording the value
			if(in_array($vq["ele_handle"], $allowedhandles)) {
				$prevEntry['handles'][] = $vq["ele_handle"];
				$prevEntry['captions'][] = $vq["ele_caption"];
				$prevEntry['values'][] = $vq["ele_value"];
				$prevEntry['disabled'][] = $allowedDisabledStatus[$vq['ele_handle']];
			}
		}
		$cachedEntryValues[$fid][$entry][$serializedElements] = $prevEntry;
	}
	return $cachedEntryValues[$fid][$entry][$serializedElements];
	
}


function displayForm($formframe, $entry="", $mainform="", $done_dest="", $button_text="", $settings="", $titleOverride="", $overrideValue="",
    $overrideMulti="", $overrideSubMulti="", $viewallforms=0, $profileForm=0, $printall=0, $screen=null) 
{
    include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';
    include_once XOOPS_ROOT_PATH.'/modules/formulize/include/extract.php';
    formulize_benchmark("Start of formDisplay.");

    if($titleOverride == "formElementsOnly") {
        $titleOverride = "all";
        $formElementsOnly = true;
    }

    if(!is_numeric($titleOverride) AND $titleOverride != "" AND $titleOverride != "all") {
        // we can pass in a text title for the form, and that will cause the $titleOverride "all" behaviour to be invoked, and meanwhile we will use this title for the top of the form
        $passedInTitle = $titleOverride;
        $titleOverride = "all";
    }

    //syntax:
    //displayform($formframe, $entry, $mainform)
    //$formframe is the id of the form OR title of the form OR name of the framework.  Can also be an array.  If it is an array, then flag 'formframe' is the $formframe variable, and flag 'elements' is an array of all the elements that are to be displayed.
    //the array option is intended for displaying only part of a form at a time
    //$entry is the numeric entry to display in the form -- if $entry is the word 'proxy' then it is meant to force a new form entry when the form is a single-entry form that the user already may have an entry in
    //$mainform is the starting form to use, if this is a framework (can be specified by form id or by handle)
    //$done_dest is the URL to go to after the form has been submitted
    //Steps:
    //1. identify form or framework
    //2. if framework, check for unified display options
    //3. if entry specified, then get data for that entry
    //4. drawform with data if necessary

	global $xoopsDB, $xoopsUser, $myts;

	global $sfidsDrawn;
	if(!is_array($sfidsDrawn)) {
		$sfidsDrawn = array();
	}

	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);

	$original_entry = $entry; // flag used to tell whether the function was called with an actual entry specified, ie: we're supposed to be editing this entry, versus the entry being set by coming back form a sub_form or other situation.

	$mid = getFormulizeModId();

	$currentURL = getCurrentURL();

	// identify form or framework
	$elements_allowed = "";
	// if a screen object is passed in, select the elements for display based on the screen's settings
	if ($screen and is_a($screen, "formulizeFormScreen")) {
		$elements_allowed = $screen->getVar("formelements");
	}
	if(is_array($formframe)) {
		$elements_allowed = $formframe['elements'];
		$printViewPages = isset($formframe['pages']) ? $formframe['pages'] : "";
		$printViewPageTitles = isset($formframe['pagetitles']) ? $formframe['pagetitles'] : "";
		$formframetemp = $formframe['formframe'];
		unset($formframe);
		$formframe = $formframetemp;
	}

	list($fid, $frid) = getFormFramework($formframe, $mainform);

	if($_POST['deletesubsflag']) { // if deletion of sub entries requested
		foreach($_POST as $k=>$v) {
			if(strstr($k, "delbox")) {
				$subs_to_del[] = $v;
			}
		}
		if(count($subs_to_del) > 0) {
			
			deleteFormEntries($subs_to_del, intval($_POST['deletesubsflag'])); // deletesubsflag will be the sub form id
 			sendNotifications($_POST['deletesubsflag'], "delete_entry", $subs_to_del, $mid, $groups);
		}
	}

	if($_POST['parent_form']) { // if we're coming back from a subform
		$entry = $_POST['parent_entry'];
		$fid = $_POST['parent_form'];
	}

	if($_POST['go_back_form']) { // we just received a subform submission
		$entry = $_POST['sub_submitted'];
		$fid = $_POST['sub_fid'];
		$go_back['form'] = $_POST['go_back_form'];
		$go_back['entry'] = $_POST['go_back_entry'];
	}

	// set $entry in the case of a form_submission where we were editing an entry (just in case that entry is not what is used to call this function in the first place -- ie: we're on a subform and the mainform has no entry specified, or we're clicking submit over again on a single-entry form where we started with no entry)
	$entrykey = "entry" . $fid;
	if((!$entry OR $entry=="proxy") AND $_POST[$entrykey]) { // $entrykey will only be set when *editing* an entry, not on new saves
		$entry = $_POST[$entrykey];
	}
	
	// this is probably not necessary any more, due to architecture changes in Formulize 3
	// formulize_newEntryIds is set when saving data
	if(!$entry AND isset($GLOBALS['formulize_newEntryIds'][$fid])) {
		$entry = $GLOBALS['formulize_newEntryIds'][$fid][0];
	}

	$member_handler =& xoops_gethandler('member');
	$gperm_handler = &xoops_gethandler('groupperm');
	if($profileForm === "new") { 
		 // spoof the $groups array based on the settings for the regcode that has been validated by register.php
		$reggroupsq = q("SELECT reg_codes_groups FROM " . XOOPS_DB_PREFIX . "_reg_codes WHERE reg_codes_code=\"" . $GLOBALS['regcode'] . "\"");
		$groups = explode("&8(%$", $reggroupsq[0]['reg_codes_groups']);
		if($groups[0] === "") { unset($groups); } // if a code has no groups associated with it, then kill the null value that will be in position 0 in the groups array.
		$groups[] = XOOPS_GROUP_USERS;
		$groups[] = XOOPS_GROUP_ANONYMOUS;
	}	
	$uid = $xoopsUser ? $xoopsUser->getVar('uid') : '0';

	$single_result = getSingle($fid, $uid, $groups, $member_handler, $gperm_handler, $mid);
	$single = $single_result['flag'];
	// if we're looking at a single entry form with no entry specified and where the user has no entry of their own, or it's an anonymous user, then set the entry based on a cookie if one is present
	// want to do this check here and override $entry prior to the security check since we don't like trusting cookies!
	$cookie_entry = (isset($_COOKIE['entryid_'.$fid]) AND !$entry AND $single AND ($single_result['entry'] == "" OR intval($uid) === 0)) ? $_COOKIE['entryid_'.$fid] : "";
	include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
	$data_handler = new formulizeDataHandler($fid);
	if($cookie_entry) { 
		// check to make sure the cookie_entry exists...
		//$check_cookie_entry = q("SELECT id_req FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req=" . intval($cookie_entry));
		//if($check_cookie_entry[0]['id_req'] > 0) {
		if($data_handler->entryExists(intval($cookie_entry))) {
			$entry = $cookie_entry; 
		} else {
			$cookie_entry = "";
		}
	}
	$owner = ($cookie_entry AND $uid) ? $uid : getEntryOwner($entry, $fid); // if we're pulling a cookie value and there is a valid UID in effect, then assume this user owns the entry, otherwise, figure out who does own the entry
	$owner_groups = $data_handler->getEntryOwnerGroups($entry);

	if($single AND !$entry AND !$overrideMulti AND $profileForm !== "new") { // only adjust the active entry if we're not already looking at an entry, and there is no overrideMulti which can be used to display a new blank form even on a single entry form -- useful for when multiple anonymous users need to be able to enter information in a form that is "one per user" for registered users. -- the pressence of a cookie on the hard drive of a user will override other settings
		$entry = $single_result['entry'];
		$owner = getEntryOwner($entry, $fid);
		unset($owner_groups);
		//$owner_groups =& $member_handler->getGroupsByUser($owner, FALSE);
		$owner_groups = $data_handler->getEntryOwnerGroups($entry);
	}

	if($entry == "proxy") { $entry = ""; } // convert the proxy flag to the actual null value expected for new entry situations (do this after the single check!)
	$editing = is_numeric($entry); // will be true if there is an entry we're looking at already

	if(!$scheck = security_check($fid, $entry, $uid, $owner, $groups, $mid, $gperm_handler) AND !$viewallforms AND !$profileForm) {
		print "<p>" . _NO_PERM . "</p>";
		return;
	}

	// main security check passed, so let's initialize flags	
	$go_back['url'] = substr($done_dest, 0, 1) == "/" ? XOOPS_URL . $done_dest : $done_dest;

	// set these arrays for the one form, and they are added to by the framework if it is in effect
	$fids[0] = $fid;
	if($entry) {
		$entries[$fid][0] = $entry;
	} else {
		$entries[$fid][0] = "";
	}


	if($frid) { 
		$linkResults = checkForLinks($frid, $fids, $fid, $entries, $gperm_handler, $owner_groups, $mid, $member_handler, $owner); 
		unset($entries);
		unset($fids);

		$fids = $linkResults['fids'];
		$entries = $linkResults['entries'];
		$sub_fids = $linkResults['sub_fids'];
		$sub_entries = $linkResults['sub_entries'];
	}
 
	// need to handle submission of entries 
	$formulize_mgr =& xoops_getmodulehandler('elements', 'formulize');

	$info_received_msg = 0;
	$info_continue = 0;
    if($entries[$fid][0]) {
        $info_continue = 1;
    }
	
	$add_own_entry = $gperm_handler->checkRight("add_own_entry", $fid, $groups, $mid);
	$add_proxy_entries = $gperm_handler->checkRight("add_proxy_entries", $fid, $groups, $mid);
	
	if ($_POST['form_submitted'] and $profileForm !== "new" and formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
		$info_received_msg = "1"; // flag for display of info received message
		if(!isset($GLOBALS['formulize_readElementsWasRun'])) {
			include_once XOOPS_ROOT_PATH . "/modules/formulize/include/readelements.php";
		}
		$temp_entries = $GLOBALS['formulize_allWrittenEntryIds']; // set in readelements.php
		
		if(!$formElementsOnly AND ($single OR $_POST['target_sub'] OR ($entries[$fid][0] AND ($original_entry OR ($_POST[$entrykey] AND !$_POST['back_from_sub']))) OR $overrideMulti OR ($_POST['go_back_form'] AND $overrideSubMulti))) { // if we just did a submission on a single form, or we just edited a multi, then assume the identity of the new entry.  Can be overridden by values passed to this function, to force multi forms to redisplay the just-saved entry.  Back_from_sub is used to override the override, when we're saving after returning from a multi-which is like editing an entry since entries are saved prior to going to a sub. -- Sept 4 2006: adding an entry in a subform forces us to stay on the same page too! -- Dec 21 2011: added check for !$formElementsOnly so that when we're getting just the elements in the form, we ignore any possible overriding, since that is an API driven situation where the called entry is the only one we want to display, period.
			$entry = $temp_entries[$fid][0];
			unset($entries);
			foreach($fids as $thisWrittenFid) {
				$entries[$thisWrittenFid] = $temp_entries[$thisWrittenFid];
			}
			// also remove any fids that aren't part of the $temp_entries...added Oct 26 2011...checkforlinks now can return the mainform when we're on a sub!  It's smarter, but displayForm (and possibly other places) were not built to assume it was that smart.
			$writtenFids = array_keys($temp_entries);
			$fids = array_intersect($fids, $writtenFids);
			$owner = getEntryOwner($entry, $fid);
			unset($owner_groups);
			$owner_groups = $data_handler->getEntryOwnerGroups($entry);
			//$owner_groups =& $member_handler->getGroupsByUser($owner, FALSE);
			$info_continue = 1;
		} elseif(!$_POST['target_sub']) { // as long as the form was submitted and we're not going to a sub form, then display the info received message and carry on with a blank form
			if(!$original_entry) { // if we're on a multi-form where the display form function was called without an entry, then clear the entries and behave as if we're doing a new add
				unset($entries);
				unset($sub_entries);
				$entries[$fid][0] = "";
				$sub_entries[$sub_fids[0]][0] = "";
			}
			$info_continue = 2;
		}
	}

	$sub_entries_synched = synchSubformBlankDefaults($fid, $entry);
	foreach($sub_entries_synched as $synched_sfid=>$synched_ids) {
		foreach($synched_ids as $synched_id) {
			$sub_entries[$synched_sfid][] = $synched_id;
		}
	}
	if(count($sub_entries_synched)>0) {
		formulize_updateDerivedValues($entry, $fid, $frid);
	}

	// special use of $settings added August 2 2006 -- jwe -- break out of form if $settings so indicates
	// used to allow saving of information when you don't want the form itself to reappear
	if($settings == "{RETURNAFTERSAVE}" AND $_POST['form_submitted']) { return "returning_after_save"; }

      // need to add code here to switch some things around if we're on a subform for the first time (add)
	// note: double nested sub forms will not work currently, since on the way back to the intermediate level, the go_back values will not be set correctly
	// target_sub is only set when adding a sub entry, and adding sub entries is now down by the subform ui
      //if($_POST['target_sub'] OR $_POST['goto_sfid']) {
	if($_POST['goto_sfid']) {
		$info_continue = 0;
		if($_POST['goto_sfid']) {
			$new_fid = $_POST['goto_sfid'];
		} else {
			$new_fid = $_POST['target_sub'];
		}
		$go_back['form'] = $fid;
		$go_back['entry'] = $temp_entries[$fid][0];
		unset($entries);
		unset($fids);
		unset($sub_fids);
		unset($sub_entries);
		$fid = $new_fid;
		$fids[0] = $new_fid;
		if($_POST['target_sub']) { // if we're adding a new entry
			$entries[$new_fid][0] = "";
		} else { // if we're going to an existing entry
			$entries[$new_fid][0] = $_POST['goto_sub'];
		}
		$entry = $entries[$new_fid][0];
		$single_result = getSingle($fid, $uid, $groups, $member_handler, $gperm_handler, $mid);
		$single = $single_result['flag'];
		if($single AND !$entry) {
			$entry = $single_result['entry'];
			unset($entries);
			$entries[$fid][0] = $entry;
		}
		unset($owner);
		$owner = getEntryOwner($entries[$new_fid][0], $new_fid); 
		$editing = is_numeric($entry); 
		unset($owner_groups);
		//$owner_groups =& $member_handler->getGroupsByUser($owner, FALSE);
		$newFidData_handler = new formulizeDataHandler($new_fid);
		$owner_groups = $newFidData_handler->getEntryOwnerGroups($entries[$new_fid][0]);
		$info_received_msg = 0;// never display this message when a subform is displayed the first time.	
		if($entry) { $info_continue = 1; }
		if(!$scheck = security_check($fid, $entries[$fid][0], $uid, $owner, $groups, $mid, $gperm_handler) AND !$viewallforms) {
			print "<p>" . _NO_PERM . "</p>";
			return;
		}
	}

    // there are several points above where $entry is set, and now that we have a final value, store in ventry
    if ($entry > 0 and (!isset($settings['ventry']) or ("addnew" != $settings['ventry']))) {
        $settings['ventry'] = $entry;
    }

	// set the alldoneoverride if necessary -- August 22 2006
	$config_handler =& xoops_gethandler('config');
	$formulizeConfig = $config_handler->getConfigsByCat(0, $mid);
	// remove the all done button if the config option says 'no', and we're on a single-entry form, or the function was called to look at an existing entry, or we're on an overridden Multi-entry form
	$allDoneOverride = (!$formulizeConfig['all_done_singles'] AND !$profileForm AND (($single OR $overrideMulti OR $original_entry) AND !$_POST['target_sub'] AND !$_POST['goto_sfid'] AND !$_POST['deletesubsflag'] AND !$_POST['parent_form'])) ? true : false;
	
	if($allDoneOverride AND $_POST['form_submitted']) {
		drawGoBackForm($go_back, $currentURL, $settings, $entry);
		print "<script type=\"text/javascript\">window.document.go_parent.submit();</script>\n";
		return;
	} else {
		// only do all this stuff below, the normal form displaying stuff, if we are not leaving this page now due to the all done button being overridden
		
		// we cannot have the back logic above invoked when dealing with a subform, but if the override is supposed to be in place, then we need to invoke it
		if(!$allDoneOverride AND !$formulizeConfig['all_done_singles'] AND !$profileForm AND ($_POST['target_sub'] OR $_POST['goto_sfid'] OR $_POST['deletesubsflag'] OR $_POST['parent_form']) AND ($single OR $original_entry OR $overrideMulti)) {
			$allDoneOverride = true;
		}
	
	
	
		/*if($uid==1) {
		print "Forms: ";
		print_r($fids);
		print "<br>Entries: ";
		print_r($entries);
		print "<br>Subforms: ";
		print_r($sub_fids);
		print "<br>Subentries: ";
		print_r($sub_entries); // debug block - ONLY VISIBLE TO USER 1 RIGHT NOW 
		} */
		
		formulize_benchmark("Ready to start building form.");
		
		$title = "";
		foreach($fids as $this_fid) {
	
			if(!$scheck = security_check($this_fid, $entries[$this_fid][0], $uid, $owner, $groups, $mid, $gperm_handler) AND !$viewallforms) {
				continue;
			}
			
            // if there is more than one form, try to make the 1-1 links
            // and if we made any, then include the newly linked up entries
            // in the index of entries that we're keeping track of
            if(count($fids) > 1) {
                list($form1s, $form2s, $form1EntryIds, $form2EntryIds) = formulize_makeOneToOneLinks($frid, $this_fid);
                foreach($form1EntryIds as $i=>$form1EntryId) {
                    // $form1EntryId set above, now set other values for this iteration based on the key
                    $form2EntryId = $form2EntryIds[$i];
                    $form1 = $form1s[$i];
                    $form2 = $form2s[$i];
						if($form1EntryId) {
							$entries[$form1][0] = $form1EntryId;
						}
						if($form2EntryId) {
							$entries[$form2][0] = $form2EntryId;
						}
					} 
				}
			
				unset($prevEntry);
            // if there is an entry, then get the data for that entry
            if ($entries[$this_fid]) {
                $groupEntryWithUpdateRights = ($single == "group" AND $gperm_handler->checkRight("update_own_entry", $fid, $groups, $mid) AND $entry == $single_result['entry']);
					$prevEntry = getEntryValues($entries[$this_fid][0], $formulize_mgr, $groups, $this_fid, $elements_allowed, $mid, $uid, $owner, $groupEntryWithUpdateRights); 
				}

				// display the form

				//get the form title: (do only once)
			$firstform = 0;
			if(!$form) {

                $firstform = 1;
                $title = isset($passedInTitle) ? $passedInTitle : trans(getFormTitle($this_fid));
                if ($screen) {
                    $title = trans($screen->getVar('title'));
                }
                unset($form);
                if($formElementsOnly) {
                    $form = new formulize_elementsOnlyForm($title, 'formulize', "$currentURL", "post", true);
                } else {
                    // extended class that puts formulize element names into the tr tags for the table, so we can show/hide them as required
                    $form = new formulize_themeForm($title, 'formulize', "$currentURL", "post", true);
                    // necessary to trigger the proper reloading of the form page, until Done is called and that form does not have this flag.
                    if (!isset($settings['ventry']))
                        $settings['ventry'] = 'new';
                    $form->addElement (new XoopsFormHidden ('ventry', $settings['ventry']));
                }
                $form->setExtra("enctype='multipart/form-data'"); // impératif!

                if(is_array($settings)) {
                    $form = writeHiddenSettings($settings, $form);
                }

                // include who the entry belongs to and the date
                // include acknowledgement that information has been updated if we have just done a submit
                // form_meta includes: last_update, created, last_update_by, created_by

                $breakHTML = "";

                if(!$profileForm AND $titleOverride != "all") {
                    // build the break HTML and then add the break to the form
                    if(!strstr($currentURL, "printview.php")) {
                        $breakHTML .= "<center class=\"no-print\">";
                        $breakHTML .= "<p><b>";
                        if($info_received_msg) {
                            $breakHTML .= _formulize_INFO_SAVED . "&nbsp;";
                        }
                        if($info_continue == 1 and formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
                            $breakHTML .= "<p class=\"no-print\">"._formulize_INFO_CONTINUE1."</p>";
                        } elseif($info_continue == 2) {
                            $breakHTML .=  "<p class=\"no-print\">"._formulize_INFO_CONTINUE2."</p>";
                        } elseif(!$entry and formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
                            $breakHTML .=  "<p class=\"no-print\">"._formulize_INFO_MAKENEW."</p>";
                        }
                        $breakHTML .= "</b></p>";
                        $breakHTML .= "</center>";
                    }

                    $breakHTML .= "<table cellpadding=5 width=100%><tr><td width=50% style=\"vertical-align: bottom;\">";
                    $breakHTML .= "<p><b>" . _formulize_FD_ABOUT . "</b><br>";

                    if($entries[$this_fid][0]) {
                        $form_meta = getMetaData($entries[$this_fid][0], $member_handler, $this_fid);
                        $breakHTML .= _formulize_FD_CREATED . $form_meta['created_by'] . " " . formulize_formatDateTime($form_meta['created']) . "<br>" . _formulize_FD_MODIFIED . $form_meta['last_update_by'] . " " . formulize_formatDateTime($form_meta['last_update']) . "</p>";
                    } else {
                        $breakHTML .= _formulize_FD_NEWENTRY . "</p>";
                    }

					$breakHTML .= "</td><td width=50% style=\"vertical-align: bottom;\">";
					if (strstr($currentURL, "printview.php") or !formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
						$breakHTML .= "<p>";
					} else {
						// get save and button button options
						$save_button_text = "";
						$done_button_text = "";
						if(is_array($button_text)) {
							$save_button_text = $button_text[1];
							$done_button_text = $button_text[0];						
						} else { 
							$done_button_text = $button_text;						
						}
						if(!$done_button_text AND !$allDoneOverride) {
							$done_button_text = _formulize_INFO_DONE1 . _formulize_DONE . _formulize_INFO_DONE2;
						} elseif($done_button_text != "{NOBUTTON}" AND !$allDoneOverride) {
							$done_button_text = _formulize_INFO_DONE1 . $done_button_text . _formulize_INFO_DONE2;
						// check to see if the user is allowed to modify the existing entry, and if they're not, then we have to draw in the all done button so they have a way of getting back where they're going
						} elseif (($entry and formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) OR !$entry) {
							$done_button_text = "";
						} else {
							$done_button_text = _formulize_INFO_DONE1 . _formulize_DONE . _formulize_INFO_DONE2;					
						}

						$nosave = false;
						if(!$save_button_text and formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
							$save_button_text = _formulize_INFO_SAVEBUTTON;
						} elseif ($save_button_text != "{NOBUTTON}" and formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
							$save_button_text = _formulize_INFO_SAVE1 . $save_button_text . _formulize_INFO_SAVE2;
						} else {
							$save_button_text = _formulize_INFO_NOSAVE;
							$nosave = true;
						}
						$breakHTML .= "<p class='no-print'>" . $save_button_text;
						if($done_button_text) {
							$breakHTML .= "<br>" . $done_button_text;
						}
					}
					$breakHTML .= "</p></td></tr></table>";
					$form->insertBreak($breakHTML, "even");
				} elseif($profileForm) {
					// if we have a profile form, put the profile fields at the top of the form, populated based on the DB values from the _users table
					$form = addProfileFields($form, $profileForm);
				}
			}

			if($titleOverride=="1" AND !$firstform) { // set onetooneTitle flag to 1 when function invoked to force drawing of the form title over again
				$title = trans(getFormTitle($this_fid));
				$form->insertBreak("<table><th>$title</th></table>","");
			}

			// if this form has a parent, then determine the $parentLinks
			if($go_back['form'] AND !$parentLinks[$this_fid]) {
				$parentLinks[$this_fid] = getParentLinks($this_fid, $frid);
			}

			formulize_benchmark("Before Compile Elements.");
			$form = compileElements($this_fid, $form, $formulize_mgr, $prevEntry, $entries[$this_fid][0], $go_back,
				$parentLinks[$this_fid], $owner_groups, $groups, $overrideValue, $elements_allowed, $profileForm,
				$frid, $mid, $sub_entries, $sub_fids, $member_handler, $gperm_handler, $title, $screen,
				$printViewPages, $printViewPageTitles);
			formulize_benchmark("After Compile Elements.");
		}	// end of for each fids

        if(!is_object($form)) {
            exit("Error: the form cannot be displayed.  Does the current group have permission to access the form?");
        }

        // DRAW IN THE SPECIAL UI FOR A SUBFORM LINK (ONE TO MANY)
        if(count($sub_fids) > 0) { // if there are subforms, then draw them in...only once we have a bonafide entry in place already
            // draw in special params for this form, but only once per page
            global $formulize_subformHiddenFieldsDrawn;
            if ($formulize_subformHiddenFieldsDrawn != true) {
                $formulize_subformHiddenFieldsDrawn = true;
                $form->addElement (new XoopsFormHidden ('target_sub', ''));
                $form->addElement (new XoopsFormHidden ('target_sub_instance', ''));
                $form->addElement (new XoopsFormHidden ('numsubents', 1));
                $form->addElement (new XoopsFormHidden ('del_subs', ''));
                $form->addElement (new XoopsFormHidden ('goto_sub', ''));
                $form->addElement (new XoopsFormHidden ('goto_sfid', ''));
            }

			foreach($sub_fids as $subform_id) {
				// only draw in the subform UI if the subform hasn't been drawn in previously, courtesy of a subform element in the form.
				// Subform elements are recommended since they provide 1. specific placement, 2. custom captions, 3. direct choice of form elements to include
				if(in_array($subform_id, $sfidsDrawn) OR $elements_allowed OR (!$scheck = security_check($subform_id, "", $uid, $owner, $groups, $mid, $gperm_handler) AND !$viewallforms)) { // no entry passed so this will simply check whether they have permission for the form or not
					continue;
				}
				$subUICols = drawSubLinks($subform_id, $sub_entries, $uid, $groups, $frid, $mid, $fid, $entry);
				unset($subLinkUI);
				if(isset($subUICols['single'])) {
					$form->insertBreak($subUICols['single'], "even");
				} else {
					$subLinkUI = new XoopsFormLabel($subUICols['c1'], $subUICols['c2']);
					$form->addElement($subLinkUI);
				}
			}
		} 
	
	
		// draw in proxy box if necessary (only if they have permission and only on new entries, not on edits)
		if(!strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
			if($gperm_handler->checkRight("add_proxy_entries", $fid, $groups, $mid) AND !$entries[$fid][0]) {
				$form = addOwnershipList($form, $groups, $member_handler, $gperm_handler, $fid, $mid);
			} elseif($entries[$fid][0] AND $gperm_handler->checkRight("update_entry_ownership", $fid, $groups, $mid)) {
				$form = addOwnershipList($form, $groups, $member_handler, $gperm_handler, $fid, $mid, $entries[$fid][0]);	
			}
		}
	
		// draw in the submitbutton if necessary
		if (!$formElementsOnly and formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
			$form = addSubmitButton($form, _formulize_SAVE, $go_back, $currentURL, $button_text, $settings, $temp_entries[$this_fid][0], $fids, $formframe, $mainform, $entry, $profileForm, $elements_allowed, $allDoneOverride, $printall, $screen);
			}
	
		if(!$formElementsOnly) {
			
			// add flag to indicate that the form has been submitted
			$form->addElement (new XoopsFormHidden ('form_submitted', "1"));
			if($go_back['form']) { // if this is set, then we're doing a subform, so put in a flag to prevent the parent from being drawn again on submission
				$form->addElement (new XoopsFormHidden ('sub_fid', $fid));
				$form->addElement (new XoopsFormHidden ('sub_submitted', $entries[$fid][0]));
				$form->addElement (new XoopsFormHidden ('go_back_form', $go_back['form']));
				$form->addElement (new XoopsFormHidden ('go_back_entry', $go_back['entry']));
			} else {
				// drawing a main form...put in the scroll position flag
				$form->addElement (new XoopsFormHidden ('yposition', 0));
			}
			
			// saving message
			print "<div id=savingmessage style=\"display: none; position: absolute; width: 100%; right: 0px; text-align: center; padding-top: 50px;\">\n";
			global $xoopsConfig;
			if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/images/saving-".$xoopsConfig['language'].".gif") ) {
				print "<img src=\"" . XOOPS_URL . "/modules/formulize/images/saving-" . $xoopsConfig['language'] . ".gif\">\n";
			} else {
				print "<img src=\"" . XOOPS_URL . "/modules/formulize/images/saving-english.gif\">\n";
			}
			print "</div>\n";

			drawJavascript($nosave);
			if(count($GLOBALS['formulize_renderedElementHasConditions'])>0) {
				drawJavascriptForConditionalElements($GLOBALS['formulize_renderedElementHasConditions'], $entries, $sub_entries);
			}

		// lastly, put in a hidden element, that will tell us what the first, primary form was that we were working with on this form submission
		$form->addElement (new XoopsFormHidden ('primaryfid', $fids[0]));
		
		}


		$idForForm = $formElementsOnly ? "" : "id=\"formulizeform\""; // when rendering disembodied forms, don't use the master id!
		print "<div $idForForm>".$form->render()."</div><!-- end of formulizeform -->"; // note, security token is included in the form by the xoops themeform render method, that's why there's no explicity references to the token in the compiling/generation of the main form object
		
		// if we're in Drupal, include the main XOOPS js file, so the calendar will work if present...
		// assumption is that the calendar javascript has already been included by the datebox due to no
		// $xoopsTpl being in effect in Drupal -- this assumption will fail if Drupal is displaying a pageworks
		// page that uses the $xoopsTpl, for instance.  (Date select box file itself checks for $xoopsTpl)
		global $user;
		static $includedXoopsJs = false;
		if(is_object($user) AND !$includedXoopsJs) {
			print "<script type=\"text/javascript\" src=\"" . XOOPS_URL . "/include/xoops.js\"></script>\n";
			$includedXoopsJs = true;
		}
	}// end of if we're not going back to the prev page because of an all done button override
}

// THIS FUNCTION FIGURES OUT THE COMMON VALUE THAT WE SHOULD WRITE WHEN A FORM IN A ONE-TO-ONE RELATIONSHIP IS BEING DISPLAYED AFTER A NEW ENTRY HAS BEEN WRITTEN
function formulize_findCommonValue($form1, $form2, $key1, $key2) {
	$commonValueToWrite = "";
	if(isset($_POST["de_".$form2."_new_".$key2]) AND $_POST["de_".$form2."_new_".$key2] == "{ID}") { // common value is pointing at a textbox that copies the entry ID, so grab the entry ID of the entry just written in the other form
		$commonValueToWrite = $GLOBALS['formulize_newEntryIds'][$form2][0];
	} elseif(isset($_POST["de_".$form2."_new_".$key2])) { // grab the value just written in the field of the other form
		$commonValueToWrite = $_POST["de_".$form2."_new_".$key2];
	} elseif(isset($_POST["de_".$form2."_".$GLOBALS['formulize_allWrittenEntryIds'][$form2][0]."_".$key2])) { // grab the value just written in the first entry we saved in the paired form
		$commonValueToWrite = $_POST["de_".$form2."_".$GLOBALS['formulize_allWrittenEntryIds'][$form2][0]."_".$key2];
	} elseif(isset($GLOBALS['formulize_allWrittenEntryIds'][$form2][0])) { // try to get the value saved in the DB for the target element in the first entry we just saved in the paired form
		$common_value_data_handler = new formulizeDataHandler($form2);
		if($candidateValue = $common_value_data_handler->getElementValueInEntry($GLOBALS['formulize_allWrittenEntryIds'][$form2][0], $key2)) {
			$commonValueToWrite = $candidateValue;
		}
	}
	return $commonValueToWrite;
}

// THIS FUNCTION ADDS THE SPECIAL PROFILE FIELDS TO THE TOP OF A PROFILE FORM
function addProfileFields($form, $profileForm) {
	// add... 
	// username
	// full name
	// e-mail
	// timezone
	// password

	global $xoopsUser, $xoopsConfig, $xoopsConfigUser;
	$config_handler =& xoops_gethandler('config');
	$xoopsConfigUser =& $config_handler->getConfigsByCat(XOOPS_CONF_USER);
	$user_handler =& xoops_gethandler('user');
	$thisUser = $user_handler->get($profileForm);

	// initialize $thisUser
	if($thisUser) {
		$thisUser_name = $thisUser->getVar('name', 'E');
		$thisUser_uname = $thisUser->getVar('uname');
		$thisUser_timezone_offset = $thisUser->getVar('timezone_offset');
		$thisUser_email = $thisUser->getVar('email');
		$thisUser_uid = $thisUser->getVar('uid');
		$thisUser_viewemail = $thisUser->user_viewemail();
		$thisUser_umode = $thisUser->getVar('umode');
		$thisUser_uorder = $thisUser->getVar('uorder');
		$thisUser_notify_method = $thisUser->getVar('notify_method');
		$thisUser_notify_mode = $thisUser->getVar('notify_mode');
		$thisUser_user_sig = $thisUser->getVar('user_sig', 'E');
		$thisUser_attachsig = $thisUser->getVar('attachsig');
	} else { // anon user
		$thisUser_name = $GLOBALS['name']; //urldecode($_GET['name']);
		$thisUser_uname = $GLOBALS['uname']; //urldecode($_GET['uname']);
		$thisUser_timezone_offset = isset($GLOBALS['timezone_offset']) ? $GLOBALS['timezone_offset'] : $xoopsConfig['default_TZ']; // isset($_GET['timezone_offset']) ? urldecode($_GET['timezone_offset']) : $xoopsConfig['default_TZ'];
		$thisUser_email = $GLOBALS['email']; //urldecode($_GET['email']);
		$thisUser_viewemail = $GLOBALS['user_viewemail']; //urldecode($_GET['viewemail']);
		$thisUser_uid = 0;
		$agree_disc = $GLOBALS['agree_disc'];
	}

		include_once XOOPS_ROOT_PATH . "/language/" . $xoopsConfig['language'] . "/user.php";

	$form->insertBreak(_formulize_ACTDETAILS, "head");
	// Check reg_codes module option to use email address as username
	$module_handler =& xoops_gethandler('module');
	$regcodesModule =& $module_handler->getByDirname("reg_codes");
	$regcodesConfig =& $config_handler->getConfigsByCat(0, $regcodesModule->getVar('mid'));

	// following borrowed from edituser.php
	if($profileForm == "new") {
		// 'new' should ONLY be coming from the modified register.php file that the registration codes module uses
		// ie: we are assuming registration codes is installed
		$form->addElement(new XoopsFormHidden('userprofile_regcode', $GLOBALS['regcode']));
		$uname_size = $xoopsConfigUser['maxuname'] < 255 ? $xoopsConfigUser['maxuname'] : 255;
		$labelhelptext = _formulize_USERNAME_HELP1; // set it to a variable so we can test for its existence; don't want to print this stuff if there's no translation
		$labeltext = $labelhelptext == "" ? _US_NICKNAME : _US_NICKNAME . _formulize_USERNAME_HELP1 . $xoopsConfigUser['minuname'] . _formulize_USERNAME_HELP2 . $uname_size . _formulize_USERNAME_HELP3;
		if ($regcodesConfig['email_as_username'] == 0)	{
			// Allow User names to be created
			$uname_label = new XoopsFormText($labeltext, 'userprofile_uname', $uname_size, $uname_size, $thisUser_uname);
			$uname_reqd = 1;
		}
		else {
			// Usernames are created based on email address
			$uname_label = new XoopsFormHidden('userprofile_uname', $thisUser_uname);
			$uname_reqd = 0;
		}
		$form->addElement($uname_label, $uname_reqd);
	} else {
		$uname_label = new XoopsFormLabel(_US_NICKNAME, $thisUser_uname);
		$form->addElement($uname_label);
	}
	$email_tray = new XoopsFormElementTray(_US_EMAIL, '<br />');
	if ($profileForm == "new" OR (($xoopsConfigUser['allow_chgmail'] == 1) && ($regcodesConfig['email_as_username'] == 0))) {
      	$email_text = new XoopsFormText('', 'userprofile_email', 30, 255, $thisUser_email);
		$email_tray->addElement($email_text, 1);
	}
	else {
        $email_text = new XoopsFormLabel('', $thisUser_email);
		$email_tray->addElement($email_text);
	}
	$email_cbox_value = $thisUser_viewemail ? 1 : 0;
	$email_cbox = new XoopsFormCheckBox('', 'userprofile_user_viewemail', $email_cbox_value);
	$email_cbox->addOption(1, _US_ALLOWVIEWEMAIL);
	$email_tray->addElement($email_cbox);
	$form->addElement($email_tray, 1);
	
		
	$passlabel = $profileForm == "new" ? _formulize_TYPEPASSTWICE_NEW : _formulize_TYPEPASSTWICE_CHANGE;
	$passlabel .= $xoopsConfigUser['minpass'] . _formulize_PASSWORD_HELP1;
	$pwd_tray = new XoopsFormElementTray(_US_PASSWORD.'<br />'.$passlabel);
	$pwd_text = new XoopsFormPassword('', 'userprofile_password', 10, 32);
	$pwd_text2 = new XoopsFormPassword('', 'userprofile_vpass', 10, 32);
	$pass_required = $profileForm == "new" ? 1 : 0;
	$pwd_tray->addElement($pwd_text, $pass_required);
	$pwd_tray->addElement($pwd_text2, $pass_required);
	$form->addElement($pwd_tray, $pass_required);
	$name_text = new XoopsFormText(_US_REALNAME, 'userprofile_name', 30, 60, $thisUser_name);
	$form->addElement($name_text, 1);
	$timezone_select = new XoopsFormSelectTimezone(_US_TIMEZONE, 'userprofile_timezone_offset', $thisUser_timezone_offset);
	$form->addElement($timezone_select);

	if($profileForm != "new") {
      	$umode_select = new XoopsFormSelect(_formulize_CDISPLAYMODE, 'userprofile_umode', $thisUser_umode);
      	$umode_select->addOptionArray(array('nest'=>_NESTED, 'flat'=>_FLAT, 'thread'=>_THREADED));
      	$form->addElement($umode_select);
      	$uorder_select = new XoopsFormSelect(_formulize_CSORTORDER, 'userprofile_uorder', $thisUser_uorder);
      	$uorder_select->addOptionArray(array(XOOPS_COMMENT_OLD1ST => _OLDESTFIRST, XOOPS_COMMENT_NEW1ST => _NEWESTFIRST));
      	$form->addElement($uorder_select);
      	include_once XOOPS_ROOT_PATH . "/language/" . $xoopsConfig['language'] . '/notification.php';
      	include_once XOOPS_ROOT_PATH . '/include/notification_constants.php';
      	$notify_method_select = new XoopsFormSelect(_NOT_NOTIFYMETHOD, 'userprofile_notify_method', $thisUser_notify_method);
      	$notify_method_select->addOptionArray(array(XOOPS_NOTIFICATION_METHOD_DISABLE=>_NOT_METHOD_DISABLE, XOOPS_NOTIFICATION_METHOD_PM=>_NOT_METHOD_PM, XOOPS_NOTIFICATION_METHOD_EMAIL=>_NOT_METHOD_EMAIL));
      	$form->addElement($notify_method_select);
      	$notify_mode_select = new XoopsFormSelect(_NOT_NOTIFYMODE, 'userprofile_notify_mode', $thisUser_notify_mode);
      	$notify_mode_select->addOptionArray(array(XOOPS_NOTIFICATION_MODE_SENDALWAYS=>_NOT_MODE_SENDALWAYS, XOOPS_NOTIFICATION_MODE_SENDONCETHENDELETE=>_NOT_MODE_SENDONCE, XOOPS_NOTIFICATION_MODE_SENDONCETHENWAIT=>_NOT_MODE_SENDONCEPERLOGIN));
      	$form->addElement($notify_mode_select);
      	$sig_tray = new XoopsFormElementTray(_US_SIGNATURE, '<br />');
      	include_once XOOPS_ROOT_PATH . '/include/xoopscodes.php';
      	$sig_tarea = new XoopsFormDhtmlTextArea('', 'userprofile_user_sig', $thisUser_user_sig);
      	$sig_tray->addElement($sig_tarea);
      	$sig_cbox_value = $thisUser_attachsig ? 1 : 0;
      	$sig_cbox = new XoopsFormCheckBox('', 'userprofile_attachsig', $sig_cbox_value);
      	$sig_cbox->addOption(1, _US_SHOWSIG);
      	$sig_tray->addElement($sig_cbox);
      	$form->addElement($sig_tray);
	} else { // display only on new account creation...
		if ($xoopsConfigUser['reg_dispdsclmr'] != 0 && $xoopsConfigUser['reg_disclaimer'] != '') {
			$disc_tray = new XoopsFormElementTray(_US_DISCLAIMER, '<br />');
			$disc_text = new XoopsFormTextarea('', 'disclaimer', trans($xoopsConfigUser['reg_disclaimer']), 8);
			$disc_text->setExtra('readonly="readonly"');
			$disc_tray->addElement($disc_text);
			$agree_chk = new XoopsFormCheckBox('', 'userprofile_agree_disc', $agree_disc);
			$agree_chk->addOption(1, "<span style=\"font-size: 14pt;\">" . _US_IAGREE . "</span>");
			$disc_tray->addElement($agree_chk);
			$form->addElement($disc_tray);
		}
		$form->addElement(new XoopsFormHidden("op", "newuser"));
	}

	$uid_check = new XoopsFormHidden("userprofile_uid", $thisUser_uid);
	$form->addElement($uid_check);
	$form->insertBreak(_formulize_PERSONALDETAILS, "head");

	return $form;

} 


// add the submit button to a form
function addSubmitButton($form, $subButtonText, $go_back="", $currentURL, $button_text, $settings, $entry, $fids, $formframe, $mainform, $cur_entry, $profileForm, $elements_allowed="", $allDoneOverride=false, $printall=0, $screen=null) { //nmc 2007.03.24 - added $printall

	if($printall == 2) { // 2 is special setting in multipage screens that means do not include any printable buttons of any kind
		return $form;
	}

	if(strstr($currentURL, "printview.php")) { // don't do anything if we're on the print view
		return $form;
	} else {

	drawGoBackForm($go_back, $currentURL, $settings, $entry);

	if(!$button_text OR ($button_text == "{NOBUTTON}" AND $go_back['form'] > 0)) { // presence of a goback form (ie: parent form) overrides {NOBUTTON} -- assumption is the save button will not also be overridden at the same time
		$button_text = _formulize_DONE; 
	} elseif(is_array($button_text)) {
		if(!$button_text[0]) { 
			$done_text_temp = _formulize_DONE; 
		} else {
			$done_text_temp = $button_text[0];
		}
		if(!$button_text[1]) { 
			$save_text_temp = _formulize_SAVE; 
		} else {
			$save_text_temp = $button_text[1];
		}
	}

	// override -- the "no-all-done-button" config option turns off the all done button and changes save into a save-and-leave button

	// need to grab the $nosubforms variable created by the multiple page function, so we know to put the printable view button (and nothing else) on the screen for multipage forms
	global $nosubforms;
	if(!$profileForm AND ($save_text_temp != "{NOBUTTON}" OR $nosubforms)) { // do not use printable button for profile forms, or forms where there is no Save button (ie: a non-standard saving process is in use and access to the normal printable option may be prohibited)
		$printbutton = new XoopsFormButton('', 'printbutton', _formulize_PRINTVIEW, 'button');
		if(is_array($elements_allowed)) {
			$ele_allowed = implode(",",$elements_allowed);
		}
		$printbutton->setExtra("onclick='javascript:PrintPop(\"$ele_allowed\");'");
		$rendered_buttons = $printbutton->render(); // nmc 2007.03.24 - added
		if ($printall) {																					// nmc 2007.03.24 - added
			$printallbutton = new XoopsFormButton('', 'printallbutton', _formulize_PRINTALLVIEW, 'button');	// nmc 2007.03.24 - added
			$printallbutton->setExtra("onclick='javascript:PrintAllPop();'");								// nmc 2007.03.24 - added
			$rendered_buttons .= "&nbsp;&nbsp;&nbsp;" . $printallbutton->render();							// nmc 2007.03.24 - added
			}
		$buttontray = new XoopsFormElementTray($rendered_buttons, "&nbsp;"); // nmc 2007.03.24 - amended [nb: FormElementTray 'caption' is actually either 1 or 2 buttons]
	} else {
		$buttontray = new XoopsFormElementTray("", "&nbsp;");
	}
	$buttontray->setClass("no-print");
	if($subButtonText == _formulize_SAVE) { // _formulize_SAVE is passed only when the save button is allowed to be drawn
		if($save_text_temp) { $subButtonText = $save_text_temp; }
		if($subButtonText != "{NOBUTTON}") {
			$saveButton = new XoopsFormButton('', 'submitx', trans($subButtonText), 'button'); // doesn't use name submit since that conflicts with the submit javascript function
			$saveButton->setExtra("onclick=javascript:validateAndSubmit();");
			$buttontray->addElement($saveButton);
		}
	}
	
	if((($button_text != "{NOBUTTON}" AND !$done_text_temp) OR (isset($done_text_temp) AND $done_text_temp != "{NOBUTTON}")) AND !$allDoneOverride) { 
		if($done_text_temp) { $button_text = $done_text_temp; }
		$donebutton = new XoopsFormButton('', 'donebutton', trans($button_text), 'button');
		$donebutton->setExtra("onclick=javascript:verifyDone();");
		$buttontray->addElement($donebutton); 
	}

	if(!$profileForm) { // do not use printable button for profile forms
		$newcurrentURL= XOOPS_URL . "/modules/formulize/printview.php";
		print "<form name='printview' action='".$newcurrentURL."' method=post target=_blank>\n";
		
		// add security token
		if(isset($GLOBALS['xoopsSecurity'])) {
			print $GLOBALS['xoopsSecurity']->getTokenHTML();
		}
		
		$currentPage = "";
		$screenid = "";
    if($screen) {
		  $screenid = $screen->getVar('sid');
			// check for a current page setting
			if(isset($settings['formulize_currentPage'])) {
				$currentPage = $settings['formulize_currentPage'];
			}
		}
    
		print "<input type=hidden name=screenid value='".$screenid."'>";
		print "<input type=hidden name=currentpage value='".$currentPage."'>";

		print "<input type=hidden name=lastentry value=".$cur_entry.">";
		if($go_back['form']) { // we're on a sub, so display this form only
			print "<input type=hidden name=formframe value=".$fids[0].">";	
		} else { // otherwise, display like normal
			print "<input type=hidden name=formframe value='".$formframe."'>";	
			print "<input type=hidden name=mainform value='".$mainform."'>";
		}
		if(is_array($elements_allowed)) {
			$ele_allowed = implode(",",$elements_allowed);
			print "<input type=hidden name=elements_allowed value='".$ele_allowed."'>";
		} else {
			print "<input type=hidden name=elements_allowed value=''>";
		}
		print "</form>";
		//added by Cory Aug 27, 2005 to make forms printable
	}

	$trayElements = $buttontray->getElements();
	if(count($trayElements) > 0 OR $nosubforms) {
		$form->addElement($buttontray);
	}
	return $form;
	}
}

// this function draws in the hidden form that handles the All Done logic that sends user off the form
function drawGoBackForm($go_back, $currentURL, $settings, $entry) {
	if($go_back['url'] == "" AND !isset($go_back['form'])) { // there are no back instructions at all, then make the done button go to the front page of whatever is going on in pageworks
		print "<form name=go_parent action=\"$currentURL\" method=post>"; //onsubmit=\"javascript:verifyDone();\" method=post>";
		if(is_array($settings)) { writeHiddenSettings($settings); }
		print "<input type=hidden name=lastentry value=$entry>";
		print "</form>";
	}
	if($go_back['form']) { // parent form overrides specified back URL
		print "<form name=go_parent action=\"$currentURL\" method=post>"; //onsubmit=\"javascript:verifyDone();\" method=post>";
		print "<input type=hidden name=parent_form value=" . $go_back['form'] . ">";
		print "<input type=hidden name=parent_entry value=" . $go_back['entry'] . ">";
		print "<input type=hidden name=ventry value=" . $settings['ventry'] . ">";
		if(is_array($settings)) { writeHiddenSettings($settings); }
		print "<input type=hidden name=lastentry value=$entry>";
		print "</form>";
	} elseif($go_back['url']) {
		print "<form name=go_parent action=\"" . $go_back['url'] . "\" method=post>"; //onsubmit=\"javascript:verifyDone();\" method=post>";
		if(is_array($settings)) { writeHiddenSettings($settings); }		
		print "<input type=hidden name=lastentry value=$entry>";
		print "</form>";
	} 
}

// this function draws in the UI for sub links
function drawSubLinks($subform_id, $sub_entries, $uid, $groups, $frid, $mid, $fid, $entry,
	$customCaption = "", $customElements = "", $defaultblanks = 0, $showViewButtons = 1, $captionsForHeadings = 0,
	$overrideOwnerOfNewEntries = "", $mainFormOwner = 0, $hideaddentries, $subformConditions, $subformElementId = 0,
	$rowsOrForms = 'row', $addEntriesText = _formulize_ADD_ENTRIES, $subform_element_object = null)
{
	$nestedSubform = false;
	if(isset($GLOBALS['formulize_inlineSubformFrid'])) {
		$frid = $GLOBALS['formulize_inlineSubformFrid'];
		$nestedSubform = true;
	}

    $member_handler = xoops_gethandler('member');
    $gperm_handler = xoops_gethandler('groupperm');

    $addEntriesText = $addEntriesText ? $addEntriesText : _formulize_ADD_ENTRIES;

	global $xoopsDB, $nosubforms;
	$GLOBALS['framework'] = $frid;
	$form_handler = xoops_getmodulehandler('forms', 'formulize');

	// limit the sub_entries array to just the entries that match the conditions, if any
	if(is_array($subformConditions) and is_array($sub_entries[$subform_id])) {
		list($conditionsFilter, $conditionsFilterOOM, $curlyBracketFormFrom) = buildConditionsFilterSQL($subformConditions, $subform_id, $entry, $mainFormOwner, $fid); // pass in mainFormOwner as the comparison ID for evaluating {USER} so that the included entries are consistent when an admin looks at a set of entries made by someone else.
		$subformObject = $form_handler->get($subform_id);
		$sql = "SELECT entry_id FROM ".$xoopsDB->prefix("formulize_".$subformObject->getVar('form_handle'))."$curlyBracketFormFrom WHERE entry_id IN (".implode(", ", $sub_entries[$subform_id]).") $conditionsFilter $conditionsFilterOOM";
		$sub_entries[$subform_id] = array();
		if($res = $xoopsDB->query($sql)) {
			while($array = $xoopsDB->fetchArray($res)) {
				$sub_entries[$subform_id][] = $array['entry_id'];
			}
		}
	}
	
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
	
	$target_sub_to_use = (isset($_POST['target_sub']) AND $_POST['target_sub'] != 0) ? $_POST['target_sub'] : $subform_id; 
	$elementq = q("SELECT fl_key1, fl_key2, fl_common_value, fl_form2_id FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_frame_id=" . intval($frid) . " AND fl_form2_id=" . intval($fid) . " AND fl_form1_id=" . intval($target_sub_to_use));
	// element_to_write is used below in writing results of "add x entries" clicks, plus it is used for defaultblanks on first drawing blank entries, so we need to get this outside of the saving routine
	if(count($elementq) > 0) {
		$element_to_write = $elementq[0]['fl_key1'];
		$value_source = $elementq[0]['fl_key2'];
		$value_source_form = $elementq[0]['fl_form2_id'];
	} else {
		$elementq = q("SELECT fl_key2, fl_key1, fl_common_value, fl_form1_id FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_frame_id=" . intval($frid) . " AND fl_form1_id=" . intval($fid) . " AND fl_form2_id=" . intval($target_sub_to_use));
		$element_to_write = $elementq[0]['fl_key2'];
		$value_source = $elementq[0]['fl_key1'];
		$value_source_form = $elementq[0]['fl_form1_id'];		
	}

    if (0 == strlen($element_to_write)) {
        error_log("Relationship $frid for subform $subform_id on form $fid is invalid.");
        $to_return = array("c1"=>"", "c2"=>"", "sigle"=>"");
        global $xoopsUser;
        if (is_object($xoopsUser) and in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
            if (0 == $frid) {
                $to_return['single'] = "This subform cannot be shown because no relationship is active.";
            } else {
                $to_return['single'] = "This subform cannot be shown because relationship $frid for subform ".
                    "$subform_id on form $fid is invalid.";
            }
        }
        return $to_return;
    }

	// check for adding of a sub entry, and handle accordingly -- added September 4 2006
	static $subformInstance;
	$subformInstance = !isset($subformInstance) ? 100 : $subformInstance;
	$subformInstance++;
	
	if($_POST['target_sub'] AND $_POST['target_sub'] == $subform_id AND $_POST['target_sub_instance'] == $subformElementId.$subformInstance) { // important we only do this on the run through for that particular sub form (hence target_sub == sfid), and also only for the specific instance of this subform on the page too, since not all entries may apply to all subform instances any longer with conditions in effect now
		// need to handle things differently depending on whether it's a common value or a linked selectbox type of link
		// uid links need to result in a "new" value in the displayElement boxes -- odd things will happen if people start adding linked values to entries that aren't theirs!
		if($element_to_write != 0) {
			if($elementq[0]['fl_common_value']) {
				// grab the value from the parent element -- assume that it is a textbox of some kind!
				if (isset($_POST['de_'.$value_source_form.'_'.$entry.'_'.$value_source])) {
					$value_to_write = $_POST['de_'.$value_source_form.'_'.$entry.'_'.$value_source];
				} else {
					// get this entry and see what the source value is
					$data_handler = new formulizeDataHandler($value_source_form);
					$value_to_write = $data_handler->getElementValueInEntry($entry, $value_source);
				}
			} else {
				$value_to_write = $entry; 
			}
			$sub_entry_new = "";
		
			for($i=0;$i<$_POST['numsubents'];$i++) { // actually goahead and create the requested number of new sub entries...start with the key field, and then do all textboxes with defaults too...
				//$subEntWritten = writeElementValue($_POST['target_sub'], $element_to_write, "new", $value_to_write, "", "", true); // Last param is override that allows direct writing to linked selectboxes if we have prepped the value first!
        if($overrideOwnerOfNewEntries) {
          $creation_user_touse = $mainFormOwner;
        } else {
          $creation_user_touse = "";
        }
        $subEntWritten = writeElementValue($_POST['target_sub'], $element_to_write, "new", $value_to_write, $creation_user_touse, "", true); // Last param is override that allows direct writing to linked selectboxes if we have prepped the value first!
	$element_handler = xoops_getmodulehandler('elements', 'formulize');
				if(!isset($elementsForDefaults)) {
					$criteria = new CriteriaCompo();
					$criteria->add(new Criteria('ele_type', 'text'), 'OR');
					$criteria->add(new Criteria('ele_type', 'textarea'), 'OR');
					$criteria->add(new Criteria('ele_type', 'radio'), 'OR');
					$elementsForDefaults = $element_handler->getObjects($criteria,$_POST['target_sub']); // get all the text or textarea elements in the form 
				}
				foreach($elementsForDefaults as $thisDefaultEle) {
					// need to write in any default values for any text boxes or text areas that are in the subform.  Perhaps other elements could be included too, but that would take too much work right now. (March 9 2009)
					$defaultTextToWrite = "";
					$ele_value_for_default = $thisDefaultEle->getVar('ele_value');
					switch($thisDefaultEle->getVar('ele_type')) {
						case "text":
							$defaultTextToWrite = getTextboxDefault($ele_value_for_default[2], $_POST['target_sub'], $subEntWritten); // position 2 is default value for text boxes
							break;
						case "textarea":
							$defaultTextToWrite = getTextboxDefault($ele_value_for_default[0], $_POST['target_sub'], $subEntWritten); // position 0 is default value for text boxes
							break;
						case "radio":
						    $thisDefaultEleValue = $thisDefaultEle->getVar('ele_value');
							$defaultTextToWrite = array_search(1, $thisDefaultEleValue);
					}
					if($defaultTextToWrite) {
						writeElementValue($_POST['target_sub'], $thisDefaultEle->getVar('ele_id'), $subEntWritten, $defaultTextToWrite);
					}
				}
				$sub_entry_written[] = $subEntWritten;
			}
		} else {
			$sub_entry_new = "new"; // this happens in uid-link situations?
			$sub_entry_written = "";
		}
	
		// need to also enforce any equals conditions that are on the subform element, if any, and assign those values to the entries that were just added
		if(is_array($subformConditions)) {
			$filterValues = array();
			foreach($subformConditions[1] as $i=>$thisOp) {
				if($thisOp == "=" AND $subformConditions[3][$i] != "oom" AND $subformConditions[2][$i] != "{BLANK}") {
					$conditionElementObject = $element_handler->get($subformConditions[0][$i]);
					$filterValues[$subformConditions[0][$i]] = prepareLiteralTextForDB($conditionElementObject, $subformConditions[2][$i]); 
				}
			}
			if(count($filterValues)>0) {
				foreach($sub_entry_written as $thisSubEntry) {
					formulize_writeEntry($filterValues,$thisSubEntry);	
				}
			}
		}
	
	}
	
	
	

	

	// need to do a number of checks here, including looking for single status on subform, and not drawing in add another if there is an entry for a single

	$sub_single_result = getSingle($subform_id, $uid, $groups, $member_handler, $gperm_handler, $mid);
	$sub_single = $sub_single_result['flag'];
	if($sub_single) {
		unset($sub_entries);
		$sub_entries[$subform_id][0] = $sub_single_result['entry'];
	}

    if(!is_array($sub_entries[$subform_id])) {
        $sub_entries[$subform_id] = array();
    }

	if($sub_entry_new AND !$sub_single AND $_POST['target_sub'] == $subform_id) {
		for($i=0;$i<$_POST['numsubents'];$i++) {
			array_unshift($sub_entries[$subform_id], $sub_entry_new);
		}
	}

	if(is_array($sub_entry_written) AND !$sub_single AND $_POST['target_sub'] == $subform_id) {
		foreach($sub_entry_written as $sew) {
			array_unshift($sub_entries[$subform_id], $sew);
		}
	}

	if(!$customCaption) {
		// get the title of this subform
		// help text removed for F4.0 RC2, this is an experiment
		$subtitle = q("SELECT desc_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form = $subform_id");
		$col_one = "<p id=\"subform-caption-f$fid-sf$subform_id\" class=\"subform-caption\"><b>" . trans($subtitle[0]['desc_form']) . "</b></p>"; // <p style=\"font-weight: normal;\">" . _formulize_ADD_HELP;
	} else {
		$col_one = "<p id=\"subform-caption-f$fid-sf$subform_id\" class=\"subform-caption\"><b>" . trans($customCaption) . "</b></p>"; // <p style=\"font-weight: normal;\">" . _formulize_ADD_HELP;
	}

	/*if(intval($sub_entries[$subform_id][0]) != 0 OR $sub_entry_new OR is_array($sub_entry_written)) {
		if(!$nosubforms) { $col_one .= "<br>" . _formulize_ADD_HELP2; }
		$col_one .= "<br>" . _formulize_ADD_HELP3;
	} */

	// list the entries, including links to them and delete checkboxes
	
	// get the headerlist for the subform and convert it into handles
	// note big assumption/restriction that we are only using the first header found (ie: only specify one header for a sub form!)
	// setup the array of elements to draw
	if(is_array($customElements)) {
		$headingDescriptions = array();
		$headerq = q("SELECT ele_caption, ele_colhead, ele_desc, ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_id IN (" . implode(", ", $customElements). ") ORDER BY ele_order");
		foreach($headerq as $thisHeaderResult) {
			$elementsToDraw[] = $thisHeaderResult['ele_id'];
			$headingDescriptions[]  = $thisHeaderResult['ele_desc'] ? $thisHeaderResult['ele_desc'] : "";
			if($captionsForHeadings) {
				$headersToDraw[] = $thisHeaderResult['ele_caption'];
			} else {
				$headersToDraw[] = $thisHeaderResult['ele_colhead'] ? $thisHeaderResult['ele_colhead'] : $thisHeaderResult['ele_caption'];
			}
		}
	} else {
		$subHeaderList = getHeaderList($subform_id);
		$subHeaderList1 = getHeaderList($subform_id, true);
		if (isset($subHeaderList[0])) {
			$headersToDraw[] = trans($subHeaderList[0]);
		}
		if (isset($subHeaderList[1])) {
			$headersToDraw[] = trans($subHeaderList[1]);
		}
		if (isset($subHeaderList[2])) {
			$headersToDraw[] = trans($subHeaderList[2]);
		}
		$elementsToDraw = array_slice($subHeaderList1, 0, 3);
	}

	$need_delete = 0;
	$drawnHeadersOnce = false;

	if($rowsOrForms=="row" OR $rowsOrForms =='') {
		$col_two = "<table id=\"formulize-subform-table-$subform_id\" class=\"formulize-subform-table\">";
	} else {
		$col_two = "";
		if(!strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
			$col_two .= "<div id=\"subform-$subformElementId\" class=\"subform-accordion-container\" subelementid=\"$subformElementId\" style=\"display: none;\">";
		}
		$col_two .= "<input type='hidden' name='subform_entry_".$subformElementId."_active' id='subform_entry_".$subformElementId."_active' value='' />";
		include_once XOOPS_ROOT_PATH ."/modules/formulize/class/data.php";
		$data_handler = new formulizeDataHandler($subform_id);
	}

	$deFrid = $frid ? $frid : ""; // need to set this up so we can pass it as part of the displayElement function, necessary to establish the framework in case this is a framework and no subform element is being used, just the default draw-in-the-one-to-many behaviour
	
	// if there's been no form submission, and there's no sub_entries, and there are default blanks to show, then do everything differently -- sept 8 2007
	
	if(!$_POST['form_submitted'] AND count($sub_entries[$subform_id]) == 0 AND $defaultblanks > 0 AND ($rowsOrForms == "row"  OR $rowsOrForms =='')) {
	
		for($i=0;$i<$defaultblanks;$i++) {
	
				// nearly same header drawing code as in the 'else' for drawing regular entries
				if(!$drawnHeadersOnce) {
					$col_two .= "<tr><td>\n";
					$col_two .= "<input type=\"hidden\" name=\"formulize_subformValueSource_$subform_id\" value=\"$value_source\">\n";
					$col_two .= "<input type=\"hidden\" name=\"formulize_subformValueSourceForm_$subform_id\" value=\"$value_source_form\">\n";
					$col_two .= "<input type=\"hidden\" name=\"formulize_subformValueSourceEntry_$subform_id\" value=\"$entry\">\n";
					$col_two .= "<input type=\"hidden\" name=\"formulize_subformElementToWrite_$subform_id\" value=\"$element_to_write\">\n";
					$col_two .= "<input type=\"hidden\" name=\"formulize_subformSourceType_$subform_id\" value=\"".$elementq[0]['fl_common_value']."\">\n";
					$col_two .= "<input type=\"hidden\" name=\"formulize_subformId_$subform_id\" value=\"$subform_id\">\n"; // this is probably redundant now that we're tracking sfid in the names of the other elements
					$col_two .= "</td>\n";
					foreach($headersToDraw as $x=>$thishead) {
						if($thishead) {
							$headerHelpLinkPart1 = $headingDescriptions[$i] ? "<a href=\"#\" onclick=\"return false;\" alt=\"".$headingDescriptions[$x]."\" title=\"".$headingDescriptions[$x]."\">" : "";
							$headerHelpLinkPart2 = $headerHelpLinkPart1 ? "</a>" : "";
							$col_two .= "<th><p>$headerHelpLinkPart1<b>$thishead</b>$headerHelpLinkPart2</p></th>\n";
						}
					}
					$col_two .= "</tr>\n";
					$drawnHeadersOnce = true;
				}
				$col_two .= "<tr>\n<td>";
				$col_two .= "</td>\n";
				include_once XOOPS_ROOT_PATH . "/modules/formulize/include/elementdisplay.php";
				foreach($elementsToDraw as $thisele) {
					if($thisele) { 
						ob_start();
						// critical that we *don't* ask for displayElement to return the element object, since this way the validation logic is passed back through the global space also (ugh).  Otherwise, no validation logic possible for subforms.
						$renderResult = displayElement($deFrid, $thisele, "subformCreateEntry_".$i."_".$subformElementId); 
						$col_two_temp = ob_get_contents();
						ob_end_clean();
						if($col_two_temp OR $renderResult == "rendered") { // only draw in a cell if there actually is an element rendered (some elements might be rendered as nothing (such as derived values)
							$col_two .= "<td>$col_two_temp</td>\n";
						} else {
							$col_two .= "<td>******</td>";
						}
					}
				}
				$col_two .= "</tr>\n";
				
		}
	
	} elseif(count($sub_entries[$subform_id]) > 0) {
		
		// need to figure out the proper order for the sub entries based on the properties set for this form
		// for now, hard code to the word number field to suit the map site only
		// if it's the word subform, then sort the entries differently
		/*if($subform_id == 281) {
			$sortClause = " fas_281, block_281, word_number ";
		} 
		elseif ($subform_id == 283) {
			$sortClause = " fas_283 ";
		}
		else {*/
			$sortClause = " entry_id ";
		//}
		
		$sformObject = $form_handler->get($subform_id);
		$subEntriesOrderSQL = "SELECT entry_id FROM ".$xoopsDB->prefix("formulize_".$sformObject->getVar('form_handle'))." WHERE entry_id IN (".implode(",", $sub_entries[$subform_id]).") ORDER BY $sortClause";
		if($subEntriesOrderRes = $xoopsDB->query($subEntriesOrderSQL)) {
			$sub_entries[$subform_id] = array();
			while($subEntriesOrderArray = $xoopsDB->fetchArray($subEntriesOrderRes)) {
				$sub_entries[$subform_id][] = $subEntriesOrderArray['entry_id'];
			}
		}

		$currentSubformInstance = $subformInstance;

		foreach($sub_entries[$subform_id] as $sub_ent) {
			if($sub_ent != "") {
				
				if($rowsOrForms=='row' OR $rowsOrForms =='') {
					
					if(!$drawnHeadersOnce) {
						$col_two .= "<tr><th></th>\n";
						foreach($headersToDraw as $i=>$thishead) {
							if($thishead) {
								$headerHelpLinkPart1 = $headingDescriptions[$i] ? "<a href=\"#\" onclick=\"return false;\" alt=\"".$headingDescriptions[$i]."\" title=\"".$headingDescriptions[$i]."\">" : "";
								$headerHelpLinkPart2 = $headerHelpLinkPart1 ? "</a>" : "";
								$col_two .= "<th><p>$headerHelpLinkPart1<b>$thishead</b>$headerHelpLinkPart2</p></th>\n";
							}
						}
						$col_two .= "</tr>\n";
						$drawnHeadersOnce = true;
					}
					$col_two .= "<tr>\n<td>";
					// check to see if we draw a delete box or not
					if ($sub_ent !== "new" and ("hideaddentries" != $hideaddentries)
						and formulizePermHandler::user_can_delete_entry($subform_id, $uid, $sub_ent) AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php"))
					{
						// note: if the add/delete entry buttons are hidden, then these delete checkboxes are hidden as well
						$need_delete = 1;
						$col_two .= "<input type=checkbox name=delbox$sub_ent value=$sub_ent></input>";
					}
					$col_two .= "</td>\n";
					include_once XOOPS_ROOT_PATH . "/modules/formulize/include/elementdisplay.php";
					foreach($elementsToDraw as $thisele) {
						if($thisele) { 
							ob_start();
							// critical that we *don't* ask for displayElement to return the element object, since this way the validation logic is passed back through the global space also (ugh).  Otherwise, no validation logic possible for subforms.
							$renderResult = displayElement($deFrid, $thisele, $sub_ent); 
							$col_two_temp = ob_get_contents();
							ob_end_clean();
							if($col_two_temp OR $renderResult == "rendered") { // only draw in a cell if there actually is an element rendered (some elements might be rendered as nothing (such as derived values)
								$col_two .= "<td>$col_two_temp</td>\n";
							} else {
								$col_two .= "<td>******</td>";
							}
						}
					}
					if(!$nosubforms AND $showViewButtons AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) { $col_two .= "<td><input type=button name=view".$sub_ent." value='"._formulize_SUBFORM_VIEW."' onclick=\"javascript:goSub('$sub_ent', '$subform_id');return false;\"></input></td>\n"; }
					$col_two .= "</tr>\n";
				} else { // display the full form
					$headerValues = array();
					foreach($elementsToDraw as $thisele) {
						$value = $data_handler->getElementValueInEntry($sub_ent, $thisele);
						$element_object = _getElementObject($thisele);
						$value = prepvalues($value, $element_object->getVar("ele_handle"), $sub_ent);
						if (is_array($value))
							$value = implode(" - ", $value); // may be an array if the element allows multiple selections (checkboxes, multiselect list boxes, etc)
						$headerValues[] = $value;
					}
					$headerToWrite = implode(" &mdash; ", $headerValues);
					if(str_replace(" &mdash; ", "", $headerToWrite) == "") {
						$headerToWrite = _AM_ELE_SUBFORM_NEWENTRY_LABEL;
					}
					
					// check to see if we draw a delete box or not
					$deleteBox = "";
					if ($sub_ent !== "new" and formulizePermHandler::user_can_delete_entry($subform_id, $uid, $sub_ent) AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
						$need_delete = 1;
						$deleteBox = "<input type=checkbox name=delbox$sub_ent value=$sub_ent></input>&nbsp;&nbsp;";
					}
					
					if(!strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
						$col_two .= "<div class=\"subform-deletebox\">$deleteBox</div><div class=\"subform-entry-container\" id=\"subform-".$subform_id."-"."$sub_ent\">
	<p class=\"subform-header\"><a href=\"#\"><span class=\"accordion-name\">".$headerToWrite."</span></a></p>
	<div class=\"accordion-content content\">";
					}
					ob_start();
					$GLOBALS['formulize_inlineSubformFrid'] = $frid;
                    if ($display_screen = get_display_screen_for_subform($subform_element_object)) {
                        $subScreen_handler = xoops_getmodulehandler('formScreen', 'formulize');
                        $subScreenObject = $subScreen_handler->get($display_screen);
                        $subScreen_handler->render($subScreenObject, $sub_ent, null, true);
                    } else {
                        // SHOULD CHANGE THIS TO USE THE DEFAULT SCREEN FOR THE FORM!!!!!!????
                        $renderResult = displayForm($subform_id, $sub_ent, "", "",  "", "", "formElementsOnly");
                    }
					if(!$nestedSubform) {
						unset($GLOBALS['formulize_inlineSubformFrid']);
					}
					$col_two_temp = ob_get_contents();
					ob_end_clean();
					$col_two .= $col_two_temp;
					if(!strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) { 
						$col_two .= "</div>\n</div>\n";
					}
				}
			}
		}

		$subformInstance = $currentSubformInstance; // instance counter might have changed because the form could include other subforms
	}

	if($rowsOrForms=='row' OR $rowsOrForms =='') {
		// complete the table if we're drawing rows
		$col_two .= "</table>";	
	} else {
		if(!strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
			$col_two .= "</div>"; // close of the subform-accordion-container
		}
		static $jqueryUILoaded = false;
		if(!$jqueryUILoaded) {
			$col_two .= "<script type=\"text/javascript\" src=\"".XOOPS_URL."/modules/formulize/libraries/jquery/jquery-ui-1.8.2.custom.min.js\"></script>\n";
			$col_two .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".XOOPS_URL."/modules/formulize/libraries/jquery/css/start/jquery-ui-1.8.2.custom.css\">\n";
			$jqueryUILoaded = true;
		}
		$col_two .= "\n
<script type=\"text/javascript\">
	jQuery(document).ready(function() {
		jQuery(\"#subform-$subformElementId\").accordion({
			autoHeight: false, // no fixed height for sections
			collapsible: true, // sections can be collapsed
			active: ";
			if($_POST['target_sub_instance'] == $subformElementId.$subformInstance AND $_POST['target_sub'] == $subform_id) {
				$col_two .= count($sub_entries[$subform_id])-$_POST['numsubents'];
			} elseif(is_numeric($_POST['subform_entry_'.$subformElementId.'_active'])) {
				$col_two .= $_POST['subform_entry_'.$subformElementId.'_active'];
			} else {
				$col_two .= 'false';
			}
			$col_two .= ",
			header: \"> div > p.subform-header\"
		});
		jQuery(\"#subform-$subformElementId\").fadeIn();
	});
</script>";
	} // end of if we're closing the subform inferface where entries are supposed to be collapsable forms

    $deleteButton = "";
	if(((count($sub_entries[$subform_id])>0 AND $sub_entries[$subform_id][0] != "") OR $sub_entry_new OR is_array($sub_entry_written)) AND $need_delete) {
        $deleteButton = "&nbsp;&nbsp;&nbsp;<input type=button name=deletesubs value='" . _formulize_DELETE_CHECKED . "' onclick=\"javascript:sub_del('$subform_id');\">";
		static $deletesubsflagIncluded = false;
		if(!$deletesubsflagIncluded) {
			$col_one .= "\n<input type=hidden name=deletesubsflag value=''>\n";
			$deletesubsflagIncluded = true;
		}
	}

    // if the 'add x entries button' should be hidden or visible
    if ("hideaddentries" != $hideaddentries) {
        $allowed_to_add_entries = false;
        if ("subform" == $hideaddentries OR 1 == $hideaddentries) {
            // for compatability, accept '1' which is the old value which corresponds to the new use-subform-permissions (saved as "subform")
            // user can add entries if they have permission on the sub form
            $allowed_to_add_entries = $gperm_handler->checkRight("add_own_entry", $subform_id, $groups, $mid);
        } else {
            // user can add entries if they have permission on the main form
            // the user should only be able to add subform entries if they can *edit* the main form entry, since adding a subform entry
            //  is like editing the main form entry. otherwise they could add subform entries on main form entries owned by other users
            $allowed_to_add_entries = formulizePermHandler::user_can_edit_entry($fid, $uid, $entry);
        }
        if ($allowed_to_add_entries AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
            if (count($sub_entries[$subform_id]) == 1 AND $sub_entries[$subform_id][0] === "" AND $sub_single) {
                $col_two .= "<p><input type=button name=addsub value='". _formulize_ADD_ONE . "' onclick=\"javascript:add_sub('$subform_id', 1, ".$subformElementId.$subformInstance.");\"></p>";
            } elseif(!$sub_single) {
                $use_simple_add_one_button = (isset($subform_element_object->ele_value["simple_add_one_button"]) ?
                    1 == $subform_element_object->ele_value["simple_add_one_button"] : false);
                $col_two .= "<p><input type=button name=addsub value='".($use_simple_add_one_button ? $subform_element_object->ele_value['simple_add_one_button_text'] : _formulize_ADD)."' onclick=\"javascript:add_sub('$subform_id', window.document.formulize.addsubentries$subform_id$subformElementId$subformInstance.value, ".$subformElementId.$subformInstance.");\">";
                if ($use_simple_add_one_button) {
                    $col_two .= "<input type=\"hidden\" name=addsubentries$subform_id$subformElementId$subformInstance id=addsubentries$subform_id$subformElementId$subformInstance value=\"1\">";
                } else {
                    $col_two .= "<input type=text name=addsubentries$subform_id$subformElementId$subformInstance id=addsubentries$subform_id$subformElementId$subformInstance value=1 size=2 maxlength=2>";
                    $col_two .= $addEntriesText;
                }
                $col_two .= $deleteButton."</p>";
            }
        }
    }

    $to_return['c1'] = $col_one;
    $to_return['c2'] = $col_two;
    $to_return['single'] = $col_one . $col_two;

    if (is_object($subform_element_object)) {
        global $xoopsUser;
        $show_element_edit_link = (is_object($xoopsUser) and in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups()));
        $edit_link = "";
        if ($show_element_edit_link) {
            $edit_link = "<a class=\"formulize-element-edit-link\" tabindex=\"-1\" href=\"" . XOOPS_URL .
                "/modules/formulize/admin/ui.php?page=element&aid=0&ele_id=" .
                $subform_element_object->getVar("ele_id") . "\" target=\"_blank\">edit element</a>";
        }
        $to_return['single'] = "<div class=\"formulize-subform-".$subform_element_object->getVar("ele_handle")."\">$edit_link $col_one $col_two</div>";
    }

    return $to_return;
}


// add the proxy list to a form
function addOwnershipList($form, $groups, $member_handler, $gperm_handler, $fid, $mid, $entry_id="") {

	global $xoopsDB;
			
			$add_groups = $gperm_handler->getGroupIds("add_own_entry", $fid, $mid);
			// May 5, 2006 -- limit to the user's own groups unless the user has global scope
			if(!$globalscope = $gperm_handler->checkRight("view_globalscope", $fid, $groups, $mid)) {
				$add_groups = array_intersect($add_groups, $groups);
			}
			$all_add_users = array();
			foreach($add_groups as $grp) {
				$add_users = $member_handler->getUsersByGroup($grp);
				$all_add_users = array_merge((array)$add_users, $all_add_users);
				unset($add_users);
			}
		
			$unique_users = array_unique($all_add_users);

			$punames = array();
			foreach($unique_users as $uid) {
				$uqueryforrealnames = "SELECT name, uname FROM " . $xoopsDB->prefix("users") . " WHERE uid=$uid";
				$uresqforrealnames = $xoopsDB->query($uqueryforrealnames);
				$urowqforrealnames = $xoopsDB->fetchRow($uresqforrealnames);
				$punames[] = $urowqforrealnames[0] ? $urowqforrealnames[0] : $urowqforrealnames[1]; // use the uname if there is no full name
			}

			// alphabetize the proxy list added 11/2/04
			array_multisort($punames, $unique_users);

			if($entry_id) {
				include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
				$data_handler = new formulizeDataHandler($fid);
				$entryMeta = $data_handler->getEntryMeta($entry_id);
				$entryOwner = $entryMeta[2];
				$entryOwnerName = $punames[array_search($entryOwner,$unique_users)]; // need to look in one array to find the key to lookup in the other array...a legacy from when corresponding arrays were a common data structure in Formulize...multidimensional arrays were not well understood in the beginning
				$proxylist = new XoopsFormSelect(_AM_SELECT_UPDATE_OWNER, 'updateowner_'.$fid.'_'.$entry_id, 0, 1);
				$proxylist->addOption('nochange', _AM_SELECT_UPDATE_NOCHANGE.$entryOwnerName);
			} else {
				$proxylist = new XoopsFormSelect(_AM_SELECT_PROXY, 'proxyuser', 0, 5, TRUE); // made multi May 3 05
				$proxylist->addOption('noproxy', _formulize_PICKAPROXY);
			}
			
			for($i=0;$i<count($unique_users);$i++)
			{
                if($unique_users[$i]) {
                    $proxylist->addOption($unique_users[$i], $punames[$i]);
                }
			}

			if(!$entry_id) {
				$proxylist->setValue('noproxy');
			} else {
				$proxylist->setValue('nochange');
			}
			$proxylist->setClass("no-print");
			$form->addElement($proxylist);
			return $form;
}


//this function takes a formid and compiles all the elements for that form
//elements_allowed is NOT based off the display values.  It is based off of the elements that are specifically designated for the current displayForm function (used to display parts of forms at once)
// $title is the title of a grid that is being displayed
function compileElements($fid, $form, $formulize_mgr, $prevEntry, $entry, $go_back, $parentLinks, $owner_groups, $groups, $overrideValue="", $elements_allowed="", $profileForm="", $frid="", $mid, $sub_entries, $sub_fids, $member_handler, $gperm_handler, $title, $screen=null, $printViewPages="", $printViewPageTitles="") {
	
	include_once XOOPS_ROOT_PATH.'/modules/formulize/include/elementdisplay.php';
	
	$entryForDEElements = is_numeric($entry) ? $entry : "new"; // if there is no entry, ie: a new entry, then $entry is "" so when writing the entry value into decue_ and other elements that go out to the HTML form, we need to use the keyword "new"
	
	global $xoopsDB, $xoopsUser;

    $elementsAvailableToUser = array();

	// set criteria for matching on display
	// set the basics that everything has to match
	$criteriaBase = new CriteriaCompo();
	$criteriaBase->add(new Criteria('ele_display', 1), 'OR');
	foreach($groups as $thisgroup) {
		$criteriaBase->add(new Criteria('ele_display', '%,'.$thisgroup.',%', 'LIKE'), 'OR');
	}
	if(is_array($elements_allowed) and count($elements_allowed) > 0) {
		// if we're limiting the elements, then add a criteria for that (multiple criteria are joined by AND unless you specify OR manually when adding them (as in the base above))
		$criteria = new CriteriaCompo();
		$criteria->add(new Criteria('ele_id', "(".implode(",",$elements_allowed).")", "IN"));
		$criteria->add($criteriaBase);
	} else {
		$criteria = $criteriaBase; // otherwise, just use the base
	}
	$criteria->setSort('ele_order');
	$criteria->setOrder('ASC');
	$elements =& $formulize_mgr->getObjects($criteria,$fid,true); // true makes the keys of the returned array be the element ids
	$count = 0;
	global $gridCounter;
	$gridCounter = array();
	$inGrid = 0;
	
	formulize_benchmark("Ready to loop elements.");

	// set the array to be used as the structure of the loop, either the passed in elements in order, or the elements as gathered from the DB
	// ignore passed in element order if there's a screen in effect, since we assume that official element order is authoritative when screens are involved
	// API should still allow arbitrary ordering, so $element_allowed can still be set manually as part of a displayForm call, and the order will be respected then
	if(!is_array($elements_allowed) OR $screen) {
		$element_order_array = $elements;
	} else {
		$element_order_array = $elements_allowed;
	}
	
	// if this is a printview page,  
	
	
	foreach($element_order_array as $thisElement) {
		if(is_numeric($thisElement)) { // if we're doing the order based on passed in element ids...
			if(isset($elements[$thisElement])) {
				$i = $elements[$thisElement]; // set the element object for this iteration of the loop
			} else {
				continue; // do not try to render elements that don't exist in the form!! (they might have been deleted from a multipage definition, or who knows what)
			}
			$this_ele_id = $thisElement; // set the element ID number
		} else { // else...we're just looping through the elements directly from the DB
			$i = $thisElement; // set the element object
			$this_ele_id = $i->getVar('ele_id'); // get the element ID number
		}
	
		// check if we're at the start of a page, when doing a printable view of all pages (only situation when printViewPageTitles and printViewPages will be present), and if we are, then put in a break for the page titles
		if($printViewPages) {
			if(!$currentPrintViewPage) {
				$currentPrintViewPage = 1;
			}
			while(!in_array($this_ele_id, $printViewPages[$currentPrintViewPage]) AND $currentPrintViewPage <= count($printViewPages)) {
				$currentPrintViewPage++;
			}
			if($this_ele_id == $printViewPages[$currentPrintViewPage][0]) {
				$form->insertBreak("<div id=\"formulize-printpreview-pagetitle\">" . $printViewPageTitles[$currentPrintViewPage] . "</div>", "head");
			}
		}
	
		// check if this element is included in a grid, and if so, skip it
		// $inGrid will be a number indicating how many times we have to skip things
		if($inGrid OR isset($gridCounter[$this_ele_id])) {
			if(!$inGrid) {
				$inGrid = $gridCounter[$this_ele_id];
			}
			$inGrid--;
			continue;
		}

		$uid = is_object($xoopsUser) ? $xoopsUser->getVar('uid') : 0;
		$owner = getEntryOwner($entry, $fid);
		$ele_type = $i->getVar('ele_type');
		$ele_value = $i->getVar('ele_value');

		
		if($go_back['form']) { // if there's a parent form...
			// check here to see if we need to initialize the value of a linked selectbox when it is the key field for a subform
			// although this is setup as a loop through all found parentLinks, only the last one will be used, since ele_value[2] is overwritten each time.
			// assumption is there will only be one parent link for this form
			for($z=0;$z<count($parentLinks['source']);$z++) {					
				if($this_ele_id == $parentLinks['self'][$z]) { // this is the element
					$ele_value[2] = $go_back['entry']; // 3.0 datastructure...needs to be tested!! -- now updated for 5.0
				}
			}
		} elseif($overrideValue){ // used to force a default setting in a form element, other than the normal default
			if(!is_array($overrideValue)) { //convert a string to an array so that strings don't screw up logic below (which is designed for arrays)
				$temp = $overrideValue;
				unset($overrideValue);
				$overrideValue[0] = $temp;
			}
			// currently only operative for select boxes
			switch($ele_type) {
				case "select":
					foreach($overrideValue as $ov) {
						if(array_key_exists($ov, $ele_value[2])) {
							$ele_value[2][$ov] = 1;
						}	
					}
					break;
				case "date":
                	// debug
                	//var_dump($overrideValue);
					foreach($overrideValue as $ov) {
						//if(ereg ("([0-9]{4})-([0-9]{2})-([0-9]{2})", $ov, $regs)) {
						if(ereg ("([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})", $ov, $regs)) {
							$ele_value[0] = $ov;
						}
					}
					break;
			}
		}

		if($ele_type != "subform" AND $ele_type != 'grid') { 
			// "" is framework, ie: not applicable
			// $i is element object
			// $entry is entry_id
			// false is "nosave" param...only used to force element to not be picked up by readelements.php after saving
			// $screen is the screen object
			// false means don't print it out to screen, return it here
			$GLOBALS['formulize_sub_fids'] = $sub_fids; // set here so we can pick it up in the render method of elements, if necessary (only necessary for subforms?);
			$deReturnValue = displayElement("", $i, $entry, false, $screen, $prevEntry, false, $profileForm, $groups);
			if(is_array($deReturnValue)) {
				$form_ele = $deReturnValue[0];
				$isDisabled = $deReturnValue[1];
			} else {
				$form_ele = $deReturnValue;
				$isDisabled = false;
			}
            $elementsAvailableToUser[$this_ele_id] = true;
			if(($form_ele == "not_allowed" OR $form_ele == "hidden")) {
				if(isset($GLOBALS['formulize_renderedElementHasConditions']["de_".$fid."_".$entryForDEElements."_".$this_ele_id])) {
					// need to add a tr container for elements that are not allowed, since if it was a condition that caused them to not show up, they might appear later on asynchronously, and we'll need the row to attach them to
					if($ele_type == "ib" AND $form_ele == "not_allowed") {
						$rowHTML = "<tr style='display: none' id='formulize-de_".$fid."_".$entryForDEElements."_".$this_ele_id."'></tr>";
					} elseif($form_ele == "not_allowed") { 
						$rowHTML = "<tr style='display: none' id='formulize-de_".$fid."_".$entryForDEElements."_".$this_ele_id."' valign='top' align='" . _GLOBAL_LEFT . "'></tr>";
					}
					// need to also get the validation code for this element, wrap it in a check for the table row being visible, and assign that to the global array that contains all the validation javascript that we need to add to the form
					// following code follows the pattern set in elementdisplay.php for actually creating rendered element objects
					if($ele_type != "ib") {
						$conditionalValidationRenderer = new formulizeElementRenderer($i);
						if($prevEntry OR $profileForm === "new") {
							$data_handler = new formulizeDataHandler($i->getVar('id_form'));
							$ele_value = loadValue($prevEntry, $i, $ele_value, $data_handler->getEntryOwnerGroups($entry), $groups, $entry, $profileForm); // get the value of this element for this entry as stored in the DB -- and unset any defaults if we are looking at an existing entry
						}
						$conditionalElementForValidiationCode = $conditionalValidationRenderer->constructElement("de_".$fid."_".$entryForDEElements."_".$this_ele_id, $ele_value, $entry, $isDisabled, $screen);
						if($js = $conditionalElementForValidiationCode->renderValidationJS()) {
							$GLOBALS['formulize_renderedElementsValidationJS'][$GLOBALS['formulize_thisRendering']][$conditionalElementForValidiationCode->getName()] = "if(window.document.getElementById('formulize-".$conditionalElementForValidiationCode->getName()."').style.display != 'none') {\n".$js."\n}\n";
						}
						unset($conditionalElementForValidiationCode);
						unset($conditionalValidationRenderer);
					}
					$form->addElement($rowHTML);
                    // since it was treated as a conditional element, and the user might interact with it, then we don't consider it a not-available-to-user element
                    unset($elementsAvailableToUser[$this_ele_id]);
				}
				continue;
			}
		}
		
		$req = !$isDisabled ? intval($i->getVar('ele_req')) : 0; 
		$GLOBALS['sub_entries'] = $sub_entries;
		if($ele_type == "subform" ) {
			$thissfid = $ele_value[0];
			if(!$thissfid) { continue; } // can't display non-specified subforms!
			$deReturnValue = displayElement("", $i, $entry, false, $screen, $prevEntry, false, $profileForm, $groups); // do this just to evaluate any conditions...it won't actually render anything, but will return "" for the first key in the array, if the element is allowed
			if(is_array($deReturnValue)) {
				$form_ele = $deReturnValue[0];
				$isDisabled = $deReturnValue[1];
			} else {
				$form_ele = $deReturnValue;
				$isDisabled = false;
			}
			if($passed = security_check($thissfid) AND $form_ele == "") {
				$GLOBALS['sfidsDrawn'][] = $thissfid;
				$customCaption = $i->getVar('ele_caption');
				$customElements = $ele_value[1] ? explode(",", $ele_value[1]) : "";
				if(isset($GLOBALS['formulize_inlineSubformFrid'])) {
					$newLinkResults = checkForLinks($GLOBALS['formulize_inlineSubformFrid'][0], array($fid), $fid, array($fid=>array($entry)), null, $owner_groups, $mid, null, $owner);
					$sub_entries = $newLinkResults['sub_entries'];
				}
                // 2 is the number of default blanks, 3 is whether to show the view button or not, 4 is whether to use captions as headings or not, 5 is override owner of entry, $owner is mainform entry owner, 6 is hide the add button, 7 is the conditions settings for the subform element, 8 is the setting for showing just a row or the full form, 9 is text for the add entries button
                $subUICols = drawSubLinks($thissfid, $sub_entries, $uid, $groups, $frid, $mid, $fid, $entry, $customCaption, $customElements, intval($ele_value[2]), $ele_value[3], $ele_value[4], $ele_value[5], $owner, $ele_value[6], $ele_value[7], $this_ele_id, $ele_value[8], $ele_value[9], $thisElement);
				if(isset($subUICols['single'])) {
					$form->insertBreak($subUICols['single'], "even");
				} else {
					$subLinkUI = new XoopsFormLabel($subUICols['c1'], $subUICols['c2']);
					$form->addElement($subLinkUI);
				}
				unset($subLinkUI);
			}
		} elseif($ele_type == "grid") {

			// we are going to have to store some kind of flag/counter with the id number of the starting element in the table, and the number of times we need to ignore things
			// we need to then listen for this up above and skip those elements as they come up.  This is why grids must come before their elements in the form definition

			include_once XOOPS_ROOT_PATH . "/modules/formulize/include/griddisplay.php";
			list($grid_title, $grid_row_caps, $grid_col_caps, $grid_background, $grid_start, $grid_count) = compileGrid($ele_value, $title, $i);
			$headingAtSide = ($ele_value[5] AND $grid_title) ? true : false; // if there is a value for ele_value[5], then the heading should be at the side, otherwise, grid spans form width as it's own chunk of HTML
			$gridCounter[$grid_start] = $grid_count;
			$gridContents = displayGrid($fid, $entry, $grid_row_caps, $grid_col_caps, $grid_title, $grid_background, $grid_start, "", "", true, $screen, $headingAtSide);
			if($headingAtSide) { // grid contents is the two bits for the xoopsformlabel when heading is at side, otherwise, it's just the contents for the break
				$form->addElement(new XoopsFormLabel($gridContents[0], $gridContents[1]));
			} else {
				$form->insertBreak($gridContents, "head"); // head is the css class of the cell
			}
		} elseif($ele_type == "ib" OR is_array($form_ele)) {
			// if it's a break, handle it differently...$form_ele may be an array if it's a non-interactive element such as a grid
			if (is_object($thisElement) /*this happens when printing*/) {
				// final param is used as id name in the table row where this element exists, so we can interact with it for showing and hiding
				$form->insertBreakFormulize("<div class=\"formulize-subform-heading\">" . trans(stripslashes($form_ele[0])) . "</div>",
					$form_ele[1], 'de_'.$fid.'_'.$entryForDEElements.'_'.$this_ele_id, $thisElement->getVar("ele_handle"));
			}
		} else {
			$form->addElement($form_ele, $req);
		}
		$count++;
		unset($hidden);
		unset($form_ele); // apparently necessary for compatibility with PHP 4.4.0 -- suggested by retspoox, sept 25, 2005
	}

	formulize_benchmark("Done looping elements.");

    // find any hidden elements in the form, that aren't available to the user in this rendering of the form...	
	unset($criteria);
	$notAllowedCriteria = new CriteriaCompo();
	$notAllowedCriteria->add(new Criteria('ele_forcehidden', 1));
    foreach($elementsAvailableToUser as $availElementId=>$boolean) {
        $notAllowedCriteria->add(new Criteria('ele_id', $availElementId, '!='));
    }
	$notAllowedCriteria->setSort('ele_order');
	$notAllowedCriteria->setOrder('ASC');
	$notAllowedElements =& $formulize_mgr->getObjects($notAllowedCriteria,$fid);

	$hiddenElements = generateHiddenElements($notAllowedElements, $entryForDEElements); // in functions.php, keys in returned array will be the element ids
  
	foreach($hiddenElements as $element_id=>$thisHiddenElement) {
		$form->addElement(new xoopsFormHidden("decue_".$fid."_".$entryForDEElements."_".$element_id, 1));
		if(is_array($thisHiddenElement)) { // could happen for checkboxes
			foreach($thisHiddenElement as $thisIndividualHiddenElement) {
				$form->addElement($thisIndividualHiddenElement);
			}
		} else {
			$form->addElement($thisHiddenElement);
		}
		unset($thisHiddenElement); // some odd reference thing going on here...$thisHiddenElement is being added by reference or something like that, so that when $thisHiddenElement changes in the next run through, every previous element that was created by adding it is updated to point to the next element.  So if you unset at the end of the loop, it forces each element to be added as you would expect.
	}

    
	if($entry AND !is_a($form, 'formulize_elementsOnlyForm')) {
        // two hidden fields encode the main entry id, the first difficult-to-use format is a legacy thing
        // the 'lastentry' format is more sensible, but is only available when there was a real entry, not 'new' (also a legacy convention)
		$form->addElement (new XoopsFormHidden ('entry'.$fid, $entry));
        if(is_numeric($entry)) {
            $form->addElement (new XoopsFormHidden ('lastentry', $entry));
        }
	}
	if($_POST['parent_form']) { // if we just came back from a parent form, then if they click save, we DO NOT want an override condition, even though we are now technically editing an entry that was previously saved when we went to the subform in the first place.  So the override logic looks for this hidden value as an exception.
		$form->addElement (new XoopsFormHidden ('back_from_sub', 1));
	}
	
    
	// add a hidden element to carry all the validation javascript that might be associated with elements rendered with elementdisplay.php...only relevant for elements rendered inside subforms or grids...the validation code comes straight from the element, doesn't have a check around it for the conditional table row id, like the custom form classes at the top of the file use, since those elements won't render as hidden and show/hide in the same way
	if(isset($GLOBALS['formulize_renderedElementsValidationJS'][$GLOBALS['formulize_thisRendering']])) {
		$formulizeHiddenValidation = new XoopsFormHidden('validation', '');
		foreach($GLOBALS['formulize_renderedElementsValidationJS'][$GLOBALS['formulize_thisRendering']] as $thisValidation) { // grab all the validation code we stored in the elementdisplay.php file and attach it to this element
			foreach(explode("\n", $thisValidation) as $thisValidationLine) {
				$formulizeHiddenValidation->customValidationCode[] = $thisValidationLine;
			}
		}
		$form->addElement($formulizeHiddenValidation, 1);
	}

	if(get_class($form) == "formulize_elementsOnlyForm") { // forms of this class are ones that we're rendering just the HTML for the elements, and we need to preserve any validation javascript to stick in the final, parent form when it's finished
		$validationJS = $form->renderValidationJS();
		if(trim($validationJS)!="") {
			$GLOBALS['formulize_elementsOnlyForm_validationCode'][] = $validationJS."\n\n";
		}
	} elseif(count($GLOBALS['formulize_elementsOnlyForm_validationCode']) > 0) {
		$elementsonlyvalidation = new XoopsFormHidden('elementsonlyforms', '');
		$elementsonlyvalidation->customValidationCode = $GLOBALS['formulize_elementsOnlyForm_validationCode'];
		$form->addElement($elementsonlyvalidation, 1);
	}
	
	return $form;

}

// $groups is deprecated and not used in this function any longer
// $owner_groups is used when dealing with a usernames or fullnames selectbox
// $element is the element object representing the element we're loading the previously saved value for
function loadValue($prevEntry, $element, $ele_value, $owner_groups, $groups, $entry, $profileForm="") {
//global $xoopsUser;
//if($xoopsUser->getVar('uid') == 1) {
//print_r($prevEntry);

//}

	global $myts;
	/*
	 * Hack by F�lix <INBOX Solutions> for sedonde
	 * myts == NULL
	 */
	if(!$myts){
		$myts =& MyTextSanitizer::getInstance();
	}
	/*
	 * Hack by F�lix <INBOX Solutions> for sedonde
	 * myts == NULL
	 */
			$type = $element->getVar('ele_type');
			// going direct from the DB since if multi-language is active, getVar will translate the caption
			//$caption = $element->getVar('ele_caption');
			$ele_id = $element->getVar('ele_id');

			// if we're handling a new profile form, check to see if the user has filled in the form already and use that value if necessary
			// This logic could be of general use in handling posted requests, except for it's inability to handle 'other' boxes.  An update may pay off in terms of speed of reloading the page.
			$value = "";
			if($profileForm === "new") {
				$dataFromUser = "";
				foreach($_POST as $k=>$v) {
					if( preg_match('/de_/', $k)){
						$n = explode("_", $k);
						if($n[3] == $ele_id) { // found the element in $_POST;
							$dataFromUser = prepDataForWrite($element, $v);
							break;
						}
					}
				}
				if($dataFromUser) {
					$value = $dataFromUser;
				}
			}

			// no value detected in form submission of this element...
			if(!$value) {
     				$handle = $element->getVar('ele_handle');
						$key = "";
	     			$keysFound = array_keys($prevEntry['handles'], $handle);
						foreach($keysFound as $thisKeyFound) {
							if("xyz".$prevEntry['handles'][$thisKeyFound] == "xyz".$handle) { // do a comparison with a prefixed string to avoid problems comparing numbers to numbers plus text, ie: "1669" and "1669_copy" since the loose typing in PHP will not interpret those as intended
								$key = $thisKeyFound;
								break;
							}
						}
     				// if the handle was not found in the existing values for this entry, then return the ele_value, unless we're looking at an existing entry, and then we need to clear defaults first
						// not sure this IF block will ever happen, this could be a holdover from the 2.0 data structure - jwe June 1 2014
     				if(!is_numeric($key) AND $key=="") { 
     					if($entry) {
     						switch($type) {
     							case "text":
     								$ele_value[2] = "";
     								break;
	     						case "textarea":
     								$ele_value[0] = "";
     								break;
     						}
     					} 
	     				return $ele_value; 
     				}
						
						if($key !== "") {
						  // grab previously saved value and treat it as the value for this element
							$value = $prevEntry['values'][$key];
						}
						
						if(($element->getVar('ele_use_default_when_blank') OR $element->getVar('ele_req')) AND !$value) {
								// do not load in saved value over top of ele_value when the saved value is empty/blank
								// and the element is required, or the element has the use-defaults-when-blank option on
								return $ele_value;
						}
						
			}

			/*print_r($ele_value);
			print "<br>After: "; //debug block
			*/
			
			// based on element type, swap in saved value from DB over top of default value for this element
			switch ($type)
			{
				case "derived":
					$ele_value[5] = $value;	// there is not a number 5 position in ele_value for derived values...we add the value to print in this position so we don't mess up any other information that might need to be carried around
					break;


                case "text":
                    $ele_value[2] = $value;
                    $ele_value[2] = eregi_replace("'", "&#039;", $ele_value[2]);
                    break;


                case "textarea":
                case "colorpick":
                    $ele_value[0] = $value;
                    break;


				case "select":
				case "radio":
				case "checkbox":
					// NOTE:  unique delimiter used to identify LINKED select boxes, so they can be handled differently.
					if(is_string($ele_value[2]) and strstr($ele_value[2], "#*=:*"))
                    {
                        // if we've got a linked select box, then do everything differently
						$ele_value[2] .= "#*=:*".$value; // append the selected entry ids to the form and handle info in the element definition
					}
					else
					{
						// put the array into another array (clearing all default values)
						// then we modify our place holder array and then reassign
	
						if ($type != "select")
						{
							$temparray = $ele_value;
						}
						else
						{
							$temparray = $ele_value[2];
						}

						if (is_array($temparray)) {
							$temparraykeys = array_keys($temparray);
						} else {
							$temparraykeys = array();
						}

						if($temparraykeys[0] === "{FULLNAMES}" OR $temparraykeys[0] === "{USERNAMES}") { // ADDED June 18 2005 to handle pulling in usernames for the user's group(s)
							$ele_value[2]['{SELECTEDNAMES}'] = explode("*=+*:", $value);
							if(count($ele_value[2]['{SELECTEDNAMES}']) > 1) { array_shift($ele_value[2]['{SELECTEDNAMES}']); }
							$ele_value[2]['{OWNERGROUPS}'] = $owner_groups;
							break;
						}
	
						// need to turn the prevEntry got from the DB into something the same as what is in the form specification so defaults show up right
						// important: this is safe because $value itself is not being sent to the browser!
						// we're comparing the output of these two lines against what is stored in the form specification, which does not have HTML escaped characters, and has extra slashes.  Assumption is that lack of HTML filtering is okay since only admins and trusted users have access to form creation.  Not good, but acceptable for now.
						$value = $myts->undoHtmlSpecialChars($value);
						if(get_magic_quotes_gpc()) { $value = addslashes($value); } 
	
						$selvalarray = explode("*=+*:", $value);
						$numberOfSelectedValues = strstr($value, "*=+*:") ? count($selvalarray)-1 : 1; // if this is a multiple selection value, then count the array values, minus 1 since there will be one leading separator on the string.  Otherwise, it's a single value element so the number of selections is 1.
						
						$assignedSelectedValues = array();
						foreach($temparraykeys as $k)
						{
							if((string)$k === (string)$value OR trans((string)$k) === (string)$value) // if there's a straight match (not a multiple selection)
							{
								$temparray[$k] = 1;
								$assignedSelectedValues[$k] = true;
							}
							elseif( is_array($selvalarray) AND (in_array((string)$k, $selvalarray, TRUE) OR in_array(trans((string)$k), $selvalarray, TRUE)) ) // or if there's a match within a multiple selection array) -- TRUE is like ===, matches type and value
							{
								$temparray[$k] = 1;
								$assignedSelectedValues[$k] = true;
							}
							else // otherwise set to zero.
							{
								$temparray[$k] = 0;
							}
						}
						if((!empty($value) OR $value === 0 OR $value === "0") AND count($assignedSelectedValues) < $numberOfSelectedValues) { // if we have not assigned the selected value from the db to one of the options for this element, then lets add it to the array of options, and flag it as out of range.  This is to preserve out of range values in the db that are there from earlier times when the options were different, and also to preserve values that were imported without validation on purpose
							foreach($selvalarray as $selvalue) {
								if(!isset($assignedSelectedValues[$selvalue]) AND (!empty($selvalue) OR $selvalue === 0 OR $selvalue === "0")) {
									$temparray[_formulize_OUTOFRANGE_DATA.$selvalue] = 1;
								}
							}
						}							
						if ($type == "radio" AND $entry != "new" AND ($value === "" OR is_null($value)) AND array_search(1, $ele_value)) { // for radio buttons, if we're looking at an entry, and we've got no value to load, but there is a default value for the radio buttons, then use that default value (it's normally impossible to unset the default value of a radio button, so we want to ensure it is used when rendering the element in these conditions)
							$ele_value = $ele_value;
						} elseif ($type != "select")
						{
							$ele_value = $temparray;
						}
						else
						{
							$ele_value[2] = $temparray;
						}
					} // end of IF we have a linked select box
					break;
				case "yn":
					if($value == 1)
					{
						$ele_value = array("_YES"=>1, "_NO"=>0);
					}
					elseif($value == 2)
					{
						$ele_value = array("_YES"=>0, "_NO"=>1);
					}
					else
					{
						$ele_value = array("_YES"=>0, "_NO"=>0);
					}
					break;
				case "date":

					$ele_value[0] = $value;

					break;
				default:
					if(file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$type."Element.php")) {
						$customTypeHandler = xoops_getmodulehandler($type."Element", 'formulize');
						return $customTypeHandler->loadValue($value, $ele_value, $element);
					} 
			} // end switch

			/*print_r($ele_value);
			print "<br>"; //debug block
			*/

			return $ele_value;
}



// THIS FUNCTION FORMATS THE DATETIME INFO FOR DISPLAY CLEANLY AT THE TOP OF THE FORM
function formulize_formatDateTime($dt) {
	// assumption is that the server timezone has been set correctly!
	// needs to figure out daylight savings time correctly...ie: is the user's timezone one that has daylight savings, and if so, if they are currently in a different dst condition than they were when the entry was created, add or subtract an hour from the seconds offset, so that the time information is displayed correctly.
	global $xoopsConfig, $xoopsUser;
	$serverTimeZone = $xoopsConfig['server_TZ'];
	$userTimeZone = $xoopsUser ? $xoopsUser->getVar('timezone_offset') : $serverTimeZone;
	$tzDiff = $userTimeZone - $serverTimeZone;
	$tzDiffSeconds = $tzDiff*3600;
	
	if($xoopsConfig['language'] == "french") {
		$return = setlocale("LC_TIME", "fr_FR.UTF8");
	}
	return _formulize_TEMP_AT . " " . strftime(dateFormatToStrftime(_MEDIUMDATESTRING), strtotime($dt)+$tzDiffSeconds); 
}


// write the settings passed to this page from the view entries page, so the view can be restored when they go back
function writeHiddenSettings($settings, $form = null) {
	//unpack settings
	$sort = $settings['sort'];
	$order = $settings['order'];
	$oldcols = $settings['oldcols'];
	$currentview = $settings['currentview'];
	$searches = array();
	if (!isset($settings['calhidden']) and !is_array($settings['calhidden']))
		$settings['calhidden'] = array();
	foreach($settings as $k=>$v) {
		if(substr($k, 0, 7) == "search_" AND $v != "") {
			$thiscol = substr($k, 7);
			$searches[$thiscol] = $v;
		}
	}
	//calculations:
	$calc_cols = $settings['calc_cols'];
	$calc_calcs = $settings['calc_calcs'];
	$calc_blanks = $settings['calc_blanks'];
	$calc_grouping = $settings['calc_grouping'];

	$hlist = $settings['hlist'];
	$hcalc = $settings['hcalc'];
	$lockcontrols = $settings['lockcontrols'];
	$asearch = $settings['asearch'];
	$lastloaded = $settings['lastloaded'];	

	// used for calendars...
	$calview = $settings['calview'];
	$calfrid = $settings['calfrid'];
	$calfid = $settings['calfid'];
	// plus there's the calhidden key that is handled below
	// plus there's the page number on the LOE screen that is handled below...
	// plus there's the multipage prev and current page

	// write hidden fields
	if($form) { // write as form objects and return form
		$form->addElement (new XoopsFormHidden ('sort', $sort));
		$form->addElement (new XoopsFormHidden ('order', $order));
		$form->addElement (new XoopsFormHidden ('currentview', $currentview));
		$form->addElement (new XoopsFormHidden ('oldcols', $oldcols));
		foreach($searches as $key=>$search) {
			$search_key = "search_" . $key;
			$search = str_replace("'", "&#39;", $search);
			$form->addElement (new XoopsFormHidden ($search_key, stripslashes($search)));
		}
		$form->addElement (new XoopsFormHidden ('calc_cols', $calc_cols));
		$form->addElement (new XoopsFormHidden ('calc_calcs', $calc_calcs));
		$form->addElement (new XoopsFormHidden ('calc_blanks', $calc_blanks));
		$form->addElement (new XoopsFormHidden ('calc_grouping', $calc_grouping));
		$form->addElement (new XoopsFormHidden ('hlist', $hlist));
		$form->addElement (new XoopsFormHidden ('hcalc', $hcalc));
		$form->addElement (new XoopsFormHidden ('lockcontrols', $lockcontrols));
		$form->addElement (new XoopsFormHidden ('lastloaded', $lastloaded));
		$asearch = str_replace("'", "&#39;", $asearch);
		$form->addElement (new XoopsFormHidden ('asearch', stripslashes($asearch)));
		$form->addElement (new XoopsFormHidden ('calview', $calview));
		$form->addElement (new XoopsFormHidden ('calfrid', $calfrid));
		$form->addElement (new XoopsFormHidden ('calfid', $calfid));
		foreach($settings['calhidden'] as $chname=>$chvalue) {
			$form->addElement (new XoopsFormHidden ($chname, $chvalue));
		}
		$form->addElement (new XoopsFormHidden ('formulize_LOEPageStart', $_POST['formulize_LOEPageStart']));
		if(isset($settings['formulize_currentPage'])) { // drawing a multipage form...
			$form->addElement( new XoopsFormHidden ('formulize_currentPage', $settings['formulize_currentPage']));
			$form->addElement( new XoopsFormHidden ('formulize_prevPage', $settings['formulize_prevPage']));
			$form->addElement( new XoopsFormHidden ('formulize_doneDest', $settings['formulize_doneDest']));
			$form->addElement( new XoopsFormHidden ('formulize_buttonText', $settings['formulize_buttonText']));
		}
		if($_POST['overridescreen']) {
			$form->addElement( new XoopsFormHidden ('overridescreen', intval($_POST['overridescreen'])));
		}
		if(strlen($_POST['formulize_lockedColumns'])>0) {
			$form->addElement( new XoopsFormHidden ('formulize_lockedColumns', $_POST['formulize_lockedColumns']));
		}
		return $form;
	} else { // write as HTML
		print "<input type=hidden name=sort value='" . $sort . "'>";
		print "<input type=hidden name=order value='" . $order . "'>";
		print "<input type=hidden name=currentview value='" . $currentview . "'>";
		print "<input type=hidden name=oldcols value='" . $oldcols . "'>";
		foreach($searches as $key=>$search) {
			$search_key = "search_" . $key;
			$search = str_replace("\"", "&quot;", $search);
			print "<input type=hidden name=$search_key value=\"" . stripslashes($search) . "\">";
		}
		print "<input type=hidden name=calc_cols value='" . $calc_cols . "'>";
		print "<input type=hidden name=calc_calcs value='" . $calc_calcs . "'>";
		print "<input type=hidden name=calc_blanks value='" . $calc_blanks . "'>";
		print "<input type=hidden name=calc_grouping value='" . $calc_grouping . "'>";
		print "<input type=hidden name=hlist value='" . $hlist . "'>";
		print "<input type=hidden name=hcalc value='" . $hcalc . "'>";
		print "<input type=hidden name=lockcontrols value='" . $lockcontrols . "'>";
		print "<input type=hidden name=lastloaded value='" . $lastloaded . "'>";
		$asearch = str_replace("\"", "&quot;", $asearch);
		print "<input type=hidden name=asearch value=\"" . stripslashes($asearch) . "\">";
		print "<input type=hidden name=calview value='" . $calview . "'>";
		print "<input type=hidden name=calfrid value='" . $calfrid . "'>";
		print "<input type=hidden name=calfid value='" . $calfid . "'>";
		foreach($settings['calhidden'] as $chname=>$chvalue) {
			print "<input type=hidden name=$chname value='" . $chvalue . "'>";
		}
		print "<input type=hidden name=formulize_LOEPageStart value='" . $_POST['formulize_LOEPageStart'] . "'>";
		if(isset($settings['formulize_currentPage'])) { // drawing a multipage form...
			print "<input type=hidden name=formulize_currentPage value='".$settings['formulize_currentPage']."'>";
			print "<input type=hidden name=formulize_prevPage value='".$settings['formulize_prevPage']."'>";
			print "<input type=hidden name=formulize_doneDest value='".$settings['formulize_doneDest']."'>";
			print "<input type=hidden name=formulize_buttonText value='".$settings['formulize_buttonText']."'>";
		}
		if($_POST['overridescreen']) {
			print "<input type=hidden name=overridescreen value='".intval($_POST['overridescreen'])."'>";
		}
		if(strlen($_POST['formulize_lockedColumns'])>0) {
			print "<input type=hidden name=formulize_lockedColumns value='".$_POST['formulize_lockedColumns']."'>";
		}
	}
}


// draw in javascript for this form that is relevant to subforms
// $nosave indicates that the user cannot save this entry, so we shouldn't check for formulizechanged
function drawJavascript($nosave) {
static $drawnJavascript = false;
if($drawnJavascript) {
	return;
}
global $xoopsUser;
$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
// Left in for possible future use by the rankOrderList element type or other elements that might use jQuery
//print "<script type=\"text/javascript\" src=\"".XOOPS_URL."/modules/formulize/libraries/jquery/jquery-1.3.2.min.js\"></script><script type=\"text/javascript\" src=\"".XOOPS_URL."/modules/formulize/libraries/jquery/jquery-ui-1.7.2.custom.min.js\"></script>";
//$GLOBALS['formulize_jQuery_included'] = true;
print "\n<script type='text/javascript'>\n";

print " initialize_formulize_xhr();\n";
print " var formulizechanged=0;\n";
print " var formulize_xhr_returned_check_for_unique_value = 'notreturned';\n";

if(isset($GLOBALS['formulize_fckEditors'])) {
	print "function FCKeditor_OnComplete( editorInstance ) { \n";
	print " editorInstance.Events.AttachEvent( 'OnSelectionChange', formulizeFCKChanged ) ;\n";
	print "}\n";
	print "function formulizeFCKChanged( editorInstance ) { \n";
	print "  formulizechanged=1; \n";
	print "}\n";
}

?>

window.onbeforeunload = function (e) {

    if(formulizechanged) {

	var e = e || window.event;

	var confirmationText = "<?php print _formulize_CONFIRMNOSAVE_UNLOAD; ?>"; // message may have single quotes in it!

	// For IE and Firefox prior to version 4
	if (e) {
	    e.returnValue = confirmationText;
	}

	// For Safari
	return confirmationText;
    }
};

<?php
print $codeToIncludejQueryWhenNecessary;
if(intval($_POST['yposition'])>0) {
		print "\njQuery(window).load(function () {\n";
		print "\tjQuery(window).scrollTop(".intval($_POST['yposition']).");\n";
		print "});\n";
}
?>


function showPop(url) {

	if (window.formulize_popup == null) {
		formulize_popup = window.open(url,'formulize_popup','toolbar=no,scrollbars=yes,resizable=yes,width=800,height=550,screenX=0,screenY=0,top=0,left=0');
      } else {
		if (window.formulize_popup.closed) {
			formulize_popup = window.open(url,'formulize_popup','toolbar=no,scrollbars=yes,resizable=yes,width=800,height=550,screenX=0,screenY=0,top=0,left=0');
            } else {
			window.formulize_popup.location = url;              
		}
	}
	window.formulize_popup.focus();
}

function validateAndSubmit() {
    var formulize_numbersonly_found= false;
    jQuery(".numbers-only-textbox").each(function() {
        if(jQuery(this).val().match(/[a-z]/i) !== null) {
            var answer = confirm ("You have entered "+jQuery(this).val()+" in a box that is supposed to have numbers only.  The letters will be removed if you save.  Is this OK?" );
            if (!answer){
                jQuery(this).focus();
                formulize_numbersonly_found = true;
            }
        }
    });

    if (formulize_numbersonly_found){
        return false;
    }

<?php
if(!$nosave) { // need to check for add or update permissions on the current user and this entry before we include this javascript, otherwise they should not be able to save the form
?>
	var validate = xoopsFormValidate_formulize();
	// this is an optional form validation function which can be provided by a screen template or form text element
	if (window.formulizeExtraFormValidation && typeof(window.formulizeExtraFormValidation) === 'function') {
		validate = window.formulizeExtraFormValidation();
	}
	if(validate) {
		if(typeof savedPage != 'undefined' && savedPage && savedPrevPage) { // set in submitForm and will have values if we're on the second time around of a two step validation, like a uniqueness check with the server
			multipageSetHiddenFields(savedPage, savedPrevPage);
		}
		jQuery(".subform-accordion-container").map(function() {
			subelementid = jQuery(this).attr('subelementid');
			window.document.getElementById('subform_entry_'+subelementid+'_active').value = jQuery(this).accordion( "option", "active" );
		});
		jQuery('#submitx').attr('disabled', 'disabled');
		if(jQuery('.formulize-form-submit-button')) {
			jQuery('.formulize-form-submit-button').attr('disabled', 'disabled');
		}
		jQuery('#yposition').val(jQuery(window).scrollTop());
        if (formulizechanged) {
            window.document.getElementById('formulizeform').style.opacity = 0.5;
            window.document.getElementById('savingmessage').style.display = 'block';
            window.scrollTo(0,0);
            formulizechanged = 0; // don't want to trigger the beforeunload warning
        }
        window.document.formulize.submit();
    }
<?php
} // end of if not $nosave
?>
}

<?php

print "	function verifyDone() {\n";
//print "		alert(formulizechanged);\n";
if(!$nosave) {
	print "	if(formulizechanged==0) {\n";
}
	print "		removeEntryLocks('submitGoParent');\n"; // true causes the go_parent form to submit
if(!$nosave) {
	print "	} else {\n";
	print "		var answer = confirm (\"" . _formulize_CONFIRMNOSAVE . "\");\n";
	print "		if (answer) {\n";
	print "			formulizechanged = 0;\n"; // don't want to trigger the beforeunload warning
	print "			removeEntryLocks('submitGoParent');\n"; // true causes the go_parent form to submit
	print "		}\n";
	print "	}\n";
}
print "   return false;"; // removeEntryLocks calls the go_parent form for us
print "	}\n";
print " function removeEntryLocks(action) {\n";
global $entriesThatHaveBeenLockedThisPageLoad;
if(count($entriesThatHaveBeenLockedThisPageLoad)>0) {
		print "var killLocks = " . formulize_javascriptForRemovingEntryLocks();
		print "		killLocks.done(function() { \n";
		print "			formulize_javascriptForAfterRemovingLocks(action);\n";
    print "			});\n";
} else {
		print "formulize_javascriptForAfterRemovingLocks(action);\n";
}
print " }\n";
	
?>

function formulize_javascriptForAfterRemovingLocks(action) {
	if(action == 'submitGoParent') {
			window.document.go_parent.submit();
	} else {
		var formAction = jQuery('form[name=formulize]').attr('action');
		var formData = jQuery('form[name=formulize]').serialize();
		jQuery.ajax({
			type: "POST",
			url: formAction,
			data: formData,
			success: function(html, x){
				document.open();
				document.write(html);
				document.close();
			}
		});
	}
}

<?php

	
print "	function add_sub(sfid, numents, instance_id) {\n";
print "		document.formulize.target_sub.value=sfid;\n";
print "		document.formulize.numsubents.value=numents;\n";
print "		document.formulize.target_sub_instance.value=instance_id;\n";
print "		validateAndSubmit();\n";
print "	}\n";

print "	function sub_del(sfid) {\n";
print "		var answer = confirm ('" . _formulize_DEL_ENTRIES . "')\n";
print "		if (answer) {\n";
print "			document.formulize.deletesubsflag.value=sfid;\n";
print "			validateAndSubmit();\n";
print "		} else {\n";
print "			return false;\n";
print "		}\n";
print "	}\n";

print "	function goSub(ent, fid) {\n";
print "		document.formulize.goto_sub.value = ent;\n";
print "		document.formulize.goto_sfid.value = fid;\n";
//print "		document.formulize.submit();\n";
print "		validateAndSubmit();\n";
print "	}\n";
			
//added by Cory Aug 27, 2005 to make forms printable


print "function PrintPop(ele_allowed) {\n";
print "		window.document.printview.elements_allowed.value=ele_allowed;\n"; // nmc 2007.03.24 - added 
print "		window.document.printview.submit();\n";
print "}\n";

//added by Cory Aug 27, 2005 to make forms printable

print "function PrintAllPop() {\n";									// nmc 2007.03.24 - added 
print "		window.document.printview.elements_allowed.value='';\n"; // nmc 2007.03.24 - added 
print "		window.document.printview.submit();\n";					// nmc 2007.03.24 - added 
print "}\n";														// nmc 2007.03.24 - added 

// try and catch changes in a datebox element
print "jQuery(document).ready(function() {
  jQuery(\"img[title='"._CALENDAR."']\").click(function() {
	formulizechanged=1;		
  }); 
});
\n";

drawXhrJavascript();


print "</script>\n";
$drawnJavascript = true;
}


function drawJavascriptForConditionalElements($conditionalElements, $entries, $sub_entries) {

global $xoopsUser;
$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;

// need to setup governing elements array...which is inverse of the conditional elements
$element_handler = xoops_getmodulehandler('elements','formulize');
$governingElements = array();
$GLOBALS['recordedEntries'] = array(); // global array used in the compile functions below, to make sure we only record a given element pair one time
foreach($conditionalElements as $handle=>$theseGoverningElements) {
	foreach($theseGoverningElements as $governingElementKey=>$thisGoverningElement) {
		$elementObject = $element_handler->get($thisGoverningElement);
		if(is_object($elementObject)) {
			if($elementObject->getVar('ele_type') == "derived") {
				unset($conditionalElements[$handle][$governingElementKey]); // derived value elements have no DOM instantiation that we can latch onto, so skip them...we should find a way to update them with the current state of the form maybe??
				continue;
			}
			$governingElements = compileGoverningElements($entries, $governingElements, $elementObject, $handle);
			$governingElements = compileGoverningElements($sub_entries, $governingElements, $elementObject, $handle);
			$governingElements = compileGoverningLinkedSelectBoxSourceConditionElements($governingElements, $handle);
		}
		// must wrap required validation javascript in some check for the pressence of the element??  
	}
}

print "
<script type='text/javascript'>

var conditionalHTML = new Array(); // needs to be global!

jQuery(window).load(function() {

	// preload the current state of the HTML for any conditional elements that are currently displayed, so we can compare against what we get back when their conditions change
	var conditionalElements = new Array('".implode("', '",array_keys($conditionalElements))."');
	var governedElements = new Array();
	var relevantElements = new Array();
	";
	$topKey = 0;
	$relevantElementArray = array();
	foreach($governingElements as $thisGoverningElement=>$theseGovernedElements) {
		print "governedElements['".$thisGoverningElement."'] = new Array();\n";
		foreach($theseGovernedElements as $innerKey=>$thisGovernedElement) {
			if(!isset($relevantElementArray[$thisGovernedElement])) {
				print "relevantElements['".$thisGovernedElement."'] = new Array();\n";
				$relevantElementArray[$thisGovernedElement] = true;
			}
			print "relevantElements['".$thisGovernedElement."'][$topKey] = '".$thisGoverningElement."';\n";
			print "governedElements['".$thisGoverningElement."'][$innerKey] = '".$thisGovernedElement."';\n";
		}
		$topKey++;
	}
	print "
	for(key in conditionalElements) {
		var handle = conditionalElements[key];
		getConditionalHTML(handle); // isolate ajax call in a function to control the scope of handle so it's the same no matter the time difference when the return value gets here
	}

	jQuery(\"[name='".implode("'], [name='", array_keys($governingElements))."']\").live('change', function() { // live is necessary because it will bind events to the right DOM elements even after they've been replaced by ajax events
		for(key in governedElements[jQuery(this).attr('name')]) {
			var handle = governedElements[jQuery(this).attr('name')][key];
			elementValuesForURL = getRelevantElementValues(relevantElements[handle]);
			checkCondition(handle, conditionalHTML[handle], elementValuesForURL);	
		}
	});
});

function getConditionalHTML(handle) {
	partsArray = handle.split('_');
	jQuery.get(\"".XOOPS_URL."/modules/formulize/formulize_xhr_responder.php?uid=".$uid."&op=get_element_row_html&elementId=\"+partsArray[3]+\"&entryId=\"+partsArray[2]+\"&fid=\"+partsArray[1], function(data) {
		assignConditionalHTML(handle, data);
	});
}

function assignConditionalHTML(handle, html) {
	conditionalHTML[handle] = html; 
}

function checkCondition(handle, currentHTML, elementValuesForURL) {
	partsArray = handle.split('_');
	jQuery.get(\"".XOOPS_URL."/modules/formulize/formulize_xhr_responder.php?uid=".$uid."&op=get_element_row_html&elementId=\"+partsArray[3]+\"&entryId=\"+partsArray[2]+\"&fid=\"+partsArray[1]+\"\"+elementValuesForURL, function(data) {
		if(data) {
			// should only empty if there is a change from the current state
			if(window.document.getElementById('formulize-'+handle).style.display == 'none' || currentHTML != data) {
				jQuery('#formulize-'+handle).empty();
				jQuery('#formulize-'+handle).append(data);
				window.document.getElementById('formulize-'+handle).style.display = 'table-row';
				ShowHideTableRow('#formulize-'+handle,false,0,function() {}); // because the newly appended row will have full opacity so immediately make it transparent
				ShowHideTableRow('#formulize-'+handle,true,1500,function() {});
				assignConditionalHTML(handle, data);
			}
		} else {
			if( window.document.getElementById('formulize-'+handle).style.display != 'none') {
				ShowHideTableRow('#formulize-'+handle,false,700,function() {
					jQuery('#formulize-'+handle).empty();
					window.document.getElementById('formulize-'+handle).style.display = 'none';
					assignConditionalHTML(handle, data);
				});
			}
		}
		
	});
}

function getRelevantElementValues(elements) {
	var ret = '';
	for(key in elements) {
		var handle = elements[key];
		if(handle.indexOf('[]')!=-1) { // grab multiple value elements from a different tag
			nameToUse = '[jquerytag='+handle.substring(0, handle.length-2)+']';
		} else {
			nameToUse = '[name='+handle+']';
		}
		elementType = jQuery(nameToUse).attr('type');
		if(elementType == 'radio') {
			formulize_selectedItems = jQuery(nameToUse+':checked').val();
		} else if(elementType == 'checkbox') {
			formulize_selectedItems = new Array();
			jQuery(nameToUse).map(function() { // need to check each one individually, because val isn't working right?!
				if(jQuery(this).attr('checked')) {
					foundval = jQuery(this).attr('value');
					formulize_selectedItems.push(foundval);
				} else {
					formulize_selectedItems.push('');		
				}
			});
		} else {
			formulize_selectedItems = jQuery(nameToUse).val();
		}
		if(jQuery.isArray(formulize_selectedItems)) {
			for(key in formulize_selectedItems) {
				ret = ret + '&'+handle+'='+encodeURIComponent(formulize_selectedItems[key]);					
			}
		} else {
			ret = ret + '&'+handle+'='+encodeURIComponent(formulize_selectedItems);				
		}

	}
	return ret;
}


function ShowHideTableRow(rowSelector, show, speed, callback)
{
    var childCellsSelector = jQuery(rowSelector).children('td');
    var ubound = childCellsSelector.length - 1;
    var lastCallback = null;

    childCellsSelector.each(function(i)
    {
        // Only execute the callback on the last element.
        if (ubound == i)
            lastCallback = callback

        if (show)
        {
            jQuery(this).fadeIn(speed, lastCallback)
        }
        else
        {
            jQuery(this).fadeOut(speed, lastCallback)
        }
    });
}




</script>";
	
}

function compileGoverningElements($entries, $governingElements, $elementObject, $handle) {
	$type = $elementObject->getVar('ele_type');
	$ele_value = $elementObject->getVar('ele_value');
	if($type == "checkbox" OR ($type == "select" AND $ele_value[1])) {
		$additionalNameParts = "[]"; // set things up with the right [] for multiple value elements
	} else {
		$additionalNameParts = "";
	}
	global $recordedEntries;
	if(isset($entries[$elementObject->getVar('id_form')])) {
		foreach($entries[$elementObject->getVar('id_form')] as $thisEntry) {
			if($thisEntry == "") {
				$thisEntry = "new";
			}
			if(!isset($recordedEntries[$elementObject->getVar('id_form')][$thisEntry][$elementObject->getVar('ele_id')][$handle])) {
			$governingElements['de_'.$elementObject->getVar('id_form').'_'.$thisEntry.'_'.$elementObject->getVar('ele_id').$additionalNameParts][] = $handle;
				$recordedEntries[$elementObject->getVar('id_form')][$thisEntry][$elementObject->getVar('ele_id')][$handle] = true;
			}
		}
	}
	return $governingElements;
}

function compileGoverningLinkedSelectBoxSourceConditionElements($governingElements, $handle) {
	// figure out if the $handle is for a lsb
	// if so, check if there are conditions on the lsb
	// check if the terms include any { } elements and grab those
	$handleParts = explode("_",$handle); // de, fid, entry, elementId
	$element_handler = xoops_getmodulehandler('elements','formulize');
	$elementObject = $element_handler->get($handleParts[3]);
	global $recordedEntries;
	if(is_object($elementObject) AND $elementObject->isLinked) {
		$ele_value = $elementObject->getVar('ele_value');
		$elementConditions = $ele_value[5];
		foreach($elementConditions[2] as $thisTerm) {
			if(substr($thisTerm,0,1)=="{" AND substr($thisTerm, -1) == "}") {
				// figure out the element, which is presumably in the same form, and assume the same entry
				$curlyBracketElement = $element_handler->get(trim($thisTerm,"{}"));
				if(!isset($recordedEntries[$curlyBracketElement->getVar('id_form')][$handleParts[2]][$curlyBracketElement->getVar('ele_id')][$handle])) {
				$governingElements['de_'.$curlyBracketElement->getVar('id_form').'_'.$handleParts[2].'_'.$curlyBracketElement->getVar('ele_id')][] = $handle;
					$recordedEntries[$curlyBracketElement->getVar('id_form')][$handleParts[2]][$curlyBracketElement->getVar('ele_id')][$handle] = true;
				}
			}
		}
	} 
	return $governingElements;
}

// determine which screen to use when displaying a subform
// - if a screen is selected in the admin section, then use that
// - if no screen is selected, use the default screen
// - if the screen selected so far is not a single-page data-entry screen, return null
function get_display_screen_for_subform($subform_element_object) {
    $selected_screen_id = null;

    if ($subform_element_object and is_a($subform_element_object, "formulizeformulize")) {
        $ele_value = $subform_element_object->getVar('ele_value');
        if (isset($ele_value['display_screen'])) {
            // use selected screen
            $selected_screen_id = intval($ele_value['display_screen']);
        } else {
            // use default screen for the form
            $form_handler = xoops_getmodulehandler('forms', 'formulize');
            $formObject = $form_handler->get($ele_value[0]);    // 0 is the form_id
            $selected_screen_id = intval($formObject->getVar('defaultform'));
        }

        if ($selected_screen_id) {
            // a screen is selected -- confirm that it is a single-page data-entry screen
            global $xoopsDB;
            $screen_type = q("SELECT type FROM ".$xoopsDB->prefix("formulize_screen").
                " WHERE sid=".intval($selected_screen_id)." and fid=".intval($ele_value[0]));
            if (1 != count($screen_type) or !isset($screen_type[0]['type']) or "form" != $screen_type[0]['type']) {
                // selected screen is not valid for displaying the subform
                $selected_screen_id = null;
            }
        }
    }

    return $selected_screen_id;
}
