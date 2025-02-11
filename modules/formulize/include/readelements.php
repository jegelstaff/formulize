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
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################

// display element data has the following format:
// de_[form]_[entry]_[elementid]
// form is the form id
// entry is the entry_id from the form's table
// elementid is the ele_id from the form elements table

// also out there...
// denosave_ which should be catalogued and then ignored
// desubformXxY_ where X is 0 through n, a number indicating which of the subform blank default entries this was, and Y is the element ID of the subform element that this blank came from, or 0 if it is not from an actual subform element (subforms will be drawn in by default at the end of the form whenever a framework/relationship is in effect, if no subform element involving that that subform has been drawn in the form already)
// userprofile_ sent back when a user profile form is being displayed (because regcodes has been applied to the system)

// proxy entries are indicated by the proxy entry box
// $_POST['proxyuser'] is an array of the proxy users selected
// default is 'noproxy'
// this would apply to all "new" entries received from this save

// This logic will process ALL form submissions from Formulize, all elements, no matter where they have appeared.

// Should always be included from the global scope!!  So all declared variables in here are in the global namespace.

if(isset($formulize_readElementsWasRun)) { return false; } // intended to make sure this file is only executed once.

if(!defined("XOOPS_ROOT_PATH")) {
	include_once "../../../mainfile.php"; // include this if it hasn't been already!  -- we can call readelements.php directly when saving data via ajax...jump up three levels to get it, because we assume that we're running here as the start of the process when such an ajax call is made.  But when a normal page loads, it won't find the mainfile that high up, because the root of the normal page load is the index.php file one directory higher than /include/
    icms::$logger->disableLogger();
	while(ob_get_level()) {
        ob_end_clean(); // no other stuff in the ajax response please
    }
}

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
include_once XOOPS_ROOT_PATH .'/modules/formulize/include/customCodeForApplications.php';

global $xoopsConfig;
// load the formulize language constants if they haven't been loaded already
if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
    include_once XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php";
} else {
    include_once XOOPS_ROOT_PATH."/modules/formulize/language/english/main.php";
}

// when called directly, no fid or frid will be set, because they are set in initialize.php as part of a normal Formulize page load. Therefore, we will take them from GET or POST as initialize.php would.
if(!isset($frid)) {
    $frid = ((isset( $_GET['frid'])) AND is_numeric( $_GET['frid'])) ? intval( $_GET['frid']) : "" ;
    $frid = ((isset($_POST['frid'])) AND is_numeric($_POST['frid'])) ? intval($_POST['frid']) : $frid ;
}
if(!isset($fid)) {
    $fid = ((isset( $_GET['fid'])) AND is_numeric( $_GET['fid'])) ? intval( $_GET['fid']) : "" ;
    $fid = ((isset($_POST['fid'])) AND is_numeric($_POST['fid'])) ? intval($_POST['fid']) : $fid ;
}

// if we're being called from pageworks, or elsewhere, then certain values won't be set so we'll need to check for them in other ways...
if(!$gperm_handler) {
	$gperm_handler =& xoops_gethandler('groupperm');
}
if(!isset($mid)) {
	$mid = getFormulizeModId();
}

if(!$myts) { $myts =& MyTextSanitizer::getInstance(); }

//$formulize_submittedElementCaptions = array(); // put into global scope and pulled down by readform.php when determining what elements have been submitted, so we don't blank data that is sent this way
global $xoopsUser;
$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS); // for some reason, even though this is set in pageworks index.php file, depending on how/when this file gets executed, it can have no value (in cases where there are pageworks blocks on pageworks pages, for instance?!)
$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
$uid = isset($GLOBALS['userprofile_uid']) ? $GLOBALS['userprofile_uid'] : $uid; // if the userprofile form is in play and a new user has been set, then use that uid

if(!isset($element_handler) OR !$element_handler) {
	$element_handler = xoops_getmodulehandler('elements', 'formulize');
}

