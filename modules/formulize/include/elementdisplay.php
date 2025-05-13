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

//THIS FILE HANDLES THE DISPLAY OF INDIVIDUAL FORM ELEMENTS.  FUNCTIONS CAN BE CALLED FROM ANYWHERE (INTENDED FOR PAGEWORKS MODULE)

include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplay.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/class/elementrenderer.php";
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';

$GLOBALS['formulize_renderedElementHasConditions'] = array();

// $groups is optional and can be passed in to override getting the user's groups.  This is necessary for the registration form to work with custom displayed elements
// $noSave is used to specify a different name for the element, so we can get back an HTML element that we will use in a different context than a normal form
function displayElement($formframe="", $ele=0, $entry="new", $noSave = false, $screen=null, $prevEntry=null, $renderElement=true, $groups="") {

	static $cachedPrevEntries = array();
	static $lockedEntries = array(); // keep track of the entries we have determined are locked
	global $entriesThatHaveBeenLockedThisPageLoad;
	if(!is_array($entriesThatHaveBeenLockedThisPageLoad)) { // which entries we have locked as part of this page load, so we don't waste time setting locks again on entries we've already locked, and so we can ignore locks that were set by ourselves!
		$entriesThatHaveBeenLockedThisPageLoad = array();
	}

	$subformCreateEntry = strstr($entry, "subformCreateEntry") ? true : false; // check for this special flag, which is mostly like a "new" situation, except for the deh hidden flag that gets passed back, since we don't want the standard readelements logic to pickup these elements!
	if($subformCreateEntry) {
		$subformMetaData = explode("_", $entry);
		$subformEntryIndex = $subformMetaData[1];
		$subformElementId = $subformMetaData[2];
	}

    if(($entry == "" AND $entry !== 0) OR $subformCreateEntry) {
        $entry = "new";
    }

	$element = _formulize_returnElement($ele);
	if(!is_object($element)) {
		return "invalid_element";
	}

	// if rendering an inline element in a list of entries, because the user clicked the edit icon on the element...
	if(isset($_GET['op']) AND $_GET['op'] == 'get_element_html' AND strstr(getCurrentURL(), 'formulize_xhr_responder.php')) {
		$element = overrideSeparatorToLineBreak($element);
	}

    $form_id = $element->getVar('id_form');

	$deprefix = $noSave ? "denosave_" : "de_";
	$deprefix = $subformCreateEntry ? "desubform".$subformEntryIndex."x".$subformElementId."_" : $deprefix; // need to pass in an entry index so that all fields in the same element can be collected
	if($noSave AND !is_bool($noSave)) {
		$renderedElementMarkupName = $noSave; // if we've passed a specific string with $noSave, then we want to use that to override the name of the element, because the developer wants to pick up that form value later after submission
		$noSave = true;
	} else {
		$renderedElementMarkupName = $deprefix.$form_id.'_'.$entry.'_'.$element->getVar('ele_id');
	}

	global $xoopsUser;
	$mid = getFormulizeModId();
	$element_handler = xoops_getmodulehandler('elements', 'formulize');
    if(!$groups) {
        // groups might be passed in, which covers the case of the registration form and getting the groups from the registration code
    $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
  }
	$user_id = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
	$username = $xoopsUser ? $xoopsUser->getVar('uname') : "unknown user";

	$owner = getEntryOwner($entry, $form_id);

	$gperm_handler = xoops_gethandler('groupperm');
	$userHasPermissionToIgnoreEntryLock = $gperm_handler->checkRight("ignore_editing_lock", $form_id, $groups, $mid);
	$update_own_entry = $noSave ? 1 : $gperm_handler->checkRight("update_own_entry", $form_id, $groups, $mid);

	$single_result = getSingle($form_id, $user_id, $groups);
	$groupEntryWithUpdateRights = ($single_result['flag'] == "group" AND $update_own_entry AND $entry == $single_result['entry']) ? true : false;

	list($allowed, $isDisabled) = elementIsAllowedForUserInEntry($element, $entry, $groups, $noSave, $renderedElementMarkupName, $subformCreateEntry);

	if($allowed) {

		// clear prior entry locks for this user - will be based on the token used to lock the prior page load, which is passed through POST key 'formulize_entry_lock_token'
		include_once XOOPS_ROOT_PATH.'/modules/formulize/formulize_deleteEntryLock.php';

		if($element->getVar('ele_type') == "subform") {
			return array("", $isDisabled);
		}

		// Another check to see if this element is disabled, for the case where the user can view the form, but not edit it.
		if (!$isDisabled AND !$noSave) {
			// note that we're using the OPPOSITE of the permission because we want to know if the element should be disabled
			$isDisabled = !formulizePermHandler::user_can_edit_entry($form_id, $user_id, $entry);
		}

		// check whether the entry is locked, and if so, then the element will be disabled.  Set a message to say that elements were disabled due to entries being edited elsewhere (first time only).
		// groups with ignore lock permission bypass this, and therefore can save entries even when locked, and saving an entry removes the lock, so that gets you out of a jam if the lock is in place when it shouldn't be.
		// locks are only valid for the session time, so if a lock is older than that, it is ignored and cleared
		// Do this last, since locking overrides other permissions!

		$lockFileName = "entry_".$entry."_in_form_".$form_id."_is_locked_for_editing";
		// if we haven't found a lock for this entry, check if there is one...(as long as it's not an entry that we locked ourselves on this page load)
    // only care about the existence of a lock if there's an editable element in play
		if(!$isDisabled AND $entry != "new" AND $entry > 0
           AND !isset($lockedEntries[$form_id][$entry])
           AND !isset($entriesThatHaveBeenLockedThisPageLoad[$form_id][$entry])
           AND $element->hasData AND $element->getVar('ele_type') != 'derived'
           AND !strstr(getCurrentURL(),"printview.php")
           AND file_exists(XOOPS_ROOT_PATH."/modules/formulize/temp/$lockFileName")
           AND !$userHasPermissionToIgnoreEntryLock) {
			formulize_scandirAndClean(XOOPS_ROOT_PATH."/modules/formulize/temp/", "_is_locked_for_editing", ini_get("session.gc_maxlifetime")); // clean up expired locks
            if(file_exists(XOOPS_ROOT_PATH."/modules/formulize/temp/$lockFileName")) {
				list($lockUid, $lockUsername) = explode(",", file_get_contents(XOOPS_ROOT_PATH."/modules/formulize/temp/$lockFileName"));
				if($lockUid != $user_id) {
                    if (count((array) $lockedEntries) == 0) { // first time here, make the warning label
                        $label = json_encode(sprintf(_formulize_ENTRY_IS_LOCKED, $lockUsername));
                        print <<<EOF
<script type='text/javascript'>
$(document).ready(function() {
    jQuery("<div id=\"formulize-entry-lock-message\"><i id=\"formulize-entry-lock-icon\" class=\"icon-lock\"></i><p>"+$label+"</p></div>").insertBefore("#formulizeform");
});
</script>
EOF;
					}
					$lockedEntries[$form_id][$entry] = true;
				}
			}
		}
		// if we've ever found a lock for this entry as part of this pageload...
		if(isset($lockedEntries[$form_id][$entry])) {
			$isDisabled = true;
		}

		if($prevEntry==null) { // preferable to pass in prevEntry!
			$prevEntry = getEntryValues($entry, "", $groups, $form_id, "", $mid, $user_id, $owner, $groupEntryWithUpdateRights);
		}

		$renderer = new formulizeElementRenderer($element);
		$ele_value = $element->getVar('ele_value');
		$ele_type = $element->getVar('ele_type');
		if(($prevEntry OR $profileForm === "new") AND $ele_type != 'subform' AND $ele_type != 'grid') {
			$data_handler = new formulizeDataHandler($form_id);
			$ele_value = loadValue($prevEntry, $element, $ele_value, $data_handler->getEntryOwnerGroups($entry), $entry); // get the value of this element for this entry as stored in the DB -- and unset any defaults if we are looking at an existing entry
		}

		//formulize_benchmark("About to render element ".$element->getVar('ele_caption').".");

		$form_ele =& $renderer->constructElement($renderedElementMarkupName, $ele_value, $entry, $isDisabled, $screen);
		if(strstr($_SERVER['PHP_SELF'], "formulize/printview.php") AND is_object($form_ele)) {
			$form_ele->setDescription('');
		}
		//formulize_benchmark("Done rendering element.");

		// put a lock on this entry in this form, so we know that the element is being edited.  Lock will be removed next page load.
		if (!$isDisabled AND !$noSave AND $entry != "new" AND $entry > 0
            AND !isset($lockedEntries[$form_id][$entry])
            AND !isset($entriesThatHaveBeenLockedThisPageLoad[$form_id][$entry])
            AND $element->hasData AND $element->getVar('ele_type') != 'derived'
            AND !strstr(getCurrentURL(),"printview.php")) {

            if (is_writable(XOOPS_ROOT_PATH."/modules/formulize/temp/")) {
                $lockFile = fopen(XOOPS_ROOT_PATH."/modules/formulize/temp/$lockFileName", "w");
                if (false !== $lockFile) {
                    fwrite($lockFile, "$user_id,$username");
                    fclose($lockFile);
                    $entriesThatHaveBeenLockedThisPageLoad[$form_id][$entry] = true;
                }
            } else {
                // cannot write to Formulize temp folder to create lock entries
                if (defined("ICMS_ERROR_LOG_SEVERITY") and ICMS_ERROR_LOG_SEVERITY >= E_WARNING) {
                    static $lock_file_warning_issued = false;
                    if (!$lock_file_warning_issued) {
                        error_log("Notice: Formulize temp folder does not exist or is not writeable, so lock file cannot be created in ".
                            XOOPS_ROOT_PATH."/modules/formulize/temp/");
                        $lock_file_warning_issued = true;
                    }
                }
            }
        }

		if(!$renderElement) {
			return array(0=>$form_ele, 1=>$isDisabled);
		} elseif($element->getVar('ele_type') == "ib") {
				print $form_ele[0];
				return "rendered";
			} elseif(is_object($form_ele)) {
					print $form_ele->render();
				// NOTE: ASSIGNING THE VALIDATION JS HERE WILL INTERFERE WITH STANDARD FORM COMPILING THE MAIN FORM IN FORMULIZE
				// Standard Formulize should always NOT be rendering the elements and they would be returned above in an array
				// And then handling the cataloguing of conditional elements, and the JS for rendered elements, etc, would proceed as normal.
				// HOWEVER, when rendering things that are 'disembodied' from the main form, such as subform row elements, this is actually the only way to get their validation code into the system?
				// Subform rows could be refactored along the line of grid elements, to more rigorously handle the generation of elements and their validation js
				          if(!empty($form_ele->customValidationCode) AND !$isDisabled) {
										if($js = $form_ele->renderValidationJS()) {
											$GLOBALS['formulize_renderedElementsValidationJS'][$GLOBALS['formulize_thisRendering']][$renderedElementMarkupName] = $js;
										}
				          } elseif($element->getVar('ele_req') AND ($element->getVar('ele_type') == "text" OR $element->getVar('ele_type') == "textarea") AND !$isDisabled) {
				            $eltname    = $form_ele->getName();
				            $eltcaption = $form_ele->getCaption();
				            $eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, $eltcaption );
				            $eltmsg = str_replace('"', '\"', stripslashes( $eltmsg ) );
										$GLOBALS['formulize_renderedElementsValidationJS'][$GLOBALS['formulize_thisRendering']][$renderedElementMarkupName] = "if ( myform.".$eltname.".value == \"\" ) { window.alert(\"".$eltmsg."\"); myform.".$eltname.".focus(); return false; }";
				          }
					if($isDisabled) {
						return "rendered-disabled";
					} else {
						return "rendered";
					}
			}
	} else {
		return "not_allowed";
	}
}

