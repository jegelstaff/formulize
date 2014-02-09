<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2011 Freeform Solutions                  ##
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

// THIS FILE SHOWS ALL THE METHODS THAT CAN BE PART OF CUSTOM ELEMENT TYPES IN FORMULIZE
// TO SEE THIS ELEMENT IN ACTION, RENAME THE FILE TO dummyElement.php
// There is a corresponding admin template for this element type in the templates/admin folder

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php"; // you need to make sure the base element class has been read in first!

class formulizeNewSelectElement extends formulizeformulize {
    
    function __construct() {
        $this->name = "Custom Select box (dropdowns and list boxes)";
        $this->hasData = true; // set to false if this is a non-data element, like the subform or the grid
        $this->needsDataType = true; // set to false if you're going force a specific datatype for this element using the overrideDataType
        $this->overrideDataType = ""; // use this to set a datatype for the database if you need the element to always have one (like 'date').  set needsDataType to false if you use this.
        $this->adminCanMakeRequired = true; // set to true if the webmaster should be able to toggle this element as required/not required
        $this->alwaysValidateInputs = false; // set to true if you want your custom validation function to always be run.  This will override any required setting that the webmaster might have set, so the recommendation is to set adminCanMakeRequired to false when this is set to true.
        parent::formulizeformulize();
    }
    
}

class formulizeNewSelectElementHandler extends formulizeElementsHandler {
    
    var $db;
    var $clickable; // used in formatDataForList
    var $striphtml; // used in formatDataForList
    var $length; // used in formatDataForList
    
    function __construct($db) {
    }
    
    function create() {
        return new formulizeNewSelectElement();
    }
    
    // this method would gather any data that we need to pass to the template, besides the ele_value and other properties that are already part of the basic element class
    // it receives the element object and returns an array of data that will go to the admin UI template
    // when dealing with new elements, $element might be FALSE
    function adminPrepare($element) {
		
		$config_handler = $config_handler =& xoops_gethandler('config');
		$formulizeConfig =& $config_handler->getConfigsByCat(0, getFormulizeModId());
		$member_handler = xoops_gethandler('member');
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		$allGroups = $member_handler->getGroups();
		$groups = array();
		foreach($allGroups as $thisGroup) {
		  $formlinkGroups[$thisGroup->getVar('groupid')] = $thisGroup->getVar('name');
		}
		
		$ele_value = array();
		$ele_uitext = "";
		$listordd = 0;
		$multiple = 0;
		$islinked = 0;
		$formlink_scope = array(0=>'all');
		if (!$element){
			$ele_value[0] = 6; //rows
			$ele_value[1] = 0; //multiple selections not allowed
			$ele_value[2] = array();
			$ele_value[4] = 0;
			$ele_value[5] = array();
			$ele_value[6] = 0;
			$ele_value[7] = 0;
			$ele_value[8] = 0;
			$ele_value[9] = 0;
			// Index 10-13 initialized to -1 since these pertains to element ID
			$ele_value[10] = -1;
			$ele_value[11] = -1;
			$ele_value[12] = -1;
			$ele_value[13] = -1;
			$ele_value[14] = 0;
			$ele_value[15] = 1;
		} else {
			$ele_value = $element->getVar('ele_value');
			$ele_uitext = $element->getVar('ele_uitext');
			if ($ele_value[0] == 1 and $ele_value[8] !== 1) {
				$listordd = 0;
			} elseif ($ele_value[0] !== 1 and $ele_value[8] !== 1) {
				$listordd = 1;
			} else {
				$listordd = 2;
			}
			$multiple = $ele_value[1];
			if(!is_array($ele_value[2])) {
				$isLinked = 1;
			} else {
				$isLinked = 0;
			}
			$formlink_scope = explode(",",$ele_value[3]);
		}
		
		$useroptions = formulize_mergeUIText($ele_value[2], $ele_uitext);
		
		list($formlink, $selectedLinkElementId) = createFieldList($ele_value[2]);
		$linkedoptions = $formlink->render();
		
		// setup the list value and export value option lists, and the default sort order list, and the list of possible default values
		$listValue = "";
		$exportValue = "";
		$optionSortOrder = "";
		$optionDefaultSelection = "";
		$optionDefaultSelectionDefaults = "";
		if($islinked) {
			$linkedMetaDataParts = explode("#*=:*", $ele_value[2]);
			$linkedSourceFid = $linkedMetaDataParts[0];
			if($linkedSourceFid) {
				list($listValue, $selectedListValue) = createFieldList($ele_value[10], false, $linkedSourceFid, "elements-ele_value[10]", _AM_ELE_LINKSELECTEDABOVE);
				$listValue = $listValue->render();
				list($exportValue, $selectedExportValue) = createFieldList($ele_value[11], false, $linkedSourceFid, "elements-ele_value[11]", _AM_ELE_VALUEINLIST);
				$exportValue = $exportValue->render();
				list($optionSortOrder, $selectedOptionsSortOrder) = createFieldList($ele_value[12], false, $linkedSourceFid, "elements-ele_value[12]", _AM_ELE_LINKFIELD_ITSELF);
				$optionSortOrder = $optionSortOrder->render();
				include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
				$linkedDataHandler = new formulizeDataHandler($linkedSourceFid);
				$allLinkedValues = $linkedDataHandler->findAllValuesForField($linkedMetaDataParts[1], "ASC");
				if(!is_array($ele_value[13])) {
					$ele_value[13] = array($ele_value[13]);
				}
				$optionDefaultSelectionDefaults = $ele_value[13];
				$optionDefaultSelection = $allLinkedValues; // array with keys as entry ids and values as text
			}
		}
		
		// setup group list:
		$formlink_scope_options = array('all'=>_AM_ELE_FORMLINK_SCOPE_ALL) + $formlinkGroups;
		
		// setup conditions:
		$selectedLinkFormId = "";
		if(isset($ele_value[2]['{FULLNAMES}']) OR isset($ele_value[2]['{USERNAMES}'])) {
			if($formulizeConfig['profileForm']) {
				$selectedLinkFormId = $formulizeConfig['profileForm'];
			}
		}
		
		if($selectedLinkElementId) {
		$selectedElementObject = $element_handler->get($selectedLinkElementId);
			if($selectedElementObject) {
				$formlinkfilter = formulize_createFilterUI($ele_value[5], "formlinkfilter", $selectedElementObject->getVar('id_form'), "form-2");      
			}
		} elseif($selectedLinkFormId) { // if usernames or fullnames is in effect, we'll have the profile form fid instead
		  $formlinkfilter = formulize_createFilterUI($ele_value[5], "formlinkfilter", $selectedLinkFormId, "form-2");
		}
		if(!$formlinkfilter) {
			$formlinkfilter = "<p>The options are not linked.</p>";
		}
		
		
		return array(
			'listordd'=>$listordd, 'multiple'=>$multiple,
			'islinked'=>$islinked, 'formlink_scope'=>$formlink_scope,
			'formlink_scope_options'=>$formlink_scope_options, 'formlinkfilter'=>$formlinkfilter,
			'optionSortOrder'=>$optionSortOrder, 'optionDefaultSelection'=>$optionDefaultSelection,
			'optionDefaultSelectionDefaults'=>$optionDefaultSelectionDefaults, 'listValue'=>$listValue,
			'exportValue'=>$exportValue, 'ele_value'=>$ele_value, 'useroptions'=>$useroptions);
		
    }
    
