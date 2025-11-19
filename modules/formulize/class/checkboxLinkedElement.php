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

if(class_exists("formulizeCheckboxLinkedElement")) {
	return;
}

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php"; // you need to make sure the base element class has been read in first!
require_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/checkboxElement.php";

class formulizeCheckboxLinkedElement extends formulizeCheckboxElement {

	public static $category = "linked";

	function __construct() {
		parent::__construct();
		$this->name = "Linked Checkboxes";
		$this->isLinked = true; // set to true if this element can have linked values
	}

	/**
	 * Static function to provide the mcp server with the schema for the properties that can be used with the create_form_element and update_form_element tools
	 * Concerned with the properties for the ele_value property of the element object
	 * Follows the convention of properties used publically (MCP, Public API, etc).
	 * @param bool|int $update True if this is being called as part of building the properties for Updating, as opposed to properties for Creating. Default is false (Creating).
	 * @return string The schema for the properties that can be used with the create_form_element and update_form_element tools
	 */
	public static function mcpElementPropertiesDescriptionAndExamples($update = false) {
		$descriptionAndExamples = "
**Element:** Linked Checkboxes (checkboxLinked)
**Description:** A series of boxes that the user can check to select multiple options. Linked Checkboxes have options drawn from values entered into another form. If multiple selections are not required, use Linked Radio Buttons or a Linked Dropdown instead. In general, Checkboxes are best with a small number of options (generally less than 7) and you want the user to see all the options at once, without having to open a dropdown list or type in an autocomplete box.
**Properties:**
- all the common properties for Linked elements, plus:
- delimiter (optional, a string indicating how to separate the items in the list. Valid values are 'linebreak', 'space', or a custom string, which can include any valid HTML. Default is 'linebreak', however this can be altered in the Formulize preferences. It is not normally necessary to specify this property, unless you want to override the default for the system, or use a custom string.)
**Examples:**
- A linked checkbox element with options drawn from the 'Name' element in a 'Games' form, and using a delimiter of a space: { sourceElement: \"games_name\", delimiter: \"space\" }
- A linked checkbox element with options drawn from the 'Name' element in a 'Games' form, and using a delimiter of an emdash: { sourceElement: \"games_name\", delimiter: \" &mdash; \" }";
		return $descriptionAndExamples;
	}
}

#[AllowDynamicProperties]
class formulizeCheckboxLinkedElementHandler extends formulizeCheckboxElementHandler {

    function create() {
        return new formulizeCheckboxLinkedElement();
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
		$sourceElementObject = null;
		if(isset($properties['sourceElement']) AND !$sourceElementObject = _getElementObject($properties['sourceElement'])) {
			throw new Exception("You must provide a valid sourceElement property for the linked dropdown list element");
		}
		if($sourceElementObject) {
			$ele_value[ELE_VALUE_SELECT_OPTIONS] = $sourceElementObject->getVar('fid')."#*=:*".$sourceElementObject->getVar('ele_handle'); // by convention all linked elements use ELE_VALUE_SELECT_OPTIONS (2) as the key in ele_value to store the source element reference, so they can all extend this class and use this method
		}
		return [
			'ele_value' => $ele_value,
		];
	}

	public function getDefaultEleValue() {
		$ele_value = array();
		$ele_value[ELE_VALUE_SELECT_OPTIONS] = ''; // an element reference for the source of the options for the linked element
		$ele_value[ELE_VALUE_SELECT_LINK_LIMITGROUPS] = 'all'; // by default, all groups are included
		return $ele_value;
	}

	/**
	 * Take data representing an element's properties, and convert any numeric dependencies to handles
	 * @param array $elementData An associative array of form data, following the form object structure
	 * @param array $dependencyIdToHandleMap An associative array mapping numeric element ids to element handles
	 * @return array The modified $formData with numeric dependencies converted to handles
	 */
	public function convertEleValueDependenciesForExport($eleValueData, $dependencyIdToHandleMap) {

		if(strstr($eleValueData[ELE_VALUE_SELECT_OPTIONS], "#*=:*")) {
			// formId#*=:*elementHandle is the format
			$linkedMetaDataParts = explode("#*=:*", $eleValueData[ELE_VALUE_SELECT_OPTIONS]);
			if(count($linkedMetaDataParts) == 2) {
				$formHandler = xoops_getmodulehandler('forms', 'formulize');
				if($sourceFormObject = $formHandler->get($linkedMetaDataParts[0])) {
					$eleValueData[ELE_VALUE_SELECT_OPTIONS] = $sourceFormObject->getVar('form_handle')."#*=:*".$linkedMetaDataParts[1];
				} else {
					throw new Exception("Could not convert form id to handle for linked checkbox element. Invalid form id ".$linkedMetaDataParts[0]);
				}
			}
		}

		$eleValueData[5] = $this->formulize_convertFilterDependenciesToHandles($eleValueData[5], $dependencyIdToHandleMap);

		foreach(array(
			EV_MULTIPLE_LIST_COLUMNS,
			EV_MULTIPLE_FORM_COLUMNS,
			EV_MULTIPLE_SPREADSHEET_COLUMNS) as $key) {

			// prep as array
			if(!is_array($eleValueData[$key])) {
				$unserialized = unserialize($eleValueData[$key]);
				if(is_array($unserialized)) {
					$workingValues = $unserialized;
				} else {
					$workingValues = array($eleValueData[$key]);
				}
			} else {
				$workingValues = $eleValueData[$key];
			}
			// convert ids to handles
			foreach($workingValues as $i => $element) {
				if(is_numeric($element)) {
					$workingValues[$i] = $dependencyIdToHandleMap[$element];
				}
			}
			// put back in original format
			if(!is_array($eleValueData[$key])) {
				$unserialized = unserialize($eleValueData[$key]);
				if(is_array($unserialized)) {
					$eleValueData[$key] = serialize($workingValues);
				} else {
					$eleValueData[$key] = $workingValues[0];
				}
			} else {
				$eleValueData[$key] = $workingValues;
			}
		}

		return $eleValueData;
	}

	/**
	 * Check an array, structured as ele_value would be structured, and return an array of elements that the element depends on
	 * @param array $values The ele_value array to check for dependencies - numeric element refs ought to have been replaced with handles, when this data was created
	 * @return array An array of element handles that this element depends on
	 */
	public function getEleValueDependencies($values) {
		$dependencies = array();
		foreach($values as $key => $value) {
			if($key == ELE_VALUE_SELECT_OPTIONS AND is_string($value)) {
				$linkedMetaDataParts = explode("#*=:*", $value);
				if(count($linkedMetaDataParts) == 2) {
					$dependencies[] = trim($linkedMetaDataParts[1]);
				}
			}
			// passed in elementData ought to have had all numeric references converted to element handles already! Or else formulize_getFilterDependencies will not work!
			if($key == 5) {
				$filterDependencies = $this->formulize_getFilterDependencies($value);
				$dependencies = array_merge($dependencies, $filterDependencies);
			}
			// passed in elementData ought to have had all numeric references converted to element handles already! Or else these keys may have numeric refs to elements not yet in existence in DB!
			if(in_array($key, array(12, EV_MULTIPLE_LIST_COLUMNS, EV_MULTIPLE_FORM_COLUMNS, EV_MULTIPLE_SPREADSHEET_COLUMNS))) {
				if(!is_array($value)) {
					$unserialized = unserialize($value);
					if(is_array($unserialized)) {
						$value = $unserialized;
					} else {
						$value = array($value);
					}
				}
				foreach($value as $element) {
					if(is_numeric($element)) {
						if($elementObject = _getElementObject($element)) {
							$dependencies[] = $elementObject->getVar('ele_handle');
						}
					} elseif($element AND $element != 'none') {
						$dependencies[] = $element;
					}
				}
			}
		}
		return array_filter(array_unique($dependencies), function($value) {
			return $value !== 'none';
		});
	}

}
