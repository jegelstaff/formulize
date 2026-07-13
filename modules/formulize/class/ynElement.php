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
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/radioElement.php";

/**
 * The Yes/No element speaks two vocabularies, and it is important to know which one any given
 * value belongs to:
 *
 * 1. THE DATABASE CODES: YES_DB_VALUE (1) and NO_DB_VALUE (2). These language-independent codes
 *    are what is stored in the data tables, and they are the keys of ele_value (following the
 *    radio-family convention that ele_value keys are the values stored in the database). The
 *    positions submitted by the rendered radio buttons also happen to coincide with the codes:
 *    Yes is always the first option (position 1) and No the second (position 2).
 *
 * 2. THE TRANSLATED TEXT: the _YES and _NO language constants ("Yes"/"No", "Oui"/"Non", etc,
 *    according to the active language). This is what users see and type, everywhere: rendered
 *    options (getListOptions), dataset values (prepareDataForDataset), and filter options
 *    (getFilterOptions). Text is converted back into the codes by prepareLiteralTextForDB.
 *
 * Conversions between the two vocabularies happen only in this file. Note that ele_value was
 * historically keyed by the sentinel strings '_YES' and '_NO' instead of the codes - the getVar
 * override below migrates that legacy structure whenever ele_value is read.
 */
class formulizeYnElement extends formulizeRadioElement {

	const YES_DB_VALUE = 1; // the language-independent code stored in the database when Yes is selected
	const NO_DB_VALUE = 2; // the language-independent code stored in the database when No is selected

	var $defaultValueKey;
	public static $category = "lists";

	function __construct() {
		parent::__construct();
		$this->name = "Yes/No Radio Buttons";
		$this->needsDataType = false; // set to false if you're going force a specific datatype for this element using the overrideDataType
		$this->overrideDataType = "tinyint"; // use this to set a datatype for the database if you need the element to always have one (like 'date').  set needsDataType to false if you use this.
	}

	// This element's ele_value structure changed over time (it used to be keyed by the sentinel
	// strings '_YES'/'_NO' rather than the database codes). Present the current structure to all
	// callers, regardless of what is stored in the database, by migrating legacy values whenever
	// ele_value is read
	public function getVar($key, $format = 's') {
		$value = parent::getVar($key, $format);
		if($key == 'ele_value' AND is_array($value)) {
			$value = formulizeYnElementHandler::backwardsCompatibility($value);
		}
		return $value;
	}

	/**
	 * Static function to provide the mcp server with the schema for the properties that can be used with the create_form_element and update_form_element tools
	 * Concerned with the options for the ele_value property of the element object
	 * Follows the convention of properties used publically (MCP, Public API, etc).
	 * @param bool|int $update True if this is being called as part of building the options for Updating, as opposed to options for Creating. Default is false (Creating).
	 * @return string The schema for the properties that can be used with the create_form_element and update_form_element tools
	 */
	public static function mcpElementPropertiesDescriptionAndExamples($update = false) {
		return
"**Element:** Yes/No Radio Buttons (yn).
**Description:** A pair of radio buttons, one for Yes and one for No.
**Properties:**
- defaultvalue (int, a 1 for 'Yes' and a 0 for 'No', if omitted or empty, no default is set)
**Examples:**
- A Yes/No radio button that has no default value: { }
- A Yes/No radio button that defaults to No: { defaultvalue: 0 }
- A Yes/No radio button that defaults to Yes: { defaultvalue: 1 }";
	}

	// return the set of options for this element, as an array of translated Yes/No text => selected flag.
	// If a state array is passed in (an ele_value as prepared by loadValue, reflecting the current entry),
	// the returned options carry that state's selection flags; otherwise the configured defaults are returned.
	function getListOptions($ele_value = null) {
		$ele_value = is_array($ele_value) ? formulizeYnElementHandler::backwardsCompatibility($ele_value) : $this->getVar('ele_value');
		$options = [_YES => 0, _NO => 0];
		foreach($ele_value as $optionKey=>$selected) {
			if($optionKey == self::YES_DB_VALUE) {
				$options[_YES] = $selected;
			} elseif($optionKey == self::NO_DB_VALUE) {
				$options[_NO] = $selected;
			}
		}
		return $options;
	}

}
#[AllowDynamicProperties]
class formulizeYnElementHandler extends formulizeRadioElementHandler {