$formulize_elementData = array(); // this array has multiple dimensions, in this order:  form id, entry id, element id.  "new" means a nea entry.  Multiple new entries will be recorded as new1, new2, etc
$formulize_subformBlankCues = array();
// loop through POST and catalogue everything that we need to do something with
foreach($_POST as $k=>$v) {

    // record all the present entries, which are sent from standard rendered forms (created in the writeHiddenSettings function)
    if(substr($k, 0, 5) == 'form_' AND substr($k, -15) == '_rendered_entry') {
        $presentFid = intval(str_replace('form_','',str_replace('_rendered_entry','',$k)));
        $GLOBALS['formulize_allPresentEntryIds'][$presentFid] = $v;
    }

	if(substr($k, 0, 12) == "updateowner_" AND $v != "nochange") {
		$updateOwnerData = explode("_", $k);
		$updateOwnerFid = intval($updateOwnerData[1]);
		$updateOwnerEntryId = intval($updateOwnerData[2]);
		$updateOwnerNewOwnerId = intval($v);
	} elseif(substr($k, 0, 9) == "denosave_") { // handle no save elements
		$element_metadata = explode("_", $k);
		$element = $element_handler->get($element_metadata[3]);
		$noSaveHandle = $element->getVar('ele_colhead') ? $element->getVar('ele_colhead') : $element->getVar('ele_caption');
		$noSaveHandle = str_replace(" ", "", ucwords(strtolower($noSaveHandle)));
		// note this will assign the raw value from POST to these globals.  It will not be human readable in many cases.
		$GLOBALS['formulizeEleSub_' . $noSaveHandle] = $v;
		$GLOBALS['formulizeEleSub_' . $element_metadata[3]] = $v;
		unset($element);

	} elseif(substr($k, 0, 9) == "desubform") { // handle blank subform elements
		$elementMetaData = explode("_", $k);
		$elementObject = $element_handler->get($elementMetaData[3]);
		$subformElementKeyParts = explode("_", $k);
		$subformMetaDataParts = explode("x",substr($subformElementKeyParts[0], 9)); // the blank counter, and the subform element id number will be separated by an x, at the end of the first part of the key in POST, ie: desubform9x231
		$blankSubformCounter = $subformMetaDataParts[0];
		$blankSubformElementId = $subformMetaDataParts[1];
        $v = prepDataForWrite($elementObject, $v, "new", $blankSubformCounter);
		if(($v === "" OR $v === "{WRITEASNULL}") AND $elementMetaData[2] == "new") { continue; } // don't store blank values for new entries, we don't want to write those (if desubform is used only for blank defaults, then it will always be "new" but we'll keep this as is for now, can't hurt)
		$formulize_elementData[$elementMetaData[1]][$elementMetaData[2].$blankSubformCounter."x".$blankSubformElementId][$elementMetaData[3]] = $v;
		if(!isset($formulize_subformBlankCues[$elementMetaData[1]])) {
			$formulize_subformBlankCues[$elementMetaData[1]] = $elementMetaData[1]; // we will watch for entries being written to this form, and store the resulting entries in global space so we can synch them later
		}

		// also...the entry id that the new entries received was stored after writing in this array:
		// this is the subform id, and the subform placeholder, which must receive the last insert id when it's values are saved
		//$GLOBALS['formulize_subformCreateEntry'][$element->getVar('id_form')][$desubformEntryIndex]

	} elseif(substr($k, 0, 6) == "decue_") {
		// store values according to form, entry and element ID
		// prep them all for writing
		$elementMetaData = explode("_", $k);
      $elementObject = $element_handler->get($elementMetaData[3]);
		if(isset($_POST["de_".$elementMetaData[1]."_".$elementMetaData[2]."_".$elementMetaData[3]])) {
      $v = prepDataForWrite($elementObject, $_POST["de_".$elementMetaData[1]."_".$elementMetaData[2]."_".$elementMetaData[3]], $elementMetaData[2]);
			$formulize_elementData[$elementMetaData[1]][$elementMetaData[2]][$elementMetaData[3]] = $v;
		} elseif(is_numeric($elementMetaData[1]) AND $elementObject->getVar('ele_type') != 'anonPasscode') {
			$formulize_elementData[$elementMetaData[1]][$elementMetaData[2]][$elementMetaData[3]] = "{WRITEASNULL}"; // no value returned for this element that was included (cue was found) so we write it as blank to the db
		}

	}
}

