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
	ob_end_clean();
	ob_end_clean(); // turn off two levels of output buffering, just in case (don't want extra stuff sent back with our ajax response)!
}

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

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

if(!$element_handler) {
	$element_handler =& xoops_getmodulehandler('elements', 'formulize');
}

$formulize_up = array(); // container for user profile info
$formulize_elementData = array(); // this array has multiple dimensions, in this order:  form id, entry id, element id.  "new" means a nea entry.  Multiple new entries will be recorded as new1, new2, etc
$formulize_subformBlankCues = array();
// loop through POST and catalogue everything that we need to do something with
foreach($_POST as $k=>$v) {

	if(substr($k, 0, 12) == "updateowner_" AND $v != "nochange") {
		$updateOwnerData = explode("_", $k);
		$updateOwnerFid = intval($updateOwnerData[1]);
		$updateOwnerEntryId = intval($updateOwnerData[2]);
		$updateOwnerNewOwnerId = intval($v);
	} elseif(substr($k, 0, 9) == "denosave_") { // handle no save elements
		$element_metadata = explode("_", $k);
		$element =& $element_handler->get($element_metadata[3]);
		$noSaveHandle = $element->getVar('ele_colhead') ? $element->getVar('ele_colhead') : $element->getVar('ele_caption');
		$noSaveHandle = str_replace(" ", "", ucwords(strtolower($noSaveHandle)));
		// note this will assign the raw value from POST to these globals.  It will not be human readable in many cases.
		$GLOBALS['formulizeEleSub_' . $noSaveHandle] = $v;
		$GLOBALS['formulizeEleSub_' . $element_metadata[3]] = $v;
		unset($element);
		
	} elseif(substr($k, 0, 9) == "desubform") { // handle blank subform elements
		$elementMetaData = explode("_", $k);
		$elementObject = $element_handler->get($elementMetaData[3]);
		$v = prepDataForWrite($elementObject, $v);
		if(($v === "" OR $v === "{WRITEASNULL}") AND $elementMetaData[2] == "new") { continue; } // don't store blank values for new entries, we don't want to write those (if desubform is used only for blank defaults, then it will always be "new" but we'll keep this as is for now, can't hurt)
		$subformElementKeyParts = explode("_", $k);
		$subformMetaDataParts = explode("x",substr($subformElementKeyParts[0], 9)); // the blank counter, and the subform element id number will be separated by an x, at the end of the first part of the key in POST, ie: desubform9x231
		$blankSubformCounter = $subformMetaDataParts[0];
		$blankSubformElementId = $subformMetaDataParts[1];
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
		if(isset($_POST["de_".$elementMetaData[1]."_".$elementMetaData[2]."_".$elementMetaData[3]])) {
			$elementObject = $element_handler->get($elementMetaData[3]);
			$v = prepDataForWrite($elementObject, $_POST["de_".$elementMetaData[1]."_".$elementMetaData[2]."_".$elementMetaData[3]]);
			$formulize_elementData[$elementMetaData[1]][$elementMetaData[2]][$elementMetaData[3]] = $v;
		} elseif(is_numeric($elementMetaData[1])) {
			$formulize_elementData[$elementMetaData[1]][$elementMetaData[2]][$elementMetaData[3]] = "{WRITEASNULL}"; // no value returned for this element that was included (cue was found) so we write it as blank to the db
		}		
	
	} elseif(substr($k, 0, 12) == "userprofile_") {
		$formulize_up[substr($k, 12)] = $v;
	}
}

// write all the user profile info
if(count($formulize_up)>0) {
	  $formulize_up['uid'] = $GLOBALS['userprofile_uid'];
		writeUserProfile($formulize_up, $uid);
}

// figure out proxy user situation
$creation_users = array();
if(isset($_POST['proxyuser'])) {
	foreach($_POST['proxyuser'] as $puser) {
		if($puser == "noproxy") { continue; }
		$creation_users[] = $puser;
	}
}
if(count($creation_users) == 0) { // no proxy users specified
	$creation_users[] = $uid;
}

