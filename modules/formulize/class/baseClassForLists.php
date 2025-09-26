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
	 * Takes an array of properties for an element Object and fills it out, validates, so it is complete
	 * If an element should set any default values for properties more specific to it, do that here
	 * Must return through the parent method so that the rest of the more basic properties are set correctly
	 * In most cases, ele_value property will already have been sorted out because it came from a public source and went through validateEleValuePublicAPIProperties already
	 * @param array $properties The properties for an element object
	 * @return array The properties to apply to the element object
	 */
	public function setupAndValidateElementProperties($properties) {
		$config_handler = xoops_gethandler('config');
		$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
		// creating a new element, so set some defaults if necessary (or take what was passed in)
		if(!isset($properties['ele_id'])) {
			$properties['ele_uitextshow'] = isset($properties['ele_uitextshow']) ? $properties['ele_uitextshow'] : 0;
			$properties['ele_delim'] = isset($properties['ele_delim']) ? $properties['ele_delim'] : $formulizeConfig['delimeter'];
		}
		return parent::setupAndValidateElementProperties($properties);
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
		if($elementIdentifier AND $thisElementObject = _getElementObject($elementIdentifier)) {
			$ele_value = $thisElementObject->getVar('ele_value');
		} else {
			$ele_value = $this->getDefaultEleValue();
		}
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
		return [
			'ele_value' => $ele_value,
			'ele_uitext' => $uiText
		];
	}

}


