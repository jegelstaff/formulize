<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) Formulize Project												 ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
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
##  Author of this file: Formulize Project  					     									 ##
##  Project: Formulize                                                       ##
###############################################################################

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php";
require_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

class formulizeDurationElement extends formulizeElement
{

	function __construct()
	{
		$this->name = "Duration";
		$this->hasData = true;
		$this->needsDataType = false; // We'll force this to be stored as INT (minutes)
		$this->overrideDataType = "int"; // Store as integer minutes
		$this->adminCanMakeRequired = true;
		$this->alwaysValidateInputs = true; // We'll always validate to ensure duration is within bounds
		$this->canHaveMultipleValues = false;
		$this->hasMultipleOptions = false;
		parent::__construct();
	}
}

class formulizeDurationElementHandler extends formulizeElementsHandler
{
	var $db;
	// Conversion factors to minutes
	private $timeUnits = array(
		'days' => 1440,
		'hours' => 60,
		'minutes' => 1
	);

	private $displayUnitSingular = array(
		'days' => _formulize_DAY,
		'hours' => _formulize_HOUR,
		'minutes' => _formulize_MINUTE
	);

	private $displayUnitPlural = array(
		'days' => _formulize_DAYS,
		'hours' => _formulize_HOURS,
		'minutes' => _formulize_MINUTES
	);

	function __construct($db)
	{
		$this->db = &$db;
	}

	function create()
	{
		return new formulizeDurationElement();
	}

	// Prepare admin UI data
	function adminPrepare($element)
	{
		$dataToSendToTemplate = array();

		if (is_object($element) && is_subclass_of($element, 'formulizeElement')) {
			// Existing element
			$ele_value = $element->getVar('ele_value');
			$dataToSendToTemplate['ele_value'] = $ele_value;
		} else {
			// New element - set defaults
			$ele_value = array(
				'show_days' => 1,
				'show_hours' => 1,
				'show_minutes' => 1,
				'min_minutes' => 0,
				'max_minutes' => 0, // 0 means no maximum
				'size' => 3 // width of each input box
			);
			$dataToSendToTemplate['ele_value'] = $ele_value;
		}

		return $dataToSendToTemplate;
	}

	// Save admin UI data
	function adminSave($element, $ele_value)
	{
		if (is_object($element) && is_subclass_of($element, 'formulizeElement')) {
			// Save which units to show
			$ele_value['show_days'] = isset($_POST['show_days']) ? 1 : 0;
			$ele_value['show_hours'] = isset($_POST['show_hours']) ? 1 : 0;
			$ele_value['show_minutes'] = isset($_POST['show_minutes']) ? 1 : 0;

			// Save min/max durations
			$ele_value['min_minutes'] = intval($_POST['min_minutes']);
			$ele_value['max_minutes'] = intval($_POST['max_minutes']);

			// Save input box size
			$ele_value['size'] = intval($_POST['size']);

			$element->setVar('ele_value', $ele_value);
		}
		return false;
	}

	// Convert form input to minutes for storage
	function prepareDataForSaving($value, $element, $entry_id = null)
	{
		if (!is_array($value)) {
			return NULL;
		}

		$ele_value = $element->getVar('ele_value');

		$totalMinutes = 0;

		foreach ($this->timeUnits as $unit => $multiplier) {
			if (isset($value[$unit]) && is_numeric($value[$unit])) {
				$totalMinutes += $value[$unit] * $multiplier;
			}
		}

		// Validate min/max duration
		if ($ele_value['min_minutes'] > 0 && $totalMinutes < $ele_value['min_minutes']) {
			return $ele_value['min_minutes'];
		}
		if ($ele_value['max_minutes'] > 0 && $totalMinutes > $ele_value['max_minutes']) {
			return $ele_value['max_minutes'];
		}

		return $totalMinutes > 0 ? $totalMinutes : NULL;
	}

	function afterSavingLogic($value, $element_id, $entry_id) {
	}

	function prepareDataForDataset($value, $handle, $entry_id) {
		return $value;
	}

	function prepareLiteralTextForDB($value, $element, $partialMatch=false) {
		$pattern = '/(\d+)d|(\d+)h|(\d+)m/';
		preg_match_all($pattern, $value, $matches);

		$days = 0;
		$hours = 0;
		$minutes = 0;

		foreach ($matches[1] as $value) {
			if ($value) {
				$days = (int) $value * $this->timeUnits['days'];
				break;
			}
		}

		foreach ($matches[2] as $value) {
			if ($value) {
				$hours = (int) $value * $this->timeUnits['hours'];
				break;
			}
		}

		foreach ($matches[3] as $value) {
			if ($value) {
				$minutes = (int) $value * $this->timeUnits['minutes'];
				break;
			}
		}

		$total = $days + $hours + $minutes;

		return $total == 0 ? false : $total;
	}

	// Convert stored minutes back to time units for display
	function loadValue($value, $ele_value, $element)
	{
		if (!is_numeric($value)) {
			return $ele_value;
		}

		$minutes = intval($value);
		$breakdown = array();

		// Convert minutes to time units
		$remaining = $minutes;
		foreach ($this->timeUnits as $unit => $multiplier) {
			if ($ele_value['show_' . $unit]) {
				$amount = floor($remaining / $multiplier);
				$breakdown[$unit] = $amount;
				$remaining -= ($amount * $multiplier);
			}
		}

		$ele_value['values'] = $breakdown;
		return $ele_value;
	}