// do all the actual writing through the formulize_writeEntry function
// log the new entry ids created
// log the notification events
$formulize_newEntryIds = array();
$formulize_newEntryUsers = array();
$formulize_allWrittenEntryIds = array();
$formulize_newSubformBlankElementIds = array();
$formulize_allWrittenFids = array();
$notEntriesList = array();
if(count($formulize_elementData) > 0 ) { // do security check if it looks like we're going to be writing things...
	$cururl = getCurrentURL();
	$module_handler =& xoops_gethandler('module');
	$config_handler =& xoops_gethandler('config');
  $formulizeModule =& $module_handler->getByDirname("formulize");
  $formulizeConfig =& $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
  $modulePrefUseToken = $formulizeConfig['useToken'];
	$useToken = $screen ? $screen->getVar('useToken') : $modulePrefUseToken; 
	if(isset($GLOBALS['xoopsSecurity']) AND $useToken) { // avoid security check for versions of XOOPS that don't have that feature, or for when it's turned off
		$GLOBALS['formulize_securityCheckPassed'] = true;
		if (!$GLOBALS['xoopsSecurity']->check() AND (!strstr($cururl, "modules/wfdownloads") AND !strstr($cururl, "modules/smartdownload"))) { // skip the security check if we're in wfdownloads/smartdownloads since that module should already be handling the security checking
			print "<b>Error: the data you submitted could not be saved in the database.</b>";
			$GLOBALS['formulize_securityCheckPassed'] = false;
			return false;
		}
	}
}

