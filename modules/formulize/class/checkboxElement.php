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
require_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

class formulizeCheckboxElement extends formulizeformulize {
    
    function __construct() {
        $this->name = "Checkboxes";
        $this->hasData = true; // set to false if this is a non-data element, like the subform or the grid
        $this->needsDataType = false; // set to false if you're going force a specific datatype for this element using the overrideDataType
        $this->overrideDataType = "text"; // use this to set a datatype for the database if you need the element to always have one (like 'date').  set needsDataType to false if you use this.
        $this->adminCanMakeRequired = true; // set to true if the webmaster should be able to toggle this element as required/not required
        $this->alwaysValidateInputs = false; // set to true if you want your custom validation function to always be run.  This will override any required setting that the webmaster might have set, so the recommendation is to set adminCanMakeRequired to false when this is set to true.
        $this->canHaveMultipleValues = true;
        $this->hasMultipleOptions = true; 
        parent::__construct();
    }
    
    // returns true if the option is one of the values the user can choose from in this element    
    function optionIsValid($option) {
        $ele_value = $this->getVar('ele_value');
        return (isset($ele_value[2][$option]) OR in_array($option, $this->getVar('ele_uitext'))) ? true : false;
    }
    
}

class formulizeCheckboxElementHandler extends formulizeElementsHandler {
    
    var $db;
    var $clickable; // used in formatDataForList
    var $striphtml; // used in formatDataForList
    var $length; // used in formatDataForList
    
    function __construct($db) {
        $this->db =& $db;
    }
    
    function create() {
        return new formulizeCheckboxElement();
    }
    
    // this method would gather any data that we need to pass to the template, besides the ele_value and other properties that are already part of the basic element class
    // it receives the element object and returns an array of data that will go to the admin UI template
    // when dealing with new elements, $element might be FALSE
    function adminPrepare($element) {
        $dataToSendToTemplate = array();
        if(is_object($element) AND is_subclass_of($element, 'formulizeformulize')) {
			$ele_value = $this->backwardsCompatibility($element->getVar('ele_value'));
            if(is_array($ele_value[2])) { // an array will be a set of hard coded options
                $ele_value[2] = formulize_mergeUIText($ele_value[2], $element->getVar('ele_uitext'));
                $dataToSendToTemplate['islinked'] = 0;
                $dataToSendToTemplate['useroptions'] = $ele_value[2];
            } else { // options are linked from another source
                $dataToSendToTemplate['islinked'] = 1;
        }
            $dataToSendToTemplate['formlink_scope'] = explode(",",$ele_value['formlink_scope']);
            $dataToSendToTemplate['ele_value'] = $ele_value;
        } else {
            $dataToSendToTemplate['formlink_scope'] = array(0=>'all');
            $dataToSendToTemplate['islinked'] = 0;
            $dataToSendToTemplate['ele_value'] = array();
        }
        
        // setup group list:
        $member_handler = xoops_gethandler('member');
        $allGroups = $member_handler->getGroups();
        $formlinkGroups = array();
        foreach($allGroups as $thisGroup) {
            $formlinkGroups[$thisGroup->getVar('groupid')] = $thisGroup->getVar('name');
        }
        $dataToSendToTemplate['formlink_scope_options'] = array('all'=>_AM_ELE_FORMLINK_SCOPE_ALL) + $formlinkGroups;
        
        // $selectedLinkElementId can be used later to initialize a set of filters, if we add that to checkboxes.
        list($formlink, $selectedLinkElementId) = createFieldList($ele_value[2]);
        $dataToSendToTemplate['linkedoptions'] = $formlink->render();
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
        $selectedElementObject = $selectedLinkElementId ? $element_handler->get($selectedLinkElementId) : null;
        
        if ($selectedLinkElementId AND $selectedElementObject) {
                $dataToSendToTemplate['formlinkfilter'] = formulize_createFilterUI($ele_value[5], "formlinkfilter", $selectedElementObject->getVar('id_form'), "form-2");
        } elseif ($selectedLinkFormId) { // if usernames or fullnames is in effect, we'll have the profile form fid instead
            $dataToSendToTemplate['formlinkfilter'] = formulize_createFilterUI($ele_value[5], "formlinkfilter", $selectedLinkFormId, "form-2");
        }
        if (!$dataToSendToTemplate['formlinkfilter']) {
            $dataToSendToTemplate['formlinkfilter'] = "<p>The options are not linked.</p>";
        }
        
		// sort order
		if($selectedElementObject) {
			list($optionSortOrder, $selectedOptionsSortOrder) = createFieldList($ele_value[12], false, $selectedElementObject->getVar('id_form'), "elements-ele_value[12]", _AM_ELE_LINKFIELD_ITSELF);
			$dataToSendToTemplate['optionSortOrder'] = $optionSortOrder->render();
            
            // list of elements to display when showing this element in a list
            list($listValue, $selectedListValue) = createFieldList($ele_value[EV_MULTIPLE_LIST_COLUMNS], false, $selectedElementObject->getVar('id_form'),
                "elements-ele_value[".EV_MULTIPLE_LIST_COLUMNS."]", _AM_ELE_LINKSELECTEDABOVE, true);
            $listValue->setValue($ele_value[EV_MULTIPLE_LIST_COLUMNS]); // mark the current selections in the form element
            $dataToSendToTemplate['listValue'] = $listValue->render();
    
            // list of elements to display when showing this element as an html form element (in form or list screens)
            list($displayElements, $selectedListValue) = createFieldList($ele_value[EV_MULTIPLE_FORM_COLUMNS], false, $selectedElementObject->getVar('id_form'),
                "elements-ele_value[".EV_MULTIPLE_FORM_COLUMNS."]", _AM_ELE_LINKSELECTEDABOVE, true);
            $displayElements->setValue($ele_value[EV_MULTIPLE_FORM_COLUMNS]); // mark the current selections in the form element
            $dataToSendToTemplate['displayElements'] = $displayElements->render();
    
            // list of elements to export to spreadsheet
            list($exportValue, $selectedExportValue) = createFieldList($ele_value[EV_MULTIPLE_SPREADSHEET_COLUMNS], false, $selectedElementObject->getVar('id_form'),
                "elements-ele_value[".EV_MULTIPLE_SPREADSHEET_COLUMNS."]", _AM_ELE_VALUEINLIST, true);
            $exportValue->setValue($ele_value[EV_MULTIPLE_SPREADSHEET_COLUMNS]); // mark the current selections in the form element
            $dataToSendToTemplate['exportValue'] = $exportValue->render();
            
		} else {
			$dataToSendToTemplate['optionSortOrder'] = "";
            $dataToSendToTemplate['exportValue'] = "";
            $dataToSendToTemplate['displayElements'] = "";
            $dataToSendToTemplate['listValue'] = "";
		}
		
        
        return $dataToSendToTemplate;
    }
    