    // this method would read back any data from the user after they click save in the admin UI, and save the data to the database, if it were something beyond what is handled in the basic element class
    // this is called as part of saving the options tab.  It receives a copy of the element object immediately prior to it being saved, so the element object will have all its properties set as they would be based on the user's changes in the names & settings tab, and in the options tab (the tabs are saved in order from left to right).
    // the exception is the special ele_value array, which is passed separately from the object (this will contain the values the user set in the Options tab)
    // You can modify the element object in this function and since it is an object, and passed by reference by default, then your changes will be saved when the element is saved.
    // You should return a flag to indicate if any changes were made, so that the page can be reloaded for the user, and they can see the changes you've made here.
    function adminSave($element, $ele_value) {
        $changed = false;
		
		if(isset($_POST['formlink']) AND $_POST['formlink'] != "none") {
			global $xoopsDB;
			$sql_link = "SELECT ele_caption, id_form, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_id = " . intval($_POST['formlink']);
				$res_link = $xoopsDB->query($sql_link);
				$array_link = $xoopsDB->fetchArray($res_link);
				$ele_value[2] = $array_link['id_form'] . "#*=:*" . $array_link['ele_handle'];
		} else {
			$ele_value[2] = array();
			list($_POST['ele_value'], $ui_text) = formulize_extractUIText($_POST['ele_value']);
			foreach($_POST['ele_value'] as $id=>$text) {
				if($text !== "") {
					$ele_value[2][$text] = isset($_POST['defaultoption'][$id]) ? 1 : 0;
				}
			}
			$element->setVar('ui_text', $ui_text);
		}
		
		$ele_value[8] = 0;
		if($_POST['elements_listordd'] == 2) {
			$ele_value[0] = 1;
			$ele_value[8] = 1;
		} else if($_POST['elements_listordd']) {
			$ele_value[0] = $ele_value[0] > 1 ? intval($ele_value[0]) : 1;
		} else {
			$ele_value[0] = 1;
		}
		
		$ele_value[1] = $_POST['elements_multiple'];
		$ele_value[3] = implode(",", $_POST['element_formlink_scope']);
		
		// handle conditions
		// grab any conditions for this page too
		// add new ones to what was passed from before

		$filter_key = 'formlinkfilter';
		if($_POST["new_".$filter_key."_term"] != "") {
			$_POST[$filter_key."_elements"][] = $_POST["new_".$filter_key."_element"];
			$_POST[$filter_key."_ops"][] = $_POST["new_".$filter_key."_op"];
			$_POST[$filter_key."_terms"][] = $_POST["new_".$filter_key."_term"];
			$_POST[$filter_key."_types"][] = "all";
		}
		if($_POST["new_".$filter_key."_oom_term"] != "") {
			$_POST[$filter_key."_elements"][] = $_POST["new_".$filter_key."_oom_element"];
			$_POST[$filter_key."_ops"][] = $_POST["new_".$filter_key."_oom_op"];
			$_POST[$filter_key."_terms"][] = $_POST["new_".$filter_key."_oom_term"];
			$_POST[$filter_key."_types"][] = "oom";
		}
		
		// then remove any that we need to
		$conditionsDeleteParts = explode("_", $_POST['optionsconditionsdelete']);
		$deleteTarget = $conditionsDeleteParts[1];
		if($_POST['optionsconditionsdelete']) { 
			// go through the passed filter settings starting from the one we need to remove, and shunt the rest down one space
			// need to do this in a loop, because unsetting and key-sorting will maintain the key associations of the remaining high values above the one that was deleted
			$originalCount = count($_POST[$filter_key."_elements"]);
			for($i=$deleteTarget;$i<$originalCount;$i++) { // 2 is the X that was clicked for this page
				if($i>$deleteTarget) {
					$_POST[$filter_key."_elements"][$i-1] = $_POST[$filter_key."_elements"][$i];
					$_POST[$filter_key."_ops"][$i-1] = $_POST[$filter_key."_ops"][$i];
					$_POST[$filter_key."_terms"][$i-1] = $_POST[$filter_key."_terms"][$i];
					$_POST[$filter_key."_types"][$i-1] = $_POST[$filter_key."_types"][$i];
				}
				if($i==$deleteTarget OR $i+1 == $originalCount) {
					// first time through or last time through, unset things
					unset($_POST[$filter_key."_elements"][$i]);
					unset($_POST[$filter_key."_ops"][$i]);
					unset($_POST[$filter_key."_terms"][$i]);
					unset($_POST[$filter_key."_types"][$i]);
				}
			}	
		}
		if(count($_POST[$filter_key."_elements"]) > 0){
			$ele_value[5][0] = $_POST[$filter_key."_elements"];
			$ele_value[5][1] = $_POST[$filter_key."_ops"];
			$ele_value[5][2] = $_POST[$filter_key."_terms"];
			$ele_value[5][3] = $_POST[$filter_key."_types"];
		} else {
			$ele_value[5] = "";
		}
		
		$element->setVar('ele_value', $ele_value);
		
		if(isset($_POST['changeuservalues']) AND $_POST['changeuservalues']==1) {
			include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
			$fid = $element->getVar('id_form');
			$data_handler = new formulizeDataHandler($fid);
			$newValues = $element->getVar('ele_value');
			$ele_id = $element->getVar('ele_id');
			if(!$changeResult = $data_handler->changeUserSubmittedValues($ele_id, $newValues)) {
				print "Error updating user submitted values for the options in element $ele_id";
			}
		}
		
        return $changed;
    }
    
