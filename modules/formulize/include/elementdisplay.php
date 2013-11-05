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
function displayElement($formframe="", $ele, $entry="new", $noSave = false, $screen=null, $prevEntry=null, $renderElement=true, $profileForm = null, $groups="") {

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
	if($entry == "" OR $subformCreateEntry) { $entry = "new"; }

	$element = _formulize_returnElement($ele, $formframe);
	if(!is_object($element)) {
		return "invalid_element";
	}

	$deprefix = $noSave ? "denosave_" : "de_";
	$deprefix = $subformCreateEntry ? "desubform".$subformEntryIndex."x".$subformElementId."_" : $deprefix; // need to pass in an entry index so that all fields in the same element can be collected
	if($noSave AND !is_bool($noSave)) {
		$renderedElementName = $noSave; // if we've passed a specific string with $noSave, then we want to use that to override the name of the element, because the developer wants to pick up that form value later after submission
		$noSave = true;
	} else {
		$renderedElementName = $deprefix.$element->getVar('id_form').'_'.$entry.'_'.$element->getVar('ele_id');
	}
	
	global $xoopsUser;
  if(!$groups) { // groups might be passed in, which covers the case of the registration form and getting the groups from the registration code
    $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
  }
	$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
	$username = $xoopsUser ? $xoopsUser->getVar('uname') : "unknown user";
	static $cachedEntryOwners = array();
	if(!isset($cachedEntryOwners[$element->getVar('id_form')][$entry])) {
		$cachedEntryOwners[$element->getVar('id_form')][$entry] = getEntryOwner($entry, $element->getVar('id_form'));
	}
	$owner = $cachedEntryOwners[$element->getVar('id_form')][$entry];
	$mid = getFormulizeModId();

	
	static $cachedViewPrivate = array();
	static $cachedUpdateOwnEntry = array();
	$gperm_handler = xoops_gethandler('groupperm');
	if(!isset($cachedViewPrivate[$element->getVar('id_form')])) {
		$cachedViewPrivate[$element->getVar('id_form')] = $gperm_handler->checkRight("view_private_elements", $element->getVar('id_form'), $groups, $mid);	
		$cachedUpdateOwnEntry[$element->getVar('id_form')] = $gperm_handler->checkRight("update_own_entry", $element->getVar('id_form'), $groups, $mid);	
	}
	$view_private_elements = $cachedViewPrivate[$element->getVar('id_form')];
	$update_own_entry = $cachedUpdateOwnEntry[$element->getVar('id_form')];
	
	// check if the user is normally able to view this element or not, by checking their groups against the display groups -- added Nov 7 2005
	// messed up.  Private should not override the display settings.  And the $entry should be checked against the security check first to determine whether the user should even see this entry in the first place.
	$display = $element->getVar('ele_display');
	$private = $element->getVar('ele_private');
	$member_handler = xoops_gethandler('member');
	$single_result = getSingle($element->getVar('id_form'), $uid, $groups, $member_handler, $gperm_handler, $mid);
	$groupEntryWithUpdateRights = ($single_result['flag'] == "group" AND $update_own_entry AND $entry == $single_result['entry']) ? true : false;

	// need to test whether the display setting should be checked first?!  Order of checks here looks wrong.  JWE Nov 25 2010
	if($private AND $uid != $owner AND !$groupEntryWithUpdateRights AND $entry != "new") {
		$allowed = $view_private_elements ? 1 : 0;
	} elseif(strstr($display, ",")) {
		$display_groups = explode(",", $display);
		$allowed = array_intersect($groups, $display_groups) ? 1 : 0;
	} elseif($display == 1) {
		$allowed = 1;	
	} else {
		$allowed = 0;
	}

	if($prevEntry==null) { // preferable to pass in prevEntry!
		$prevEntry = getEntryValues($entry, "", $groups, $element->getVar('id_form'), "", $mid, $uid, $owner, $groupEntryWithUpdateRights);			
	}

	$elementFilterSettings = $element->getVar('ele_filtersettings');
	if($allowed AND count($elementFilterSettings[0]) > 0) {
		// cache the filterElements for this element, so we can build the right stuff with them later in javascript, to make dynamically appearing elements
		$GLOBALS['formulize_renderedElementHasConditions'][$renderedElementName] = $elementFilterSettings[0];
		
		// need to check if there's a condition on this element that is met or not
		static $cachedEntries = array();
		if($entry != "new") {
			if(!isset($cachedEntries[$element->getVar('id_form')][$entry])) {
				$cachedEntries[$element->getVar('id_form')][$entry] = getData("", $element->getVar('id_form'), $entry);
			}	
			$entryData = $cachedEntries[$element->getVar('id_form')][$entry];
		}
		
		$filterElements = $elementFilterSettings[0];
		$filterOps = $elementFilterSettings[1];
		$filterTerms = $elementFilterSettings[2];
		/* ALTERED - 20100316 - freeform - jeff/julian - start */
		$filterTypes = $elementFilterSettings[3];

		// find the filter indexes for 'match all' and 'match one or more'
		$filterElementsAll = array();
		$filterElementsOOM = array();
		for($i=0;$i<count($filterTypes);$i++) {
			if($filterTypes[$i] == "all") {
				$filterElementsAll[] = $i;
			} else {
				$filterElementsOOM[] = $i;
			}
		}
		/* ALTERED - 20100316 - freeform - jeff/julian - stop */

		// setup evaluation condition as PHP and then eval it so we know if we should include this element or not
		$evaluationCondition = "\$passedCondition = false;\n";
		$evaluationCondition .= "if(";

		/* ALTERED - 20100316 - freeform - jeff/julian - start */
		$evaluationConditionAND = buildEvaluationCondition("AND",$filterElementsAll,$filterElements,$filterOps,$filterTerms,$entry,$entryData);
		$evaluationConditionOR = buildEvaluationCondition("OR",$filterElementsOOM,$filterElements,$filterOps,$filterTerms,$entry,$entryData);

		$evaluationCondition .= $evaluationConditionAND;
		if( $evaluationConditionOR ) {
			if( $evaluationConditionAND ) {
				$evaluationCondition .= " AND (" . $evaluationConditionOR . ")";
				//$evaluationCondition .= " OR (" . $evaluationConditionOR . ")";
			} else {
				$evaluationCondition .= $evaluationConditionOR;
			}
		}
		/* ALTERED - 20100316 - freeform - jeff/julian - stop */

		$evaluationCondition .= ") {\n";
		$evaluationCondition .= "  \$passedCondition = true;\n";
		$evaluationCondition .= "}\n";

		//print( $evaluationCondition );

		eval($evaluationCondition);
		if(!$passedCondition) {
			$allowed = 0;
		}
	}
	
	if($allowed) {
		
		if($element->getVar('ele_type') == "subform") {
			return array("", $isDisabled);
		}

		if(isset($GLOBALS['formulize_forceElementsDisabled']) AND $GLOBALS['formulize_forceElementsDisabled'] == true) {
			$isDisabled = true;
		} else {
			$ele_disabled = $element->getVar('ele_disabled');
			$isDisabled = false;
			if($ele_disabled == 1) {
				$isDisabled = true;
			} elseif(!is_numeric($ele_disabled)) {
				$disabled_groups = explode(",", $ele_disabled);
				if(array_intersect($groups, $disabled_groups) AND !array_diff($groups, $disabled_groups)) {
					$isDisabled = true;
				}
			}
		}

		// Another check to see if this element is disabled, for the case where the user can view the form, but not edit it.
		if (!$is_Disabled) {
			$update_other_entries = $gperm_handler->checkRight("update_other_entries", $element->getVar('id_form'), $groups, $mid);
			$add_proxy_entries    = $gperm_handler->checkRight("add_proxy_entries", $element->getVar('id_form'), $groups, $mid);
			if ($entry == "new") {
				if (!$update_own_entry AND !$add_proxy_entries) {
					$isDisabled = true;
				}
			} else {
				if ($uid == $owner) {
					if (!$update_own_entry){
						$isDisabled = true;
					}
				} else {
					if (!$update_other_entries){
						$isDisabled = true;
					}
				}
			}
		}

		// check whether the entry is locked, and if so, then the element is not allowed.  Set a message to say that elements were disabled due to entries being edited elsewhere (first time only).
		// groups with ignore lock permission bypass this, and therefore can save entries even when locked, and saving an entry removes the lock, so that gets you out of a jam if the lock is in place when it shouldn't be.
		// locks are only valid for the session time, so if a lock is older than that, it is ignored and cleared
		// Do this last, since locking overrides other permissions!
		
		$lockFileName = "entry_".$entry."_in_form_".$element->getVar('id_form')."_is_locked_for_editing_by_user_".$uid;
		// if we haven't found a lock for this entry, check if there is one...(as long as it's not an entry that we locked ourselves on this page load)
		if($entry != "new" AND !isset($lockedEntries[$element->getVar('id_form')][$entry]) AND !isset($entriesThatHaveBeenLockedThisPageLoad[$element->getVar('id_form')][$entry]) AND file_exists(XOOPS_ROOT_PATH."/modules/formulize/temp/$lockFileName") AND !$gperm_handler->checkRight("ignore_editing_lock", $element->getVar('id_form'), $groups, $mid)) {
			$maxSessionLifeTime = ini_get("session.gc_maxlifetime");
			$fileCreationTime = filectime(XOOPS_ROOT_PATH."/modules/formulize/temp/$lockFileName");
			if($fileCreationTime + $maxSessionLifeTime > time()) {
				list($lockUid, $lockUsername) = explode(",", file_get_contents(XOOPS_ROOT_PATH."/modules/formulize/temp/$lockFileName"));
				if($lockUid != $uid) {
					// lock is still valid, hasn't expired yet.
					if(count($lockedEntries)==0) {
						print "<script type='text/javascript'>\n";
						print "alert(\"".sprintf(_formulize_ENTRY_IS_LOCKED, str_replace('"', "'", $lockUsername))."\")\n";
						print "</script>";
					}
					$lockedEntries[$element->getVar('id_form')][$entry] = true;
				}
			} else {
				// clean up expired locks
				formulize_scandirAndClean(XOOPS_ROOT_PATH."/modules/formulize/temp/", "_".$entry."_in_form_".$element->getVar('id_form')."_", $maxSessionLifeTime); 
			}
		}
		// if we've ever found a lock for this entry as part of this pageload...
		if(isset($lockedEntries[$element->getVar('id_form')][$entry])) {
			$isDisabled = true;
		}
		
		$renderer = new formulizeElementRenderer($element);
		$ele_value = $element->getVar('ele_value');
		$ele_type = $element->getVar('ele_type');
		if(($prevEntry OR $profileForm === "new") AND $ele_type != 'subform' AND $ele_type != 'grid') {
			$data_handler = new formulizeDataHandler($element->getVar('id_form'));
			$ele_value = loadValue($prevEntry, $element, $ele_value, $data_handler->getEntryOwnerGroups($entry), $groups, $entry, $profileForm); // get the value of this element for this entry as stored in the DB -- and unset any defaults if we are looking at an existing entry
		}
		
		formulize_benchmark("About to render element ".$element->getVar('ele_caption').".");
		
		$form_ele =& $renderer->constructElement($renderedElementName, $ele_value, $entry, $isDisabled, $screen);

		formulize_benchmark("Done rendering element.");
		
		// put a lock on this entry in this form, so we know that the element is being edited.  Lock will be removed next time the entry is saved.
		if ($entry > 0 AND !isset($lockedEntries[$element->getVar('id_form')][$entry])) {
            if (is_writable(XOOPS_ROOT_PATH."/modules/formulize/temp/")) {
                $lockFile = fopen(XOOPS_ROOT_PATH."/modules/formulize/temp/$lockFileName", "w");
                if (false !== $lockFile) {
                    fwrite($lockFile, "$uid,$username");
                    fclose($lockFile);
                    $entriesThatHaveBeenLockedThisPageLoad[$element->getVar('id_form')][$entry] = true;
                }
            } else {
                if (defined("ICMS_ERROR_LOG_SEVERITY") and ICMS_ERROR_LOG_SEVERITY >= E_WARNING) {
                    static $lock_file_warning_issued = false;
                    if (!$lock_file_warning_issued) {
                        // cannot write to Formulize temp folder to create lock entries
                        error_log("Notice: Formulize temp folder does not exist or is not writeable, so lock file cannot be created in ".
                            XOOPS_ROOT_PATH."/modules/formulize/temp/");
                        $lock_file_warning_issued = true;
                    }
                }
            }
        }
		
		if(!$renderElement) {
			return array(0=>$form_ele, 1=>$isDisabled);			
		} else {
			if($element->getVar('ele_type') == "ib") {
				print $form_ele[0];
				return "rendered";
			} elseif(is_object($form_ele)) {
					print $form_ele->render();
				          if(!empty($form_ele->customValidationCode) AND !$isDisabled) {
				            $GLOBALS['formulize_renderedElementsValidationJS'][$GLOBALS['formulize_thisRendering']][$form_ele->getName()] = $form_ele->renderValidationJS();
				          } elseif($element->getVar('ele_req') AND ($element->getVar('ele_type') == "text" OR $element->getVar('ele_type') == "textarea") AND !$isDisabled) {
				            $eltname    = $form_ele->getName();
				            $eltcaption = $form_ele->getCaption();
				            $eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, $eltcaption );
				            $eltmsg = str_replace('"', '\"', stripslashes( $eltmsg ) );
				            $GLOBALS['formulize_renderedElementsValidationJS'][$GLOBALS['formulize_thisRendering']][$eltname] = "if ( myform.".$eltname.".value == \"\" ) { window.alert(\"".$eltmsg."\"); myform.".$eltname.".focus(); return false; }";
				          }
					if($isDisabled) {
						return "rendered-disabled";
					} else {
						return "rendered";	
					}
			}
		}
  		

	// or, even if the user is not supposed to see the element, put in a hidden element with its default value (only on new entries for elements with the forcehidden flag on)
	// NOTE: YOU CANNOT HAVE DEFAULT VALUES ON A LINKED FIELD CURRENTLY
	// So, no handling of linked values is included here.
	} elseif($forcehidden = $element->getVar('ele_forcehidden') AND $entry=="new" AND !$noSave) {
		// hiddenElements keys will be the element ids
		$hiddenElements = generateHiddenElements(array($element), $entry);
		$thisHiddenElement = isset($hiddenElements[$element->getVar('ele_id')]) ? $hiddenElements[$element->getVar('ele_id')] : "";
		if(!$renderElement) {
			return array("hidden", $isDisabled, $thisHiddenElement); // if the element is hidden, then return an array, but with hidden as the first key, so that logic that was not expecting an element back, will still function as is.  This is a backwards compatibility thing.  For hidden elements, the element is in the third key, if in fact you need it/are looking for it in the user land code...note that in the case of checkboxes, the elements returned will be in an array
		} else {
			$cueElement = new xoopsFormHidden("decue_".$fid."_".$entry."_".$element_id, 1);
			print $cueElement->render();
			if(is_array($thisHiddenElement)) { // could happen for checkboxes
				foreach($thisHiddenElement as $thisIndividualHiddenElement) {
					if(is_object($thisIndividualHiddenElement)) {
						print $thisIndividualHiddenElement->render()."\n";
					}
				}
			} elseif(is_object($thisHiddenElement)) {
				print $thisHiddenElement->render()."\n";
			}
			return "hidden";	
		}
	} else {
		return "not_allowed";
	}
}

