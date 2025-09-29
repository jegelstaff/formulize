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

define('ELE_VALUE_DATE_DEFAULT', 0);
define('ELE_VALUE_DATE_MIN', 'date_past_days');
define('ELE_VALUE_DATE_MAX', 'date_future_days');

class formulizeDateElement extends formulizeElement {

	var $defaultValueKey;
	public static $category = "selectors";

	function __construct() {
		$this->name = "Date Selector";
		$this->hasData = true; // set to false if this is a non-data element, like the subform or the grid
		$this->needsDataType = false; // set to false if you're going force a specific datatype for this element using the overrideDataType
		$this->overrideDataType = "date"; // use this to set a datatype for the database if you need the element to always have one (like 'date').  set needsDataType to false if you use this.
		$this->adminCanMakeRequired = true; // set to true if the webmaster should be able to toggle this element as required/not required
		$this->alwaysValidateInputs = false; // set to true if you want your custom validation function to always be run.  This will override any required setting that the webmaster might have set, so the recommendation is to set adminCanMakeRequired to false when this is set to true.
		$this->canHaveMultipleValues = false;
		$this->hasMultipleOptions = false;
		parent::__construct();
	}

	/**
	 * Static function to provide the mcp server with the schema for the properties that can be used with the create_form_element and update_form_element tools
	 * Concerned with the properties for the ele_value property of the element object
	 * Follows the convention of properties used publically (MCP, Public API, etc).
	 * @param bool|int $update True if this is being called as part of building the properties for Updating, as opposed to properties for Creating. Default is false (Creating).
	 * @return string The schema for the properties that can be used with the create_form_element and update_form_element tools
	 */
	public static function mcpElementPropertiesDescriptionAndExamples($update = false) {
		return
"**Element:** Date Selector (date).
**Properties:**
- defaultDate (date, the default date value for the date selector in YYYY-MM-DD format. Can also be {TODAY} to default to the current date, or a relative date like {TODAY+7} or {TODAY-30}. Leave blank for no default date.)
- minDate (date, optional, the minimum date that can be selected in YYYY-MM-DD format)
- maxDate (date, optional, the maximum date that can be selected in YYYY-MM-DD format)
**Examples:**
- A date selector that defaults to the current date: { defaultDate: \"{TODAY}\" }
- A date selector that defaults to May 9, 1969: { defaultDate: \"1969-05-09\" }
- A date selector with a minimum date: { minDate: \"2020-01-01\" }
- A date selector with a maximum date: { maxDate: \"2020-12-31\" }
- A date selector that defaults to 7 days from today, with a minimum date of today and a maximum date of 30 days from today: { defaultvalue: \"{TODAY+7}\", mindate: \"{TODAY}\", maxdate: \"{TODAY+30}\" }";
	}
}

#[AllowDynamicProperties]
class formulizeDateElementHandler extends formulizeElementsHandler {

	var $db;
	var $clickable; // used in formatDataForList
	var $striphtml; // used in formatDataForList
	var $length; // used in formatDataForList

	function __construct($db) {
		$this->db =& $db;
	}

	function create() {
		return new formulizeDateElement();
	}

	/**
	 * Validate properties for this element type, based on the structure used publically (MCP, Public API, etc).
	 * The description in the mcpElementPropertiesDescriptionAndExamples static method on the element class, follows this convention
	 * properties are the contents of the ele_value property on the object
	 * @param array $properties The properties to validate
	 * @param array $ele_value The ele_value settings for this element, if applicable. Should be set by the caller, to the current ele_value settings of the element, if this is an existing element.
	 * @param int|string|object $elementIdentifier The element id, handle or object of the element for which we're validating the properties.
	 * @return array An array of properties ready for the object. Usually just ele_value but could be others too.
	 */
	public function validateEleValuePublicAPIProperties($properties, $ele_value = [], $elementIdentifier = null) {
		foreach($properties as $key => $value) {
			// accept any string that starts and ends with {} as is, otherwise strings must be in YYYY-MM-DD format
			// integers are not valid for date elements
			// empty values are accepted as is (results in no default date)
			if($value AND (
				!is_string($value) OR
					(!preg_match("/^\d{4}-\d{2}-\d{2}$/", $value) AND (substr($value, 0, 1) != "{" AND substr($value, 1, -1) != "}"))
				)){
				unset($properties[$key]);
			}
		}
		if(isset($properties['defaultDate'])) {
			$ele_value[ELE_VALUE_DATE_DEFAULT] = $properties['defaultDate'];
		}
		if(isset($properties['minDate'])) {
			$ele_value[ELE_VALUE_DATE_MIN] = $properties['minDate'];
		}
		if(isset($properties['maxDate'])) {
			$ele_value[ELE_VALUE_DATE_MAX] = $properties['maxDate'];
		}
		return [
			'ele_value' => $ele_value
		];
	}

	public function getDefaultEleValue() {
		return array(
			ELE_VALUE_DATE_DEFAULT => '',
			ELE_VALUE_DATE_MIN => '',
			ELE_VALUE_DATE_MAX => ''
		);
	}

	// this method would gather any data that we need to pass to the template, besides the ele_value and other properties that are already part of the basic element class
	// it receives the element object and returns an array of data that will go to the admin UI template
	// when dealing with new elements, $element might be FALSE
	// can organize template data into two top level keys, advanced-tab-values and options-tab-values, if there are some options for the element type that appear on the Advanced tab in the admin UI. This requires an additional template file with _advanced.html as the end of the name. Text elements have an example.
	function adminPrepare($element) {
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
			if($ele_value[0] != _DATE_DEFAULT AND $ele_value[0] != "") { // still checking for old YYYY-mm-dd string, just in case.  It should never be sent back as a value now, but if we've missed something and it is sent back, leaving this check here ensures it will properly be turned into "", ie: no date.
				if(preg_replace("/[^A-Z{}]/","", $ele_value[0]) === "{TODAY}") {
					$ele_value[0] = $ele_value[0];
				} elseif(substr($ele_value[0], 0, 1) !='{' OR substr($ele_value[0], -1) !='}') {
					$ele_value[0] = date("Y-m-d", strtotime($ele_value[0]));
				}
			} else {
				$ele_value[0] = "";
			}
			$element->setVar('ele_value', $ele_value);
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
		$ele_value = $element->getVar('ele_value');
		$default = getDateElementDefault($ele_value[0], $entry_id);
		if (false !== $default) {
			$default = is_numeric($default) ? date("Y-m-d", $default) : $default;
		}
		return $default;
	}

	// this method reads the current state of an element based on the user's input, and the admin options, and sets ele_value to what it needs to be so we can render the element correctly
	// it must return $ele_value, with the correct value set in it, so that it will render as expected in the render method
	// $element is the element object
	// $value is the value that was retrieved from the database for this element in the active entry.  It is a raw value, no processing has been applied, it is exactly what is in the database (as prepared in the prepareDataForSaving method and then written to the DB)
	// $entry_id is the ID of the entry being loaded
	function loadValue($element, $value, $entry_id) {
		$ele_value = $element->getVar('ele_value');
		if(!$value AND substr($ele_value[0],0,1) == '{' AND substr($ele_value[0],-1) == '}') {
			$value = $ele_value[0];
		}
		$ele_value[0] = $value;
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

		if($isDisabled) {

			$form_ele = new XoopsFormLabel($caption, (($ele_value[0] AND $ele_value[0] != _DATE_DEFAULT) ? $this->prepareDataForDataset($ele_value[0], $element->getVar('ele_handle'), $entry_id) : ""));

		} else {

			// if there's no value (ie: it's blank) ... OR it's the default value because someone submitted a date field without actually specifying a date, that last part added by jwe 10/23/04
			if(!$ele_value[0] OR $ele_value[0] == _DATE_DEFAULT) {
				$form_ele = new XoopsFormTextDateSelect($caption, $markupName, 15, "");
				$form_ele->setExtra(" onchange=\"javascript:formulizechanged=1;\" jquerytag=\"$markupName\" ");
			} else {
				$form_ele = new XoopsFormTextDateSelect($caption, $markupName, 15, getDateElementDefault($ele_value[0], $entry_id));
				$form_ele->setExtra(" onchange=\"javascript:formulizechanged=1;\" jquerytag=\"$markupName\" ");
			} // end of check to see if the default setting is for real
			if (!$isDisabled) {
				$limit_past = (isset($ele_value["date_past_days"]) and $ele_value["date_past_days"] != "");
				$limit_future = (isset($ele_value["date_future_days"]) and $ele_value["date_future_days"] != "");
				if ($limit_past or $limit_future) {
					if($limit_past AND $pastSeedDate = getDateElementDefault($ele_value["date_past_days"], $entry_id)) {
						$form_ele->setExtra(" min='".date('Y-m-d', $pastSeedDate)."' ");
					}
					if($limit_future AND $futureSeedDate = getDateElementDefault($ele_value["date_future_days"], $entry_id)) {
						$form_ele->setExtra(" max='".date('Y-m-d', $futureSeedDate)."' ");
					}
					$form_ele->setExtra(" onchange=\"javascript:formulizechanged=1;check_date_limits('$markupName');\" onclick=\"javascript:check_date_limits('$markupName');\" onblur=\"javascript:check_date_limits('$markupName');\" jquerytag=\"$markupName\" ");
				} else {
					$form_ele->setExtra(" onchange=\"javascript:formulizechanged=1;\" jquerytag=\"$markupName\" ");
				}
			}
		}
		return $form_ele;
	}

	// this method returns any custom validation code (javascript) that should figure out how to validate this element
	// 'myform' is a name enforced by convention that refers to the form where this element resides
	// use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
	function generateValidationCode($caption, $markupName, $element, $entry_id=false) {
		$validationCode = array();
		// added validation code - sept 5 2007 - jwe
		$eltname = $markupName;
		$eltcaption = $caption;
		$eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, strip_tags(htmlspecialchars_decode($eltcaption, ENT_QUOTES)));
		$eltmsg = str_replace('"', '\"', stripslashes( $eltmsg ) );
		// parseInt() is used to determine if the element value contains a number
		// Date.parse() would be better, except that it will fail for dd-mm-YYYY format, ie: 22-11-2013
		$validationCode[] = "\nif (isNaN(parseInt(myform.{$eltname}.value))) {\n window.alert(\"{$eltmsg}\");\n myform.{$eltname}.focus();\n return false;\n }\n";
		return $validationCode;
	}

	// this method will read what the user submitted, and package it up however we want for insertion into the form's datatable
	// You can return {WRITEASNULL} to cause a null value to be saved in the database
	// $value is what the user submitted
	// $element is the element object
	// $subformBlankCounter is the counter for the subform blank entries, if applicable
	function prepareDataForSaving($value, $element, $entry_id=null, $subformBlankCounter=null) {
		$timestamp = strtotime($value);
		if ($value != _DATE_DEFAULT AND $value != "" AND $timestamp !== false) { // $timestamp !== false should catch everything by itself? under some circumstance not yet figured out, the other checks could be useful?
			$value = date("Y-m-d", $timestamp);
		} else {
			$value = "{WRITEASNULL}"; // forget about this date element and go on to the next element in the form
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
		return $value ? date(_SHORTDATESTRING, strtotime($value)) : "";
	}

	// this method will take a text value that the user has specified at some point, and convert it to a value that will work for comparing with values in the database.  This is used primarily for preparing user submitted text values for saving in the database, or for comparing to values in the database, such as when users search for things.  The typical user submitted values would be coming from a condition form (ie: fieldX = [term the user typed in]) or other situation where the user types in a value that needs to interact with the database.
	// it is only necessary to do special logic here if the values stored in the database do not match what users would be typing, ie: you're using coded numbers in the database, but displaying text on screen to users
	// this would be where a Yes value would be converted to a 1, for example, in the case of a yes/no element, since 1 is how yes is represented in the database for that element type
	// $partialMatch is used to indicate if we should search the values for partial string matches, like On matching Ontario.  This happens in the getData function when processing filter terms (ie: searches typed by users in a list of entries)
	// if $partialMatch is true, then an array may be returned, since there may be more than one matching value, otherwise a single value should be returned.
	// if literal text that users type can be used as is to interact with the database, simply return the $value
	// LINKED ELEMENTS AND UITEXT ARE RESOLVED PRIOR TO THIS METHOD BEING CALLED
	function prepareLiteralTextForDB($value, $element, $partialMatch=false) {
		return $value;
	}

	// this method will format a dataset value for display on screen when a list of entries is prepared
	// for standard elements, this step is where linked selectboxes potentially become clickable or not, among other things
	// Set certain properties in this function, to control whether the output will be sent through a "make clickable" function afterwards, sent through an HTML character filter (a security precaution), and trimmed to a certain length with ... appended.
	function formatDataForList($value, $handle="", $entry_id=0, $textWidth=100) {
		$this->clickable = false;
		$this->striphtml = false;
		$this->length = 100;
		return parent::formatDataForList($value); // always return the result of formatDataForList through the parent class (where the properties you set here are enforced)
	}

}