	function create() {
		return new formulizeYnElement();
	}

	/**
	 * Migrate a legacy ele_value structure to the current one. Historically, ele_value was keyed
	 * by the sentinel strings '_YES' and '_NO'. The current structure is keyed by the database
	 * codes (YES_DB_VALUE and NO_DB_VALUE), matching the radio-family convention that ele_value
	 * keys are the values stored in the database. Idempotent - current structures pass through.
	 * @param array $ele_value The raw ele_value
	 * @return array The ele_value in the current structure
	 */
	static function backwardsCompatibility($ele_value) {
		if(isset($ele_value['_YES']) OR isset($ele_value['_NO'])) {
			$ele_value = array(
				formulizeYnElement::YES_DB_VALUE => isset($ele_value['_YES']) ? $ele_value['_YES'] : 0,
				formulizeYnElement::NO_DB_VALUE => isset($ele_value['_NO']) ? $ele_value['_NO'] : 0
			);
		}
		return $ele_value;
	}

	/**
	 * Validate properties for this element type, based on the structure used publically (MCP, Public API, etc).
	 * The description in the mcpElementPropertiesDescriptionAndExamples static method on the element class, follows this convention
	 * properties are the contents of the ele_value property on the object
	 * @param array $properties The properties to validate
	 * @param array $ele_value The ele_value settings for this element, if applicable. Should be set by the caller, to the current ele_value settings of the element, if this is an existing element.
	 * @param int|string|object $elementIdentifier The element id, handle or object of the element for which we're validating the properties.. Should be set by the caller, to the current ele_value settings of the element, if this is an existing element.
	 * @return array An array of properties ready for the object. Usually just ele_value but could be others too.
	 */
	public function validateEleValuePublicAPIProperties($properties, $ele_value = [], $elementIdentifier = null) {
		$ele_value = self::backwardsCompatibility($ele_value); // make sure anything we write back out is in the current structure
		if(isset($properties['defaultvalue'])) {
			if($properties['defaultvalue']) {
				$ele_value[formulizeYnElement::YES_DB_VALUE] = 1;
				$ele_value[formulizeYnElement::NO_DB_VALUE] = 0;
			} elseif(!$properties['defaultvalue']) {
				$ele_value[formulizeYnElement::YES_DB_VALUE] = 0;
				$ele_value[formulizeYnElement::NO_DB_VALUE] = 1;
			}
		}
		return [
			'ele_value' => $ele_value
		];
	}

	public function getDefaultEleValue() {
		return array(
			formulizeYnElement::YES_DB_VALUE => 0, // a 1/0 indicating if Yes is the default
			formulizeYnElement::NO_DB_VALUE => 0 // a 1/0 indicating if No is the default
		);
	}

	/**
	 * Return the filter options for a yes/no element.
	 *
	 * The values stored in the database are the language-independent codes, but users see and
	 * type the translated Yes/No text, so the filter options are keyed on that text -
	 * prepareLiteralTextForDB() below converts the text back into the codes when the filter is
	 * applied to the database.
	 * @param object $element The element object
	 * @return array Associative array of filter value => label
	 */
	function getFilterOptions($element = null) {
		return array(_YES => formulizeYnElement::YES_DB_VALUE, _NO => formulizeYnElement::NO_DB_VALUE);
	}

	/**
	 * A yes/no element's ele_value keys are the database codes, and the value coming from a
	 * previous entry has already been converted to the translated Yes/No text by
	 * prepareDataForDataset(). So the option text match that the radio element does would never
	 * succeed here - map the translated text onto the fixed option positions instead (Yes is
	 * always the first option, No always the second).
	 * @param string $value The value from the previous entry (as prepared for a dataset)
	 * @param array $prevEleValue The ele_value of the element in the previous form (not needed)
	 * @return int|bool The zero-based position of the matching option, or false if there is no match
	 */
	function previousEntryOptionKey($value, $prevEleValue) {
		if($value == _YES) {
			return 0;
		} elseif($value == _NO) {
			return 1;
		}
		return false;
	}

