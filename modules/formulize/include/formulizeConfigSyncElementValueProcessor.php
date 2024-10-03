<?php

class FormulizeConfigSyncElementValueProcessor {
    private $typeHandlers = [];

    public function __construct() {
			$this->initializeTypeHandlers();
    }

    private function initializeTypeHandlers() {
        $this->typeHandlers = [
            'text' => [$this, 'handleTextElement'],
            'textarea' => [$this, 'handleTextareaElement'],
            'select' => [$this, 'handleSelectElement'],
            'checkbox' => [$this, 'handleCheckboxElement'],
            'radio' => [$this, 'handleRadioElement'],
            'date' => [$this, 'handleDateElement'],
            // Add more element types as needed
        ];
    }

    public function processElementValue($eleType, $jsonValue, $dbValue) {
        if (!isset($this->typeHandlers[$eleType])) {
            throw new \Exception("Unsupported element type: $eleType");
        }

        return call_user_func($this->typeHandlers[$eleType], $jsonValue, $dbValue);
    }

    private function handleTextElement($jsonValue, $dbValue) {
        $dbArray = unserialize($dbValue);
        $differences = [];

        $mapping = [
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

        foreach ($mapping as $jsonKey => $dbKey) {
            if (isset($jsonValue[$jsonKey]) && (!isset($dbArray[$dbKey]) || $jsonValue[$jsonKey] != $dbArray[$dbKey])) {
                $differences[$jsonKey] = [
                    'json_value' => $jsonValue[$jsonKey],
                    'db_value' => $dbArray[$dbKey] ?? null
                ];
            }
        }

        return $differences;
    }

    private function handleTextareaElement($jsonValue, $dbValue) {
        // Implementation similar to handleTextElement, with textarea-specific fields
    }

    private function handleSelectElement($jsonValue, $dbValue) {
        // Handle select element specifics, including options
    }

    private function handleCheckboxElement($jsonValue, $dbValue) {
        // Handle checkbox element specifics
    }

    private function handleRadioElement($jsonValue, $dbValue) {
        // Handle radio element specifics
    }

    private function handleDateElement($jsonValue, $dbValue) {
        // Handle date element specifics
    }

    // Add more handler methods for other element types
}
