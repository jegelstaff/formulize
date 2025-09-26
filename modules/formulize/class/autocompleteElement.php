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
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/selectElement.php";

class formulizeAutocompleteElement extends formulizeSelectElement {

	function __construct() {
		parent::__construct();
		$this->name = "Autocomplete List";
		$this->hasData = true; // set to false if this is a non-data element, like the subform or the grid
		$this->needsDataType = true; // set to false if you're going force a specific datatype for this element using the overrideDataType
		$this->overrideDataType = ""; // use this to set a datatype for the database if you need the element to always have one (like 'date').  set needsDataType to false if you use this.
		$this->adminCanMakeRequired = true; // set to true if the webmaster should be able to toggle this element as required/not required
		$this->alwaysValidateInputs = false; // set to true if you want your custom validation function to always be run.  This will override any required setting that the webmaster might have set, so the recommendation is to set adminCanMakeRequired to false when this is set to true.
		// $this->canHaveMultipleValues = false; // set by setCanHaveMultipleValues method in the parent class, which is called as part of 'get' operation (in _setElementProperties method in elements.php)
		$this->hasMultipleOptions = true;
		$this->isLinked = false; // set to true if this element can have linked values
	}

	/**
	 * Static function to provide the mcp server with the schema for the properties that can be used with the create_form_element and update_form_element tools
	 * Concerned with the properties for the ele_value property of the element object
	 * Follows the convention of properties used publically (MCP, Public API, etc).
	 * @param bool|int $update True if this is being called as part of building the properties for Updating, as opposed to properties for Creating. Default is false (Creating).
	 * @return string The schema for the properties that can be used with the create_form_element and update_form_element tools
	 */
	public static function mcpElementPropertiesDescriptionAndExamples($update = false) {
		list($commonNotes, $commonProperties, $commonExamples) = formulizeHandler::mcpElementPropertiesBaseDescriptionAndExamplesForLists($update);
		$descriptionAndExamples = "
**Element:** Autocomplete List (autocomplete)
**Description:** A single-line text box that provides autocomplete suggestions from a predefined list of options as the user types. The user can select one of the suggested options, or if the allowNewValues property is enabled then the user can enter a new value that is not found in the list. Autocomplete Lists can be set to allow multiple selections, with the allowMultipleSelections property. Autocomplete Lists are good for a large number of options that would be too many for a Dropdown List, Radio Buttons, or Checkboxes. For a small number of predefined options, use Radio Buttons or Dropdown Lists, or use Checkboxes if selecting multiple options must be possible.";
		if($commonNotes) {
			$descriptionAndExamples .= "
$commonNotes";
		}
		if($commonProperties) {
			$descriptionAndExamples .= "
$commonProperties
- allowNewValues (optional, a 1/0 indicating if users should be allowed to enter values that are not in the predefined list of options. Default is 0, meaning users can only select from the predefined options. Set to 1 to allow users to enter new values.)
- allowMultipleSelections (optional, a 1/0 indicating if multiple selections should be allowed. For Autocomplete Lists, the default is 0. Set to 1 to allow multiple selections.)";
		}
		if($commonExamples) {
			$descriptionAndExamples .= "
$commonExamples
- A list of cutlery and flatware, allowing multiple selections: { options: [ 'fork', 'knife', 'spoon', 'plate', 'bowl', 'cup' ], allowMultipleSelections: 1 }
- A list of authors, allowing new values to be entered so that users don't have to select from the predefined options: { options: [ 'Isaac Asimov', 'Arthur C. Clarke', 'Philip K. Dick', 'Frank Herbert' ], allowNewValues: 1 }";
		}
		return $descriptionAndExamples;
	}

}

#[AllowDynamicProperties]
class formulizeAutocompleteElementHandler extends formulizeSelectElementHandler {

	function create() {
		return new formulizeAutocompleteElement();
	}

	/**
	 * Validate properties for this element type, based on the structure used publically (MCP, Public API, etc).
	 * The description in the mcpElementPropertiesDescriptionAndExamples static method on the element class, follows this convention
	 * properties are the contents of the ele_value property on the object
	 * @param array $properties The properties to validate
	 * @param int|string|object|null $elementIdentifier the id, handle, or element object of the element we're preparing properties for. Null if unknown.
	 * @return array An array of properties ready for the object. Usually just ele_value but could be others too.
	 */
	public function validateEleValuePublicAPIProperties($properties, $elementIdentifier = null) {
		list($ele_value, $ele_uitext) = array_values(formulizeBaseClassForListsElementHandler::validateEleValuePublicAPIProperties($properties, $elementIdentifier)); // array_values will take the values in the associative array and assign them to the list variables correctly, since list expects numeric keys
		if(isset($properties['allowMultipleSelections'])) {
			$ele_value[ELE_VALUE_SELECT_MULTIPLE] = $properties['allowMultipleSelections'];
		}
		if(isset($properties['allowNewValues'])) {
			$ele_value[ELE_VALUE_SELECT_AUTOCOMPLETEALLOWSNEW] = $properties['allowNewValues'];
		}
		return [
			'ele_value' => $ele_value,
			'ele_uitext' => $ele_uitext
		];
	}

	protected function getDefaultEleValue() {
		$ele_value = array();
		$ele_value[ELE_VALUE_SELECT_NUMROWS] = 1;
		$ele_value[ELE_VALUE_SELECT_MULTIPLE] = 0; // a 1/0 indicating if multiple selections should be allowed
		$ele_value[ELE_VALUE_SELECT_OPTIONS] = array(); // an array of options for the select box
		$ele_value[ELE_VALUE_SELECT_AUTOCOMPLETE] = 1; // a 1/0 indicating if this is an autocomplete box
		$ele_value[ELE_VALUE_SELECT_RESTRICTSELECTION] = 0; // 0/1/2/3 indicating restrictions on how many times an option can be picked. 0 - no limit, 1 - only once, 2 - once per user, 3 - once per group
		$ele_value[ELE_VALUE_SELECT_AUTOCOMPLETEALLOWSNEW] = 0; // a 1/0 indicating if users should be allowed to enter values that are not in the predefined list of options
		return $ele_value;
	}

}


