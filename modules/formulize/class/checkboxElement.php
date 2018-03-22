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

class formulizeCheckboxElement extends formulizeformulize {
    
    function __construct() {
        $this->name = "Checkboxes";
        $this->hasData = true; // set to false if this is a non-data element, like the subform or the grid
        $this->needsDataType = true; // set to false if you're going force a specific datatype for this element using the overrideDataType
        $this->overrideDataType = ""; // use this to set a datatype for the database if you need the element to always have one (like 'date').  set needsDataType to false if you use this.
        $this->adminCanMakeRequired = true; // set to true if the webmaster should be able to toggle this element as required/not required
        $this->alwaysValidateInputs = false; // set to true if you want your custom validation function to always be run.  This will override any required setting that the webmaster might have set, so the recommendation is to set adminCanMakeRequired to false when this is set to true.
        parent::__construct();
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
            $ele_value = $element->getVar('ele_value');
			$ele_value = formulize_mergeUIText($ele_value, $ele_uitext);
			$dataToSendToTemplate['useroptions'] = $ele_value;
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
			list($_POST['ele_value'], $ele_uitext) = formulize_extractUIText($_POST['ele_value']);
			$ele_value = array();
			foreach($_POST['ele_value'] as $id=>$text) {
				if($text !== "") {
					$ele_value[$text] = isset($_POST['defaultoption'][$id]) ? 1 : 0;
            }
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

		$temparray = $ele_value;

		if (is_array($temparray)) {
			$temparraykeys = array_keys($temparray);
			$temparray = array_fill_keys($temparraykeys, 0); // actually remove the defaults!
		} else {
			$temparraykeys = array();
		}
		
		if($temparraykeys[0] === "{FULLNAMES}" OR $temparraykeys[0] === "{USERNAMES}") { // ADDED June 18 2005 to handle pulling in usernames for the user's group(s)
			$ele_value[2]['{SELECTEDNAMES}'] = explode("*=+*:", $value);
			if(count($ele_value[2]['{SELECTEDNAMES}']) > 1) { array_shift($ele_value[2]['{SELECTEDNAMES}']); }
			$ele_value[2]['{OWNERGROUPS}'] = $owner_groups;
        return $ele_value;
    }
    
		// need to turn the prevEntry got from the DB into something the same as what is in the form specification so defaults show up right
		// important: this is safe because $value itself is not being sent to the browser!
		// we're comparing the output of these two lines against what is stored in the form specification, which does not have HTML escaped characters, and has extra slashes.  Assumption is that lack of HTML filtering is okay since only admins and trusted users have access to form creation.  Not good, but acceptable for now.
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
		return $temparray;
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
    function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen) {
	
		global $myts;
		$selected = array();
		$options = array();
		$disabledHiddenValue = array();
		$disabledHiddenValues = "";
		$disabledOutputText = array();
		$opt_count = 1;
		while( $i = each($ele_value) ){
			$options[$opt_count] = $myts->stripSlashesGPC($i['key']);
			if( $i['value'] > 0 ){
				$selected[] = $opt_count;
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
					$o = formulize_swapUIText($o, $element->getVar('ele_uitext'));
					$other = formulizeElementRenderer::optOther($o['value'], $markupName, $entry_id, $counter, true);
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
			break;
			default:
				$form_ele1 = new XoopsFormElementTray($caption, $delimSetting);
				$counter = 0; // counter used for javascript that works with 'Other' box
				while( $o = each($options) ){
					$o = formulize_swapUIText($o, $element->getVar('ele_uitext'));
					$other = formulizeElementRenderer::optOther($o['value'], $markupName, $entry_id, $counter, true);
					$t = new XoopsFormCheckBox(
						'',
						$markupName.'[]',
						$selected,
						$delimSetting
					);
					if($other != false){
						$t->addOption($o['key'], _formulize_OPT_OTHER.$other);
						if(in_array($o['key'], $selected)) {
							$disabledOutputText[] = _formulize_OPT_OTHER.$other;
						}
					}else{
						$t->addOption($o['key'], $o['value']);
						if(in_array($o['key'], $selected)) {
							$disabledOutputText[] = $o['value'];
						}
						if(strstr($o['value'], _formulize_OUTOFRANGE_DATA)) {
							$hiddenOutOfRangeValuesToWrite[$o['key']] = str_replace(_formulize_OUTOFRANGE_DATA, "", $o['value']); // if this is an out of range value, grab the actual value so we can stick it in a hidden element later
						}
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
        }

		$form_ele = new XoopsFormLabel(
			$caption,
			"$renderedElement\n$renderedHoorvs\n$disabledHiddenValues\n"
		);
		$ele_desc = $element->getVar('ele_desc', "f"); // the f causes no stupid reformatting by the ICMS core to take place
		$form_ele->setDescription(html_entity_decode($ele_desc,ENT_QUOTES));

		return $form_ele;
    }
    
    // this method returns any custom validation code (javascript) that should figure out how to validate this element
    // 'myform' is a name enforced by convention that refers to the form where this element resides
    // use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
    function generateValidationCode($caption, $markupName, $element, $entry_id) {
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
	
		global $myts;
		$selected_value = '';
        $opt_count = 1;
        $numberOfSelectionsFound = 0;
		$ele_id = $element->getVar('ele_id');
		$ele_value = $element->getVar('ele_value');
        while ($v = each($ele_value) ) {
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
    
}