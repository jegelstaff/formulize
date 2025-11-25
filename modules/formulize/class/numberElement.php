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
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/textElement.php"; // we extend the text element class

class formulizeNumberElement extends formulizeTextElement {

	var $defaultValueKey;
	public static $category = "textboxes";

	function __construct() {
		parent::__construct();
		$this->name = "Number Box";
		$this->hasData = true; // set to false if this is a non-data element, like the subform or the grid
		$this->needsDataType = true; // set to false if you're going force a specific datatype for this element using the overrideDataType
		$this->overrideDataType = ""; // use this to set a datatype for the database if you need the element to always have one (like 'date').  set needsDataType to false if you use this.
		$this->adminCanMakeRequired = true; // set to true if the webmaster should be able to toggle this element as required/not required
		$this->alwaysValidateInputs = true; // set to true if you want your custom validation function to always be run.  This will override any required setting that the webmaster might have set, so the recommendation is to set adminCanMakeRequired to false when this is set to true. -- in this case we need the parent routine of the textElement to always run!
		$this->canHaveMultipleValues = false;
		$this->hasMultipleOptions = false;
		$this->defaultValueKey = ELE_VALUE_TEXT_DEFAULTVALUE; // text and textarea do not share the same default value key :(
	}

	/**
	 * Static function to provide the mcp server with the schema for the properties that can be used with the create_form_element and update_form_element tools
	 * Concerned with the properties for the ele_value property of the element object
	 * Follows the convention of properties used publically (MCP, Public API, etc).
	 * @param bool|int $update True if this is being called as part of building the properties for Updating, as opposed to properties for Creating. Default is false (Creating).
	 * @return string The schema for the properties that can be used with the create_form_element and update_form_element tools
	 */
	public static function mcpElementPropertiesDescriptionAndExamples($update = false) {
		$config_handler = xoops_gethandler('config');
		$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
		return
"**Element:** Number Box (number)
**Description:** A single line box for entering numbers, with optional formatting for decimals, prefixes, suffixes, and thousands separators.
**Properties:**
- size (int, width of the box in characters, default is ".$formulizeConfig['t_width'].")
- defaultValue (int or float, default value for new entries)
- decimals (int, number of decimal places to allow, default is ".$formulizeConfig['number_decimals']."),
- prefix (string, text to show before the number, default is '".$formulizeConfig['number_prefix']."'),
- decimalsSeparator (string, character to use as the decimal separator, default is '".$formulizeConfig['number_decimalsep']."')
- thousandsSeparator (string, character to use as the thousands separator, default is '".$formulizeConfig['number_sep']."')
- suffix (string, text to show after the number, default is '".$formulizeConfig['number_suffix']."')
**Examples:**
- A basic number box requires no properties, system defaults will be used
- A number box for recording values between 0 and 99: { size: 2 }
- A three digit number box with a default value of 100: { size: 3, defaultValue: 100 }
- A number box for recording prices up to $999,999.99: { size: 9, defaultValue: 0, decimals: 2, prefix: '$', thousandsSeparator: ',', decimalsSeparator: '.' }";
	}

	/**
	 * An optional method an element class can implement, if there are special considerations for the datatype that should be used for this element type
	 * Called by formulizeHandler::upsertElementSchemaAndResources when an element is created or updated
	 * Also called from element_advanced_save.php when the admin UI is being used to change the datatype for an element
	 * The if function_exists checks for a function defined in the element_adanced_save.php file, which will indicate if we should follow what the user put into the UI
	 * Otherwise, we should go with a passed in default which will be from the upsertElementSchemaAndResources method
	 * @param string $defaultType The default type to use if the element does not have special considerations
	 * @return string The data type to use for this element
	 */
	public function getDefaultDataType($defaultType = 'int') {
		$ele_value = $this->getVar('ele_value');
		if(isset($ele_value[ELE_VALUE_TEXT_DECIMALS]) AND $ele_value[ELE_VALUE_TEXT_DECIMALS] > 0) {
			if($datadecimals = intval($ele_value[ELE_VALUE_TEXT_DECIMALS])) {
				if($datadecimals > 20) {
					$datadecimals = 20;
				}
			} else {
				$datadecimals = 2;
			}
			$datadigits = $datadecimals < 10 ? 11 : $datadecimals + 1; // digits must be larger than the decimal value, but a minimum of 11
			return "decimal($datadigits,$datadecimals)";
		} else {
			return 'int(10)';
		}
	}