/**
 * Determines whether a user is allowed to see an element.
 * Does various book keeping and record keeping related to rendering entries as well, such as cataloguing whether the element has conditions and what it's governing elements are.
 * @param object $elementObject The formuilze element object
 * @param int|string $entry_id The entry id of the entry being considered, or 'new' for a new entry not yet saved.
 * @param array $groups Optional. By default, the groups of the active user are checked. If provided, this array of group ids will be used instead.
 * @param boolean $noSave Optional. A flag to indicate if the element is being generated out of context of a form, in which case the user's permissions are not relevant, only group memberships.
 * @param string $renderedElementMarkupName. Optional. The markup handle for the element, ie: de_FID_ENTRYID_ELEMENTID
 * @param boolean $subformCreateEntry. Optional. A flag to indicate if we're creating elements for fake new subform entries or not. Such elements never have conditions attached to them.
 * @return array Returns an array of booleans, first is whether the element is allowed for the user, second is whether the element is disabled.
 */
function elementIsAllowedForUserInEntry($elementObject, $entry_id, $groups = array(), $noSave = false, $renderedElementMarkupName = null, $subformCreateEntry = false) {

	if($_SERVER['SCRIPT_NAME'] == '/modules/formulize/admin/fakeform.php') {
		return array(true, false);
	}

	// basic security check...
	$form_id = $elementObject->getVar('id_form');
	if(security_check($form_id, $entry_id) == false)	{
		return false;
	}

	// setup variables
	global $xoopsUser;
	$user_id = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
	if(empty($groups)) {
		$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
	}
	$mid = getFormulizeModId();
	$owner = getEntryOwner($entry_id, $form_id);
	if(!$renderedElementMarkupName) {
		$renderedElementMarkupName = 'de_'.$form_id.'_'.$entry_id.'_'.$elementObject->getVar('ele_id');
	}

	// gather permissions...
	static $cachedViewPrivate = array();
	static $cachedUpdateOwnEntry = array();
	$groupsKey = serialize($groups);
	if(!isset($cachedViewPrivate[$form_id][$groupsKey]) AND !$noSave) {
		$gperm_handler = xoops_gethandler('groupperm');
		$cachedViewPrivate[$form_id][$groupsKey] = $gperm_handler->checkRight("view_private_elements", $form_id, $groups, $mid);
		$cachedUpdateOwnEntry[$form_id][$groupsKey] = $gperm_handler->checkRight("update_own_entry", $form_id, $groups, $mid);
	}
	$view_private_elements = $noSave ? 1 : $cachedViewPrivate[$form_id][$groupsKey];
	$update_own_entry = $noSave ? 1 : $cachedUpdateOwnEntry[$form_id][$groupsKey];
	$single_result = getSingle($form_id, $user_id, $groups);
	$groupEntryWithUpdateRights = ($single_result['flag'] == "group" AND $update_own_entry AND $entry_id == $single_result['entry']) ? true : false;

	$display = $elementObject->getVar('ele_display');
	$private = $elementObject->getVar('ele_private');

	$allowed = 0;
	if(strstr($display, ",")) {
		$display_groups = explode(",", $display);
		$allowed = array_intersect($groups, $display_groups) ? 1 : 0;
	} elseif($display == 1) {
		$allowed = 1;
	}

	if($allowed AND $private AND $user_id != $owner AND !$groupEntryWithUpdateRights AND $entry_id != "new") {
		$allowed = $view_private_elements ? 1 : 0;
	}

	// record the list of elements that are allowed in principle for a form (regardless of conditional status)
	if($allowed) {
		$GLOBALS['formulize_renderedElementsForForm'][$form_id][$entry_id][$renderedElementMarkupName] = $elementObject->getVar('ele_handle');
	}

	$elementFilterSettings = $elementObject->getVar('ele_filtersettings');
	if($allowed AND is_array($elementFilterSettings[0]) AND count((array) $elementFilterSettings[0]) > 0 AND (!$noSave OR $entry_id != 'new')) {
		// cache the filterElements for this element, so we can build the right stuff with them later in javascript, to make dynamically appearing elements
		if(!$subformCreateEntry) {
			catalogConditionalElement($renderedElementMarkupName, array_unique($elementFilterSettings[0]));
		}
		$allowed = checkElementConditions($elementFilterSettings, $form_id, $entry_id);
	}

	$isDisabled = false;
	if(isset($GLOBALS['formulize_forceElementsDisabled']) AND $GLOBALS['formulize_forceElementsDisabled'] == true) {
		$isDisabled = true;
	} else {
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		$isDisabled = $element_handler->isElementDisabledForUser($elementObject, $xoopsUser) ? true : false;
		if($isDisabled) {
			$disabledConditions = $elementObject->getVar('ele_disabledconditions');
			if(is_array($disabledConditions[0]) AND count((array) $disabledConditions[0]) > 0) {
				$isDisabled = checkElementConditions($disabledConditions, $form_id, $entry_id);
				// Also, catalogue the governing elements so that dynamic conditional behaviour will work.
				// Only if it's not a new entry, because there's no point in having elements in a new entry, with no values yet, and then be disabling them. Need to enter some information first??? This is especially necessary if elements should disable after they're NOT blank, because dynamic re-rendering would then disable them before you had saved the value you entered! The NOT Blank condition will kick in on next page load when the entry is no longer 'new'.
				if($entry_id != 'new' AND !$subformCreateEntry) {
					catalogConditionalElement($renderedElementMarkupName, array_unique($disabledConditions[0]));
				}
			}
		}
	}

	$allowed = $allowed ? true : false;
	$isDisabled = $isDisabled ? true : false;
	return array($allowed, $isDisabled);
}

