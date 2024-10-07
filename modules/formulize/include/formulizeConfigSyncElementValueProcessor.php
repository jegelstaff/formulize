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
		'options' => 2,
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
			'checkbox' => $this->checkboxElementMapping,
			'select' => $this->selectElementMapping,
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