	// Render the duration inputs
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen = false, $owner = null)
	{
		if ($isDisabled) {
			// Render as text for disabled state
			$output = "";
			if (isset($ele_value['values'])) {
				foreach ($ele_value['values'] as $unit => $amount) {
					if ($amount > 0 && $ele_value['show_' . $unit]) {
						$output .= $amount . " " . $unit . " ";
					}
				}
			}
			return new XoopsFormLabel($caption, trim($output), $markupName);
		}

		// Create container for inputs
		$container = new XoopsFormElementTray($caption, ' ');

		// Add input field for each enabled time unit
		foreach ($this->timeUnits as $unit => $multiplier) {
			$unitMarkupName = $markupName . '[' . $unit . ']';
			if ($ele_value['show_' . $unit]) {
				$value = isset($ele_value['values'][$unit]) ? $ele_value['values'][$unit] : '';
				${"input_$unit"} = new XoopsFormText(
					$this->displayUnitPlural[$unit].":",
					$unitMarkupName,
					$ele_value['size'],
					5,
					$value,
					false,
					true
				);
				${"input_$unit"}->setExtra("min='0'");
				${"input_$unit"}->setExtra("class='formulize-duration-element-input'");
				${"input_$unit"}->setExtra(" onchange=\"javascript:formulizechanged=1;\" jquerytag=\"$unitMarkupName\" ");
				$container->addElement(${"input_$unit"});
			}
		}

		$renderedElement = $container->render();

		$form_ele = new XoopsFormLabel(
			$caption,
			trans($renderedElement),
			$markupName
		);

		return $form_ele;
	}

	// Validate the duration is within bounds
	function generateValidationCode($caption, $markupName, $element, $entry_id = false)
	{
		$validationCode = array();
		$ele_value = $element->getVar('ele_value');

		// First input name for focus on validation failure
		foreach ($this->timeUnits as $unit => $multiplier) {
			if ($ele_value['show_' . $unit]) {
				$firstInputName = $markupName . '[' . $unit . ']';
				break;
			}
		}

		// Basic required validation if element is required
		if ($element->getVar('ele_req')) {
			$validationCode[] = "var hasValue = false;\n";
			foreach ($this->timeUnits as $unit => $multiplier) {
				if ($ele_value['show_' . $unit]) {
					$validationCode[] = "if(myform['{$markupName}[{$unit}]'].value != '') hasValue = true;\n";
				}
			}
			$validationCode[] = "if(!hasValue) {\n";
			$validationCode[] = "    window.alert('Please enter a value for ${caption}.');\n";
			$validationCode[] = "    myform['{$firstInputName}'].focus();\n";
			$validationCode[] = "    return false;\n";
			$validationCode[] = "}\n";
		}

		// Validate min/max duration
		$validationCode[] = "var totalMinutes = 0;\n";
		foreach ($this->timeUnits as $unit => $multiplier) {
			if ($ele_value['show_' . $unit]) {
				$validationCode[] = "if(myform['{$markupName}[{$unit}]'].value != '') {\n";
				$validationCode[] = "    totalMinutes += parseInt(myform['{$markupName}[{$unit}]'].value) * $multiplier;\n";
				$validationCode[] = "}\n";
			}
		}

		if ($ele_value['min_minutes'] > 0) {
			$validationCode[] = "if(totalMinutes < {$ele_value['min_minutes']}) {\n";
			$validationCode[] = "    window.alert('${caption} must be at least {$ele_value['min_minutes']} minutes.');\n";
			$validationCode[] = "    myform['{$firstInputName}'].focus();\n";
			$validationCode[] = "    return false;\n";
			$validationCode[] = "}\n";
		}

		if ($ele_value['max_minutes'] > 0) {
			$validationCode[] = "if(totalMinutes > {$ele_value['max_minutes']}) {\n";
			$validationCode[] = "    window.alert('${caption} must not exceed {$ele_value['max_minutes']} minutes.');\n";
			$validationCode[] = "    myform['{$firstInputName}'].focus();\n";
			$validationCode[] = "    return false;\n";
			$validationCode[] = "}\n";
		}

		return $validationCode;
	}

	// Format duration for display in lists
	function formatDataForList($value, $handle = "", $entry_id = 0, $textWidth = 100)
	{
		if (!is_numeric($value)) {
			return '';
		}

		$minutes = intval($value);
		$output = array();

		$remaining = $minutes;
		foreach ($this->timeUnits as $unit => $multiplier) {
			$amount = floor($remaining / $multiplier);
			if ($amount > 0) {
				$diplayUnit = $amount == 1 ? $this->displayUnitSingular[$unit] : $this->displayUnitPlural[$unit];
				$output[] = $amount . " " . $diplayUnit;
			}
			$remaining -= ($amount * $multiplier);
		}

		$this->clickable = false;
		$this->striphtml = true;
		$this->length = 0;

		return implode(", ", $output);
	}
}
