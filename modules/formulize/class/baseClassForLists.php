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

class formulizeBaseClassForListsElement extends formulizeElement {

	/**
	 * Static function to provide the common properties and examples used by all list elements for with the mcp server
	 * Follows the convention of properties used publically (MCP, Public API, etc).
	 * @param bool|int $update True if this is being called as part of building the options for Updating, as opposed to options for Creating. Default is false (Creating).
	 * @return array an array of with the important notes first, common properties first, and the common examples second
	 */
	public static function mcpElementPropertiesBaseDescriptionAndExamples($update = false) {

		$notes = $update ?
"**Important notes:**
- When altering options in a list, consider whether any data that users have entered into the form already, should be altered as well to match the new options. See the updateExistingEntriesToMatchTheseOptions property below. This is only relevant when options are being changed. If options are being re-organized, or new ones added, or old ones deleted, you do not need to update existing entries to match."
: "";

		$properties =
"**Properties:**
- options (array, list of options for the element, optionally a distinct value to store in the database vs to show the user can be specified using the pipe character: | See the examples for details.)
- selectedByDefault (optional, an array containing a value or values from the options array that should be selected by default when the element appears on screen to users. If this is not specified, no options will be selected by default. If alternate database values are being used, the values in this array should be from the options array, not the databaseValues array.)
- databaseValues (optional, an array of values to store in the database, if different from the values shown to users. This is not normally used, but if the application would require a coded value to be stored in the database, for compatibility with other code or other systems, this is useful. Must be the same length as the options array, and each value in this array corresponds by position to the value in the options array. If not provided, the values in the options array will be used as the values stored in the database.)";
		$properties .= $update ?
"- updateExistingEntriesToMatchTheseOptions (optional, a 1/0 indicating if existing entries should be updated to match the new options. Default is 0. Set this to 1 when an element has existing options that are being changed. When set to 1, everywhere in the database where the old first option was selected, will change to having the new first option selected, and everywhere the old second option was selected will change to the new second option, etc. This is useful when correcting typos, or making refinements, such as an element with options 'S', 'M', 'L' that is changing to 'Small', 'Medium', 'Large'. Sometimes changes to options are just reordering the existing options, or adding new options, or removing removing options, and in those cases this setting should be left unspecified or set to 0.)"
: "";

		$examples =
"**Examples:**
- A list of toppings for pizza: { options: [ 'pepperoni', 'mushrooms', 'onions', 'extra cheese', 'green peppers', 'bacon' ] }
- A list of toppings for pizza, with 'pepperoni' and 'mushrooms' selected by default: { options: [ 'pepperoni', 'mushrooms', 'onions', 'extra cheese', 'green peppers', 'bacon' ], selectedByDefault: [ 'pepperoni', 'mushrooms' ] }
- A list of movies: { options: [ '2001: A Space Odyssey', 'WarGames', 'WALL-E', 'The Matrix', 'Inception', 'Children of Men' ] }
- A list of movies, with 'Children of Men' selected by default: { options: [ '2001: A Space Odyssey', 'WarGames', 'WALL-E', 'The Matrix', 'Inception', 'Children of Men' ], selectedByDefault: [ 'Children of Men' ] }
- A list of states where the value stored in the database is the shortform code, but the user sees the full state name: { options: [ 'California', 'Delaware', 'Hawaii', 'Maine', 'New York', 'Vermont' ], databaseValues: [ 'CA', 'DE', 'HI', 'ME', 'NY', 'VT' ] }";
		$examples .= $update ?
"- A list which previously had the options 'No', 'Maybe', 'Yes', and is now being updated with new options, that should replace the old options everywhere people have filled in the form already: { options: [ 'Never', 'Sometimes', 'Always' ], updateExistingEntriesToMatchTheseOptions: 1 }"
: "";

		return [
			$notes,
			$properties,
			$examples
		];
	}

}

#[AllowDynamicProperties]
class formulizeBaseClassForListsElementHandler extends formulizeElementsHandler {

	/**
	 * Takes an array of properties for an element Object and fills it out, validates, so it is complete
	 * If an element should set any default values for properties more specific to it, do that here
	 * Must return through the parent method so that the rest of the more basic properties are set correctly
	 * In most cases, ele_value property will already have been sorted out because it came from a public source and went through validateEleValuePublicAPIOptions already
	 * @param array $properties The properties for an element object
	 * @return array The properties to apply to the element object
	 */
	public function setupAndValidateElementProperties($properties) {
		$config_handler = xoops_gethandler('config');
		$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
		$properties['ele_uitextshow'] = isset($properties['ele_uitextshow']) ? $properties['ele_uitextshow'] : 0;
		$properties['ele_delim'] = isset($properties['ele_delim']) ? $properties['ele_delim'] : $formulizeConfig['delimiter'];
		return parent::setupAndValidateElementProperties($properties);
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
		$validOptions = array();
		$uiText = array();
		if(isset($options['options'])) {
			foreach($options['options'] as $i => $value) {
				if(is_string($value) OR is_numeric($value)) {
					if(isset($options['databaseValues']) AND is_array($options['databaseValues']) AND isset($options['databaseValues'][$i]) AND strlen($options['databaseValues'][$i])) {
						$uiText[$options['databaseValues'][$i]] = $value;
						$validOptions[$options['databaseValues'][$i]] = (isset($options['selectedByDefault']) AND in_array($value, $options['selectedByDefault'])) ? 1 : 0;
					} else {
						$validOptions[$value] = (isset($options['selectedByDefault']) AND in_array($value, $options['selectedByDefault'])) ? 1 : 0;
					}
				}
			}
		}
		$ele_value = $this->getDefaultEleValue();
		$ele_value[2] = $validOptions;
		if(isset($options['updateExistingEntriesToMatchTheseOptions']) AND $options['updateExistingEntriesToMatchTheseOptions'] == 1 AND $elementIdentifier AND $elementObject = _getElementObject($elementIdentifier)) {
			$data_handler = new formulizeDataHandler($elementObject->getVar('id_form'));
			if(!$changeResult = $data_handler->changeUserSubmittedValues($elementObject, $ele_value[2])) {
				throw new Exception("Could not change existing user submitted values to match the new options, for element '".$elementObject->getVar('ele_caption')."' (id: ".$elementObject->getVar('ele_id').")");
			}
		}
		return [
			'ele_value' => $ele_value,
			'ele_uitext' => $uiText
		];
	}

}