/* ALTERED - 20100316 - freeform - jeff/julian - start */
function buildEvaluationCondition($match,$indexes,$filterElements,$filterOps,$filterTerms,$entry,$entryData)
{
	$evaluationCondition = "";

	for($io=0;$io<count($indexes);$io++) {
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
		if(isset($GLOBALS['formulize_asynchronousFormDataInAPIFormat'][$entry][$filterElements[$i]])) {
			$compValue = $GLOBALS['formulize_asynchronousFormDataInAPIFormat'][$entry][$filterElements[$i]];
		} elseif($entry == "new") {
			// for textboxes, let's try to get their default value
			// for other elements, generate the default is too tricky to get it to work at present, not enough time available
			$element_handler = xoops_getmodulehandler('elements', 'formulize');
			$elementObject = $element_handler->get($filterElements[$i]);
			if(is_object($elementObject)) {
				$ele_type = $elementObject->getVar('ele_type');
				if($ele_type == "text" OR $ele_type == "textarea") {
					$ele_value = $elementObject->getVar('ele_value');
					$defaultKey = $ele_type == "text" ? 2 : 0; // default key is in different places for different types of elements
					$compValue = getTextboxDefault($ele_value[$defaultKey], $elementObject->getVar('id_form'), $entry);
				} else {
					$compValue = "";
				}
			} else {
				$compValue = "";
			}
		} else {
			$compValue = display($entryData[0], $filterElements[$i]);
		}
		if(is_array($compValue)) {
			if($thisOp == "=") {
				$thisOp = "LIKE";
			}
			if($thisOp == "!=") {
				$thisOp = "NOT LIKE";
			}
			$compValue = implode(",",$compValue);
		} else {
			$compValue = addslashes($compValue);
		}
		if($thisOp == "LIKE") {
			$evaluationCondition .= "strstr('".$compValue."', '".addslashes($filterTerms[$i])."')"; 
		} elseif($thisOp == "NOT LIKE") {
			$evaluationCondition .= "!strstr('".$compValue."', '".addslashes($filterTerms[$i])."')";
		} else {
			$evaluationCondition .= "'".$compValue."' $thisOp '".addslashes($filterTerms[$i])."'";
		}
	}

	return $evaluationCondition;
}
/* ALTERED - 20100316 - freeform - jeff/julian - stop */