    // this method reads the current state of an element based on the user's input, and the admin options, and sets ele_value to what it needs to be so we can render the element correctly
    // it must return $ele_value, with the correct value set in it, so that it will render as expected in the render method
    // $value is the value that was retrieved from the database for this element in the active entry.  It is a raw value, no processing has been applied, it is exactly what is in the database (as prepared in the prepareDataForSaving method and then written to the DB)
    // $ele_value will contain the options set for this element (based on the admin UI choices set by the user, possibly altered in the adminSave method)
    // $element is the element object
    function loadValue($value, $ele_value, $element) {
		global $myts;
		if(!$myts){
			$myts =& MyTextSanitizer::getInstance();
		}
		
		if(strstr($ele_value[2], "#*=:*")) { // if we've got a linked select box, then do everything differently
			$ele_value[2] .= "#*=:*".$value; // append the selected entry ids to the form and handle info in the element definition
		} else {
			$temparray = $ele_value[2];	
			$temparraykeys = array_keys($temparray);

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
			if(get_magic_quotes_gpc()){ 
				$value = addslashes($value); 
			} 

			$selvalarray = explode("*=+*:", $value);
			$numberOfSelectedValues = strstr($value, "*=+*:") ? count($selvalarray)-1 : 1; // if this is a multiple selection value, then count the array values, minus 1 since there will be one leading separator on the string.  Otherwise, it's a single value element so the number of selections is 1.
			
			$assignedSelectedValues = array();
			foreach($temparraykeys as $k)
			{
				if((string)$k === (string)html_entity_decode($value, ENT_QUOTES)) // if there's a straight match (not a multiple selection)
				{
					$temparray[$k] = 1;
					$assignedSelectedValues[$k] = true;
				}
				elseif( is_array($selvalarray) AND in_array((string)htmlspecialchars($k, ENT_QUOTES), $selvalarray, TRUE) ) // or if there's a match within a multiple selection array) -- TRUE is like ===, matches type and value
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
			$ele_value[2] = $temparray;
		}
        return $ele_value;
    }
    
    // this method renders the element for display in a form
    // the caption has been pre-prepared and passed in separately from the element object
    // if the element is disabled, then the method must take that into account and return a non-interactable label with some version of the element's value in it
    // $ele_value is the options for this element - which will either be the admin values set by the admin user, or will be the value created in the loadValue method
    // $caption is the prepared caption for the element
    // $markupName is what we have to call the rendered element in HTML
    // $isDisabled flags whether the element is disabled or not so we know how to render it
    // $element is the element object
    // $entry_id is the ID number of the entry where this particular element comes from
    function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id) {
		global $xoopsDB, $xoopsUser, $myts;
		$renderer = new formulizeElementRenderer();
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$id_form = $element->getVar('id_form');
		if($entry_id != "new") {
			$owner = getEntryOwner($entry_id, $id_form);
		} else {
			$owner = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
		}
		$formObject = $form_handler->get($id_form);
		$isDisabled = false;
		if (strstr(getCurrentURL(),"printview.php")) {
			$isDisabled = true; // disabled all elements if we're on the printable view
		}
		$ele_desc = $element->getVar('ele_desc', "f");
		
        if(strstr($ele_value[2], "#*=:*")) { // if we've got a link on our hands... -- jwe 7/29/04
			// new process for handling links...May 10 2008...new datastructure for formulize 3.0
			$boxproperties = explode("#*=:*", $ele_value[2]);
			$sourceFid = $boxproperties[0];
			$sourceHandle = $boxproperties[1];
			$sourceEntryIds = explode(",", trim($boxproperties[2],","));

			// grab the user's groups and the module id
			global $regcode;
			if($regcode) { // if we're dealing with a registration code, determine group membership based on the code
				$reggroupsq = q("SELECT reg_codes_groups FROM " . XOOPS_DB_PREFIX . "_reg_codes WHERE reg_codes_code=\"$regcode\"");
				$groups = explode("&8(%$", $reggroupsq[0]['reg_codes_groups']);
				if($groups[0] === "") { unset($groups); } // if a code has no groups associated with it, then kill the null value that will be in position 0 in the groups array.
				$groups[] = XOOPS_GROUP_USERS;
				$groups[] = XOOPS_GROUP_ANONYMOUS;
			} else {
				$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
			}
			$module_id = getFormulizeModId();
			
			$pgroups = array();
			// handle new linkscope option -- August 30 2006
			$emptylist = false;
			if($ele_value[3]) {
				$scopegroups = explode(",",$ele_value[3]);
				if(!in_array("all", $scopegroups)) {
					if($ele_value[4]) { // limit by user's groups
						foreach($groups as $gid) { // want to loop so we can get rid of reg users group simply
							if($gid == XOOPS_GROUP_USERS) { 
								continue; 
							}
							if(in_array($gid, $scopegroups)) { 
								$pgroups[] = $gid;
							}
						}
					} else { // just use scopegroups
						$pgroups = $scopegroups;
					}
					if(count($pgroups) == 0) { // specific scope was specified, and nothing found, so we should show nothing
						$emptylist = true;
					}
				} else {
					if($ele_value[4]) { // all groups selected, but limiting by user's groups is turned on
						foreach($groups as $gid) { // want to loop so we can get rid of reg users group simply
							if($gid == XOOPS_GROUP_USERS) {
								continue; 
							}
							$pgroups[] = $gid;
						}
					} else { // all groups should be used
						unset($pgroups);
						$allgroupsq = q("SELECT groupid FROM " . $xoopsDB->prefix("groups")); //  . " WHERE groupid != " . XOOPS_GROUP_USERS); // use all groups now, if all groups are picked, with no restrictions on membership or anything, then use all groups
						foreach($allgroupsq as $thisgid) {
							$pgroups[] = $thisgid['groupid'];
						}
					}
				}
			}

			// Note: OLD WAY: if no groups were found, then pguidq will be empty and so all entries will be shown, no restrictions
			// NEW WAY: if a specific group(s) was specified, and no match with the current user was found, then we return an empty list
			array_unique($pgroups); // remove duplicate groups from the list
			
			if($ele_value[6] AND count($pgroups) > 0) {  
				$pgroupsfilter = " (";
				$start = true;
				foreach($pgroups as $thisPgroup) {
					if(!$start) { $pgroupsfilter .= " AND "; }
					$pgroupsfilter .= "EXISTS(SELECT 1 FROM ".$xoopsDB->prefix("formulize_entry_owner_groups")." AS t2 WHERE t2.groupid=$thisPgroup AND t2.fid=$sourceFid AND t2.entry_id=t1.entry_id)";
					$start = false;
				}
				$pgroupsfilter .= ")";
			} elseif(count($pgroups) > 0) {
				$pgroupsfilter = " t2.groupid IN (".$xoopsDB->escape(implode(",",$pgroups)).") AND t2.entry_id=t1.entry_id AND t2.fid=$sourceFid";
			} else {
				$pgroupsfilter = "";
			}
			
			$sourceFormObject = $form_handler->get($sourceFid);

			list($conditionsfilter, $conditionsfilter_oom, $parentFormFrom) = buildConditionsFilterSQL($ele_value[5], $sourceFid, $entry_id, $owner, $formObject, "t1");

			// if there is a restriction in effect, then add some SQL to reject options that have already been selected ??
			$restrictSQL = "";
			if($ele_value[9]) {
				$restrictSQL = " AND (
				NOT EXISTS (
				SELECT 1 FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." AS t4 WHERE t4.`".$element->getVar('ele_handle')."` LIKE CONCAT( '%,', t1.`entry_id` , ',%' ) AND t4.entry_id != ".intval($entry_id);
				$restrictSQL .= $renderer->addEntryRestrictionSQL($ele_value[9], $id_form, $groups); // pass in the flag about restriction scope, and the form id, and the groups
				$restrictSQL .= " ) OR EXISTS (
				SELECT 1 FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." AS t4 WHERE t4.`".$element->getVar('ele_handle')."` LIKE CONCAT( '%,', t1.`entry_id` , ',%' ) AND t4.entry_id = ".intval($entry_id);
				$restrictSQL .= $renderer->addEntryRestrictionSQL($ele_value[9], $id_form, $groups);
				$restrictSQL .= ") )";
			}

			static $cachedSourceValuesQ = array();
			static $cachedSourceValuesAutocompleteFile = array();
			static $cachedSourceValuesAutocompleteLength = array();

			// setup the sort order based on ele_value[12], which is an element id number
			$sortOrder = $ele_value[15] == 2 ? " DESC" : "ASC";
			if($ele_value[12]=="none" OR !$ele_value[12]) {
				$sortOrderClause = " ORDER BY t1.`$sourceHandle` $sortOrder";
			} else {
				list($sortHandle) = convertElementIdsToElementHandles(array($ele_value[12]), $sourceFormObject->getVar('id_form'));
				$sortOrderClause = " ORDER BY t1.`$sortHandle` $sortOrder";
			}

			if($pgroupsfilter) { // if there is a groups filter, then join to the group ownership table
				$sourceValuesQ = "SELECT t1.entry_id, t1.`".$sourceHandle."` FROM ".$xoopsDB->prefix("formulize_".$sourceFormObject->getVar('form_handle'))." AS t1, ".$xoopsDB->prefix("formulize_entry_owner_groups")." AS t2 $parentFormFrom WHERE $pgroupsfilter $conditionsfilter $conditionsfilter_oom $restrictSQL GROUP BY t1.entry_id $sortOrderClause";				
			} else { // otherwise just query the source table
				$sourceValuesQ = "SELECT t1.entry_id, t1.`".$sourceHandle."` FROM ".$xoopsDB->prefix("formulize_".$sourceFormObject->getVar('form_handle'))." AS t1 $parentFormFrom WHERE t1.entry_id>0 $conditionsfilter $conditionsfilter_oom $restrictSQL GROUP BY t1.entry_id $sortOrderClause";					
			}
			//print "$sourceValuesQ<br><br>";
			if(!$isDisabled) {
				// set the default selections, based on the entry_ids that have been selected as the defaults, if applicable
				$hasNoValues = trim($boxproperties[2]) == "" ? true : false;
				$useDefaultsWhenEntryHasNoValue = $ele_value[14];
				if(($entry_id == "new" OR ($useDefaultsWhenEntryHasNoValue AND $hasNoValues)) AND ((is_array($ele_value[13]) AND count($ele_value[13]) > 0) OR $ele_value[13])) {
					$defaultSelected = $ele_value[13];
				} else {
					$defaultSelected = "";
				}
				$form_ele = new XoopsFormSelect($caption, $markupName, $defaultSelected, $ele_value[0], $ele_value[1]);
				$form_ele->setExtra("onchange=\"javascript:formulizechanged=1;\" jquerytag='$markupName'");
				if($ele_value[0] == 1) { // add the initial default entry, singular or plural based on whether the box is one line or not.
					$form_ele->addOption("none", _AM_FORMLINK_PICK);
				}
			} else {
				$disabledHiddenValue = array();
				$disabledOutputText = array();
			}
			
			if(!isset($cachedSourceValuesQ[$sourceValuesQ])) {
				$element_handler = xoops_getmodulehandler('elements', 'formulize');
				$sourceElementObject = $element_handler->get($boxproperties[1]);
				if($sourceElementObject->isLinked) {
					// need to jump one more level back to get value that this value is pointing at
					$sourceEleValue = $sourceElementObject->getVar('ele_value');
					$originalSource = explode("#*=:*", $sourceEleValue[2]);
					include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
					$data_handler = new formulizeDataHandler($originalSource[0]);
				}
				$reslinkedvaluesq = $xoopsDB->query($sourceValuesQ);
				if($reslinkedvaluesq) {
					while($rowlinkedvaluesq = $xoopsDB->fetchRow($reslinkedvaluesq)) {
						if($rowlinkedvaluesq[1]==="") { continue; }
						if($sourceElementObject->isLinked) {
							$rowlinkedvaluesq[1] = $data_handler->getElementValueInEntry(trim($rowlinkedvaluesq[1], ","), $originalSource[1]);
						}
						$linkedElementOptions[$rowlinkedvaluesq[0]] = strip_tags($rowlinkedvaluesq[1]);
					}
				}
				$cachedSourceValuesQ[$sourceValuesQ] = $linkedElementOptions;
				/* ALTERED - 20100318 - freeform - jeff/julian - start */
				if(!$isDisabled AND $ele_value[8] == 1) {
					// write the possible values to a cached file so we can look them up easily when we need them, don't want to actually send them to the browser, since it could be huge, but don't want to replicate all the logic that has already gathered the values for us, each time there's an ajax request
					$cachedLinkedOptionsFileName = "formulize_linkedOptions_".str_replace(".","",microtime(true));
					formulize_scandirAndClean(XOOPS_ROOT_PATH."/cache/", "formulize_linkedOptions_");
					$cachedLinkedOptions = fopen(XOOPS_ROOT_PATH."/cache/$cachedLinkedOptionsFileName","w");
					fwrite($cachedLinkedOptions, "<?php\n\r");
					$maxLength = 0;
					foreach($linkedElementOptions as $id=>$text) {
						$thisTextLength = strlen($text);
						$maxLength = $thisTextLength > $maxLength ? $thisTextLength : $maxLength;
						$text = str_replace("\$", "\\\$", $text);
						$quotedText = "\"".str_replace("\"", "\\\"", html_entity_decode($text, ENT_QUOTES))."\"";
						$singleQuotedText = str_replace("'", "\'", "[$quotedText,$id]");
						fwrite($cachedLinkedOptions,"if(stristr($quotedText, \$term)){ \$found[]='".$singleQuotedText."'; }\n");
					}
					fwrite($cachedLinkedOptions, "?>");
					fclose($cachedLinkedOptions);
					$cachedSourceValuesAutocompleteFile[$sourceValuesQ] = $cachedLinkedOptionsFileName;
					$cachedSourceValuesAutocompleteLength[$sourceValuesQ] = $maxLength;
				} 
			}
			
			// if we're rendering an autocomplete box
			if(!$isDisabled AND $ele_value[8] == 1) {
				// do autocomplete rendering logic here
				if($boxproperties[2]) {
					$default_value = trim($boxproperties[2], ",");
					$data_handler_autocomplete = new formulizeDataHandler($boxproperties[0]);
					$default_value_user = $data_handler_autocomplete->getElementValueInEntry(trim($boxproperties[2], ","), $boxproperties[1]);
				}
				$renderedComboBox = $renderer->formulize_renderQuickSelect($markupName, $cachedSourceValuesAutocompleteFile[$sourceValuesQ], $default_value, $default_value_user, $cachedSourceValuesAutocompleteLength[$sourceValuesQ]);
				$form_ele = new xoopsFormLabel($caption, $renderedComboBox);
				$form_ele->setDescription(html_entity_decode($ele_desc,ENT_QUOTES));
			}
			
			// only do this if we're rendering a normal element, that is not disabled
			if(!$isDisabled AND $ele_value[8] == 0) {
				$form_ele->addOptionArray($cachedSourceValuesQ[$sourceValuesQ]);
			}

			// only do this if we're rendering a normal element (may be disabled)
			if($ele_value[8] == 0) {
				foreach($sourceEntryIds as $thisEntryId) {
					if(!$isDisabled) {
						$form_ele->setValue($thisEntryId);
					} else {
						$disabledName = $ele_value[1] ? $markupName."[]" : $markupName;
						$disabledHiddenValue[] = "<input type=hidden name=\"$disabledName\" value=\"$thisEntryId\">";
						$disabledOutputText[] = $cachedSourceValuesQ[$sourceValuesQ][$thisEntryId]; // the text value of the option(s) that are currently selected
					}
				}
			}

			if($isDisabled) {
				$form_ele = new XoopsFormLabel($caption, implode(", ", $disabledOutputText) . implode("\n", $disabledHiddenValue));
				$form_ele->setDescription(html_entity_decode($ele_desc,ENT_QUOTES));
			} elseif($ele_value[8] == 0) {
				// this is a hack because the size attribute is private and only has a getSize and not a setSize, setting the size can only be done through the constructor
				$count = count( $form_ele->getOptions() );
				$size = $ele_value[0];
				$new_size = ( $count < $size ) ? $count : $size;
				$form_ele->_size = $new_size;
			}
			/* ALTERED - 20100318 - freeform - jeff/julian - stop */	
		} else { // or if we don't have a link...
				
			$selected = array();
			$options = array();
			$disabledOutputText	= array();
			$disabledHiddenValue = array();
			$disabledHiddenValues = "";
			// add the initial default entry, singular or plural based on whether the box is one line or not.
			if($ele_value[0] == 1) {
				$options["none"] = _AM_FORMLINK_PICK;
			}
			
			// set opt_count to 1 if the box is NOT a multiple selection box. -- jwe 7/26/04
			if($ele_value[1]) {
				$opt_count = 0;
			} else {
				$opt_count = 1;
			}
			$hiddenOutOfRangeValuesToWrite = array();
			while( $i = each($ele_value[2]) ){

				// handle requests for full names or usernames -- will only kick in if there is no saved value (otherwise ele_value will have been rewritten by the loadValues function in the form display
				// note: if the user is about to make a proxy entry, then the list of users displayed will be from their own groups, but not from the groups of the user they are about to make a proxy entry for.  ie: until the proxy user is known, the choice of users for this list can only be based on the current user.  This could lead to confusing or buggy situations, such as users being selected who are outside the groups of the proxy user (who will become the owner) and so there will be an invalid value stored for this element in the db.
				if($i['key'] === "{FULLNAMES}" OR $i['key'] === "{USERNAMES}") { // ADDED June 18 2005 to handle pulling in usernames for the user's group(s)
					if($i['key'] === "{FULLNAMES}") { 
						$nametype = "name"; 
					}
					if($i['key'] === "{USERNAMES}") { 
						$nametype = "uname"; 
					}
					if(isset($ele_value[2]['{OWNERGROUPS}'])) {
						$groups = $ele_value[2]['{OWNERGROUPS}'];
					} else {
						global $regcode;
						if($regcode) { // if we're dealing with a registration code, determine group membership based on the code
							$reggroupsq = q("SELECT reg_codes_groups FROM " . XOOPS_DB_PREFIX . "_reg_codes WHERE reg_codes_code=\"$regcode\"");
							$groups = explode("&8(%$", $reggroupsq[0]['reg_codes_groups']);
							if($groups[0] === "") { unset($groups); } // if a code has no groups associated with it, then kill the null value that will be in position 0 in the groups array.
							$groups[] = XOOPS_GROUP_USERS;
							$groups[] = XOOPS_GROUP_ANONYMOUS;
						} else {
							global $xoopsUser;
							$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
						}
					}
					$pgroups = array();
					if($ele_value[3]) {
						$scopegroups = explode(",",$ele_value[3]);
						if(!in_array("all", $scopegroups)) {
							if($ele_value[4]) { // limit by users's groups
								foreach($groups as $gid) { // want to loop so we can get rid of reg users group simply
									if($gid == XOOPS_GROUP_USERS) { continue; }
									if(in_array($gid, $scopegroups)) {
										$pgroups[] = $gid;
									}
								}
								if(count($pgroups) > 0) { 
									unset($groups);
									$groups = $pgroups;
								} else {
									$groups = array();
								}
							} else { // don't limit by user's groups
								$groups = $scopegroups;
							}
						} else { // use all
							if(!$ele_value[4]) { // really use all (otherwise, we're just going with all user's groups, so existing value of $groups will be okay
								unset($groups);
								global $xoopsDB;
								$allgroupsq = q("SELECT groupid FROM " . $xoopsDB->prefix("groups")); //  . " WHERE groupid != " . XOOPS_GROUP_USERS); // removed exclusion of registered users group March 18 2009, since it doesn't make sense in this situation.  All groups should mean everyone, period.
								foreach($allgroupsq as $thisgid) {
									$groups[] = $thisgid['groupid'];
								}
							} 
						}
					}
					$namelist = gatherNames($groups, $nametype, $ele_value[6], $ele_value[5]);
					foreach($namelist as $auid=>$aname) {
						$options[$auid] = $aname;
					}
				} elseif($i['key'] === "{SELECTEDNAMES}") { 
					// loadValue in formDisplay will create a second option with this key that contains an array of the selected values
					$selected = $i['value'];
				} elseif($i['key'] === "{OWNERGROUPS}") { 
					// do nothing with this piece of metadata that gets set in loadValue, since it's used above
				} else { // regular selection list....
					$options[$opt_count] = $myts->stripSlashesGPC($i['key']);
					if(strstr($i['key'], _formulize_OUTOFRANGE_DATA)) {
						$hiddenOutOfRangeValuesToWrite[$opt_count] = str_replace(_formulize_OUTOFRANGE_DATA, "", $i['key']); // if this is an out of range value, grab the actual value so we can stick it in a hidden element later
					}
					if( $i['value'] > 0 ){
						$selected[] = $opt_count;
					}
					$opt_count++;
				}
			}

			$count = count($options);
			$size = $ele_value[0];
			$final_size = ( $count < $size ) ? $count : $size;

			$form_ele1 = new XoopsFormSelect(
				$caption,
				$markupName,
				$selected,
				$final_size,	//	size
				$ele_value[1]	  //	multiple
			);

			$form_ele1->setExtra("onchange=\"javascript:formulizechanged=1;\" jquerytag='$markupName'");

			// must check the options for uitext before adding to the element -- aug 25, 2007
			foreach($options as $okey=>$ovalue) {
				$options[$okey] = formulize_swapUIText($ovalue, $element->getVar('ele_uitext'));
			}
			$form_ele1->addOptionArray($options);

			if($selected) {
				if(is_array($selected)) {
					$hiddenElementName = $ele_value[1] ? $form_ele1->getName()."[]" : $form_ele1->getName();
					foreach($selected as $thisSelected) {
						$disabledOutputText[] = $options[$thisSelected];
						$disabledHiddenValue[] = "<input type=hidden name=\"$hiddenElementName\" value=\"$thisSelected\">";
					}
				} elseif($ele_value[1]) { // need to keep [] in the hidden element name if multiple values are expected, even if only one is chosen
					$disabledOutputText[] = $options[$selected];
					$disabledHiddenValue[] = "<input type=hidden name=\"".$form_ele1->getName()."[]\" value=\"$selected\">";
				} else {
					$disabledOutputText[] = $options[$selected];
					$disabledHiddenValue[] = "<input type=hidden name=\"".$form_ele1->getName()."\" value=\"$selected\">";
				}
			}

			$renderedHoorvs = "";
			if(count($hiddenOutOfRangeValuesToWrite) > 0) {
				foreach($hiddenOutOfRangeValuesToWrite as $hoorKey=>$hoorValue) {
					$thisHoorv = new xoopsFormHidden('formulize_hoorv_'.$true_ele_id.'_'.$hoorKey, $hoorValue);
					$renderedHoorvs .= $thisHoorv->render() . "\n";
					unset($thisHoorv);
				}
			}

			if($isDisabled) {
				$disabledHiddenValues = implode("\n", $disabledHiddenValue); // glue the individual value elements together into a set of values
				$renderedElement = implode(", ", $disabledOutputText);
			} elseif($ele_value[8] == 1) {
				// autocomplete construction: make sure that $renderedElement is the final output of this chunk of code
				// write the possible values to a cached file so we can look them up easily when we need them, don't want to actually send them to the browser, since it could be huge, but don't want to replicate all the logic that has already gathered the values for us, each time there's an ajax request
				$cachedOptionsFileName = "formulize_Options_".str_replace(".","",microtime(true));
				formulize_scandirAndClean(XOOPS_ROOT_PATH."/cache/", "formulize_Options_");
				$cachedOptions = fopen(XOOPS_ROOT_PATH."/cache/$cachedOptionsFileName","w");
				fwrite($cachedOptions, "<?php\n\r");
				$maxLength = 0;
				foreach($options as $id=>$text) {
					$thisTextLength = strlen($text);
					$maxLength = $thisTextLength > $maxLength ? $thisTextLength : $maxLength;
					//$quotedText = "\"".str_replace("\"", "\\\"", trim($text))."\"";
					$quotedText = "\"".str_replace("\"", "\\\"", $text)."\"";
					fwrite($cachedOptions,"if(stristr($quotedText, \$term)){ \$found[]='[$quotedText,$id]'; }\n\r");
				}
				fwrite($cachedOptions, "?>");
				fclose($cachedOptions);
				//print_r($selected); print_r($options);
				$defaultSelected = is_array($selected) ? $selected[0] : $selected;
				$renderedComboBox = $renderer->formulize_renderQuickSelect($markupName, $cachedOptionsFileName, $defaultSelected, $options[$defaultSelected], $maxLength);
				$form_ele2 = new xoopsFormLabel($caption, $renderedComboBox);
				$renderedElement = $form_ele2->render();
			} else { // normal element
				$renderedElement = $form_ele1->render();
			}
			
			$form_ele = new XoopsFormLabel(
				$caption,
				"<nobr>$renderedElement</nobr>\n$renderedHoorvs\n$disabledHiddenValues\n"
			);
			$form_ele->setDescription(html_entity_decode($ele_desc,ENT_QUOTES));
		
		} // end of if we have a link on our hands. -- jwe 7/29/04
        return $form_ele;
    }
    
    // this method returns any custom validation code (javascript) that should figure out how to validate this element
    // 'myform' is a name enforced by convention that refers to the form where this element resides
    // use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
    function generateValidationCode($caption, $markupName, $element, $entry_id) {
        $ele_value = $element->getVar('ele_value');
		$validationCode = array();
		
		if($element->getVar('ele_req') AND !$isDisabled) {
			$eltname = $markupName;
			$eltcaption = $caption;
			$eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, $eltcaption );
			$eltmsg = str_replace('"', '\"', stripslashes( $eltmsg ) );
			if($ele_value[8] == 1) {
				$validationCode = "\nif ( myform.{$eltname}.value == '' ) {\n window.alert(\"{$eltmsg}\");\n myform.{$eltname}_user.focus();\n return false;\n }\n";
			} elseif($ele_value[0] == 1) { 
				$validationCode = "\nif ( myform.{$eltname}.options[0].selected ) {\n window.alert(\"{$eltmsg}\");\n myform.{$eltname}.focus();\n return false;\n }\n";
			} elseif($ele_value[0] > 1) {
				$validationCode = "selection = false;\n";
				$validationCode = "\nfor(i=0;i<myform.{$eltname}.options.length;i++) {\n";
				$validationCode = "if(myform.{$eltname}.options[i].selected) {\n";
				$validationCode = "selection = true;\n";
				$validationCode = "}\n";
				$validationCode = "}\n";
				$validationCode = "if(selection == false) { window.alert(\"{$eltmsg}\");\n myform.{$eltname}.focus();\n return false;\n }\n";
			}
		}
				
				if($isDisabled) {
					$isDisabled = false; // disabled stuff handled here in element, so don't invoke generic disabled handling below (which is only for textboxes and their variations)
				}
        return $validationCode;
    }
    
