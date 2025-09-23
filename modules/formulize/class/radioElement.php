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

// There is a corresponding admin template for this element type in the templates/admin folder

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php"; // you need to make sure the base element class has been read in first!
require_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/baseClassForLists.php";

class formulizeRadioElement extends formulizeBaseClassForListsElement {

	var $defaultValueKey;

	function __construct() {
		$this->name = "Radio Buttons";
		$this->hasData = true; // set to false if this is a non-data element, like the subform or the grid
		$this->needsDataType = true; // set to false if you're going force a specific datatype for this element using the overrideDataType
		$this->overrideDataType = ""; // use this to set a datatype for the database if you need the element to always have one (like 'date').  set needsDataType to false if you use this.
		$this->adminCanMakeRequired = true; // set to true if the webmaster should be able to toggle this element as required/not required
		$this->alwaysValidateInputs = false; // set to true if you want your custom validation function to always be run.  This will override any required setting that the webmaster might have set, so the recommendation is to set adminCanMakeRequired to false when this is set to true.
		$this->canHaveMultipleValues = false;
		$this->hasMultipleOptions = true;
		parent::__construct();
	}

	/**
	 * Static function to provide the mcp server with the schema for the properties that can be used with the create_form_element and update_form_element tools
	 * Concerned with the options for the ele_value property of the element object
	 * Follows the convention of properties used publically (MCP, Public API, etc).
	 * @param bool|int $update True if this is being called as part of building the options for Updating, as opposed to options for Creating. Default is false (Creating).
	 * @return string The schema for the properties that can be used with the create_form_element and update_form_element tools
	 */
	public static function mcpElementPropertiesDescriptionAndExamples($update = false) {
		list($commonNotes, $commonProperties, $commonExamples) = formulizeBaseClassForListsElement::mcpElementPropertiesBaseDescriptionAndExamples($update);
		$descriptionAndExamples =
"Element: Radio Buttons (radio)
Description: A list of options where the user can select only one choice. Radio buttons are best used when there are a small number of options (generally less than 7) and you want the user to see all the options at once, without having to open a dropdown list or type in an autocomplete box.";
		if($commonNotes) {
			$descriptionAndExamples .= "
$commonNotes";
		}
		if($commonProperties) {
			$descriptionAndExamples .= "
$commonProperties";
		}
		if($commonExamples) {
			$descriptionAndExamples .= "
$commonExamples";
		}
		return $descriptionAndExamples;
	}

	// returns true if the option is one of the values the user can choose from in this element
  // returns false if the element does not have options
	function optionIsValid($option) {
		$ele_value = $this->getVar('ele_value');
		$uitext = $this->getVar('ele_uitext');
		return (isset($ele_value[$option]) OR in_array($option, $uitext)) ? true : false;
	}

}

#[AllowDynamicProperties]
class formulizeRadioElementHandler extends formulizeBaseClassForListsElementHandler {

	var $db;
	var $clickable; // used in formatDataForList
	var $striphtml; // used in formatDataForList
	var $length; // used in formatDataForList

	function __construct($db) {
		$this->db =& $db;
	}

	function create() {
		return new formulizeRadioElement();
	}

	/**
	 * Validate options for this element type, based on the structure used publically (MCP, Public API, etc).
	 * The description in the mcpElementPropertiesDescriptionAndExamples static method on the element class, follows this convention
	 * Options are the contents of the ele_value property on the object
	 * @param array $options The options to validate
	 * @param int|string|object|null $elementIdentifier the id, handle, or element object of the element we're preparing options for. Null if unknown.
	 * @return array An array of properties ready for the object. Usually just ele_value but could be others too.
	 */
	public function validateEleValuePublicAPIOptions($options, $elementIdentifier = null) {
		list($ele_value, $ele_uitext) = formulizeBaseClassForListsElementHandler::validateEleValuePublicAPIOptions($options, $elementIdentifier);
		return [
			'ele_value' => $ele_value[2], // radio buttons are the only list elements that have plain ele_value array, all others put options in key 2 by convention, so that is what the parent method returns. We have to compensate for it here.
			'ele_uitext' => $ele_uitext
		];
	}