/**
 * If this element has a separator setting (checkbox or radio), set to line break.
 * @param object $elementObject The element that we are working with
 * @param object The modified element with the alternate separator setting
 */
function overrideSeparatorToLineBreak($elementObject) {
	$ele_delim = $elementObject->getVar('ele_delim');
	if($ele_delim AND $ele_delim != 'br') {
		$ele_delim = 'br';
		$elementObject->setVar('ele_delim', $ele_delim);
	}
	return $elementObject;
}

/**
 * Record an element and its filter conditions so we can reference them later when generating the JS for contitional elements
 *
 * Do not catalogue when there is a closure callback on the current output buffer, because that happens when we are rendering inline subform elements, which can never respond to conditions.
 *
 * @param string $renderedElementMarkupName The markup handle for the element, ie: de_FID_ENTRYID_ELEMENTID
 * @param array $governingElements An array of element handles, of the elements which control the conditions that apply to the rendered element
 * @return Nothing
 */
function catalogConditionalElement($renderedElementMarkupName, $governingElements) {
	$bufferingStatus = ob_get_status();
	if($bufferingStatus['name'] != 'Closure::__invoke') {
		if(!isset($GLOBALS['formulize_renderedElementHasConditions'][$renderedElementMarkupName])) {
			$GLOBALS['formulize_renderedElementHasConditions'][$renderedElementMarkupName] = $governingElements;
		} else {
			foreach($governingElements as $governingElement) {
				if(!in_array($governingElement, $GLOBALS['formulize_renderedElementHasConditions'][$renderedElementMarkupName])) {
					$GLOBALS['formulize_renderedElementHasConditions'][$renderedElementMarkupName][] = $governingElement;
				}
			}
		}
	}
}