	// write code to a file
	public function setVar($key, $value, $not_gpc = false) {
		parent::setVar($key, $value, $not_gpc);
	}

	// read code from a file
	public function getVar($key, $format = 's') {
		$format = $key == "ele_value" ? "f" : $format;
		$value = parent::getVar($key, $format);
		return $value;
	}
}

#[AllowDynamicProperties]
class formulizeNumberElementHandler extends formulizeTextElementHandler {

	function create() {
		return new formulizeNumberElement();
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
			switch($key) {
				case 'size':
				case 'decimals':
					$properties[$key] = intval($value);
					if($properties[$key] < 0) {
						$properties[$key] = 0;
					}
					break;
				case 'defaultValue':
					$properties[$key] = is_numeric($value) ? $value + 0 : 0; // force to int or float
					break;
				case 'prefix':
				case 'decimalsSeparator':
				case 'thousandsSeparator':
				case 'suffix':
					$properties[$key] = trim($value);
					break;
				default:
					unset($properties[$key]); // remove anything we don't recognize
			}
		}
		if(isset($properties['size'])) {
			$ele_value[ELE_VALUE_TEXT_WIDTH] = $properties['size'];
			$ele_value[ELE_VALUE_TEXT_MAXCHARS] = $properties['size'];
		}
		if(isset($properties['defaultValue'])) {
			$ele_value[ELE_VALUE_TEXT_DEFAULTVALUE] = $properties['defaultValue'];
		}
		if(isset($properties['decimals'])) {
			$ele_value[ELE_VALUE_TEXT_DECIMALS] = $properties['decimals'];
		}
		if(isset($properties['prefix'])) {
			$ele_value[ELE_VALUE_TEXT_PREFIX] = $properties['prefix'];
		}
		if(isset($properties['decimalsSeparator'])) {
			$ele_value[ELE_VALUE_TEXT_DECIMALS_SEPARATOR] = $properties['decimalsSeparator'];
		}
		if(isset($properties['thousandsSeparator'])) {
			$ele_value[ELE_VALUE_TEXT_THOUSANDS_SEPARATOR] = $properties['thousandsSeparator'];
		}
		if(isset($properties['suffix'])) {
			$ele_value[ELE_VALUE_TEXT_SUFFIX] = $properties['suffix'];
		}
		return [
			'ele_value' => $ele_value
		];
	}

	public function getDefaultEleValue() {
		$ele_value = array();
		$config_handler = xoops_gethandler('config');
		$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
		$ele_value[ELE_VALUE_TEXT_WIDTH] = isset($formulizeConfig['t_width']) ? $formulizeConfig['t_width'] : 30;
		$ele_value[ELE_VALUE_TEXT_MAXCHARS] = isset($formulizeConfig['t_width']) ? $formulizeConfig['t_width'] : 30;
		$ele_value[ELE_VALUE_TEXT_NUMBERSONLY] = 1;
		$ele_value[ELE_VALUE_TEXT_DECIMALS] = isset($formulizeConfig['number_decimals']) ? $formulizeConfig['number_decimals'] : 0;
		$ele_value[ELE_VALUE_TEXT_PREFIX] = isset($formulizeConfig['number_prefix']) ? $formulizeConfig['number_prefix'] : '';
		$ele_value[ELE_VALUE_TEXT_DECIMALS_SEPARATOR] = isset($formulizeConfig['number_decimalsep']) ? $formulizeConfig['number_decimalsep'] : '.';
		$ele_value[ELE_VALUE_TEXT_THOUSANDS_SEPARATOR] = isset($formulizeConfig['number_sep']) ? $formulizeConfig['number_sep'] : ',';
		$ele_value[ELE_VALUE_TEXT_SUFFIX] = isset($formulizeConfig['number_suffix']) ? $formulizeConfig['number_suffix'] : '';
		$ele_value[ELE_VALUE_TEXT_TRIM_VALUE] = 1;
		return $ele_value;
	}

	// this method would gather any data that we need to pass to the template, besides the ele_value and other properties that are already part of the basic element class
	// it receives the element object and returns an array of data that will go to the admin UI template
	// when dealing with new elements, $element might be FALSE
	// can organize template data into two top level keys, advanced-tab-values and options-tab-values, if there are some options for the element type that appear on the Advanced tab in the admin UI. This requires an additional template file with _advanced.html as the end of the name. Text elements have an example.
	function adminPrepare($element) {
		$dataToSendToTemplate = array();
		if(is_object($element) AND is_subclass_of($element, 'formulizeElement')) { // existing element
			$dataToSendToTemplate['ele_value'] = $element->getVar('ele_value');
		} else { // new element
			$dataToSendToTemplate['ele_value'] = $this->getDefaultEleValue();
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
			// enforce certain values for number boxes
			$ele_value[ELE_VALUE_TEXT_MAXCHARS] = $ele_value[ELE_VALUE_TEXT_WIDTH];
			$ele_value[ELE_VALUE_TEXT_NUMBERSONLY] = 1;
			$ele_value[ELE_VALUE_TEXT_TRIM_VALUE] = 1;
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
		return $ele_value[ELE_VALUE_TEXT_DEFAULTVALUE];
	}

	// this method reads the current state of an element based on the user's input, and the admin options, and sets ele_value to what it needs to be so we can render the element correctly
	// it must return $ele_value, with the correct value set in it, so that it will render as expected in the render method
	// $element is the element object
	// $value is the value that was retrieved from the database for this element in the active entry.  It is a raw value, no processing has been applied, it is exactly what is in the database (as prepared in the prepareDataForSaving method and then written to the DB)
	// $entry_id is the ID of the entry being loaded
	function loadValue($element, $value, $entry_id) {
		$ele_value = $element->getVar('ele_value');
		$ele_value[$this->defaultValueKey] = $ele_value[ELE_VALUE_TEXT_DECIMALS] > 0 ? floatval($value) : intval($value);
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

		if (!strstr(getCurrentURL(),"printview.php") AND !$isDisabled) {
			$form_ele = new XoopsFormText(
				$caption,
				$markupName,
				$ele_value[ELE_VALUE_TEXT_WIDTH],	//	box width
				$ele_value[ELE_VALUE_TEXT_MAXCHARS],	//	max width
				$ele_value[ELE_VALUE_TEXT_DEFAULTVALUE],	//	value
				false,					// autocomplete in browser
				'number'		// numbers only
			);
			$form_ele->setExtra("class='numbers-only-textbox'");
		} else {
			$form_ele = new XoopsFormLabel ($caption, formulize_numberFormat($ele_value[ELE_VALUE_TEXT_DEFAULTVALUE], $element->getVar('ele_handle')), $markupName);
		}
		return $form_ele;
	}

	// this method will read what the user submitted, and package it up however we want for insertion into the form's datatable
	// You can return {WRITEASNULL} to cause a null value to be saved in the database
	// $value is what the user submitted
	// $element is the element object
	// $entry_id is the ID number of the entry that this data is being saved into. Can be "new", or null in the event of a subformblank entry being saved.
	// $subformBlankCounter is the counter for the subform blank entries, if applicable
	function prepareDataForSaving($value, $element, $entry_id=null, $subformBlankCounter=null) {
		$value = preg_replace ('/[^0-9.-]+/', '', trim($value));
		$value = (!is_numeric($value) AND $value == "") ? "{WRITEASNULL}" : $value;
		return $value;
	}

	// this method will format a dataset value for display on screen when a list of entries is prepared
	// for standard elements, this step is where linked selectboxes potentially become clickable or not, among other things
	// Set certain properties in this function, to control whether the output will be sent through a "make clickable" function afterwards, sent through an HTML character filter (a security precaution), and trimmed to a certain length with ... appended.
	function formatDataForList($value, $handle="", $entry_id=0, $textWidth=100) {
		$this->clickable = false;
		$this->striphtml = false;
		$this->length = 100;
		if(strpos($value, '.') !== false) {
			$value = floatval(trim($value));
		} else {
			$value = intval(trim($value));
		}
		return formulize_numberFormat($value, $handle);
	}

}


