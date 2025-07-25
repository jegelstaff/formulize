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

class formulizeTextElement extends formulizeElement {

    function __construct() {
        $this->name = "Textbox";
        $this->hasData = true; // set to false if this is a non-data element, like the subform or the grid
        $this->needsDataType = true; // set to false if you're going force a specific datatype for this element using the overrideDataType
        $this->overrideDataType = ""; // use this to set a datatype for the database if you need the element to always have one (like 'date').  set needsDataType to false if you use this.
        $this->adminCanMakeRequired = true; // set to true if the webmaster should be able to toggle this element as required/not required
        $this->alwaysValidateInputs = true; // set to true if you want your custom validation function to always be run.  This will override any required setting that the webmaster might have set, so the recommendation is to set adminCanMakeRequired to false when this is set to true.
        $this->canHaveMultipleValues = false;
        $this->hasMultipleOptions = false;
        parent::__construct();
    }

		// write code to a file
		public function setVar($key, $value, $not_gpc = false) {
			if($key == 'ele_value') {
				$valueToWrite = is_array($value) ? $value : unserialize($value);
				if(strstr((string)$valueToWrite[2], "\$default")) {
					$filename = 'text_'.$this->getVar('ele_handle').'.php';
					formulize_writeCodeToFile($filename, $valueToWrite[2]);
					$valueToWrite[2] = '';
					$value = is_array($value) ? $valueToWrite : serialize($valueToWrite);
				}
			}
			parent::setVar($key, $value, $not_gpc = false);
		}

		// read code from a file
		public function getVar($key, $format = 's') {
			$format = $key == "ele_value" ? "f" : $format;
			$value = parent::getVar($key, $format);
			if($key == 'ele_value' AND is_array($value)) {
				$filename ='text_'.$this->getVar('ele_handle').'.php';
				$filePath = XOOPS_ROOT_PATH.'/modules/formulize/code/'.$filename;
				$fileValue = "";
				if(file_exists($filePath)) {
					$fileValue = strval(file_get_contents($filePath));
				}
				$value[2] = $fileValue ? $fileValue : (is_array($value) AND isset($value[2]) ? $value[2] : null);
			}
			return $value;
		}
}

#[AllowDynamicProperties]
class formulizeTextElementHandler extends formulizeElementsHandler {

    var $db;
    var $clickable; // used in formatDataForList
    var $striphtml; // used in formatDataForList
    var $length; // used in formatDataForList

    function __construct($db) {
        $this->db =& $db;
    }

    function create() {
        return new formulizeTextElement();
    }