/**
 * Remove an element from the conditional element catalogue. This would typically be because you don't want it included in the conditional event handling JS.
 * @param string $renderedElementMarkupName The markup handle for the element, ie: de_FID_ENTRYID_ELEMENTID
 * @return Nothing
 */
function removeFromConditionalCatalogue($renderedElementMarkupName) {
	if(isset($GLOBALS['formulize_renderedElementHasConditions'][$renderedElementMarkupName])) {
		unset($GLOBALS['formulize_renderedElementHasConditions'][$renderedElementMarkupName]);
	}
}

/**
 *
 */
function checkElementConditions($elementFilterSettings, $form_id, $entry) {
	// need to check if there's a condition on this element that is met or not
	static $cachedEntries = array();
	if($entry != "new") {
		if(!isset($cachedEntries[$form_id][$entry])) {
			$cachedEntries[$form_id][$entry] = getData("", $form_id, $entry, cacheKey: 'bypass'.microtime(true));
		}
		$entryData = $cachedEntries[$form_id][$entry];
	}

	$filterElements = $elementFilterSettings[0];
	$filterOps = $elementFilterSettings[1];
	$filterTerms = $elementFilterSettings[2];
	$filterTypes = $elementFilterSettings[3];

	// find the filter indexes for 'match all' and 'match one or more'
	$filterElementsAll = array();
	$filterElementsOOM = array();
	for($i=0;$i<count((array) $filterTypes);$i++) {
		if($filterTypes[$i] == "all") {
			$filterElementsAll[] = $i;
		} else {
			$filterElementsOOM[] = $i;
		}
	}

	// setup evaluation condition as PHP and then eval it so we know if we should include this element or not
	$evaluationCondition = "if(";

	$evaluationConditionAND = buildEvaluationCondition("AND",$filterElementsAll,$filterElements,$filterOps,$filterTerms,$entry,$entryData);
	$evaluationConditionOR = buildEvaluationCondition("OR",$filterElementsOOM,$filterElements,$filterOps,$filterTerms,$entry,$entryData);

	$evaluationCondition .= $evaluationConditionAND;
	if( $evaluationConditionOR ) {
		if( $evaluationConditionAND ) {
			$evaluationCondition .= " AND (" . $evaluationConditionOR . ")";
		} else {
			$evaluationCondition .= $evaluationConditionOR;
		}
	}

	$evaluationCondition .= ") {\n";
	$evaluationCondition .= "  \$passedCondition = true;\n";
	$evaluationCondition .= "}\n";

	$passedCondition = false;
	eval($evaluationCondition);

	$allowed = 1;
	if(!$passedCondition) {
		$allowed = 0;
	}
	return $allowed;
}