    // this method will read what the user submitted, and package it up however we want for insertion into the form's datatable
    // You can return {WRITEASNULL} to cause a null value to be saved in the database
    // $value is what the user submitted
    // $element is the element object
    function prepareDataForSaving($value, $element) {
		global $myts;
		$ele = $value;
		$ele_value = $element->getVar('ele_value');
		// handle the new possible default value -- sept 7 2007
		if($ele_value[0] == 1 AND $ele == "none") { // none is the flag for the "Choose an option" default value
			$value = "{WRITEASNULL}"; // this flag is used to terminate processing of this value
			break;
		}
          
		// section to handle linked select boxes differently from others...
		if(strstr($ele_value[2], "#*=:*")) { // if we've got a formlink, then handle it here...
			if(is_array($ele)) {
				$startWhatWasSelected = true;
				foreach($ele as $whatwasselected) {
					if(!is_numeric($whatwasselected)) {
						continue; 
					}
					if($startWhatWasSelected) {
						$value = ",";
						$startWhatWasSelected = false;
					}
					$value .= $whatwasselected.",";
				}
			} elseif(is_numeric($ele)) {
				$value = $ele;
			}	else {
				$value = "";
			}
			break;			
		} else {
			$value = '';

			// The following code block is a replacement for the previous method for reading a select box which didn't work reliably -- jwe 7/26/04
			// print_r($ele_value[2]);
			$temparraykeys = array_keys($ele_value[2]);
			if($temparraykeys[0] === "{FULLNAMES}" OR $temparraykeys[0] === "{USERNAMES}") { // ADDED June 18 2005 to handle pulling in usernames for the user's group(s) -- updated for real live use September 6 2006
				if(is_array($ele)) {
					$value = "";
					foreach($ele as $auid) {
						$value .= "*=+*:" . $auid;
					}
				} else {
					$value = $ele;
				}
				break;
			}

			// THIS REALLY OLD CODE IS HARD TO READ....HERE'S A GLOSS...
			// ele_value[2] is all the options that make up this element.  The values passed back from the form will be numbers indicating which value was selected.  First value is 0 for a multi-selection box, and 1 for a single selection box.
			// Subsequent values are one number higher and so on all the way to the end.  Five values in a multiple selection box, the numbers are 0, 1, 2, 3, 4.
			// masterentlistjwe and entrycounterjwe will be the same!!  There's these array_keys calls here, which result basically in a list of numbers being created, keysPassedBack, and that list is going to start at 0 and go up to whatever the last value is.  It always starts at zero, even if the list is a single selection list.  entrycounterjwe will also always start at zero.
			// After that, we basically just loop through all the possible places, 0 through n, that the user might have selected, and we check if they did.
			// The check lines are if($whattheuserselected == $masterentlistjwe) and $ele == ($masterentlistjwe+1) ....note the +1 to make this work for single selection boxes where the numbers start at 1 instead of 0.
			// This is all further complicated by the fact that we're grabbing values from $entriesPassedBack, which is just the list of options in the form, so that we can populate the ultimate $value that is going to be written to the database.
            
			$entriesPassedBack = array_keys($ele_value[2]);
			$keysPassedBack = array_keys($entriesPassedBack);
			$entrycounterjwe = 0;
			$numberOfSelectionsFound = 0;
			foreach($keysPassedBack as $masterentlistjwe) {
				if(is_array($ele)) {
				//foreach($ele as $whattheuserselected) // note this loop within a loop should not be necessary...we do not need to check all the submitted values from the form once for each possible value in the form!
				if(in_array($masterentlistjwe, $ele)) {
					
					if(get_magic_quotes_gpc()) { 
						$entriesPassedBack[$entrycounterjwe] = stripslashes($entriesPassedBack[$entrycounterjwe]); 
					}
					
					$entriesPassedBack[$entrycounterjwe] = $myts->htmlSpecialChars($entriesPassedBack[$entrycounterjwe]);
					$value = $value . "*=+*:" . $entriesPassedBack[$entrycounterjwe];
					$numberOfSelectionsFound++;
				}
				$entrycounterjwe++;
				} else {
				//print "internal loop $entrycounterjwe<br>userselected: $ele<br>selectbox contained: $masterentlistjwe<br><br>";	
				if($ele == ($masterentlistjwe+1)) // plus 1 because single entry select boxes start their option lists at 1.
				{
				  //print "WE HAVE A MATCH!<BR>";
				  if(get_magic_quotes_gpc()) { $entriesPassedBack[$entrycounterjwe] = stripslashes($entriesPassedBack[$entrycounterjwe]); }
				  $entriesPassedBack[$entrycounterjwe] = $myts->htmlSpecialChars($entriesPassedBack[$entrycounterjwe]);
				  $value = $entriesPassedBack[$entrycounterjwe];
				}
				$entrycounterjwe++;
				}
			}
              // handle out of range values that are in the DB, added March 2 2008 by jwe
              if(is_array($ele)) {
                while($numberOfSelectionsFound < count($ele) AND $entrycounterjwe < 1000) { // if a value was received that was out of range...added by jwe March 2 2008...in this case we are assuming that if there are more values passed back than selections found in the valid options for the element, then there are out-of-range values we want to preserve
                  if(in_array($entrycounterjwe, $ele)) { // keep looking for more values...get them out of the hiddenOutOfRange info
                    $value = $value.'*=+*:'.$myts->htmlSpecialChars($_POST['formulize_hoorv_'.$ele_id.'_'.$entrycounterjwe]);
                    $numberOfSelectionsFound++;
                  }
                  $entrycounterjwe++;
                }
              } else {
                if($ele > $entrycounterjwe) { // if a value was received that was out of range...added by jwe March 2 2008 (note that unlike with radio buttons, we need to check only for greater than, due to the +1 (starting at 1) that happens with single option selectboxes
                  $value = $myts->htmlSpecialChars($_POST['formulize_hoorv_'.$ele_id.'_'.$ele]); // get the out of range value from the hidden values that were passed back
                }
              }
                
                
		} // end of if that checks for a linked select box.
              
        return $xoopsDB->escape($value); // strictly speaking, formulize will already escape all values it writes to the database, but it's always a good habit to never trust what the user is sending you!
    }
    