// THIS FUNCTION RETURNS THE CAPTION FOR AN ELEMENT 
// added June 25 2006 -- jwe
function displayCaption($formframe="", $ele) {
	$element = _formulize_returnElement($ele, $formframe);
  if(!is_object($element)) {
    return "invalid_element";
  }
	return $element->getVar('ele_caption');
}

// THIS FUNCTION RETURNS THE description FOR AN ELEMENT 
function displayDescription($formframe="", $ele) {
	$element = _formulize_returnElement($ele, $formframe);
  if(!is_object($element)) {
    return "invalid_element";
  }
	return $element->getVar('ele_desc');
}

// this function takes an element object, or an element id number or handle (or framework handle with framework id, but that's deprecated)
function _formulize_returnElement($ele, $formframe="") {
  $element = "";
	if(is_object($ele)) {	
		if(get_class($ele) == "formulizeformulize" OR is_subclass_of($ele, 'formulizeformulize')) {
			$element = $ele;
		} else {
			return "invalid_element";
		}
	}
	if(!$element) {
		if(!$formulize_mgr) {
			$formulize_mgr =& xoops_getmodulehandler('elements', 'formulize');
		}
		if(is_numeric($ele)) {
			$element =& $formulize_mgr->get($ele);
		} else {
			$framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
			$frameworkObject = $framework_handler->get($formframe);
			if(is_object($frameworkObject)) {
				$frameworkElementIds = $frameworkObject->getVar('element_ids');
				if(isset($frameworkElementIds[$ele])) {
					$element_id = $frameworkElementIds[$ele];
					$element =& $formulize_mgr->get($element_id);
				}
			}
			if(!is_object($element)) {
				$element =& $formulize_mgr->get($ele);
			}
		}
		if(!is_object($element)) {
			return "invalid_element";
		}
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
  $element = _formulize_returnElement($ele, $formframe);
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



?>