foreach($formulize_elementData as $elementFid=>$entryData) { // for every form we found data for...
	
	// figure out permissions on the forms
	$add_own_entry = $gperm_handler->checkRight("add_own_entry", $elementFid, $groups, $mid);
	$add_proxy_entries = $gperm_handler->checkRight("add_proxy_entries", $elementFid, $groups, $mid);
	$update_own_entry = $gperm_handler->checkRight("update_own_entry", $elementFid, $groups, $mid);
	$update_other_entries = $gperm_handler->checkRight("update_other_entries", $elementFid, $groups, $mid);
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$formulize_formObject = $form_handler->get($elementFid);
	$oneEntryPerGroupForm = $formulize_formObject->getVar('single') == "group" ? true : false;

	foreach($entryData as $currentEntry=>$values) { // for every entry in the form...
		if(substr($currentEntry, 0 , 3) == "new") { // handle entries in the form that are new...if there is more than one new entry in a dataset, they will be listed as new1, new2, new3, etc
			$subformElementId = 0;
			if(strstr($currentEntry, "x")) {
				$subformMetaDataParts = explode("x",$currentEntry);
				$subformElementId = $subformMetaDataParts[1];
			}
			if(strlen($currentEntry) > 3) { $currentEntry = "new"; } // remove the number from the end of any new entry flags that have numbers, which will be subform blanks (and not anything else?)
			foreach($creation_users as $creation_user) {
				if(($creation_user == $uid AND $add_own_entry) OR ($creation_user != $uid AND $add_proxy_entries)) { // only proceed if the user has the right permissions
					$writtenEntryId = formulize_writeEntry($values, $currentEntry, "", $creation_user, "", false); // last false causes setting ownership data to be skipped...it's more efficient for readelements to package up all the ownership info and write it all at once below.
					if(isset($formulize_subformBlankCues[$elementFid])) {
						$GLOBALS['formulize_subformCreateEntry'][$elementFid][] = $writtenEntryId;
					}
					$formulize_newEntryIds[$elementFid][] = $writtenEntryId; // log new ids (and all ids) and users for recording ownership info later
					$formulize_newEntryUsers[$elementFid][] = $creation_user;
					$formulize_allWrittenEntryIds[$elementFid][] = $writtenEntryId;
					$formulize_newSubformBlankElementIds[$elementFid][$writtenEntryId] = $subformElementId;
					if(!isset($formulize_allWrittenFids[$elementFid])) {
						$formulize_allWrittenFids[$elementFid] = $elementFid;
					}
					$notEntriesList['new_entry'][$elementFid][] = $writtenEntryId; // log the notification info
					writeOtherValues($writtenEntryId, $elementFid); // write the other values for this entry
					if($creation_user == 0) { // handle cookies for anonymous users
						setcookie('entryid_'.$elementFid, $writtenEntryId, time()+60*60*24*7, '/');	// the slash indicates the cookie is available anywhere in the domain (not just the current folder)				
						$_COOKIE['entryid_'.$elementFid] = $writtenEntryId;
					}
					afterSavingLogic($values, $writtenEntryId);
				}
			}
		} else { // handle existing entries...
			$owner = getEntryOwner($currentEntry, $elementFid);
			if(($owner == $uid AND $update_own_entry) OR ($owner != $uid AND ($update_other_entries OR ($update_own_entry AND $oneEntryPerGroupForm)))) { // only proceed if the user has the right permissions
				$writtenEntryId = formulize_writeEntry($values, $currentEntry);
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

unset($GLOBALS['formulize_afterSavingLogicRequired']); // now that saving is done, we don't need this any longer, so clean up

// set the ownership info of the new entries created...use a custom named handler, so we don't conflict with any other data handlers that might be using the more conventional 'data_handler' name, which can happen depending on the scope within which this file is included
foreach($formulize_newEntryIds as $newEntryFid=>$entries){
	$data_handler_for_owner_groups = new formulizeDataHandler($newEntryFid);
	$data_handler_for_owner_groups->setEntryOwnerGroups($formulize_newEntryUsers[$newEntryFid],$formulize_newEntryIds[$newEntryFid]);
	unset($data_handler_for_owner_groups);
}

// reassign entry ownership for an entry if the user requested that, and has permission
if(isset($updateOwnerFid) AND $gperm_handler->checkRight("update_entry_ownership", $updateOwnerFid, $groups, $mid)) {
	$data_handler_for_owner_updating = new formulizeDataHandler($updateOwnerFid);
	if(!$data_handler_for_owner_updating->setEntryOwnerGroups($updateOwnerNewOwnerId, $updateOwnerEntryId, true)) { // final true causes an update, instead of a normal setting of the groups from scratch.  Entry's creation user is updated too.
		print "<b>Error: could not update the entry ownership information.  Please report this to the webmaster right away, including which entry you were trying to update.</b>";		
	}
	$data_handler_for_owner_updating->updateCaches($updateOwnerEntryId);
}


// update the derived values for all forms that we saved data for, now that we've saved all the data from all the forms
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$mainFormHasDerived = false;
if($fid) {
	$mainFormObject = $form_handler->get($fid);
	$mainFormHasDerived = array_search("derived", $mainFormObject->getVar('elementTypes'));
}
$mainFormEntriesUpdatedForDerived = array();
$formsUpdatedInFramework = array();
// check all the entries that were written...
foreach($formulize_allWrittenEntryIds as $allWrittenFid=>$entries) {
	$derivedValueFound = false;
	$formObject = $form_handler->get($allWrittenFid);
	if(array_search("derived", $formObject->getVar('elementTypes'))) { // only bother if there is a derived value in the form
		$derivedValueFound = true;
		if(!$frid) { // if no framework in effect, then update each form's derived values in isolation
			foreach($entries as $thisEntry) {
				formulize_updateDerivedValues($thisEntry, $allWrittenFid);
			}
		}
	}
	if($frid AND ($mainFormHasDerived OR $derivedValueFound)) { // if there is a framework in effect, then update derived values across the entire framework...strong assumption would be that when a framework is in effect, all the forms being saved are related...if there are outliers they will not get their derived values updated!  We handle them below.
		foreach($entries as $thisEntry) {
			if($allWrittenFid == $fid) {
				$foundEntries['entries'][$fid] = $entries;
			} else {
				// Since this isn't the main form, then we need to check for which mainform entries match to the entries we're updating right now
				$foundEntries = checkForLinks($frid, array($allWrittenFid), $allWrittenFid, array($allWrittenFid=>array($thisEntry)));
			}
			foreach($foundEntries['entries'][$fid] as $mainFormEntry) {
				if(!in_array($mainFormEntry, $mainFormEntriesUpdatedForDerived)) {
					formulize_updateDerivedValues($mainFormEntry, $fid, $frid);
					$mainFormEntriesUpdatedForDerived[] = $mainFormEntry;
					if(!isset($formsUpdatedInFramework[$allWrittenFid])) { // if the form we're on has derived values, then flag it as one of the updated forms
						$formsUpdatedInFramework[$allWrittenFid] = $allWrittenFid;
					}
				}
			}
			if($allWrittenFid == $fid) {
				break; // we will now have processed all the $entries, so we can bail on this loop (when we're on the mainform, the $entries will be all the mainform entries, but when it's another form, then the entries might link to who knows what other entries in the main form.)
			}
		}
	}
	
	// check for things that we should be updating based on the framework in effect for any override screen that has been declared
	if($_POST['overridescreen'] AND $derivedValueFound) {
		$override_screen_handler = xoops_getmodulehandler('screen', 'formulize');
		$overrideScreenObject = $override_screen_handler->get($_POST['overridescreen']);
		$overrideFrid = $overrideScreenObject->getVar('frid');
		$overrideFid = $overrideScreenObject->getVar('fid');
		if($overrideFrid) {
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
	
}

// check for any forms that were written, that did not have derived values updated as part of the framework
if($frid) {
	$notUpdatedForms = array_diff($formulize_allWrittenFids, $formsUpdatedInFramework);
	foreach($notUpdatedForms as $thisFid) {
		$formObject = $form_handler->get($thisFid);
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

// set the variables that need to be in global space, just in case this file was included from inside a function, which can happen in some cases
$GLOBALS['formulize_newEntryIds'] = $formulize_newEntryIds;
$GLOBALS['formulize_newEntryUsers'] = $formulize_newEntryUsers;
$GLOBALS['formulize_allWrittenEntryIds'] = $formulize_allWrittenEntryIds;
$GLOBALS['formulize_newSubformBlankElementIds'] = $formulize_newSubformBlankElementIds;
$GLOBALS['formulize_readElementsWasRun'] = $formulize_readElementsWasRun;

// Update for Ajax Save
// Check if the request is an Ajax request (Since we have passed security check above, this is secured!)
if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
	// take special care for ajax request here
  
  	// create new security token
  	$token = $GLOBALS['xoopsSecurity']->createToken();
  	// print_r($formulize_newEntryIds);
  	// check if user has just made a new entry and get entryId and fid
  	if(count($formulize_newEntryIds) >= 1){
  		if(count($formulize_newEntryIds) >= 2){
  			// Should be here!
  			echo "ERROR: Unsupported Case: Two forms are modified when we add a single entry!? Check out line 362 of readelements";
			return;
		} else {
  			reset($formulize_newEntryIds);
  			$response_fid = key($formulize_newEntryIds);
			$entries = $formulize_newEntryIds[$response_fid];
			if(count($entries) >= 2 || count($entries) < 1){
				$num = count($entries);
				echo "num" . "$num";
				echo "ERROR: Unsupported Case: Two entries are modified when we modify a single form?! Check out line 368 of readelements";
				return;
			}
			$response_entryId = $entries[0];
  		}
  	}
  	// set fid and entryIds
  	if($response_fid){
  		// if new entry
  		$fid = $response_fid;
  		$entryId = $response_entryId;
  	} else {
  		// if an existing entry
  		$fid = $elementMetaData[1];
		$entryId = $elementMetaData[2];
  	}
  	$newMetaData = genFormMetaData($entryId, $fid, $member_handler);
	$formInstructionUpdate = genFormInstruction(1, $fid, $entryId, true, $owner, $uid, $groups, $mid);
	$formInstructionMakeNew = genFormInstruction(2, $fid, $entryId, true, $owner, $uid, $groups, $mid);

  	$proxylist = genOwnershipList($fid, $mid, $groups, $entryId);
	$ownershipListHtml = $proxylist->render();
  	$ownershipCaption = $proxylist->getCaption();
  	$response = array(
  					'new_xoops_token_request' => $token,
  					'fid' => $response_fid,
  					'entryId' => $response_entryId,
  					'metaData' => $newMetaData,
  					'formInstructionUpdate' => $formInstructionUpdate,
  					'formInstructionMakeNew' => $formInstructionMakeNew,
  					'ownershipListHtml' => $ownershipListHtml,
  					'ownershipCaption'=> $ownershipCaption,
			 	);
  	// send json back to ajax save!
  	echo json_encode($response);
}


// End of Update for Ajax Save
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

// this could be done a whole lot smarter, if we make a good way of figuring out if there's derived value elements in the form, and also if there are any formulas in the form/framework that use any of the elements that we have just saved values for
// but that's a whole lot of inspection we're not going to do right now.
function formulize_updateDerivedValues($entry, $fid, $frid="") {
	$GLOBALS['formulize_forceDerivedValueUpdate'] = true;
	getData($frid, $fid, $entry);
	unset($GLOBALS['formulize_forceDerivedValueUpdate']);
}


// THIS FUNCTION TAKES THE DATA PASSED BACK FROM THE USERPROFILE PART OF A FORM AND SAVES IT AS PART OF THE XOOPS USER PROFILE
function writeUserProfile($data, $uid) {

	// following code largely borrowed from edituser.php
	// values we receive:
	// name
	// email
	// viewemail
	// timezone_offset
	// password
	// vpass
	// attachsig
	// user_sig
	// umode
	// uorder
	// notify_method
	// notify_mode

	global $xoopsUser, $xoopsConfig;
	$config_handler =& xoops_gethandler('config');
	$xoopsConfigUser =& $config_handler->getConfigsByCat(XOOPS_CONF_USER);

	include_once XOOPS_ROOT_PATH . "/language/" . $xoopsConfig['language'] . "/user.php";

	$errors = array();
    if (!empty($data['uid'])) {
        $uid = intval($data['uid']);
    }
		
    if (empty($uid)) {
	redirect_header(XOOPS_URL,3,_US_NOEDITRIGHT);
        exit();
    } elseif(is_object($xoopsUser)) {
			if($xoopsUser->getVar('uid') != $uid) {
				redirect_header(XOOPS_URL,3,_US_NOEDITRIGHT);
				exit();	
			}
    }

    $myts =& MyTextSanitizer::getInstance();
    if ($xoopsConfigUser['allow_chgmail'] == 1) {
        $email = '';
        if (!empty($data['email'])) {
            $email = $myts->stripSlashesGPC(trim($data['email']));
        }
        if ($email == '' || !checkEmail($email)) {
            $errors[] = _US_INVALIDMAIL;
        }
    }
    $password = '';
    $vpass = '';
    if (!empty($data['password'])) {
     	  $password = $myts->stripSlashesGPC(trim($data['password']));
    }
    if ($password != '') {
     	  if (strlen($password) < $xoopsConfigUser['minpass']) {
           	$errors[] = sprintf(_US_PWDTOOSHORT,$xoopsConfigUser['minpass']);
        }
        if (!empty($data['vpass'])) { 
     	      $vpass = $myts->stripSlashesGPC(trim($data['vpass']));
        }
     	  if ($password != $vpass) {
            $errors[] = _US_PASSNOTSAME;
     	  }
    }
    if (count($errors) > 0) {
        echo '<div>';
        foreach ($errors as $er) {
            echo '<span style="color: #ff0000; font-weight: bold;">'.$er.'</span><br />';
        }
        echo '</div><br />';
    } else {
        $member_handler =& xoops_gethandler('member');
        $edituser =& $member_handler->getUser($uid);
        $edituser->setVar('name', $data['name']);
        if ($xoopsConfigUser['allow_chgmail'] == 1) {
            $edituser->setVar('email', $email, true);
        }
        $user_viewemail = (!empty($data['user_viewemail'])) ? 1 : 0;
        $edituser->setVar('user_viewemail', $user_viewemail);
        if ($password != '') {
            $edituser->setVar('pass', md5($password), true);
        }
        $edituser->setVar('timezone_offset', $data['timezone_offset']);
        $attachsig = !empty($data['attachsig']) ? 1 : 0;
	  $edituser->setVar('attachsig', $attachsig);
        $edituser->setVar('user_sig', xoops_substr($data['user_sig'], 0, 255));
        $edituser->setVar('uorder', $data['uorder']);
        $edituser->setVar('umode', $data['umode']);
        $edituser->setVar('notify_method', $data['notify_method']);
        $edituser->setVar('notify_mode', $data['notify_mode']);

        if (!$member_handler->insertUser($edituser)) {
            echo $edituser->getHtmlErrors();
						exit();
        }
    }

}

?>