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

class formulizeSliderElement extends formulizeElement {

	public static $category = "selectors";

	function __construct() {
			$this->name = "Range Slider";
			$this->hasData = true;
			$this->needsDataType = false; // should always take integer
			$this->overrideDataType = 'int';
			$this->adminCanMakeRequired = true;
			$this->alwaysValidateInputs = false; // only validate when the admin says it's a required element
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
		$config_handler = xoops_gethandler('config');
		$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
		return
"**Element:** Range Slider (slider)
**Description:** A line with a knob that can be dragged with a mouse (or with a finger on a touchscreen). The setting of the knob indicates a certain number. Range Sliders are useful for allowing users to pick a number from a range of numbers, without having to type in the number.
**Properties:**
- minValue (integer, the minimum value for the slider. Default is 0.)
- maxValue (integer, the maximum value for the slider. Default is 100.)
- stepValue (integer, the increments allowed for the slider. Default is 10.)
- defaultValue (integer, the starting value for the slider. Default is 0.)
**Examples:**
- A range slider where the user can pick any number between 0 and 100, defaults to 50: { minValue: 0, maxValue: 100, stepValue: 1, defaultValue: 50 }
- A range slider where the user can pick 10, 20, 30, 40, or 50. Default to 10: { minValue: 10, maxValue: 50, stepValue: 10, defaultValue: 10 }
- A range slider where the user can pick any number from -10 to 10. Default will be 0 since it is not specified: { minValue: -10, maxValue: 10, stepValue: 1 }";
	}

}

#[AllowDynamicProperties]
class formulizeSliderElementHandler extends formulizeElementsHandler {
    var $db;
    var $clickable; // used in formatDataForList
    var $striphtml; // used in formatDataForList
    var $length; // used in formatDataForList

    function __construct($db) {
        $this->db =& $db;
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
		if(isset($properties['minValue']) AND is_numeric($properties['minValue'])) {
			$ele_value[0] = $properties['minValue'];
		}
		if(isset($properties['maxValue']) AND is_numeric($properties['maxValue'])) {
			$ele_value[1] = $properties['maxValue'];
		}
		if(isset($properties['stepValue']) AND is_numeric($properties['stepValue'])) {
			$ele_value[2] = $properties['stepValue'];
		}
		if(isset($properties['defaultValue']) AND is_numeric($properties['defaultValue'])) {
			$ele_value[3] = $properties['defaultValue'];
		}
		return [
			'ele_value' => $ele_value
		];
	}

		public function getDefaultEleValue() {
			return [
				0 => 0, // min
				1 => 100, // max
				2 => 10, // step
				3 => 0 // default
			];
		}

    function create() {
        return new formulizeSliderElement();
    }

    // Gathers data to pass to the template
    // Excludes $ele_value and other properties that are part of the basic element class
    // Receives the element object
    // Returns array of data to the admin UI template
    // For new elements $element might be FALSE
    // can organize template data into two top level keys, advanced-tab-values and options-tab-values, if there are some options for the element type that appear on the Advanced tab in the admin UI. This requires an additional template file with _advanced.html as the end of the name. Text elements have an example.
	function adminPrepare($element) {
        $ele_value = $element ? $element->getVar('ele_value') : array();

        $formlink = createFieldList($ele_value[3], true);
        if (!$element) {
            //Min Velue
            $ele_value[0] = 0;
            //Max Value
            $ele_value[1] = 100;
            //Step size
            $ele_value[2] = 10;
        }
        return array('formlink'=>$formlink->render(), 'ele_value'=>$ele_value);
    }

    // Gather any data to pass to template besides the ele_value
    // Receives the element object
    // Returns an array of data that will go to the admin UI template
    // When dealing with new elements, $element might be FALSE
    // advancedTab is a flag to indicate if this is being called from the advanced tab (as opposed to the Options tab, normal behaviour). In this case, you have to go off first principals based on what is in $_POST to setup the advanced values inside ele_value (presumably).
	function adminSave($element, $ele_value = array(), $advancedTab = false) {
        $changed = false;
        $element->setVar('ele_value', $ele_value);
        return $changed;
    }

		/**
		 * Returns the default value for this element, for a new entry in the specified form.
		 * Determines database ready values, not necessarily human readable values
		 * @param $element The element object
		 * @param int|string $entry_id 'new' or the id of an entry we should use when evaluating the default value - only relevant when determining second pass at defaults when subform entries are written? (which would be better done by comprehensive conditional rendering?)
		 * @return mixed The default value
		 */
		function getDefaultValue($element, $entry_id = 'new') {
			$ele_value = $element->getVar('ele_value');
			return intval($ele_value[3]);
		}

    // Reads current state of element, updates ele_value to a renderable state
		// $element is the element object
		// $value is the value that was retrieved from the database for this element in the active entry.  It is a raw value, no processing has been applied, it is exactly what is in the database (as prepared in the prepareDataForSaving method and then written to the DB)
    // $entry_id is the ID of the entry being loaded
	function loadValue($element, $value, $entry_id) {
				$ele_value = $element->getVar('ele_value');
        $ele_value[3] = $value;
        return $ele_value;
    }