// figure out proxy user situation
$creation_users = array();
if(isset($_POST['proxyuser'])) {
	foreach($_POST['proxyuser'] as $puser) {
		if($puser == "noproxy") { continue; }
		$creation_users[] = $puser;
	}
}
if(count((array) $creation_users) == 0) { // no proxy users specified
	$creation_users[] = $uid;
}

// do all the actual writing through the formulize_writeEntry function
// log the new entry ids created
// log the notification events
$formulize_newEntryIds = array();
$formulize_newEntryUsers = array();
$formulize_allWrittenEntryIds = array();
$formulize_allSubmittedEntryIds = array();
$formulize_newSubformBlankElementIds = array();
$formulize_allWrittenFids = array();
$notEntriesList = array();
if(count((array) $formulize_elementData) > 0 ) { // do security check if it looks like we're going to be writing things...
	$cururl = getCurrentURL();
	$module_handler =& xoops_gethandler('module');
	$config_handler =& xoops_gethandler('config');
  $formulizeModule =& $module_handler->getByDirname("formulize");
  $formulizeConfig =& $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
  $modulePrefUseToken = $formulizeConfig['useToken'];
	$useToken = $screen ? $screen->getVar('useToken') : $modulePrefUseToken;
	if(isset($GLOBALS['xoopsSecurity']) AND $useToken) { // avoid security check for versions of XOOPS that don't have that feature, or for when it's turned off
		$GLOBALS['formulize_securityCheckPassed'] = false;
		if ($GLOBALS['xoopsSecurity']->check()) {
			$GLOBALS['formulize_securityCheckPassed'] = true;
		} elseif($GLOBALS['xoopsSecurity']->validateToken($_POST["detoken_".$elementMetaData[1]."_".$elementMetaData[2]."_".$elementMetaData[3]], name: 'formulize_display_element_token')) {
			$GLOBALS['formulize_securityCheckPassed'] = true;
		}
		if($GLOBALS['formulize_securityCheckPassed'] == false) {
			print "<b>Error: the data you submitted could not be saved in the database.</b>";
			return false;
		}
	}
}

	$form_handler = xoops_getmodulehandler('forms', 'formulize');