    // this method would gather any data that we need to pass to the template, besides the ele_value and other properties that are already part of the basic element class
    // it receives the element object and returns an array of data that will go to the admin UI template
    // when dealing with new elements, $element might be FALSE
    function adminPrepare($element) {
			$dataToSendToTemplate = array();
			if(is_object($element) AND is_subclass_of($element, 'formulizeElement')) { // existing element
				$ele_value = $element->getVar('ele_value');
				$formlink = createFieldList($ele_value[4], true);
				$dataToSendToTemplate['formlink'] = $formlink->render();
				$dataToSendToTemplate['ele_value'] = $element->getVar('ele_value');
			} else { // new element
				$config_handler = xoops_gethandler('config');
        $formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
				$ele_value[0] = $formulizeConfig['t_width'];
				$ele_value[1] = $formulizeConfig['t_max'];
				$ele_value[3] = 0;
				$ele_value[5] = isset($formulizeConfig['number_decimals']) ? $formulizeConfig['number_decimals'] : 0;
				$ele_value[6] = isset($formulizeConfig['number_prefix']) ? $formulizeConfig['number_prefix'] : '';
				$ele_value[7] = isset($formulizeConfig['number_decimalsep']) ? $formulizeConfig['number_decimalsep'] : '.';
				$ele_value[8] = isset($formulizeConfig['number_sep']) ? $formulizeConfig['number_sep'] : ',';
				$ele_value[10] = isset($formulizeConfig['number_suffix']) ? $formulizeConfig['number_suffix'] : '';
				$ele_value[12] = 1; // Default trim option to enabled
				$dataToSendToTemplate['ele_value'] = $ele_value;
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
			if(is_object($element) AND is_subclass_of($element, 'formulizeElement')) {
				$ele_value[4] = $_POST['formlink'];
				$element->setVar('ele_value', $ele_value);
			}
			return $changed;
    }

    // this method reads the current state of an element based on the user's input, and the admin options, and sets ele_value to what it needs to be so we can render the element correctly
    // it must return $ele_value, with the correct value set in it, so that it will render as expected in the render method
    // $value is the value that was retrieved from the database for this element in the active entry.  It is a raw value, no processing has been applied, it is exactly what is in the database (as prepared in the prepareDataForSaving method and then written to the DB)
    // $ele_value will contain the options set for this element (based on the admin UI choices set by the user, possibly altered in the adminSave method)
    // $element is the element object
    function loadValue($value, $ele_value, $element) {
			$ele_value[2] = $value;
			$ele_value[2] = str_replace("'", "&#039;", $ele_value[2]);
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

			$ele_value[2] = stripslashes($ele_value[2]);
			$ele_value[2] = interpretTextboxValue($element, $entry_id, $ele_value[2]);
			//if placeholder value is set
			if($ele_value[11] AND ($entry_id == 'new' OR $ele_value[2] === "")) { // always go straight to source for placeholder for new entries, or entries where there is no value
        $rawEleValue = $element->getVar('ele_value');
				$placeholder = $rawEleValue[2];
				$ele_value[2] = "";
			}
			if (!strstr(getCurrentURL(),"printview.php") AND !$isDisabled) {
				$form_ele = new XoopsFormText(
					$caption,
					$markupName,
					$ele_value[0],	//	box width
					$ele_value[1],	//	max width
					$ele_value[2],	//	value
					false,					// autocomplete in browser
					($ele_value[3] ? 'number' : 'text')		// numbers only
				);
				//if placeholder value is set
				if($ele_value[11]) {
					$form_ele->setExtra("placeholder='".$placeholder."'");
				}
				//if numbers-only option is set
				if ($ele_value[3]) {
					$form_ele->setExtra("class='numbers-only-textbox'");
				}
			} else {
				$form_ele = new XoopsFormLabel ($caption, formulize_numberFormat($ele_value[2], $element->getVar('ele_handle')), $markupName);
			}
			return $form_ele;
    }

    // this method returns any custom validation code (javascript) that should figure out how to validate this element
    // 'myform' is a name enforced by convention that refers to the form where this element resides
    // use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
    function generateValidationCode($caption, $markupName, $element, $entry_id=false) {
			$validationCode = array();
			$ele_value = $element->getVar('ele_value');
			$eltname = $markupName;
			$eltcaption = $caption;
			$eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, strip_tags(htmlspecialchars_decode($eltcaption, ENT_QUOTES)));
			$eltmsg = str_replace('"', '\"', stripslashes($eltmsg));
			if($element->getVar('ele_required')) { // need to manually handle required setting, since only one validation routine can run for an element, so we need to include required checking in this unique checking routine, if the user selected required too
				$validationCode[] = "\nif ( myform.{$eltname}.value == '' ) {\n";
				$validationCode[] = "window.alert(\"{$eltmsg}\");\n myform.{$eltname}.focus();\n return false;\n";
				$validationCode[] = "}\n";
			}
			if(isset($ele_value[9]) AND $ele_value[9]) {
				$eltmsgUnique = empty($eltcaption) ? sprintf( _formulize_REQUIRED_UNIQUE, $eltname ) : sprintf( _formulize_REQUIRED_UNIQUE, $eltcaption );
				$validationCode[] = "if ( myform.{$eltname}.value != '' ) {\n";
				$validationCode[] = "if(\"{$eltname}\" in formulize_xhr_returned_check_for_unique_value && formulize_xhr_returned_check_for_unique_value[\"{$eltname}\"] != 'notreturned') {\n"; // a value has already been returned from xhr, so let's check that out...
				$validationCode[] = "if(\"{$eltname}\" in formulize_xhr_returned_check_for_unique_value && formulize_xhr_returned_check_for_unique_value[\"{$eltname}\"] != 'valuenotfound') {\n"; // request has come back, form has been resubmitted, but the check turned up postive, ie: value is not unique, so we have to halt submission , and reset the check for unique flag so we can check again when the user has typed again and is ready to submit
				$validationCode[] = "window.alert(\"{$eltmsgUnique}\");\n";
				$validationCode[] = "hideSavingGraphic();\n";
				$validationCode[] = "delete formulize_xhr_returned_check_for_unique_value.{$eltname};\n"; // unset this key
				$validationCode[] = "myform.{$eltname}.focus();\n return false;\n";
				$validationCode[] = "}\n";
				$validationCode[] = "} else {\n";	 // do not submit the form, just send off the request, which will trigger a resubmission after setting the returned flag above to true so that we won't send again on resubmission
				$validationCode[] = "\nvar formulize_xhr_params = []\n";
				$validationCode[] = "formulize_xhr_params[0] = myform.{$eltname}.value;\n";
				$validationCode[] = "formulize_xhr_params[1] = ".$element->getVar('ele_id').";\n";
				$xhr_entry_to_send = is_numeric($entry_id) ? $entry_id : "'".$entry_id."'";
				$validationCode[] = "formulize_xhr_params[2] = ".$xhr_entry_to_send.";\n";
				$validationCode[] = "formulize_xhr_params[4] = leave;\n"; // will have been passed in to the main function and we need to preserve it after xhr is done
				$validationCode[] = "formulize_xhr_send('check_for_unique_value', formulize_xhr_params);\n";
				//$validationCode[] = "showSavingGraphic();\n";
				$validationCode[] = "return false;\n";
				$validationCode[] = "}\n";
				$validationCode[] = "}\n";
			}
		  return $validationCode;
    }