    // Renders the element for display in a form
    // Caption is pre-prepared and passed in separately from the element object
    // If element is disabled return a label with some version of the elements value
    // $ele_value contains options for this element
    // $caption is the prepared caption for the element
    // $markupName name of rendered element in the HTML
    // $isDisabled flags whether the element should be rendered or not
		// $element is the element object
    // $entry_id is the ID number of the entry where this particular element comes from
    // $screen is the screen object that is in effect, if any (may be null)
    function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen, $owner) {
        $slider_html = "<input type=\"range\" ";
        $slider_html .= "name=\"{$markupName}\"";
        $slider_html .= "id=\"{$markupName}\"";
        $slider_html .= "min=\"{$ele_value[0]}\" ";
        $slider_html .= "max=\"{$ele_value[1]}\" ";
        $slider_html .= "step=\"{$ele_value[2]}\" ";
        $slider_html .= "value=\"{$ele_value[3]}\" ";
        $slider_html .= "aria-describedby=\"{$markupName}-help-text\" ";
        $slider_html .= "oninput=\"updateTextInput_{$markupName}(value);formulizechanged=1;\">";
        $slider_html .= "</input>";

        $value_html = "<output id=\"rangeValue_{$markupName}\" type=\"text\" size=\"3\"";
        $value_html.= "for=\"{$markupName}\"";
        $value_html .= ">{$ele_value[3]}<output>";

        $form_slider_value = new XoopsFormLabel($caption, $value_html);
        $form_slider = new XoopsFormLabel($caption, $slider_html);

        $update_script = "<script type=\"text/javascript\">";
        $update_script .= "function updateTextInput_{$markupName}(val) {";
        $update_script .= "document.getElementById('rangeValue_{$markupName}').value=val;\n";
        $update_script .= "let event = new Event('change');\n";
        $update_script .= "document.getElementById('{$markupName}').dispatchEvent(event);}\n";
        $update_script .= "</script>";

        if($isDisabled) {
            $renderedValue = $form_slider_value->render();
						$config_handler = xoops_gethandler('config');
    				$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
						if(trim(strip_tags($renderedValue)) == 0 AND $formulizeConfig['show_empty_elements_when_read_only'] == false) {
							$renderedValue = "";
						}
            $form_ele = new XoopsFormLabel($caption, "$renderedValue");
        } else {
            $renderedSlider = $form_slider->render();
            $renderedValue = $form_slider_value->render();
            $form_ele = new XoopsFormLabel($caption, "<nobr>$renderedSlider $renderedValue</nobr>$update_script");
        }

        return $form_ele;
    }

    // Returns any custom validation code (javascript) to validate this element
    // 'myform' is a name enforced by convention to refer to the current form
    // adminCanMakeRequired/alwaysValidateInputs properties control usage
    function generateValidationCode($caption, $markupName, $element, $entry_id) {
    }

    // this method will read what the user submitted, and package it up however we want for insertion into the form's datatable
	// You can return {WRITEASNULL} to cause a null value to be saved in the database
	// $value is what the user submitted
	// $element is the element object
	// $entry_id is the ID number of the entry that this data is being saved into. Can be "new", or null in the event of a subformblank entry being saved.
	// $subformBlankCounter is the counter for the subform blank entries, if applicable
	function prepareDataForSaving($value, $element, $entry_id=null, $subformBlankCounter=null) {
        return formulize_db_escape($value);
    }

    // Handle any final actions that have to happen after data has been saved
    // Typically required for modifications to new entries after the entry ID has been assigned because before now the entry ID will have been "new"
    // $value is the value that was just saved
    // $element_id is the id of the element that just had data saved
    // $entry_id is the entry id that was just saved
    // ALSO, $GLOBALS['formulize_afterSavingLogicRequired']['elementId'] = type , must be declared in the prepareDataForSaving step if further action is required now -- see fileUploadElement.php for an example
    function afterSavingLogic($value, $element_id, $entry_id) {
    }

    // Returns data from the database to be printed in the List Screen
    // $value is the raw value that has been found in the database
    // $handle is the element handle for the field being retrieved
    // $entry_id is the element id in the form
    function prepareDataForDataset($value, $handle, $entry_id) {
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
      return $value;
    }

    // this method will format a dataset value for display on screen when a list of entries is prepared
    // for standard elements, this step is where linked selectboxes potentially become clickable or not, among other things
    // Set certain properties in this function, to control whether the output will be sent through a "make clickable" function afterwards, sent through an HTML character filter (a security precaution), and trimmed to a certain length with ... appended.
    function formatDataForList($value, $handle="", $entry_id=0, $textWidth=100) {
        $this->clickable = false; // make urls clickable
        $this->striphtml = false; // remove html tags as a security precaution
        $this->length = 0; // truncate to a maximum of 100 characters, and append ... on the end

        return parent::formatDataForList($value); // always return the result of formatDataForList through the parent class (where the properties you set here are enforced)
    }

}