foreach($formulize_elementData as $elementFid=>$entryData) { // for every form we found data for...

	$formulize_formObject = $form_handler->get($elementFid);
    // TODO: should the one-entry-per-group permission be checked in the permissions handler instead?
	$oneEntryPerGroupForm = ($formulize_formObject->getVar('single') == "group");

    // for every entry in the form...
    foreach($entryData as $currentEntry => $values) {

				// FIRST, try to handle any one to one situations where new entries have been written...
				// If the entry is new, check if a newvalue was submitted from a counterpart form in a one to one connection to this one, use that entry instead of 'new'
				if(substr($currentEntry, 0 , 3) == "new") {
					checkForLinks($frid, array(), $elementFid, entries: false, unified_display: true); // sets up the $GLOBALS metadata we loop through next, which will be an array of arrays, each having keys fid, keyself, keyother, common
					foreach($GLOBALS['formulize_checkForLinks_oneToOneMetaData'] as $linkData) {
						// if the form for this link was submitted this pageload...
						if(in_array($linkData['fid'], array_keys($formulize_elementData))) {
							// figure out the fid, entry id, and element id that we should lookup in $_POST to see if it has the 'newvalue:' prefix
							// assume only one entry (first) submitted to be written for the fid is all we care about
							$lookupFid = $linkData['fid'];
							$lookupEntryId = array_key_first($formulize_elementData[$lookupFid]);
							$lookupElementId = $linkData['keyself'];
							$keyselfElementObject = $element_handler->get($lookupElementId);
							// if the keyself element is a linked element and it wrote a new value into the target form
							if($keyselfElementObject->isLinked AND substr($_POST["de_{$lookupFid}_{$lookupEntryId}_{$lookupElementId}"], 0, 9) === 'newvalue:') {
								if($linkData['common']) {
									// write the value from the key element in the other form, as the value for the key element in this form
									$values[$linkData['keyother']] = $formulize_elementData[$lookupFid][$lookupEntryId][$lookupElementId];
								} else {
									// write the current data ($values) that we'll be saving next, into that entry which was just written in response to the 'newvalue' operation, instead of creating another new entry in this form
									$currentEntry = $formulize_elementData[$lookupFid][$lookupEntryId][$lookupElementId];
								}
								break; // go with the first one we found
							}
						}
					}
				}

        if(substr($currentEntry, 0 , 3) == "new") {
            // handle entries in the form that are new. if there is more than one new entry, they will be listed as new1, new2, new3, etc
			$subformElementId = 0;
			if(strstr($currentEntry, "x")) {
				$subformMetaDataParts = explode("x",$currentEntry);
				$subformElementId = $subformMetaDataParts[1];
                $subformBlankCounter = str_replace("new", "", $subformMetaDataParts[0]);
			} else {
                $subformBlankCounter = null;
            }
            if (strlen($currentEntry) > 3) {
                // remove the number from the end of any new entry flags that have numbers, which will be subform blanks (and not anything else?)
                $currentEntry = "new";
            }
			foreach($creation_users as $creation_user) {
                if (formulizePermHandler::user_can_edit_entry($elementFid, $creation_user, $currentEntry)) {
					if($writtenEntryId = formulize_writeEntry($values, $currentEntry, "", $creation_user, "", false)) { // last false causes setting ownership data to be skipped...it's more efficient for readelements to package up all the ownership info and write it all at once below.
                        if(isset($formulize_subformBlankCues[$elementFid])) {
                            $GLOBALS['formulize_subformCreateEntry'][$elementFid][] = $writtenEntryId;
                        }
                        $formulize_newEntryIds[$elementFid][] = $writtenEntryId; // log new ids (and all ids) and users for recording ownership info later
                        if(isset($GLOBALS['formulize_overrideProxyUser'])) {
                            $formulize_newEntryUsers[$elementFid][] = intval($GLOBALS['formulize_overrideProxyUser']);
                            unset($GLOBALS['formulize_overrideProxyUser']);
                        } else {
                            $formulize_newEntryUsers[$elementFid][] = $creation_user;
                        }
                        $formulize_allWrittenEntryIds[$elementFid][] = $writtenEntryId;
                        $formulize_allSubmittedEntryIds[$elementFid][] = $writtenEntryId;
                        $formulize_newSubformBlankElementIds[$elementFid][$writtenEntryId] = $subformElementId;
                        if(!isset($formulize_allWrittenFids[$elementFid])) {
                            $formulize_allWrittenFids[$elementFid] = $elementFid;
                        }
                        $notEntriesList['new_entry'][$elementFid][] = $writtenEntryId; // log the notification info
                        writeOtherValues($writtenEntryId, $elementFid, $subformBlankCounter); // write the other values for this entry
                        if($creation_user == 0) { // handle cookies for anonymous users
                            setcookie('entryid_'.$elementFid, $writtenEntryId, time()+60*60*24*7, '/');	// the slash indicates the cookie is available anywhere in the domain (not just the current folder)
                            $_COOKIE['entryid_'.$elementFid] = $writtenEntryId;
                        }
                        afterSavingLogic($values, $writtenEntryId);
                    }
                }
			}
		} elseif($currentEntry > 0) {
            // save changes to existing elements
            // TODO: should this use $uid or a proxy user setting?
            if (formulizePermHandler::user_can_edit_entry($elementFid, $uid, $currentEntry)) {
                $formulize_allSubmittedEntryIds[$elementFid][] = $currentEntry;
				if($writtenEntryId = formulize_writeEntry($values, $currentEntry)) {
                    $formulize_allWrittenEntryIds[$elementFid][] = $writtenEntryId; // log the written id
                    if(!isset($formulize_allWrittenFids[$elementFid])) {
                        $formulize_allWrittenFids[$elementFid] = $elementFid;
                    }
                    $notEntriesList['update_entry'][$elementFid][] = $writtenEntryId; // log the notification info
                    writeOtherValues($writtenEntryId, $elementFid); // write the other values for this entry
                    afterSavingLogic($values, $writtenEntryId);
                }
            }
        }
    }
}