	protected function getDefaultEleValue() {
		$ele_value = array();
		return $ele_value;
	}

	// this method would gather any data that we need to pass to the template, besides the ele_value and other properties that are already part of the basic element class
	// it receives the element object and returns an array of data that will go to the admin UI template
	// when dealing with new elements, $element might be FALSE
	// can organize template data into two top level keys, advanced-tab-values and options-tab-values, if there are some options for the element type that appear on the Advanced tab in the admin UI. This requires an additional template file with _advanced.html as the end of the name. Text elements have an example.
	function adminPrepare($element) {
		$dataToSendToTemplate = array();
		if(is_object($element) AND is_subclass_of($element, 'formulizeElement')) { // existing element
			$ele_value = formulize_mergeUIText($element->getVar('ele_value'), $element->getVar('ele_uitext'));
    	$newEleValueForRadios = array();
    	foreach($ele_value as $k=>$v) {
        $newEleValueForRadios[str_replace('&', '&amp;', $k)] = $v;
    	}
    	$dataToSendToTemplate['useroptions'] = $newEleValueForRadios;
		}
		return $dataToSendToTemplate;
	}

	// this method would read back any data from the user after they click save in the admin UI, and save the data to the database, if it were something beyond what is handled in the basic element class
	// this is called as part of saving the options tab.  It receives a copy of the element object immediately prior to it being saved, so the element object will have all its properties set as they would be based on the user's changes in the names & settings tab, and in the options tab (the tabs are saved in order from left to right).
	// the exception is the special ele_value array, which is passed separately from the object (this will contain the values the user set in the Options tab)
	// You can modify the element object in this function and since it is an object, and passed by reference by default, then your changes will be saved when the element is saved.
	// You should return a flag to indicate if any changes were made, so that the page can be reloaded for the user, and they can see the changes you've made here.
	// advancedTab is a flag to indicate if this is being called from the advanced tab (as opposed to the Options tab, normal behaviour). In this case, you have to go off first principals based on what is in $_POST to setup the advanced values inside ele_value (presumably).
	function adminSave($element, $ele_value = array(), $advancedTab = false) {
		$changed = false;
		if(is_object($element) AND is_subclass_of($element, 'formulizeElement')) {
			$checked = is_numeric($_POST['defaultoption']) ? intval($_POST['defaultoption']) : "";
			$newValues = array();
  		list($ele_value, $ele_uitext) = formulize_extractUIText($_POST['ele_value']);
			foreach($ele_value as $id=>$text) {
				if($text !== "") {
					$newValues[$text] = intval($id) === $checked ? 1 : 0;
				}
			}
			if(!empty($newValues) AND isset($_POST['changeuservalues']) AND $_POST['changeuservalues']==1) {
  			$data_handler = new formulizeDataHandler($element->getVar('fid'));
				if(!$changeResult = $data_handler->changeUserSubmittedValues($element, $newValues)) {
					print "Error updating user submitted values for the options in element '".$element->getVar('ele_caption')."'";
				}
			}
  		$element->setVar('ele_value', $newValues);
			$element->setVar('ele_uitext', $ele_uitext);
		}
		return $changed;
	}

	/**
	 * Returns the default value for this element, for a new entry in the specified form.
	 * Determines database ready values, not necessarily human readable values
	 * @param object $element The element object
	 * @param int|string $entry_id 'new' or the id of an entry we should use when evaluating the default value - only relevant when determining second pass at defaults when subform entries are written? (which would be better done by comprehensive conditional rendering?)
	 * @return mixed The default value
	 */
	function getDefaultValue($element, $entry_id = 'new') {
    return array_search(1, $element->getVar('ele_value'));
	}