    // this method will read what the user submitted, and package it up however we want for insertion into the form's datatable
    // You can return {WRITEASNULL} to cause a null value to be saved in the database
    // $value is what the user submitted
    // $element is the element object
		// $entry_id is the ID number of the entry that this data is being saved into. Can be "new", or null in the event of a subformblank entry being saved.
    function prepareDataForSaving($value, $element, $entry_id=null) {
			$ele_value = $element->getVar('ele_value');
			// Trim the value if the option is set
			if (isset($ele_value[12]) && $ele_value[12]) {
				$value = trim($value);
			}
			// if $ele_value[3] is 1 (default is 0) then treat this as a numerical field
			if ($ele_value[3] AND $value != "{ID}" AND $value != "{SEQUENCE}") {
					$value = preg_replace ('/[^0-9.-]+/', '', $value);
			}
			global $myts;
			$value = $myts->htmlSpecialChars($value);
			$value = (!is_numeric($value) AND $value == "") ? "{WRITEASNULL}" : $value;
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
			return $value;
    }

    // this method will take a text value that the user has specified at some point, and convert it to a value that will work for comparing with values in the database.  This is used primarily for preparing user submitted text values for saving in the database, or for comparing to values in the database, such as when users search for things.  The typical user submitted values would be coming from a condition form (ie: fieldX = [term the user typed in]) or other situation where the user types in a value that needs to interact with the database.
    // it is only necessary to do special logic here if the values stored in the database do not match what users would be typing, ie: you're using coded numbers in the database, but displaying text on screen to users
    // this would be where a Yes value would be converted to a 1, for example, in the case of a yes/no element, since 1 is how yes is represented in the database for that element type
    // $partialMatch is used to indicate if we should search the values for partial string matches, like On matching Ontario.  This happens in the getData function when processing filter terms (ie: searches typed by users in a list of entries)
    // if $partialMatch is true, then an array may be returned, since there may be more than one matching value, otherwise a single value should be returned.
    // if literal text that users type can be used as is to interact with the database, simply return the $value
    function prepareLiteralTextForDB($value, $element, $partialMatch=false) {
      return convertStringToUseSpecialCharsToMatchDB($value); // function required as long as $myts->htmlSpecialChars is used in prepareDataForSavingMethod
    }

    // this method will format a dataset value for display on screen when a list of entries is prepared
    // for standard elements, this step is where linked selectboxes potentially become clickable or not, among other things
    // Set certain properties in this function, to control whether the output will be sent through a "make clickable" function afterwards, sent through an HTML character filter (a security precaution), and trimmed to a certain length with ... appended.
    function formatDataForList($value, $handle="", $entry_id=0, $textWidth=100) {
        $this->clickable = true;
        $this->striphtml = true;
        $this->length = $textWidth;
        return parent::formatDataForList(trans($value)); // always return the result of formatDataForList through the parent class (where the properties you set here are enforced)
    }

}