unset($GLOBALS['formulize_afterSavingLogicRequired']); // now that saving is done, we don't need this any longer, so clean up

// check if we should take into account any defaults set for a form screen that we have just saved an entry from
// and if none, then check for fundamental filters on a list screen and use those
// CAN WE MOVE INTO DATA HANDLER SO ON BEFORE SAVE IS AWARE OF CONTEXT
// need to also take these into account when displaying forms, as if they are element defaults!
// May want to refactor to use formulize_renderedEntryScreen, rather than deducing from settings and making assumptions?
$fundamentalDefaults = array();
$viewEntryScreenObject = false;
if(isset($_POST['formulize_renderedEntryScreen']) AND $screenToLoad = determineViewEntryScreen($screen, $fid)) {
    $ves_handler = xoops_getmodulehandler('screen', 'formulize');
    if($viewEntryScreenObject = $ves_handler->get($screenToLoad)) {
        if($viewEntryScreenObject->getVar('type') != 'form' AND $viewEntryScreenObject->getVar('type') != 'multiPage') { // only work with form screens or multipage screens now
            $viewEntryScreenObject = false;
        }
    }
}
if(!$viewEntryScreenObject AND $screen AND (is_a($screen, 'formulizeFormScreen') OR is_a($screen, 'formulizeMultiPageFormScreen'))) {
    $viewEntryScreenObject = $screen;
}
if($viewEntryScreenObject) {
    $viewEntryScreenDefaults = $viewEntryScreenObject->getVar('elementdefaults');
    if(is_array($viewEntryScreenDefaults) AND count((array) $viewEntryScreenDefaults) > 0) {
        foreach($viewEntryScreenDefaults as $elementId=>$defaultValue) {
            if($elementObject = $element_handler->get($elementId)) {
                // refactor getFilterValuesForEntry to work with this structure of inputs too...?
                $fundamentalDefaults[$elementObject->getVar('id_form')][$elementObject->getVar('ele_handle')] = $defaultValue;
            }
        }
    }
}
if(count((array) $fundamentalDefaults) == 0 AND $screen AND is_a($screen, 'formulizeListOfEntriesScreen')) {
    $fundamental_filters = $screen->getVar('fundamental_filters')    ;
    if(is_array($fundamental_filters)) {
        $fundamentalDefaults = getFilterValuesForEntry($fundamental_filters);
    }
}
// set the ownership info of the new entries created...use a custom named handler, so we don't conflict with any other data handlers that might be using the more conventional 'data_handler' name, which can happen depending on the scope within which this file is included
// plus set any fundamental filters on new entries
foreach($formulize_newEntryIds as $newEntryFid=>$entries){
	$data_handler_for_owner_groups = new formulizeDataHandler($newEntryFid);
	$data_handler_for_owner_groups->setEntryOwnerGroups($formulize_newEntryUsers[$newEntryFid],$formulize_newEntryIds[$newEntryFid]);
	unset($data_handler_for_owner_groups);
    // first, set any fundamental filters if any
    if(isset($fundamentalDefaults[$newEntryFid])) {
        foreach($entries as $thisEntry) {
            formulize_writeEntry($fundamentalDefaults[$newEntryFid],$thisEntry);
        }
    }
}