/* ALTERED - 20100316 - freeform - jeff/julian - start */
// THIS SHOULD BE REFACTORED SO THAT ELEMENT DISPLAY CONDITIONS RUN OFF THE SAME SQL FILTER LOGIC AS EVERY OTHER INSTANCE OF A FILTER
function buildEvaluationCondition($match,$indexes,$filterElements,$filterOps,$filterTerms,$entry,$entryData) {
    $evaluationCondition = "";

    // convert the internal database representation to the displayed value, if this element has uitext that we're supposed to use
    // translate yes/no choices for yes/no elements if French is active language
    global $xoopsConfig;
		$element_handler = xoops_getmodulehandler('elements', 'formulize');

    foreach ($filterElements as $key => $element) {
			if(!isMetaDataField($element)) {
				// make sure that the filterElements array is using handles, as originally designed and required by code below
				if($filterElementObject = $element_handler->get($element)) {
					$filterElements[$key] = $filterElementObject->getVar('ele_handle');
				} else {
					print "Formulize Error: a display or disabled condition for a form element, is referencing a non existent element in another form. Probably the element was deleted?<br>";
					return false;
				}
				$element_metadata = formulize_getElementMetaData($element, !is_numeric($element));
				if($element_metadata['ele_uitextshow'] AND isset($element_metadata['ele_uitext'])) {
					$filterTerms[$key] = formulize_swapUIText($filterTerms[$key], unserialize($element_metadata['ele_uitext']));
				}
				if($element_metadata['ele_type'] == 'yn' AND ($filterTerms[$key] == 'Yes' OR $filterTerms[$key] == 'No') AND $xoopsConfig['language'] == 'french') {
					$filterTerms[$key] = $filterTerms[$key] == 'Yes' ? 'Oui' : $filterTerms[$key];
					$filterTerms[$key] = $filterTerms[$key] == 'No' ? 'Non' : $filterTerms[$key];
				}
			}
    }


	for($io=0;$io<count((array) $indexes);$io++) {
		$i = $indexes[$io];
		if(!($evaluationCondition == "")) {
			$evaluationCondition .= " $match ";
		}
		switch($filterOps[$i]) {
			case "=";
				$thisOp = "==";
				break;
			case "NOT";
				$thisOp = "!=";
				break;
			default:
				$thisOp = $filterOps[$i];
		}
		if($filterTerms[$i] === "{BLANK}") {
			$filterTerms[$i] = "";
		}

        $filterTerms[$i] = parseUserAndToday($filterTerms[$i]);

        // convert { } element references to their API format version (prepValues function output), unless the filter element is creation_uid or mod_uid
        if(substr($filterTerms[$i],0,1) == "{" AND substr($filterTerms[$i],-1)=="}") {
            $handle_reference = substr($filterTerms[$i],1,-1);
            if($filterElements[$i] != 'creation_uid' AND $filterElements[$i] != 'mod_uid') { // comparing to a regular element, get the db value
                $filterTerms[$i] = $entry == 'new' ? '' : display($entryData[0], $handle_reference); // get blank, but we could try to get defaults like below
            } elseif($entry != 'new') { // comparing to user metadata field, entry is not new
                // take a wild guess that the reference is to something that should be a uid in the db...
                $element_handler = xoops_getmodulehandler('elements', 'formulize');
                $form_handler = xoops_getmodulehandler('forms', 'formulize');
                $elementObject = $element_handler->get($handle_reference);
                $formObject = $form_handler->get($elementObject->getVar('id_form'));
                global $xoopsDB;
                $sql = 'SELECT '.formulize_db_escape($handle_reference).' FROM '.$xoopsDB->prefix('formulize_'.$formObject->getVar('form_handle')).' WHERE entry_id = '.intval($entry);
                $res = $xoopsDB->query($sql);
                $row = $xoopsDB->fetchRow($res);
                $filterTerms[$i] = $row[0];
            } else { // comparing to user metadata field, entry is new
                $filterTerms[$i] = 0;
            }
        }

		if(isset($GLOBALS['formulize_asynchronousFormDataInAPIFormat'][$entry][$filterElements[$i]])) {
			$compValue = $GLOBALS['formulize_asynchronousFormDataInAPIFormat'][$entry][$filterElements[$i]];
		} elseif($entry == "new") {
			$elementObject = $element_handler->get($filterElements[$i]);
			if(is_object($elementObject)) {
                // get defaults for certain element types, function needs expanding
                $defaultValueMap = getEntryDefaults($elementObject->getVar('id_form'),$entry);
                $compValue = isset($defaultValueMap[$elementObject->getVar('ele_id')]) ? $defaultValueMap[$elementObject->getVar('ele_id')] : "";
			} else {
				$compValue = "";
			}
		} else {
			$compValue = display($entryData[0], $filterElements[$i]);
		}
		if(is_array($compValue)) {
			if($thisOp == "==") {
				$thisOp = "LIKE";
			}
			if($thisOp == "!=") {
				$thisOp = "NOT LIKE";
			}
			$compValue = addslashes(implode(",",$compValue));
		} else {
			$compValue = addslashes($compValue);
		}
		$compValueQuoted = is_numeric($compValue) ? $compValue : "'".$compValue."'";

		$rawFilterTerms = $filterTerms[$i];
		$filterTermToUse = addslashes($filterTerms[$i]);

    // in PHP 8 can't use empty strings for comparison in stristr because it will always give a false positive
		if($thisOp == "LIKE" AND $filterTermToUse != '') {
			$evaluationCondition .= "stristr('".$compValue."', '".$filterTermToUse."')";
		} elseif($thisOp == "NOT LIKE" AND $filterTermToUse != '') {
			$evaluationCondition .= "!stristr('".$compValue."', '".$filterTermToUse."')";
    } elseif($thisOp == "LIKE") {
      $evaluationCondition .= $compValue ? 'FALSE' : 'TRUE';
    } elseif($thisOp == "NOT LIKE") {
      $evaluationCondition .= $compValue ? 'TRUE' : 'FALSE';
		} elseif($thisOp == "IN") {
			$cleanTerms = array();
			foreach(explode(',',$rawFilterTerms) as $ft) {
				$cleanTerms[] = str_replace("'", "\'", trim(htmlspecialchars_decode($ft, ENT_QUOTES), " \n\r\t\v\x00\"'"));
			}
			$evaluationCondition .= "in_array(".$compValueQuoted.", array('".implode("','",$cleanTerms)."'))";
		} else {
			$filterTermToUse = is_numeric($filterTermToUse) ? $filterTermToUse : "'".$filterTermToUse."'";
			$evaluationCondition .= "$compValueQuoted $thisOp $filterTermToUse";
		}
	}

	return $evaluationCondition;
}
/* ALTERED - 20100316 - freeform - jeff/julian - stop */

