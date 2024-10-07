<?php

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

	public function __construct()
	{
		$this->initializeElementMapping();
	}
	/**
	 * Initialize element mapping
	 */
	private function initializeElementMapping()
	{
		$this->elementMapping = [
			'text' => $this->textElementMapping,
			'textarea' => $this->textareaMapping,
			'checkbox' => $this->checkboxElementMapping,
			'radio' => $this->checkboxElementMapping,
			'yn' => $this->ynradioMapping,
			'select' => $this->selectElementMapping,
			'date' => $this->dateMapping,
		];
	}

	/**
	 * Process the value of an element based on its type from JSON to DB format
	 *
	 * @param string $eleType
	 * @param array $jsonValue
	 * @param string $dbValue
	 * @return array
	 */
	public function processElementValueForImport($eleType, $jsonValue)
	{
		// If we don't have a specifc handler for this element type, return the dbArray as is
		if (!array_key_exists($eleType, $this->elementMapping)) {
			return $jsonValue;
		}
		return $this->importElement($jsonValue, $this->elementMapping[$eleType]);
	}

	/**
	 * Convert an element from JSON to DB format
	 *
	 * @param array $jsonValue
	 * @param string $dbValue
	 * @return array
	 */
	private function importElement($jsonValue, $mapping)
	{
		$importArray = [];

		foreach ($jsonValue as $jsonKey => $jsonValue) {
			if (array_key_exists($jsonKey, $mapping)) {
				$importArray[$mapping[$jsonKey]] = $jsonValue;
			} else {
				$importArray[$jsonKey] = $jsonValue;
			}
		}

		return $importArray;
	}

	/**
	 * Process the value of an element based on its type from DB to JSON format
	 *
	 * @param string $eleType
	 * @param array $jsonValue
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
	 * Convert an element from DB to JSON format
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