// reassign entry ownership for an entry if the user requested that, and has permission
if(isset($updateOwnerFid) AND $gperm_handler->checkRight("update_entry_ownership", $updateOwnerFid, $groups, $mid)) {
	updateOwnerForFormEntry($updateOwnerFid, $updateOwnerNewOwnerId, $updateOwnerEntryId);

    // check if any other form that was submitted, is used in a subform element where the subform entries are supposed to be owned by the owner of the mainform entry
    // if so, reassign the submitted entries from that form too
    $formulize_formObject = $form_handler->get($updateOwnerFid);
    $elementTypes = $formulize_formObject->getVar('elementTypes');
    foreach(array_keys($elementTypes, 'subform') as $subformElementId) {
        $subformElement = $element_handler->get($subformElementId);
        $subformEleValue = $subformElement->getVar('ele_value');
        if(isset($_POST['form_'.$subformEleValue[0].'_rendered_entry']) AND $subformEleValue[5] == 1) { // this subform was part of the page, and it's supposed to have the same owner as the mainform entry
            foreach($_POST['form_'.$subformEleValue[0].'_rendered_entry'] as $subformEntryId) {
                updateOwnerForFormEntry($subformEleValue[0], $updateOwnerNewOwnerId, $subformEntryId);
            }
        }
	}
}

// set the variables that need to be in global space, just in case this file was included from inside a function, which can happen in some cases
$GLOBALS['formulize_newEntryIds'] = $formulize_newEntryIds;
$GLOBALS['formulize_newEntryUsers'] = $formulize_newEntryUsers;
$GLOBALS['formulize_allWrittenEntryIds'] = $formulize_allWrittenEntryIds;
$GLOBALS['formulize_allSubmittedEntryIds'] = $formulize_allSubmittedEntryIds;
$GLOBALS['formulize_newSubformBlankElementIds'] = $formulize_newSubformBlankElementIds;

if(isset($_POST['overridescreen']) AND $_POST['overridescreen'] AND is_numeric($_POST['overridescreen'])) {
    $override_screen_handler = xoops_getmodulehandler('screen', 'formulize');
	$overrideScreenObject = $override_screen_handler->get($_POST['overridescreen']);
    $overrideFrid = $overrideScreenObject->getVar('frid');
	$overrideFid = $overrideScreenObject->getVar('fid');
} elseif($viewEntryScreenObject) {
    $overrideFrid = $viewEntryScreenObject->getVar('frid');
    $overrideFid = $viewEntryScreenObject->getVar('fid');
} else {
    $overrideFrid = $frid;
    $overrideFid = $fid;
}
synchExistingSubformEntries($overrideFrid);
synchSubformBlankDefaults();

if(isset($notEntriesList['update_entry'])) {
    foreach($notEntriesList['update_entry'] as $updateFid=>$updateEntries) {
        $GLOBALS['formulize_snapshotRevisions'][$updateFid] = formulize_getCurrentRevisions($updateFid, $updateEntries);
    }
}

// update the derived values for all forms that we saved data for, now that we've saved all the data from all the forms
// SHOULD CHECK OVERRIDE FRID FOR DERIVED VALUES TOO?
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$mainFormHasDerived = false;
if($fid) {
	$mainFormObject = $form_handler->get($fid, true); // true causes all elements to be gathered, including ones that are not displayed to the users
	$mainFormHasDerived = array_search("derived", $mainFormObject->getVar('elementTypes'));
}
	$derivedValueFound = false;
if(!$mainFormHasDerived AND $frid) {
            // check if any form in the relationship has derived values
            include_once XOOPS_ROOT_PATH ."/modules/formulize/class/frameworks.php";
            $relationshipObject = new formulizeFramework($frid);
            foreach($relationshipObject->getVar('fids') as $relationshipFid) {
                if($relationshipFid == $fid) { continue; } // we know the main form has no derived values already
                $formObject = $form_handler->get($relationshipFid, true); // true causes all elements to be gathered, including ones that are not displayed to the users
                if(array_search("derived", $formObject->getVar('elementTypes'))) {
                    $derivedValueFound = true;
                    break;
                }
            }
        }