// this function takes an element object, or an element id number or handle (or framework handle with framework id, but that's deprecated)
function _formulize_returnElement($ele) {
  $element = "";
	if(is_object($ele)) {
		if(get_class($ele) == "formulizeElement" OR is_subclass_of($ele, 'formulizeElement')) {
			$element = $ele;
		} else {
			return false;
		}
	} else {
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		$element = $element_handler->get($ele);
	}
  return $element;
}

// THIS FUNCTION DRAWS IN A SAVE BUTTON AT THE POINT REQUESTED BY THE USER
// The displayElementRedirect is passed back to the page and is used to override the currently specified page, so the user can go to different content upon submitting the form
// Redirect must be a valid pageworks page number!
// Note that the URL does not change, even though the page contents do!
function displayElementSave($text="", $style="", $redirect_page="") {
	if($text == "") { $text = _pageworks_SAVE_BUTTON; }
	print "<input type=\"hidden\" name=\"displayElementRedirect\" value=\"$redirect_page\">\n";
	print "<input type=\"submit\" name=\"submitelementdisplayform\" id=\"submitelementdisplayform\" value=\"$text\" style=\"$style\">\n";
}

// FUNCTION FOR DISPLAYING A TEXT LINK OR BUTTON THAT APPENDS OR OVERWRITES VALUES FOR AN ELEMENT
// DO NOT USE WITH LINKED FIELDS!!!
//function displayButton($text, $ele, $value, $entry="new", $append="replace", $buttonOrLink="button") {
function displayButton($text, $ele, $value, $entry="new", $append="replace", $buttonOrLink="button", $formframe = "") {
	//echo "text: $text, ele: $ele, value: $value, entry: $entry, append: $append, buttonOrLink: $buttonOrLink, formframe: $formframe<br>";

	// 1. check for button or link
	// 2. write out the element
  $element = _formulize_returnElement($ele);
  if(!is_object($element)) {
    print "invalid_element";
  }

	if($prevValueThisElement = getElementValue($entry, $element->getVar('ele_id'), $element->getVar('id_form'))) {
		$prevValue = 1;
	} else {
		$prevValue = 0;
	}

	if($buttonOrLink == "button") {
		$curtime = time();
		print "<input type=button name=displayButton_$curtime id=displayButton_$curtime value=\"$text\" onclick=\"javascript:displayButtonProcess('$formframe', '$ele', '$entry', '$value', '$append', '$prevValue');return false;\">\n";
	} elseif($buttonOrLink == "link") {
		print "<a href=\"\" onclick=\"javascript:displayButtonProcess('$formframe', '$ele', '$entry', '$value', '$append', '$prevValue');return false;\">$text</a>\n";
	} else {
		exit("Error: invalid button or link option specified in a call to displayButton");
	}
}

/** THIS FUNCTION RETURNS THE CAPTION FOR AN ELEMENT
 *
 * DEPRECATED! Use $elementObject->getVar('ele_caption')
 *
 * @param string|int $formframe - not used
 * @param object|string|int $ele - an element object, or id, or handle
 * @return string The caption for the element
 */
// added June 25 2006 -- jwe
function displayCaption($formframe='', $ele=0) {
	$element = _formulize_returnElement($ele);
  if(!is_object($element)) {
    return "invalid_element";
  }
	return $element->getVar('ele_caption');
}

/** THIS FUNCTION RETURNS THE DESCRIPTION FOR AN ELEMENT
 *
 * DEPRECATED! Use $elementObject->getVar('ele_desc')
 *
 * @param string|int $formframe - not used
 * @param object|string|int $ele - an element object, or id, or handle
 * @return string The description (help text) for the element
 */
// THIS FUNCTION RETURNS THE description FOR AN ELEMENT
function displayDescription($formframe='', $ele=0) {
	$element = _formulize_returnElement($ele);
  if(!is_object($element)) {
    return "invalid_element";
  }
	return $element->getVar('ele_desc');
}
