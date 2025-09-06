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
class FormulizeConfigSyncElementValueProcessor
{
	private $elementMapping = [];
	private $textElementMapping = [
		'width' => 0,
		'maxlength' => 1,
		'default' => 2,
		'regex' => 11,
		'regexdescription' => 3,
		'regexerror' => 5,
		'regexplaceholders' => 6,
		'regexmods' => 10,
		'decimal_places' => 7,
		'thousands_sep' => 8,
		'numbertype' => 4,
	];
	private $checkboxElementMapping = [
		'options' => 2,
	];
	private $selectElementMapping = [
		'number_of_rows' => 0,
		'multiple_selections' => 1,
		'options' => 2,
		'groups' => 3,
		'limit_to_user_groups' => 4,
		'filter_conditions' => 5,
		'limit_to_groups_the_current_user_is_a_member_of' => 6,
		'clickable' => 7,
		'autocomplete' => 8,
		'selection_limit' => 9,
		'list_supplied_values_element_id' => 10,
		'export_supplied_values_element_id' => 11,
		'sort_values_element_id' => 12,
		'default_value_entry_id' => 13,
		'show_default_text_when_no_values' => 14
	];
	private $textareaMapping = [
		'default_text' => 0,
		'rows' => 1,
		'columns' => 2
	];
	private $ynradioMapping = [
		'yes' => '_YES',
		'no' => '_NO',
	];
	private $dateMapping = [
		'default' => 0
	];
	private $sliderMapping = [
		'minValue' => 0,
		'maxValue' => 1,
		'stepSize' => 2,
		'defaultValue' => 3,
	];

	public function __construct()
	{
		$this->initializeElementMapping();
	}
	/**
	 * Initialize element mapping
	 */
	private function initializeElementMapping()
	{
		// REVIEW QUESTION FOR ARVIN... THIS SEEMS LIKE SOMETHING WE COULD/SHOULD ADD INTO THE ELEMENT CLASS FILES THEMSELVES, NOW THAT WE HAVE CLASS FILES FOR ALL ELEMENT TYPES??
		// FURTHERMORE, WE HAVE SOME ADDITIONAL TYPES NOW:
		// checkbox is standard checkboxes
		// checkboxlinked is checkboxes with linked values
		// select is standard select dropdowns
		// selectlinked is select dropdowns with linked values
		// selectusers is a username list
		// listbox is a multi-select listbox
		// listboxlinked is a multi-select listbox with linked values
		// listboxusers is a multi-select listbox of usernames
		// autocomplete is an autocomplete box
		// autocompletelinked is an autocomplete box with linked values
		// autocompleteusers is an autocomplete box of usernames
		// The function anySelectElementType can be passed the type of an element object and will return true if it is any of the ones based on "select" (all the select, listbox and autocomplete types extend the monster selectElement class)
		// Also, number is a numbers-only text box (but standard textboxes can also be switched into numbers only mode if you want, and switch back. Number elements are always only number elements.)
		// The great part is we have a specific class file for every single type now! So we can put in helper methods in the element classes to provide type-specific options/functionality as required
		$this->elementMapping = [
			'text' => $this->textElementMapping,
			'textarea' => $this->textareaMapping,
			'checkbox' => $this->checkboxElementMapping,
			'radio' => $this->checkboxElementMapping,
			'yn' => $this->ynradioMapping,
			'select' => $this->selectElementMapping,
			'date' => $this->dateMapping,
			'slider' => $this->sliderMapping,
		];
	}

	/**
	 * Process the value of an element based on its type from Config to DB format
	 *
	 * @param string $eleType
	 * @param array $configValue
	 * @param string $dbValue
	 * @return array
	 */
	public function processElementValueForImport($eleType, $configValue)
	{
		// If we don't have a specifc handler for this element type, return the dbArray as is
		if (!array_key_exists($eleType, $this->elementMapping)) {
			return $configValue;
		}

		return $this->importElement($configValue, $this->elementMapping[$eleType]);
	}

	/**
	 * Convert an element from Config to DB format
	 *
	 * @param array $configValue
	 * @param string $dbValue
	 * @return array
	 */
	private function importElement($configValue, $mapping)
	{
		$importArray = [];

		foreach ($configValue as $configKey => $configValue) {
			if (array_key_exists($configKey, $mapping)) {
				$importArray[$mapping[$configKey]] = $configValue;
			} else {
				$importArray[$configKey] = $configValue;
			}
		}

		return $importArray;
	}

	/**
	 * Process the value of an element based on its type from DB to Config format
	 *
	 * @param string $eleType
	 * @param array $configValue
	 * @param array $dbArray
	 * @return array
	 */
	public function processElementValueForExport(string $eleType, array $dbArray)
	{
		// If we don't have a specifc handler for this element type, return the dbArray as is
		if (!array_key_exists($eleType, $this->elementMapping)) {
			return $dbArray;
		}
		return $this->exportElement($dbArray, $this->elementMapping[$eleType]);
	}

	/**
	 * Convert an element from DB to Config format
	 *
	 * @param array $dbArray
	 * @return array
	 */
	private function exportElement(array $dbArray, $mapping)
	{
		$exportArray = [];

		$flippedMapping = array_flip($mapping);

		foreach ($dbArray as $dbKey => $dbValue) {
			if (array_key_exists($dbKey, $flippedMapping)) {
				$exportArray[$flippedMapping[$dbKey]] = $dbValue;
			} else {
				$exportArray[$dbKey] = $dbValue;
			}
		}

		return $exportArray;
	}
}
