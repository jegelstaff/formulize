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
	public $elementMapping = [];
	private $textElementMapping = [
		'width' => 0,
		'maxlength' => 1,
		'default' => 2,
		'numbers_only' => 3,
		'associated_element_id' => 4,
		'decimals' => 5,
		'prefix' => 6,
		'decimal_separator' => 7,
		'thousands_separator' => 8,
		'unique_value_reqiured' => 9,
		'suffix' => 10,
		'default_value_as_placeholder' => 11,
		'trim_value' => 12
 	];
	private $gridElementMapping = [
		'source_of_caption' => 0,
		'row_labels' => 1,
		'column_labels' => 2,
		'shading_orientation' => 3,
		'initial_element_id' => 4,
		'caption_at_side' => 5
	];
	private $checkboxElementMapping = [
		'options' => 2,
	];
	private $checkboxLinkedElementMapping = [
		'options' => 2,
		'sort_values_element_id' => 12,
		'sort_order' => 15,
		'list_supplied_values_element_ids' => 10,
		'export_supplied_values_element_ids' => 11,
		'form_supplied_values_element_ids' => 17
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
		'list_supplied_values_element_ids' => 10,
		'export_supplied_values_element_ids' => 11,
		'sort_values_element_id' => 12,
		'default_value_entry_id' => 13,
		'show_default_text_when_no_values' => 14,
		'sort_order' => 15,
		'autocomplete_allow_new_values' => 16,
		'form_supplied_values_element_ids' => 17
	];
	private $textareaMapping = [
		'default_text' => 0,
		'rows' => 1,
		'columns' => 2,
		'associated_element_id' => 3,
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
	private $subformListingsElementMapping = [
		'subform_form_id' => 0,
		'elements_to_show' => 1,
		'number_of_blanks' => 2,
		'view_entry_mode' => 3,
		'column_headings_or_captions' => 4,
		'mainform_as_entry_owner' => 5,
		'add_entries_perm_source' => 6,
		'filter_included_entries' => 7,
		'subform_type' => 8,
		'add_entry_button_text' => 9
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
		// @todo this mapping could be provided by the element classes
		$this->elementMapping = [
			'text' => $this->textElementMapping,
			'number' => $this->textElementMapping,
			'textarea' => $this->textareaMapping,
			'checkbox' => $this->checkboxElementMapping,
			'checkboxLinked' => $this->checkboxLinkedElementMapping,
			'radio' => $this->checkboxElementMapping,
			'yn' => $this->ynradioMapping,
			'select' => $this->selectElementMapping,
			'selectLinked' => $this->selectElementMapping,
			'selectUsers' => $this->selectElementMapping,
			'listbox' => $this->selectElementMapping,
			'listboxLinked' => $this->selectElementMapping,
			'listboxUsers' => $this->selectElementMapping,
			'autocomplete' => $this->selectElementMapping,
			'autocompleteLinked' => $this->selectElementMapping,
			'autocompleteUsers' => $this->selectElementMapping,
			'date' => $this->dateMapping,
			'slider' => $this->sliderMapping,
			'grid' => $this->gridElementMapping,
			'subformFullForm' => $this->subformListingsElementMapping,
			'subformEditableRow' => $this->subformListingsElementMapping,
			'subformListings' => $this->subformListingsElementMapping,
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
