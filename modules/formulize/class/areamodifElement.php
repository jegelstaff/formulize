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

// Areamodif element - Text for display (caption and contents)

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php";
require_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

class formulizeAreamodifElement extends formulizeElement {

	public static $category = "layout";

	function __construct() {
		$this->name = "Text for display (caption and contents)";
		$this->hasData = false; // Areamodif is a non-data element
		$this->needsDataType = false;
		$this->overrideDataType = "";
		$this->adminCanMakeRequired = false;
		$this->alwaysValidateInputs = false;
		$this->canHaveMultipleValues = false;
		$this->hasMultipleOptions = false;
		parent::__construct();
	}

	// write code to a file
	public function setVar($key, $value, $not_gpc = false) {
		if($key == 'ele_value') {
			$valueToWrite = is_array($value) ? $value : unserialize($value);
			if(strstr((string)$valueToWrite[0], "\$value")) {
				$filename = 'areamodif_'.$this->getVar('ele_handle').'.php';
				formulize_writeCodeToFile($filename, $valueToWrite[0]);
				$valueToWrite[0] = '';
				$value = is_array($value) ? $valueToWrite : serialize($valueToWrite);
			}
		}
		parent::setVar($key, $value, $not_gpc);
	}

	// read code from a file
	public function getVar($key, $format = 's') {
		$format = $key == "ele_value" ? "f" : $format;
		$value = parent::getVar($key, $format);
		if($key == 'ele_value' AND is_array($value)) {
			$filename = 'areamodif_'.$this->getVar('ele_handle').'.php';
			$filePath = XOOPS_ROOT_PATH.'/modules/formulize/code/'.$filename;
			$fileValue = "";
			if(file_exists($filePath)) {
				$fileValue = strval(file_get_contents($filePath));
			}
			$value[0] = $fileValue ? $fileValue : ((is_array($value) AND isset($value[0])) ? $value[0] : '');
		}
		return $value;
	}
}

#[AllowDynamicProperties]
class formulizeAreamodifElementHandler extends formulizeElementsHandler {

	var $db;

	function __construct($db) {
		$this->db =& $db;
	}

	function create() {
		return new formulizeAreamodifElement();
	}

	function getDefaultEleValue() {
		$ele_value[0] = '';
		return $ele_value;
	}

	function adminPrepare($element) {
		$dataToSendToTemplate = array();
		if(is_object($element) AND is_subclass_of($element, 'formulizeElement')) {
			$dataToSendToTemplate['options-tab-values']['ele_value'] = $element->getVar('ele_value');
		} else {
			$dataToSendToTemplate['options-tab-values']['ele_value'] = $this->getDefaultEleValue();
		}
		return $dataToSendToTemplate;
	}

	function adminSave($element, $ele_value = array(), $advancedTab = false) {
		$changed = false;
		if(is_object($element) AND is_subclass_of($element, 'formulizeElement')) {
			$element->setVar('ele_value', $ele_value);
		}
		return $changed;
	}

	/**
	 * Renders the Areamodif element (text for display with caption and contents)
	 * Returns an XoopsFormLabel object
	 */
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen=false, $owner=null) {
		// Replace curly bracket references and variables using the renderer's method
		// We need to instantiate the renderer to access its method
		require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elementrenderer.php";
		$elementRenderer = new formulizeElementRenderer($element);
		$ele_value[0] = $elementRenderer->formulize_replaceReferencesAndVariables(
			$ele_value[0], 
			$entry_id, 
			$element->getVar('id_form'), 
			$markupName, 
			$screen
		);
		
		// Check if PHP evaluation is needed
		if(strstr($ele_value[0], "\$value=") OR strstr($ele_value[0], "\$value =")) {
			$form_id = $element->getVar('id_form');
			$entryData = gatherDataset($form_id, filter: $entry_id, frid: 0);
			$entry = $entryData[0];
			$creation_datetime = getValue($entry, "creation_datetime");
			$entryData = $entry; // alternate variable name for backwards compatibility
			$ele_value[0] = removeOpeningPHPTag($ele_value[0]);
			$value = ""; // will be set in eval
			$evalResult = eval($ele_value[0]);
			if($evalResult === false) {
				$ele_value[0] = _formulize_ERROR_IN_LEFTRIGHT;
			} else {
				$ele_value[0] = $value; // value is supposed to be the thing set in the eval'd code
				$ele_value[0] = $elementRenderer->formulize_replaceReferencesAndVariables(
					$ele_value[0], 
					$entry_id, 
					$element->getVar('id_form'), 
					$markupName, 
					$screen
				); // just in case PHP might have added { } refs
			}
		}
		
		// Create and return XoopsFormLabel
		$form_ele = new XoopsFormLabel(
			$caption,
			$ele_value[0],
			$markupName
		);
		$form_ele->setClass("formulize-text-for-display");
		
		return $form_ele;
	}

	function getDefaultValue($element, $entry_id = 'new') {
		return null; // Areamodif elements don't have values
	}

	function loadValue($element, $value, $entry_id) {
		return $element->getVar('ele_value'); // Areamodif doesn't load values from database
	}

	function prepareDataForSaving($value, $element, $entry_id=null, $subformBlankCounter=null) {
		return null; // Areamodif elements don't save data
	}

	function generateValidationCode($caption, $markupName, $element, $entry_id=false) {
		return array(); // No validation needed for Areamodif elements
	}

	function prepareDataForDataset($value, $handle, $entry_id) {
		return null; // Areamodif elements don't have data in datasets
	}

	function prepareLiteralTextForDB($value, $element, $partialMatch=false) {
		return null; // Areamodif elements don't interact with database values
	}

	function formatDataForList($value, $handle="", $entry_id=0, $textWidth=100) {
		return ""; // Areamodif elements don't appear in lists
	}
}