// NOTE:
// Parsing and processing derived values could be done a whole lot smarter, if we make a good way of figuring out if there's derived value elements in the form, and also if there are any formulas in the form/framework that use any of the elements that we have just saved values for
// but that's a whole lot of inspection we're not going to do right now.
// Basically, the entire saving routine would be nicer if it were smart about not saving data that hasn't changed!


$mainFormEntriesUpdatedForDerived = array();
$formsUpdatedInFramework = array();
// check all the entries that were written...
foreach($formulize_allWrittenEntryIds as $allWrittenFid=>$entries) {
    if(!$frid) {
        $formObject = $form_handler->get($allWrittenFid, true); // true causes all elements to be gathered, including ones that are not displayed to the users
        if(array_search("derived", $formObject->getVar('elementTypes'))) { // only bother if there is a derived value in the form
            $updateFrid = $allWrittenFid == $overrideFid ? $overrideFrid : $frid;
			foreach($entries as $thisEntry) {
				formulize_updateDerivedValues($thisEntry, $allWrittenFid, $updateFrid);
			}
		}
	} else {
        if($mainFormHasDerived OR $derivedValueFound) { // if there is a framework in effect, then update derived values across the entire framework...strong assumption would be that when a framework is in effect, all the forms being saved are related...if there are outliers they will not get their derived values updated!  We handle them below.
            foreach($entries as $thisEntry) {
                if($allWrittenFid == $fid) {
                    $foundEntries['entries'][$fid] = $entries;
                } else {
                    // Since this isn't the main form, then we need to check for which mainform entries match to the entries we're updating right now
                    $foundEntries = checkForLinks($frid, array($allWrittenFid), $allWrittenFid, array($allWrittenFid=>array($thisEntry)));
                }
                foreach($foundEntries['entries'][$fid] as $mainFormEntry) {
                    if(!in_array($mainFormEntry, $mainFormEntriesUpdatedForDerived)
                       AND $mainFormEntry
                       AND (
                        (isset($formulize_allSubmittedEntryIds[$fid]) AND in_array($mainFormEntry, $formulize_allSubmittedEntryIds[$fid]))
                        OR (isset($GLOBALS['formulize_allPresentEntryIds']) AND isset($GLOBALS['formulize_allPresentEntryIds'][$fid]) AND in_array($mainFormEntry, $GLOBALS['formulize_allPresentEntryIds'][$fid]))
                        )
                      ) {
                        // regarding final in_array checks... // if we have deduced the mainform entry, then depending on the structure of the relationship, it is possible that if checkforlinks was used above, it would return entries that were not part of pageload, in which case we must ignore them!!
                        // note that allPresentEntryIds will not exist if this is a disembodied rendering. Only standard renderings through formDisplay invoke writeHiddenSettings, which in turn causes the values in _POST which become that array
                        formulize_updateDerivedValues($mainFormEntry, $fid, $frid);
                        $mainFormEntriesUpdatedForDerived[] = $mainFormEntry;
                    }
                    if(!isset($formsUpdatedInFramework[$allWrittenFid]) AND ( (isset($formulize_allSubmittedEntryIds[$fid]) AND in_array($mainFormEntry, $formulize_allSubmittedEntryIds[$fid])) OR (isset($GLOBALS['formulize_allPresentEntryIds']) AND isset($GLOBALS['formulize_allPresentEntryIds'][$fid]) AND in_array($mainFormEntry, $GLOBALS['formulize_allPresentEntryIds'][$fid])) )) { // if the form we're on has derived values, then flag it as one of the updated forms, since at least one matching mainform entry was found and will have been updated including the framework
                        $formsUpdatedInFramework[$allWrittenFid] = $allWrittenFid;
                    }
                }
            }
        }
    }

	// check for things that we should be updating based on the framework in effect for any override screen that has been declared...should we be doing the same lookup of entries in checkForLinks as we do above in normal procedure, so we update only based on mainform(s) in the overrideFrid??
	if($overrideFrid AND $overrideFrid != $frid AND $derivedValueFound) {
        if($allWrittenFid == $overrideFid) {
            foreach($entries as $thisEntry) {
                formulize_updateDerivedValues($thisEntry, $allWrittenFid, $overrideFrid);
                if(!isset($formsUpdatedInFramework[$allWrittenFid])) { // if the form we're on has derived values, then flag it as one of the updated forms
                    $formsUpdatedInFramework[$allWrittenFid] = $allWrittenFid;
                }
            }
        }
	}

}