    // this method will handle any final actions that have to happen after data has been saved
    // this is typically required for modifications to new entries, after the entry ID has been assigned, because before now, the entry ID will have been "new"
    // value is the value that was just saved
    // element_id is the id of the element that just had data saved
    // entry_id is the entry id that was just saved
    // ALSO, $GLOBALS['formulize_afterSavingLogicRequired']['elementId'] = type , must be declared in the prepareDataForSaving step if further action is required now -- see fileUploadElement.php for an example
    function afterSavingLogic($value, $element_id, $entry_id) {
    }
    
    // this method will prepare a raw data value from the database, to be included in a dataset when formulize generates a list of entries or the getData API call is made
    // in the standard elements, this particular step is where multivalue elements, like checkboxes, get converted from a string that comes out of the database, into an array, for example
    // $value is the raw value that has been found in the database
    // $handle is the element handle for the field that we're retrieving this for
    // $entry_id is the entry id of the entry in the form that we're retrieving this for
    function prepareDataForDataset($value, $handle, $entry_id) {
		$elementArray = formulize_getElementMetaData($handle, true);
		$ele_value = unserialize($elementArray['ele_value']);
     	$listtype = key($ele_value[2]);
		if($listtype === "{USERNAMES}" OR $listtype === "{FULLNAMES}") {
			$uids = explode("*=+*:", $value);
			if(count($uids) > 0) {
				if(count($uids) > 1){ 
					array_shift($uids); 
				}
				$uidFilter = extract_makeUidFilter($uids);
				$listtype = $listtype == "{USERNAMES}" ? 'uname' : 'name';
				$names = go("SELECT uname, name FROM " . DBPRE . "users WHERE $uidFilter ORDER BY $listtype");
				$value = "";
				foreach($names as $thisname) {
					if($thisname[$listtype]) {
						$value .= "*=+*:" . $thisname[$listtype];
					} else {
						$value .= "*=+*:" . $thisname['uname'];
					}
				}     
			} else {
				$value = "";
			}
		}
        return $value; // we're not making any modifications for this element type
    }
    
    // this method will take a text value that the user has specified at some point, and convert it to a value that will work for comparing with values in the database.  This is used primarily for preparing user submitted text values for saving in the database, or for comparing to values in the database.  The typical user submitted values would be coming from a condition form (ie: fieldX = [term the user typed in]) or other situation where the user types in a value that needs to interact with the database.
    // this would be where a Yes value would be converted to a 1, for example, in the case of a yes/no element, since 1 is how yes is represented in the database for that element type
    function prepareLiteralTextForDB($value, $element) {
        return $value;
    }
    
    // this method will format a dataset value for display on screen when a list of entries is prepared
    // for standard elements, this step is where linked selectboxes potentially become clickable or not, among other things
    // Set certain properties in this function, to control whether the output will be sent through a "make clickable" function afterwards, sent through an HTML character filter (a security precaution), and trimmed to a certain length with ... appended.
    function formatDataForList($value, $handle, $entry_id) {
		return $value;
    }
    
}