	// this method reads the current state of an element based on the user's input, and the admin options, and sets ele_value to what it needs to be so we can render the element correctly
	// it must return $ele_value, with the correct value set in it, so that it will render as expected in the render method
	// $element is the element object
	// $value is the value that was retrieved from the database for this element in the active entry.  It is a raw value, no processing has been applied, it is exactly what is in the database (as prepared in the prepareDataForSaving method and then written to the DB)
	// $entry_id is the ID of the entry being loaded
	function loadValue($element, $value, $entry_id) {
		$ele_value = $element->getVar('ele_value');
		if (is_array($ele_value)) {
			$temparraykeys = array_keys($ele_value);
			$temparray = array_fill_keys($temparraykeys, 0); // actually remove the defaults!
		} else {
			$temparraykeys = array();
			$temparray = $ele_value;
		}
		// need to turn the prevEntry got from the DB into something the same as what is in the form specification so defaults show up right
		// we're comparing the output of these two lines against what is stored in the form specification, which does not have HTML escaped characters, and has extra slashes.  Assumption is that lack of HTML filtering is okay since only admins and trusted users have access to form creation.  Not good, but acceptable for now.
		global $myts;
		if(!$myts){
			$myts =& MyTextSanitizer::getInstance();
		}
		$value = $myts->undoHtmlSpecialChars($value);
		$numberOfSelectedValues = 1;
		$assignedSelectedValues = array();

		foreach($temparraykeys as $k) {
			// if there's a straight match (not a multiple selection)
			if((string)$k === (string)$value) {
				$temparray[$k] = 1;
				$assignedSelectedValues[$k] = true;

			// check for a match within an English translated value and assign that, otherwise set to zero
			// assumption is that development was done first in English and then translated
			// this safety net will not work if a system is developed first and gets saved data prior to translation in language other than English!!
			} elseif(!is_numeric($k) AND strlen((string)$k) > 0 AND strpos((string)$k, '[en]') !== false AND trim(trans((string)$k, "en")) == trim(trans($value,"en"))) {
				$temparray[$k] = 1;
				$assignedSelectedValues[$k] = true;
				break;
			}
		}

		if((!empty($value) OR $value === 0 OR $value === "0") AND count((array) $assignedSelectedValues) < $numberOfSelectedValues) { // if we have not assigned the selected value from the db to one of the options for this element, then lets add it to the array of options, and flag it as out of range.  This is to preserve out of range values in the db that are there from earlier times when the options were different, and also to preserve values that were imported without validation on purpose
			if(!isset($assignedSelectedValues[$value]) AND (!empty($value) OR $value === 0 OR $value === "0")) {
				$temparray[_formulize_OUTOFRANGE_DATA.$value] = 1;
			}
		}
		if ($entry_id != "new" AND ($value === "" OR is_null($value)) AND array_search(1, (array) $ele_value)) { // for radio buttons, if we're looking at an entry, and we've got no value to load, but there is a default value for the radio buttons, then use that default value (it's normally impossible to unset the default value of a radio button, so we want to ensure it is used when rendering the element in these conditions)
			$ele_value = $ele_value;
		} else {
			$ele_value = $temparray;
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
	// $screen is the screen object that is in effect, if any (may be null)
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen=false, $owner=null) {

		$selected = "";
		$options = array();
		$opt_count = 1;
		global $myts;
    foreach($ele_value as $iKey=>$iValue) {
			// yn are translated at runtime since webmaster and user might be in different languages
			if($element->getVar('ele_type')	== 'yn') {
				if($iKey == "_YES") {
					$iKey = _YES;
				} elseif($iKey == "_NO") {
					$iKey = _NO;
				}
			}
		  $options[$opt_count] = $myts->displayTarea($iKey, 1); // 1 means allow HTML through
			if( $iValue > 0 ){
				$selected = $opt_count;
			}
			$opt_count++;
		}
		$delimSetting = "";
		if($element->getVar('ele_delim') != "") {
			$delimSetting = $element->getVar('ele_delim');
		}
		$delimSetting = $myts->undoHtmlSpecialChars($delimSetting);
		if($delimSetting == "br") { $delimSetting = "<br />"; }
		$hiddenOutOfRangeValuesToWrite = array();
		switch($delimSetting){
			case 'space':
				$form_ele1 = new XoopsFormRadio(
					'',
					$markupName,
					$selected
				);
				$counter = 0;
        foreach($options as $oKey=>$oValue) {
					$oValue = formulize_swapUIText($oValue, $element->getVar('ele_uitext'));
					$other = optOther($oValue, $markupName, $entry_id, $counter, false, $isDisabled);
					if( $other != false ){
						$form_ele1->addOption($oKey, _formulize_OPT_OTHER.$other);
						if($oKey == $selected) {
							$disabledOutputText = _formulize_OPT_OTHER.$other;
						}
					}else{
						$form_ele1->addOption($oKey, $oValue);
						if($oKey == $selected) {
							$disabledOutputText = $oValue;
						}
						if(strstr($oValue, _formulize_OUTOFRANGE_DATA)) {
							$hiddenOutOfRangeValuesToWrite[$oKey] = str_replace(_formulize_OUTOFRANGE_DATA, "", $oValue); // if this is an out of range value, grab the actual value so we can stick it in a hidden element later
						}
					}
					$counter++;
				}
				$form_ele1->setExtra("onchange=\"javascript:formulizechanged=1;\"");
        $GLOBALS['formulize_lastRenderedElementOptions'] = $form_ele1->getOptions();
				break;
			default:
				$form_ele1 = new XoopsFormElementTray('', $delimSetting);
				$counter = 0;
				foreach($options as $oKey=>$oValue) {
					$oValue = formulize_swapUIText($oValue, $element->getVar('ele_uitext'));
					$t = new XoopsFormRadio(
						'',
						$markupName,
						$selected
					);
					$other = optOther($oValue, $markupName, $entry_id, $counter, false, $isDisabled);
					if( $other != false ){
						$t->addOption($oKey, _formulize_OPT_OTHER."</label><label>$other"); // epic hack to terminate radio button's label so it doesn't include the clickable 'other' box!!
						if($oKey == $selected) {
							$disabledOutputText = _formulize_OPT_OTHER.$other;
						}
						$GLOBALS['formulize_lastRenderedElementOptions'][$oKey] = _formulize_OPT_OTHER;
					}else{
						$t->addOption($oKey, $oValue);
						if($oKey == $selected) {
							$disabledOutputText = $oValue;
						}
						if(strstr($oValue, _formulize_OUTOFRANGE_DATA)) {
							$hiddenOutOfRangeValuesToWrite[$oKey] = str_replace(_formulize_OUTOFRANGE_DATA, "", $oValue); // if this is an out of range value, grab the actual value so we can stick it in a hidden element later
						}
						$GLOBALS['formulize_lastRenderedElementOptions'][$oKey] = $oValue;
					}
					$t->setExtra("onchange=\"javascript:formulizechanged=1;\"");
					$form_ele1->addElement($t);
					unset($t);
					$counter++;
				}
				break;
			}
			$renderedHoorvs = "";
			if(count((array) $hiddenOutOfRangeValuesToWrite) > 0) {
				foreach($hiddenOutOfRangeValuesToWrite as $hoorKey=>$hoorValue) {
					$thisHoorv = new xoopsFormHidden('formulize_hoorv_'.$element->getVar('ele_id').'_'.$hoorKey, $hoorValue);
					$renderedHoorvs .= $thisHoorv->render() . "\n";
					unset($thisHoorv);
				}
			}
			if($isDisabled) {
				$renderedElement = $disabledOutputText; // just text for disabled elements
			} else {
				$renderedElement = $form_ele1->render();
			}
			$form_ele = new XoopsFormLabel(
				$caption,
				trans($renderedElement),
				$markupName
			);
			return $form_ele;
	}

	// this method returns any custom validation code (javascript) that should figure out how to validate this element
	// 'myform' is a name enforced by convention that refers to the form where this element resides
	// use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
	function generateValidationCode($caption, $markupName, $element, $entry_id=false) {
		$validationCode = array();
		$eltname = $markupName;
		$eltcaption = $caption;
		$eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, strip_tags(htmlspecialchars_decode($eltcaption, ENT_QUOTES)));
		$eltmsg = str_replace('"', '\"', stripslashes( $eltmsg ) );
		$validationCode[] = "selection = false;\n";
		$validationCode[] = "if(myform.{$eltname}.length) {\n";
		$validationCode[] = "for(var i=0;i<myform.{$eltname}.length;i++){\n";
		$validationCode[] = "if(myform.{$eltname}[i].checked){\n";
		$validationCode[] = "selection = true;\n";
		$validationCode[] = "}\n";
		$validationCode[] = "}\n";
		$validationCode[] = "}\n";
		$validationCode[] = "if(selection == false) { window.alert(\"{$eltmsg}\");\n myform.{$eltname}.focus();\n return false;\n }\n";
		return $validationCode;
	}

