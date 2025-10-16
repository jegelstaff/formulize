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

#[AllowDynamicProperties]
class formulizeBaseClassForListsElementHandler extends formulizeElementsHandler {

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
		$validOptions = array();
		$uiText = array();
		if(isset($properties['options'])) {
			foreach($properties['options'] as $i => $value) {
				if(is_string($value) OR is_numeric($value)) {
					if(isset($properties['databaseValues']) AND is_array($properties['databaseValues']) AND isset($properties['databaseValues'][$i]) AND strlen($properties['databaseValues'][$i])) {
						$uiText[$properties['databaseValues'][$i]] = $value;
						$validOptions[$properties['databaseValues'][$i]] = (isset($properties['selectedByDefault']) AND in_array($value, $properties['selectedByDefault'])) ? 1 : 0;
					} else {
						$validOptions[$value] = (isset($properties['selectedByDefault']) AND in_array($value, $properties['selectedByDefault'])) ? 1 : 0;
					}
				}
			}
			if(!empty($validOptions)) {
				$ele_value[2] = $validOptions;
			}
		}
		if(isset($properties['updateExistingEntriesToMatchTheseOptions']) AND $properties['updateExistingEntriesToMatchTheseOptions'] == 1 AND $elementIdentifier AND $elementObject = _getElementObject($elementIdentifier)) {
			$data_handler = new formulizeDataHandler($elementObject->getVar('id_form'));
			if(!$changeResult = $data_handler->changeUserSubmittedValues($elementObject, $ele_value[2])) {
				throw new Exception("Could not change existing user submitted values to match the new options, for element '".$elementObject->getVar('ele_caption')."' (id: ".$elementObject->getVar('ele_id').")");
			}
		}
		$config_handler = xoops_gethandler('config');
		$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
		$delimiter = $formulizeConfig['delimeter']; // yes, misspelled in the preferences :(
		if(isset($properties['delimiter']) AND is_string($properties['delimiter'])) {
			switch($properties['delimiter']) {
				case 'linebreak':
					$delimiter = "br";
					break;
				case 'space':
				default:
					$delimiter = $properties['delimiter'];
			}
		} elseif($elementIdentifier AND $elementObject = _getElementObject($elementIdentifier)) {
			$delimiter = $elementObject->getVar('ele_delim');
		}
		return [
			'ele_value' => $ele_value,
			'ele_uitext' => $uiText,
			'ele_delim' => $delimiter
		];
	}

}