	// this method would gather any data that we need to pass to the template, besides the ele_value and other properties that are already part of the basic element class
	// it receives the element object and returns an array of data that will go to the admin UI template
	// when dealing with new elements, $element might be FALSE
	// can organize template data into two top level keys, advanced-tab-values and options-tab-values, if there are some options for the element type that appear on the Advanced tab in the admin UI. This requires an additional template file with _advanced.html as the end of the name. Text elements have an example.
	function adminPrepare($element) {
		$dataToSendToTemplate = array();
		// send the database codes to the template, so the radio buttons there can submit them back to adminSave
		$dataToSendToTemplate['yes_db_value'] = formulizeYnElement::YES_DB_VALUE;
		$dataToSendToTemplate['no_db_value'] = formulizeYnElement::NO_DB_VALUE;
		if($element != false) {
			$ele_value = $element->getVar('ele_value');
			$dataToSendToTemplate['ele_value_yes'] = $ele_value[formulizeYnElement::YES_DB_VALUE];
    	$dataToSendToTemplate['ele_value_no'] = $ele_value[formulizeYnElement::NO_DB_VALUE];
		}
		return $dataToSendToTemplate;
	}

	// this method would read back any data from the user after they click save in the admin UI, and save the data to the database, if it were something beyond what is handled in the basic element class
	// this is called as part of saving the options tab.  It receives a copy of the element object immediately prior to it being saved, so the element object will have all its properties set as they would be based on the user's changes in the names & settings tab, and in the options tab (the tabs are saved in order from left to right).
	// the exception is the special ele_value array, which is passed separately from the object (this will contain the values the user set in the Options tab)
	// You can modify the element object in this function and since it is an object, and passed by reference by default, then your changes will be saved when the element is saved.
	// You should return a flag to indicate if any changes were made, so that the page can be reloaded for the user, and they can see the changes you've made here.
	// advancedTab is a flag to indicate if this is being called from the advanced tab (as opposed to the Options tab, normal behaviour). In this case, $ele_value is empty and you have to go off first principals based on what is in $_POST.
	function adminSave($element, $ele_value = array(), $advancedTab = false) {
		$changed = false;
		if(is_object($element) AND is_subclass_of($element, 'formulizeElement')) {
			$ele_value = array();
			if($_POST['elements_ele_value'] == formulizeYnElement::YES_DB_VALUE) {
				$ele_value[formulizeYnElement::YES_DB_VALUE] = 1;
				$ele_value[formulizeYnElement::NO_DB_VALUE] = 0;
			} elseif($_POST['elements_ele_value'] == formulizeYnElement::NO_DB_VALUE) {
				$ele_value[formulizeYnElement::YES_DB_VALUE] = 0;
				$ele_value[formulizeYnElement::NO_DB_VALUE] = 1;
			} else {
				$ele_value[formulizeYnElement::YES_DB_VALUE] = 0;
				$ele_value[formulizeYnElement::NO_DB_VALUE] = 0;
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
		$default = null; // no default configured (neither Yes nor No flagged)
    if($ele_value[formulizeYnElement::YES_DB_VALUE] == 1) {
      $default = formulizeYnElement::YES_DB_VALUE;
    } elseif($ele_value[formulizeYnElement::NO_DB_VALUE] == 1) {
      $default = formulizeYnElement::NO_DB_VALUE;
    }
		return $default;
	}

	// this method reads the current state of an element based on the user's input, and the admin options, and sets ele_value to what it needs to be so we can render the element correctly
	// it must return $ele_value, with the correct value set in it, so that it will render as expected in the render method
	// $element is the element object
	// $value is the value that was retrieved from the database for this element in the active entry.  It is a raw value, no processing has been applied, it is exactly what is in the database (as prepared in the prepareDataForSaving method and then written to the DB)
	// $entry_id is the ID of the entry being loaded
	function loadValue($element, $value, $entry_id) {
		if($value == formulizeYnElement::YES_DB_VALUE) {
			$ele_value = array(formulizeYnElement::YES_DB_VALUE=>1, formulizeYnElement::NO_DB_VALUE=>0);
		} elseif($value == formulizeYnElement::NO_DB_VALUE) {
			$ele_value = array(formulizeYnElement::YES_DB_VALUE=>0, formulizeYnElement::NO_DB_VALUE=>1);
		} else {
			$ele_value = array(formulizeYnElement::YES_DB_VALUE=>0, formulizeYnElement::NO_DB_VALUE=>0);
		}
		return $ele_value;
	}

	// this method will read what the user submitted, and package it up however we want for insertion into the form's datatable
	// You can return null to cause a null value to be saved in the database
	// $value is what the user submitted
	// $element is the element object
	// $entry_id is the ID number of the entry that this data is being saved into. Can be "new", or null in the event of a subformblank entry being saved.
	// $subformBlankCounter is the counter for the subform blank entries, if applicable
	function prepareDataForSaving($value, $element, $entry_id=null, $subformBlankCounter=null) {
		// the rendered radio buttons submit the position of the chosen option (see the radio
		// element's render method), and the positions happen to coincide with the database codes:
		// Yes is always the first option (position 1 = YES_DB_VALUE) and No the second
		// (position 2 = NO_DB_VALUE), so the submitted value can be stored as is
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
		if ($value == formulizeYnElement::YES_DB_VALUE) {
			$value = _YES;
		} elseif ($value == formulizeYnElement::NO_DB_VALUE) {
			$value = _NO;
		} else {
			$value = "";
		}
		return $value;
	}

	// this method will take a text value that the user has specified at some point, and convert it to a value that will work for comparing with values in the database.  This is used primarily for preparing user submitted text values for saving in the database, or for comparing to values in the database, such as when users search for things.  The typical user submitted values would be coming from a condition form (ie: fieldX = [term the user typed in]) or other situation where the user types in a value that needs to interact with the database.
	// it is only necessary to do special logic here if the values stored in the database do not match what users would be typing, ie: you're using coded numbers in the database, but displaying text on screen to users
	// this would be where a Yes value would be converted to a 1, for example, in the case of a yes/no element, since 1 is how yes is represented in the database for that element type
	// $partialMatch is used to indicate if we should search the values for partial string matches, like On matching Ontario.  This happens in the gatherDataset function when processing filter terms (ie: searches typed by users in a list of entries)
	// if $partialMatch is true, then an array may be returned, since there may be more than one matching value, otherwise a single value should be returned.
	// if literal text that users type can be used as is to interact with the database, simply return the $value
	// LINKED ELEMENTS AND UITEXT ARE RESOLVED PRIOR TO THIS METHOD BEING CALLED
	function prepareLiteralTextForDB($value, $element, $partialMatch=false) {
		// since we're matching based on even a single character match between the query and the yes/no language constants, if the current language has the same letters or letter combinations in yes and no, then sometimes only Yes may be searched for
		if (($value AND strstr(strtoupper(_YES), strtoupper($value))) OR strtoupper($value) == "YES") {
			$value = formulizeYnElement::YES_DB_VALUE;
		} elseif (($value AND strstr(strtoupper(_NO), strtoupper($value))) OR strtoupper($value) == "NO") {
			$value = formulizeYnElement::NO_DB_VALUE;
		} elseif (
			$value !== ""
			AND $value !== null
      AND $value != formulizeYnElement::YES_DB_VALUE
      AND $value != formulizeYnElement::NO_DB_VALUE
		) {
			$value = false; // not a valid code, so there is no match
		}

		return $value;
	}

	// this method will format a dataset value for display on screen when a list of entries is prepared
	// for standard elements, this step is where linked selectboxes potentially become clickable or not, among other things
	// Set certain properties in this function, to control whether the output will be sent through a "make clickable" function afterwards, sent through an HTML character filter (a security precaution), and trimmed to a certain length with ... appended.
	function formatDataForList($value, $handle="", $entry_id=0, $textWidth=100) {
		$this->clickable = false;
		$this->striphtml = false;
		$this->length = 0;
		return parent::formatDataForList($value); // always return the result of formatDataForList through the parent class (where the properties you set here are enforced)
	}

}