	// this method will read what the user submitted, and package it up however we want for insertion into the form's datatable
	// You can return {WRITEASNULL} to cause a null value to be saved in the database
	// $value is what the user submitted
	// $element is the element object
	// $entry_id is the ID number of the entry that this data is being saved into. Can be "new", or null in the event of a subformblank entry being saved.
	// $subformBlankCounter is the counter for the subform blank entries, if applicable
	function prepareDataForSaving($value, $element, $entry_id=null, $subformBlankCounter=null) {
		$opt_count = 1;
		$ele_id = $element->getVar('ele_id');
		$valueFound = false;
		global $myts;
		foreach($element->getVar('ele_value') as $ele_value_key=>$ele_value_value) {
			if ($opt_count == $value ) {
				$otherValue = checkOther($ele_value_key, $ele_id, $entry_id, $subformBlankCounter);
				if($otherValue !== false) {
					if($subformBlankCounter !== null) {
						$GLOBALS['formulize_other'][$ele_id]['blanks'][$subformBlankCounter] = $otherValue;
					} else {
						$GLOBALS['formulize_other'][$ele_id][$entry_id] = $otherValue;
					}
				}
				$ele_value_key = $myts->htmlSpecialChars($ele_value_key);
				$value = $ele_value_key;
				$valueFound = true;
				break;
			}
			$opt_count++;
		}
		// if a value was received that was out of range
		if ($valueFound == false AND $value >= $opt_count) {
			// get the out of range value from the hidden values that were passed back
			$value = $myts->htmlSpecialChars($_POST['formulize_hoorv_'.$ele_id.'_'.$value]);
		}
		return $value;
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
		return applyReadableValueTransformations($value, $handle, $entry_id);
	}