    // this method would read back any data from the user after they click save in the admin UI, and save the data to the database, if it were something beyond what is handled in the basic element class
    // this is called as part of saving the options tab.  It receives a copy of the element object immediately prior to it being saved, so the element object will have all its properties set as they would be based on the user's changes in the names & settings tab, and in the options tab (the tabs are saved in order from left to right).
    // the exception is the special ele_value array, which is passed separately from the object (this will contain the values the user set in the Options tab)
    // You can modify the element object in this function and since it is an object, and passed by reference by default, then your changes will be saved when the element is saved.
    // You should return a flag to indicate if any changes were made, so that the page can be reloaded for the user, and they can see the changes you've made here.
    function adminSave($element, $ele_value) {
        $changed = false;
        
        if(is_object($element) AND is_subclass_of($element, 'formulizeformulize')) {
            
			$ele_value = array(
                EV_MULTIPLE_LIST_COLUMNS=>$ele_value[EV_MULTIPLE_LIST_COLUMNS],
                EV_MULTIPLE_FORM_COLUMNS=>$ele_value[EV_MULTIPLE_FORM_COLUMNS],
                EV_MULTIPLE_SPREADSHEET_COLUMNS=>$ele_value[EV_MULTIPLE_SPREADSHEET_COLUMNS],
                12=>$ele_value[12],
                15=>$ele_value[15],
                'checkbox_scopelimit'=>$ele_value['checkbox_scopelimit'],
                'checkbox_formlink_anyorall'=>$ele_value['checkbox_formlink_anyorall']
            ); // initialize with the values that we don't need to parse/adjust
            
            if(isset($_POST['formlink']) AND $_POST['formlink'] != "none") {
                global $xoopsDB;
                $sql_link = "SELECT ele_caption, id_form, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_id = " . intval($_POST['formlink']);
                $res_link = $xoopsDB->query($sql_link);
                $array_link = $xoopsDB->fetchArray($res_link);
                $ele_value[2] = $array_link['id_form'] . "#*=:*" . $array_link['ele_handle'];
            } else {
			list($_POST['ele_value'], $ele_uitext) = formulize_extractUIText($_POST['ele_value']);
			foreach($_POST['ele_value'] as $id=>$text) {
				if($text !== "") {
					$ele_value[2][$text] = isset($_POST['defaultoption'][$id]) ? 1 : 0;
            }
        }
            }
             
            // grab formlink scope options
            $ele_value['formlink_scope'] = implode(",", $_POST['element_formlink_scope']);
             
            // handle conditions
            // grab any conditions for this page too
            // add new ones to what was passed from before
            // need to send "changed" so the screen will redraw since the filter options have changed and need to be regenerated/updated
            $filter_key = 'formlinkfilter';
            if($_POST["new_".$filter_key."_term"] != "") {
                $_POST[$filter_key."_elements"][] = $_POST["new_".$filter_key."_element"];
                $_POST[$filter_key."_ops"][] = $_POST["new_".$filter_key."_op"];
                $_POST[$filter_key."_terms"][] = $_POST["new_".$filter_key."_term"];
                $_POST[$filter_key."_types"][] = "all";
                $changed = true;
            }
            if($_POST["new_".$filter_key."_oom_term"] != "") {
                $_POST[$filter_key."_elements"][] = $_POST["new_".$filter_key."_oom_element"];
                $_POST[$filter_key."_ops"][] = $_POST["new_".$filter_key."_oom_op"];
                $_POST[$filter_key."_terms"][] = $_POST["new_".$filter_key."_oom_term"];
                $_POST[$filter_key."_types"][] = "oom";
                $changed = true;
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
                $changed = true;
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
			$element->setVar('ele_uitext', $ele_uitext);
			
        }
        return $changed;
    }
    
    // this method reads the current state of an element based on the user's input, and the admin options, and sets ele_value to what it needs to be so we can render the element correctly
    // it must return $ele_value, with the correct value set in it, so that it will render as expected in the render method
    // $value is the value that was retrieved from the database for this element in the active entry.  It is a raw value, no processing has been applied, it is exactly what is in the database (as prepared in the prepareDataForSaving method and then written to the DB)
    // $ele_value will contain the options set for this element (based on the admin UI choices set by the user, possibly altered in the adminSave method)
    // $element is the element object
    function loadValue($value, $ele_value, $element) {
	
		// put the array into another array (clearing all default values)
		// then we modify our place holder array and then reassign

		$ele_value = $this->backwardsCompatibility($ele_value);
		
		$temparray = $ele_value[2];

		if (is_array($temparray)) {
			$temparraykeys = array_keys($temparray);
			$temparray = array_fill_keys($temparraykeys, 0); // actually remove the defaults!
		} else {
			$temparraykeys = array();
		}
		
		// need to turn the prevEntry got from the DB into something the same as what is in the form specification so defaults show up right
		// important: this is safe because $value itself is not being sent to the browser!
		// we're comparing the output of these two lines against what is stored in the form specification, which does not have HTML escaped characters, and has extra slashes.  Assumption is that lack of HTML filtering is okay since only admins and trusted users have access to form creation.  Not good, but acceptable for now.
        
        if($value AND !strstr($value, "*=+*:")) { // not a standard checkbox value, so it should be a set of comma separated element ids, represented linked options
            $boxproperties = $boxproperties = explode("#*=:*", $ele_value[2]);
            $sourceFid = $boxproperties[0];
            $sourceHandle = $boxproperties[1];
            $ele_value[2] = $boxproperties[0] . "#*=:*" . $boxproperties[1] . "#*=:*" . implode(",",explode("*=+*:",trim($value, "*=+*:"))); // append the selected ids onto the end of the metadata for the linked options
            return $ele_value;
        }
        
		global $myts;
		$value = $myts->undoHtmlSpecialChars($value);

		$selvalarray = explode("*=+*:", $value);
		$numberOfSelectedValues = strstr($value, "*=+*:") ? count($selvalarray)-1 : 1; // if this is a multiple selection value, then count the array values, minus 1 since there will be one leading separator on the string.  Otherwise, it's a single value element so the number of selections is 1.
		
		$assignedSelectedValues = array();
		foreach($temparraykeys as $k) {
			
			// if there's a straight match (not a multiple selection)
			if((string)$k === (string)$value) {
				$temparray[$k] = 1;
				$assignedSelectedValues[$k] = true;
				
			// or if there's a match within a multiple selection array) -- TRUE is like ===, matches type and value
			} elseif( is_array($selvalarray) AND in_array((string)$k, $selvalarray, TRUE) ) {
				$temparray[$k] = 1;
				$assignedSelectedValues[$k] = true;
				
			// check for a match within an English translated value and assign that, otherwise set to zero
			// assumption is that development was done first in English and then translated
			// this safety net will not work if a system is developed first and gets saved data prior to translation in language other than English!!
			} else {
				foreach($selvalarray as $selvalue) {
					if(trim(trans((string)$k, "en")) == trim(trans($selvalue,"en"))) {
						$temparray[$k] = 1;
						$assignedSelectedValues[$k] = true;
						continue 2; // move on to next iteration of outer loop
					} 
				}
				if($temparray[$k] != 1) {
					$temparray[$k] = 0;
				}
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
    // $screen is the screen object that is in effect, if any (may be null)
    // $renderAsHiddenDefault is a flag to control what happens when we render as a hidden element for users who can't normally access the element -- typically we would set the default value inside a hidden element, or the current value if for some reason an entry is passed
    function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen=false, $owner, $renderAsHiddenDefault = false) {
	
		$ele_value = $this->backwardsCompatibility($ele_value);
	
        // if options are linked...
        $element_ele_value = $element->getVar('ele_value');
		$isLinked = false;
        if(!is_array($element_ele_value[2]) AND strstr($element_ele_value[2], "#*=:*")) {
			$isLinked = true;
            $boxproperties = explode("#*=:*", $ele_value[2]);
            $sourceFid = $boxproperties[0];
            $sourceHandle = $boxproperties[1];
            $sourceEntryIds = explode(",", trim($boxproperties[2],","));
            
            $form_handler = xoops_getmodulehandler('forms', 'formulize');
            $formObject = $form_handler->get($element->getVar('id_form'));
            $sourceFormObject = $form_handler->get($sourceFid);

            list($conditionsfilter, $conditionsfilter_oom, $parentFormFrom) = buildConditionsFilterSQL($element_ele_value[5], $sourceFid, $entry_id, $owner, $formObject, "t1");
        
			// setup the sort order based on ele_value[12], which is an element id number
			$sortOrder = $ele_value[15] == 2 ? " DESC" : "ASC";
			if($ele_value[12]=="none" OR !$ele_value[12]) {
				$sortOrderClause = " ORDER BY t1.`$sourceHandle` $sortOrder";
			} else {
				list($sortHandle) = convertElementIdsToElementHandles(array($ele_value[12]), $sourceFormObject->getVar('id_form'));
				$sortOrderClause = " ORDER BY t1.`$sortHandle` $sortOrder";
			}
		
            $groupLimitClause = prepareLinkedElementGroupFilter($sourceFid, $ele_value['formlink_scope'], $ele_value['checkbox_scopelimit'], $ele_value['checkbox_formlink_anyorall']);
            list($sourceEntrySafetyNetStart, $sourceEntrySafetyNetEnd) = prepareLinkedElementSafetyNets($sourceEntryIds);
            $extra_clause = prepareLinkedElementExtraClause($groupLimitClause, $parentFormFrom, $sourceEntrySafetyNetStart);
            
            global $xoopsDB;
            // missing $restrictSQL that linked selectboxes use, otherwise same features as linked selectboxes?
            $sourceValuesQ = "SELECT t1.entry_id, t1.`".$sourceHandle."` FROM ".$xoopsDB->prefix("formulize_".$sourceFormObject->getVar('form_handle'))." AS t1
                $extra_clause $conditionsfilter $conditionsfilter_oom $sourceEntrySafetyNetEnd GROUP BY t1.entry_id $sortOrderClause";
                
            if($sourceValuesRes = $xoopsDB->query($sourceValuesQ)) {
                // rewrite the values and ui text based on the data coming out of the database
                $ele_value = array();
                $ele_uitext = array();
                while($resultArray = $xoopsDB->fetchArray($sourceValuesRes)) {
                    $ele_value[2][$resultArray['entry_id']] = in_array($resultArray['entry_id'],$sourceEntryIds) ? 1 : 0;
                    $ele_uitext[$resultArray['entry_id']] = $resultArray[$sourceHandle];
                }
            } else {
                $ele_uitext = $element->getVar('ele_uitext');    
            }
        } else {
            $ele_uitext = $element->getVar('ele_uitext');
        }
	
		global $myts;
		$selected = array();
		$options = array();
		$disabledHiddenValue = array();
		$disabledHiddenValues = "";
		$disabledOutputText = array();
		$opt_count = 1;
        
        foreach($ele_value[2] as $key=>$value) {
			// linked checkboxes will send back the entry id to save
			// non linked boxes send back an ordinal number that shows which options were picked
			$valueToUse = $isLinked ? $key : $opt_count;
			$options[$valueToUse] = $myts->stripSlashesGPC($key);
			if( $value > 0 ){
				$selected[] = $valueToUse;
				$disabledHiddenValue[] = "<input type=hidden name=\"".$markupName."[]\" value=\"$opt_count\">";
			}
			$opt_count++;
		}
		if($element->getVar('ele_delim') != "") {
			$delimSetting = $element->getVar('ele_delim');
		} 
		$delimSetting = $myts->undoHtmlSpecialChars($delimSetting);
		if($delimSetting == "br") { $delimSetting = "<br />"; }
		$hiddenOutOfRangeValuesToWrite = array();
		switch($delimSetting){
			case 'space':
				$form_ele1 = new XoopsFormCheckBox(
					$caption,
					$markupName,
					$selected
				);
				$counter = 0; // counter used for javascript that works with 'Other' box
				while( $o = each($options) ){
					$o = formulize_swapUIText($o, $ele_uitext);
					$other = formulizeElementRenderer::optOther($o['value'], $markupName, $entry_id, $counter, true, $isDisabled);
					if( $other != false ){
						$form_ele1->addOption($o['key'], _formulize_OPT_OTHER.$other);
						if(in_array($o['key'], $selected)) {
							$disabledOutputText[] = _formulize_OPT_OTHER.$other;
						}
					}else{
						$form_ele1->addOption($o['key'], $o['value']);
						if(in_array($o['key'], $selected)) {
							$disabledOutputText[] = $o['value'];
						}
						if(strstr($o['value'], _formulize_OUTOFRANGE_DATA)) {
							$hiddenOutOfRangeValuesToWrite[$o['key']] = str_replace(_formulize_OUTOFRANGE_DATA, "", $o['value']); // if this is an out of range value, grab the actual value so we can stick it in a hidden element later
						}
					}
					$counter++;
				}
				$form_ele1->setExtra(" onchange=\"javascript:formulizechanged=1;\" jquerytag=\"$markupName\" ");
                $GLOBALS['formulize_lastRenderedElementOptions'] = $form_ele1->getOptions();
			break;
			default:
				$form_ele1 = new XoopsFormElementTray($caption, $delimSetting);
				$counter = 0; // counter used for javascript that works with 'Other' box
				while( $o = each($options) ){
					$o = formulize_swapUIText($o, $ele_uitext);
					$other = formulizeElementRenderer::optOther($o['value'], $markupName, $entry_id, $counter, true, $isDisabled);
					$t = new XoopsFormCheckBox(
						'',
						$markupName.'[]',
						$selected,
						""
					); // "" means absolutely nothing as delimiter, which gets chucked onto the end of this individual box at render time. :(
					if($other != false){
						$t->addOption($o['key'], _formulize_OPT_OTHER.$other);
						if(in_array($o['key'], $selected)) {
							$disabledOutputText[] = _formulize_OPT_OTHER.$other;
						}
                        $GLOBALS['formulize_lastRenderedElementOptions'][$o['key']] = _formulize_OPT_OTHER;
					}else{
						$t->addOption($o['key'], $o['value']);
						if(in_array($o['key'], $selected)) {
							$disabledOutputText[] = $o['value'];
						}
						if(strstr($o['value'], _formulize_OUTOFRANGE_DATA)) {
							$hiddenOutOfRangeValuesToWrite[$o['key']] = str_replace(_formulize_OUTOFRANGE_DATA, "", $o['value']); // if this is an out of range value, grab the actual value so we can stick it in a hidden element later
						}
                        $GLOBALS['formulize_lastRenderedElementOptions'][$o['key']] = $o['value'];
					}
					$t->setExtra(" onchange=\"javascript:formulizechanged=1;\" jquerytag=\"$markupName\" ");
					$form_ele1->addElement($t);
					unset($t);
					$counter++;
				}
			break;
		}
		$renderedHoorvs = "";

		if(count($hiddenOutOfRangeValuesToWrite) > 0) {
			foreach($hiddenOutOfRangeValuesToWrite as $hoorKey=>$hoorValue) {
				$thisHoorv = new xoopsFormHidden('formulize_hoorv_'.$element->getVar('ele_id').'_'.$hoorKey, $hoorValue); 
				$renderedHoorvs .= $thisHoorv->render() . "\n";
				unset($thisHoorv);
			}
		}
		
        if($isDisabled) {
			$disabledHiddenValues = implode("\n", $disabledHiddenValue); // glue the individual value elements together into a set of values
			$renderedElement = implode(", ", $disabledOutputText);
        } else {
			$renderedElement = $form_ele1->render();
            if($renderAsHiddenDefault) {
                $renderedElement .= "\n$renderedHoorvs\n$disabledHiddenValues\n";    
            }
        }

		$form_ele = new XoopsFormLabel(
			$caption,
			$renderedElement
		);
		$ele_desc = $element->getVar('ele_desc', "f"); // the f causes no stupid reformatting by the ICMS core to take place
		$form_ele->setDescription(html_entity_decode($ele_desc,ENT_QUOTES));

		return $form_ele;
    }
    
    // this method returns any custom validation code (javascript) that should figure out how to validate this element
    // 'myform' is a name enforced by convention that refers to the form where this element resides
    // use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
    function generateValidationCode($caption, $markupName, $element, $entry_id=false) {
		$eltname = $markupName;
		$eltcaption = $caption;
		$eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, $eltcaption );
		$eltmsg = str_replace('"', '\"', stripslashes( $eltmsg ) );
        $validationCode = array();
		$validationCode[] = "selection = true;\n";
		$validationCode[] = "checkboxes = $('[jquerytag={$eltname}]:checked');\n"; // need to use this made up attribute here, because there is no good way to select the checkboxes using the name or anything else that XOOPS/Impress is giving us!!
		$validationCode[] = "if(checkboxes.length == 0) { window.alert(\"{$eltmsg}\");\n $('[jquerytag={$eltname}]').focus();\n return false;\n }\n";
        return $validationCode;
    }
    
    // this method will read what the user submitted, and package it up however we want for insertion into the form's datatable
    // You can return {WRITEASNULL} to cause a null value to be saved in the database
    // $value is what the user submitted
    // $element is the element object
	// $entry_id is the ID number of the entry that this data is being saved into. Can be "new", or null in the event of a subformblank entry being saved.
    function prepareDataForSaving($value, $element, $entry_id=null) {
	
        $ele_value = $this->backwardsCompatibility($element->getVar('ele_value'));
		
        if(!is_array($ele_value[2]) AND strstr($ele_value[2], "#*=:*")) {
            $filteredValues = array();
            foreach($value as $whatwasselected) {
				if(!is_numeric($whatwasselected)) { continue; }
                $filteredValues[] = $whatwasselected;
            }
            return formulize_db_escape(implode(",",$filteredValues));
        }
	
		global $myts;
		$selected_value = '';
        $opt_count = 1;
        $numberOfSelectionsFound = 0;
		$ele_id = $element->getVar('ele_id');
        while ($v = each($ele_value[2]) ) {
            // it's always an array, right?!
            if (is_array($value)) {
                if (in_array($opt_count, $value) ) {
                    $numberOfSelectionsFound++;
                    $otherValue = checkOther($v['key'], $ele_id, $entry_id, $subformBlankCounter);
                    if($otherValue !== false) {
                        if($subformBlankCounter !== null) {
                            $GLOBALS['formulize_other'][$ele_id]['blanks'][$subformBlankCounter] = $otherValue;
                        } else {
                            $GLOBALS['formulize_other'][$ele_id][$entry_id] = $otherValue;
                        }
                    }
                    $v['key'] = $myts->htmlSpecialChars($v['key']);
                    $selected_value = $selected_value.'*=+*:'.$v['key'];
                }
                $opt_count++;
            }
        }

        // if a value was received that was out of range. in this case we are assuming that if there are more values passed back than selections found in the valid options for the element, then there are out-of-range values we want to preserve
        while ($numberOfSelectionsFound < count($value) AND $opt_count < 1000) {
            // keep looking for more values. get them out of the hiddenOutOfRange info
            if (in_array($opt_count, $value)) {
                $selected_value = $selected_value.'*=+*:'.$myts->htmlSpecialChars($_POST['formulize_hoorv_'.$ele_id.'_'.$opt_count]);
                $numberOfSelectionsFound++;
            }
            $opt_count++;
        }

        return formulize_db_escape($selected_value); // strictly speaking, formulize will already escape all values it writes to the database, but it's always a good habit to never trust what the user is sending you!
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
		global $xoopsDB;
		$newValueq = go("SELECT other_text FROM " . $xoopsDB->prefix("formulize_other")." as o, " . $xoopsDB->prefix("formulize")." as f WHERE o.ele_id = f.ele_id AND f.ele_handle=\"" . formulize_db_escape($handle) . "\" AND o.id_req='".intval($entry_id)."' LIMIT 0,1");
        // removing the "Other: " part...we just want to show what people actually typed...doesn't have to be flagged specifically as an "other" value
        $value_other = $newValueq[0]['other_text'];
		$value = preg_replace('/\{OTHER\|+[0-9]+\}/', $value_other, $value); 
        return explode("*=+*:",$value); 
    }
    
    // this method will take a text value that the user has specified at some point, and convert it to a value that will work for comparing with values in the database.  This is used primarily for preparing user submitted text values for saving in the database, or for comparing to values in the database, such as when users search for things.  The typical user submitted values would be coming from a condition form (ie: fieldX = [term the user typed in]) or other situation where the user types in a value that needs to interact with the database.
    // it is only necessary to do special logic here if the values stored in the database do not match what users would be typing, ie: you're using coded numbers in the database, but displaying text on screen to users
    // this would be where a Yes value would be converted to a 1, for example, in the case of a yes/no element, since 1 is how yes is represented in the database for that element type
    // $partialMatch is used to indicate if we should search the values for partial string matches, like On matching Ontario.  This happens in the getData function when processing filter terms (ie: searches typed by users in a list of entries)
    // if $partialMatch is true, then an array may be returned, since there may be more than one matching value, otherwise a single value should be returned.
    // if literal text that users type can be used as is to interact with the database, simply return the $value 
    function prepareLiteralTextForDB($value, $element, $partialMatch=false) {
        // this needs to be refactored to take into account what is happening in the function of the same name in the modules/formulize/include/functions.php file!
        return $value;
    }
    
    // this method will format a dataset value for display on screen when a list of entries is prepared
    // for standard elements, this step is where linked selectboxes potentially become clickable or not, among other things
    // Set certain properties in this function, to control whether the output will be sent through a "make clickable" function afterwards, sent through an HTML character filter (a security precaution), and trimmed to a certain length with ... appended.
    function formatDataForList($value, $handle, $entry_id) {
        $this->clickable = true; // make urls clickable
        $this->striphtml = true; // remove html tags as a security precaution
        $this->length = 1000; // truncate to a maximum of 100 characters, and append ... on the end
        
        return parent::formatDataForList($value); // always return the result of formatDataForList through the parent class (where the properties you set here are enforced)
    }
    
	function backwardsCompatibility($ele_value) {
		if(!is_numeric(key($ele_value)) OR (!isset($ele_value[2]) AND (!isset($ele_value[5]) OR (!is_array($ele_value[5]) AND !is_numeric($ele_value[5]))))) {
			$ele_value = array(2=>$ele_value,5=>array());
		}
		return $ele_value;
	}
	
}