// check for any forms that were written, that did not have derived values updated as part of the framework
if($frid) {
	$notUpdatedForms = array_diff($formulize_allWrittenFids, $formsUpdatedInFramework);
	foreach($notUpdatedForms as $thisFid) {
		$formObject = $form_handler->get($thisFid, true); // true causes all elements to be gathered, including ones that are not displayed to the users
		if(array_search("derived", $formObject->getVar('elementTypes'))) {
			foreach($formulize_allWrittenEntryIds[$thisFid] as $thisEntry) {
				formulize_updateDerivedValues($thisEntry, $thisFid);
			}
		}
	}
}

// send notifications
foreach($notEntriesList as $notEvent=>$notDetails) {
	foreach($notDetails as $notFid=>$notEntries) {
		$notEntries = array_unique($notEntries);
		sendNotifications($notFid, $notEvent, $notEntries);
	}
}

$formulize_readElementsWasRun = true; // flag that will prevent this from running again
$GLOBALS['formulize_readElementsWasRun'] = $formulize_readElementsWasRun; // just in case we're not in globals scope at the moment

// if there is more than one form, try to make the 1-1 links
if($overrideFrid) {
    foreach($formulize_allSubmittedEntryIds as $this_fid => $entryIds) {
        formulize_makeOneToOneLinks($overrideFrid, $this_fid);
    }
}

return $formulize_allWrittenEntryIds;

// this function handles triggering the after Saving Logic of custom elements after each entry is written to the database
// values are the element handle->data value pairs that were written to the database
// entry_id is the entry id that was just written to the database
function afterSavingLogic($values,$entry_id) {
	if(isset($GLOBALS['formulize_afterSavingLogicRequired'])) { // elements must declare at the prepDataForWrite stage if they have after saving logic required
		$element_handler = xoops_getmodulehandler('elements','formulize');
		foreach($GLOBALS['formulize_afterSavingLogicRequired'] as $elementId=>$thisAfterSavingRequestType) {
			// if the entry that was just written was one that included the element type that we need after saving logic for, then go for it
			if(isset($values[$elementId])) {
				$elementTypeHandler = xoops_getmodulehandler($thisAfterSavingRequestType."Element", "formulize");
				$elementTypeHandler->afterSavingLogic($values[$elementId],$elementId,$entry_id);
			}
		}
	}
}

// THIS FUNCTION UPDATES THE OWNERSHIP INFORMATION ON A GIVEN FORM ENTRY
function updateOwnerForFormEntry($updateOwnerFid, $updateOwnerNewOwnerId, $updateOwnerEntryId) {
    $data_handler_for_owner_updating = new formulizeDataHandler($updateOwnerFid);
	if(!$data_handler_for_owner_updating->setEntryOwnerGroups($updateOwnerNewOwnerId, $updateOwnerEntryId, true)) { // final true causes an update, instead of a normal setting of the groups from scratch.  Entry's creation user is updated too.
		print "<b>Error: could not update the entry ownership information.  Please report this to the webmaster right away, including which entry you were trying to update.</b>";
	}
	$data_handler_for_owner_updating->updateCaches($updateOwnerEntryId);
}