	// this method will take a text value that the user has specified at some point, and convert it to a value that will work for comparing with values in the database.  This is used primarily for preparing user submitted text values for saving in the database, or for comparing to values in the database, such as when users search for things.  The typical user submitted values would be coming from a condition form (ie: fieldX = [term the user typed in]) or other situation where the user types in a value that needs to interact with the database.
	// it is only necessary to do special logic here if the values stored in the database do not match what users would be typing, ie: you're using coded numbers in the database, but displaying text on screen to users
	// this would be where a Yes value would be converted to a 1, for example, in the case of a yes/no element, since 1 is how yes is represented in the database for that element type
	// $partialMatch is used to indicate if we should search the values for partial string matches, like On matching Ontario.  This happens in the getData function when processing filter terms (ie: searches typed by users in a list of entries)
	// if $partialMatch is true, then an array may be returned, since there may be more than one matching value, otherwise a single value should be returned.
	// if literal text that users type can be used as is to interact with the database, simply return the $value
	// LINKED ELEMENTS AND UITEXT ARE RESOLVED PRIOR TO THIS METHOD BEING CALLED
	function prepareLiteralTextForDB($value, $element, $partialMatch=false) {
		return convertStringToUseSpecialCharsToMatchDB($value);
	}

	// this method will format a dataset value for display on screen when a list of entries is prepared
	// for standard elements, this step is where linked selectboxes potentially become clickable or not, among other things
	// Set certain properties in this function, to control whether the output will be sent through a "make clickable" function afterwards, sent through an HTML character filter (a security precaution), and trimmed to a certain length with ... appended.
	function formatDataForList($value, $handle="", $entry_id=0, $textWidth=100) {
		$this->clickable = false;
		$this->striphtml = false;
		$this->length = $textWidth;
		return parent::formatDataForList($value); // always return the result of formatDataForList through the parent class (where the properties you set here are enforced)
	}

}


