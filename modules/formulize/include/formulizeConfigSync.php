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

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formulizeConfigSyncElementValueProcessor.php";

class FormulizeConfigSync
{
	private $db;
	private $configPath;
	private $elementValueProcessor;
	private $formHandler;
	private $elementHandler;
	private $changes = [];
	private $diffLog = [];
	private $errorLog = [];

	private $configFiles = [
		'forms' => 'forms.json'
	];

	/**
	 * Constructor
	 * @param string $configPath
	 */
	public function __construct(string $configPath)
	{
		$this->configPath = rtrim($configPath, '/');
		$this->formHandler = xoops_getmodulehandler('forms', 'formulize');
		$this->elementHandler = xoops_getmodulehandler('elements', 'formulize');
		$this->elementValueProcessor = new FormulizeConfigSyncElementValueProcessor();
		$this->initializeDatabase();
	}

	/**
	 * Initialize the database connection
	 * @return void
	 */
	private function initializeDatabase()
	{
		try {
			// @todo we should probably be usign the $xoopsDB object
			// But it's an older version of PDO which means that many
			// of the newer features we're using here will need to be
			// refactored. For now, we'll just use a new PDO object.
			$this->db = new \PDO(
				'mysql:host=' . XOOPS_DB_HOST . ';dbname=' . XOOPS_DB_NAME,
				XOOPS_DB_USER,
				XOOPS_DB_PASS
			);
			$this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			$this->db->query("SET NAMES utf8mb4");
		} catch (\PDOException $e) {
			throw new \Exception("Database connection failed: " . $e->getMessage());
		}
	}

	/**
	 * Load a configuration file
	 * @param string $filename
	 * @return array Array of configuration data
	 */
	private function loadConfigFile(string $filename): array
	{
		$filepath = XOOPS_ROOT_PATH . '/modules/formulize' . $this->configPath . '/' . $filename;
		if (!file_exists($filepath)) {
			$this->errorLog[] = "Warning: Configuration file not found: {$filepath}";
			return [];
		}

		$content = file_get_contents($filepath);
		$config = json_decode($content, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new \Exception("Invalid JSON in {$filename}: " . json_last_error_msg());
		}

		return $config;
	}

	/**
	 * Load configuration data from a database table
	 * @param string $table
	 * @return array
	 */
	private function loadDatabaseConfig(string $table, string $where = ''): array
	{
		$table = $this->prefixTable($table);
		$sql = "SELECT * FROM {$table}";
		if ($where) {
			$sql .= " WHERE $where";
		}
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	/**
	 * Initiate the comparision between a config file and the database
	 * @return array Array of changes, log, and errors
	 */
	public function compareConfigurations(): array
	{
		$this->changes = [];
		$this->diffLog = [];
		$this->errorLog = [];

		foreach ($this->configFiles as $type => $filename) {
			$config = $this->loadConfigFile($filename);
			if (!$config) {
				continue;
			}

			// @todo additional cases for future expansion
			switch ($type) {
				case 'forms':
					$this->compareForms($config);
					break;
			}
		}

		return [
			'changes' => $this->changes,
			'log' => $this->diffLog,
			'errors' => $this->errorLog
		];
	}


	/**
	 * Compare configuration against the database for all forms
	 * @param array $config
	 * @return void
	 */
	private function compareForms(array $config): void
	{
		$formExcludedFields = ['defaultform', 'defaultlist'];
		// Load all forms from the database
		$dbForms = $this->loadDatabaseConfig('formulize_id');
		foreach ($config['forms'] as $configForm) {
			// Find the corresponding DB form and compare to the configuration
			$dbForm = $this->findInArray($dbForms, 'form_handle', $configForm['form_handle']);

			$strippedFormConfig = $this->stripArrayKey($configForm, 'elements');

			if (!$dbForm) {
				$this->addChange('forms', 'create', $strippedFormConfig['form_handle'], $strippedFormConfig);
			} else {
				$differences = $this->compareFields($strippedFormConfig, $dbForm, $formExcludedFields);
				if (!empty($differences)) {
					$this->addChange('forms', 'update', $strippedFormConfig['form_handle'], $strippedFormConfig, $differences);
				}
			}

			if (isset($configForm['elements'])) {
				if ($dbForm) {
					$dbElements = $this->loadDatabaseConfig('formulize', "id_form = {$dbForm['id_form']}");
					$this->compareElements($configForm['elements'], $dbElements, $configForm['form_handle']);
				} else {
					$this->compareElements($configForm['elements'], [], $configForm['form_handle']);
				}
			}
		}

		// Check for forms in DB that are not in Config
		foreach ($dbForms as $dbForm) {
			$found = false;
			foreach ($config['forms'] as $formConfig) {
				if ($formConfig['form_handle'] === $dbForm['form_handle']) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				$this->addChange('forms', 'delete', $dbForm['form_handle'], $dbForm);
				$dbElements = $this->loadDatabaseConfig('formulize', "id_form = {$dbForm['id_form']}");
				$this->compareElements([], $dbElements, $dbForm['form_handle']);
			}
		}
	}

	/**
	 * Compare elements of a form
	 * @param array $configElements elements from the configuration
	 * @param array $dbElements elements from the database
	 * @param string $formHandle handle of the form
	 * @param bool $formExistsInDb
	 * @return void
	 */
	private function compareElements(array $configElements, array $dbElements, string $formHandle): void
	{
		foreach ($configElements as $index => $element) {
			$eleOrder = $index + 1;
			$dbElement = $this->findInArray($dbElements, 'ele_handle', $element['ele_handle']);
			$preparedElement = $this->prepareElementForDb($element, $eleOrder);
			$configMetadata = [
				'form_handle' => $formHandle,
				'data_type' => $element['metadata']['data_type']
			];

			if (!$dbElement) {
				$this->addChange('elements', 'create', $preparedElement['ele_handle'], $preparedElement, [], $configMetadata);
				continue;
			}

			$formulizeDbElement = $this->elementHandler->get($dbElement['ele_handle']);
			$formulizeDbElementDataType = $formulizeDbElement->getDataTypeInformation();

			$dbMetadata = [
				'form_handle' => $formHandle,
				'data_type' => $formulizeDbElementDataType['dataTypeString']
			];

			// Compare the element fields
			$differences = $this->compareElementFields($preparedElement, $dbElement, $configMetadata, $dbMetadata);
			if (!empty($differences)) {
				$this->addChange('elements', 'update', $preparedElement['ele_handle'], $preparedElement, $differences, $configMetadata);
			}
		}

		// Check for elements in DB that are not in JSON
		foreach ($dbElements as $dbElement) {
			$found = false;
			foreach ($configElements as $element) {
				if ($element['ele_handle'] === $dbElement['ele_handle']) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				$this->addChange('elements', 'delete', $dbElement['ele_handle'], $dbElement, [], ['form_handle' => $formHandle]);
			}
		}
	}

	private function findInArray(array $array, string $key, $value)
	{
		foreach ($array as $item) {
			if ($item[$key] === $value) {
				return $item;
			}
		}
		return null;
	}

	/**
	 * Generic field comparison
	 *
	 * @param array $configObject Config object
	 * @param array $dbObject Database object
	 * @param array $excludeFields
	 * @return array
	 */
	private function compareFields(array $configObject, array $dbObject, array $excludeFields = []): array
	{
		$differences = [];
		foreach ($configObject as $field => $value) {
			if (in_array($field, $excludeFields)) {
				continue;
			}
			$normalizedJSONValue = $this->normalizeValue($value);
			$normalizedDBValue = $this->normalizeValue($dbObject[$field]);
			if ($normalizedJSONValue !== $normalizedDBValue) {
				$differences[$field] = [
					'config_value' => $normalizedJSONValue,
					'db_value' => $normalizedDBValue
				];
			}
		}
		return $differences;
	}

	/**
	 * Compare a Config and DB element and return the differences
	 *
	 * @param array $configElement Config element configuration
	 * @param array $dbElement Database element configuration
	 * @param array $configMetadata Metadata from the configuration
	 * @param array $dbMetadata Metadata from the database
	 * @return array
	 */
	private function compareElementFields(array $configElement, array $dbElement, array $configMetadata = [], array $dbMetadata = []): array
	{
		$differences = [];

		foreach ($configElement as $field => $value) {
			$eleValueDiff = [];
			// ele_value fields are processed differently because they are serialized
			if ($field === 'ele_value') {
				$convertedConfigEleValue = $value !== "" ? unserialize($value) : [];
				$dbEleValue = $dbElement['ele_value'] !== "" ? unserialize($dbElement['ele_value']) : [];
				foreach ($convertedConfigEleValue as $key => $val) {
					if (!array_key_exists($key, $dbEleValue) || $val !== $dbEleValue[$key]) {
						$eleValueDiff[$key] = [
							'config_value' => $val,
							'db_value' => $dbEleValue[$key] ?? null
						];
					}
				}
				if (!empty($eleValueDiff)) {
					$differences['ele_value'] = $eleValueDiff;
				}
			} elseif (!array_key_exists($field, $dbElement) || $this->normalizeValue($value) !== $this->normalizeValue($dbElement[$field])) {
				$differences[$field] = [
					'config_value' => $value,
					'db_value' => $dbElement[$field] ?? null
				];
			}
		}

		if ($dbMetadata['data_type'] !== $configMetadata['data_type']) {
			$differences['data_type'] = [
				'config_value' => $configMetadata['data_type'],
				'db_value' => $dbMetadata['data_type']
			];
		}

		return $differences;
	}

	/**
	 * Prepare an element for database storage
	 *
	 * @param array $element
	 * @param int $eleOrder The value to use for the ele_order field
	 * @return array
	 */
	private function prepareElementForDb(array $element, int $eleOrder): array
	{
		$preparedElement = $element;
		foreach ($preparedElement as $key => $value) {
			if (is_object($value) || is_array($value)) {
				if ($key == 'ele_value') {
					$preparedElement[$key] = serialize($this->elementValueProcessor->processElementValueForImport($element['ele_type'], $value));
				} else {
					$preparedElement[$key] = serialize($value);
				}
			}
		}
		// Remove metadata type fields
		unset($preparedElement['metadata']);
		// Add the ele_order field
		$preparedElement['ele_order'] = $eleOrder;
		return $preparedElement;
	}

	/**
	 * Add a change to the list of changes
	 *
	 * @param string $type
	 * @param string $operation
	 * @param array $data
	 * @param array $differences
	 * @return void
	 */
	private function addChange(
		string $type,
		string $operation,
		string $id,
		array $data,
		array $differences = [],
		array $metadata = []
	): void {
		$this->changes[] = [
			'type' => $type,
			'operation' => $operation,
			'id' => $id,
			'data' => $data,
			'differences' => $differences,
			'metadata' => $metadata
		];

		// $identifierField = $type === 'forms' ? 'form_handle' : ($type === 'elements' ? 'ele_handle' : 'rel_handle');
		$this->diffLog[] = sprintf(
			"%s: %s %s '%s'",
			ucfirst($operation),
			$type,
			$data,
			$differences
		);
	}

	/**
	 * Apply changes to the database
	 *
	 * @return array
	 */
	public function applyChanges(array $changeIds): array
	{
		$results = ['success' => [], 'failure' => []];
		$changes = [];

		if (empty($changeIds)) {
			$changes = $this->changes;
		} else {
			foreach ($this->changes as $change) {
				if (in_array($change['id'], $changeIds)) {
					$changes[] = $change;
				}
			}
		}
		// Apply all form changes
		foreach ($changes as $change) {
			if ($change['type'] === 'forms') {
				try {
					$this->applyFormChange($change);
					$results['success'][] = $change;
				} catch (\Exception $e) {
					$results['failure'][] = ['error' => $e->getMessage(), 'change' => $change];
				}
			}
		}

		// Apply all element changes
		foreach ($changes as $change) {
			if ($change['type'] === 'elements') {
				try {
					$this->applyElementChange($change);
					$results['success'][] = $change;
				} catch (\Exception $e) {
					$results['failure'][] = ['error' => $e->getMessage(), 'change' => $change];
				}
			}
		}

		return $results;
	}

	private function applyFormChange(array $change): void
	{
		$table = $this->getTableForType($change['type']);
		$primaryKey = $this->getPrimaryKeyForType($change['type']);

		switch ($change['operation']) {
			case 'create':
				// Ensure the form does not exist
				$existingForm = $this->formHandler->getByHandle($change['data']['form_handle']);
				if ($existingForm) {
					throw new \Exception("Form handle {$change['data']['form_handle']} already exists");
				}
				// Insert form record
				$formId = $this->insertRecord($table, $change['data']);
				// Create data table
				$this->formHandler->createDataTable($formId);
				$formObject = $this->formHandler->get($formId);
				// create the default form screen for this form
				$multiPageScreenHandler = xoops_getmodulehandler('multiPageScreen', 'formulize');
				$defaultFormScreen = $multiPageScreenHandler->create();
				$multiPageScreenHandler->setDefaultFormScreenVars($defaultFormScreen, $formObject->getVar('title') . ' Form', $formId, $formObject->getVar('title'));
				$defaultFormScreenId = $multiPageScreenHandler->insert($defaultFormScreen);
				// create the default list screen for this form
				$listScreenHandler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
				$screen = $listScreenHandler->create();
				$listScreenHandler->setDefaultListScreenVars($screen, $defaultFormScreenId, $formObject->getVar('title') . ' List', $formId);
				$defaultListScreenId = $listScreenHandler->insert($screen);
				// Assign default screens to the form
				$formObject->setVar('defaultform', $defaultFormScreenId);
				$formObject->setVar('defaultlist', $defaultListScreenId);
				$this->formHandler->insert($formObject);
				break;

			case 'update':
				// Ensure the fom exists
				$existingForm = $this->formHandler->getByHandle($change['data']['form_handle']);
				if (!$existingForm) {
					throw new \Exception("Form handle {$change['data']['form_handle']} does not exist");
				}
				$this->updateRecord($table, $change['data'], $primaryKey);
				break;

			case 'delete':
				$form = $this->formHandler->getByHandle($change['data']['form_handle']);
				if ($form) {
					$this->formHandler->delete($form);
				}
				break;
		}
		return;
	}

	private function applyElementChange(array $change): void
	{
		$table = $this->getTableForType($change['type']);
		$primaryKey = $this->getPrimaryKeyForType($change['type']);
		$dataType = $change['metadata']['data_type'];

		switch ($change['operation']) {
			case 'create':
				// Ensure the element does not exist
				$existingElement = $this->elementHandler->get($change['data']['ele_handle']);
				if ($existingElement) {
					throw new \Exception("Element handle {$change['data']['ele_handle']} already exists");
				}
				$formHandle = $change['metadata']['form_handle'];
				$form = $this->formHandler->getByHandle($formHandle);
				if (!$form) {
					throw new \Exception("Form handle $formHandle not found");
				} else {
					$formId = $form->getVar('id_form');
					$change['data']['id_form'] = $formId;
					$elementId = $this->insertRecord($table, $change['data']);
					$this->formHandler->insertElementField($elementId, $dataType);
				}
				break;

			case 'update':
				// Ensure the element exists
				$existingElement = $this->elementHandler->get($change['data']['ele_handle']);
				if (!$existingElement) {
					throw new \Exception("Element handle {$change['data']['ele_handle']} does not exists");
				}
				$this->updateRecord($table, $change['data'], $primaryKey);
				// Apply data type changes to the element
				$this->formHandler->updateField($existingElement, $change['data']['ele_handle'], $dataType);
				break;

			case 'delete':
				$elementId = $change['data']['ele_id'];
				$element = $this->elementHandler->get($elementId);
				if ($element) {
					$this->elementHandler->delete($element);
					$this->formHandler->deleteElementField($elementId);
				}
				break;
		}

		return;
	}

	/**
	 * Insert a record into a database table
	 *
	 * @param string $table
	 * @param array $data
	 * @return void
	 */
	private function insertRecord(string $table, array $data): int
	{
		$fields = array_keys($data);
		$placeholders = array_fill(0, count($fields), '?');

		$sql = sprintf(
			"INSERT INTO %s (%s) VALUES (%s)",
			$this->prefixTable($table),
			'`' . implode('`, `', $fields) . '`',
			implode(', ', $placeholders)
		);

		$stmt = $this->db->prepare($sql);
		$stmt->execute(array_values($data));

		return (int) $this->db->lastInsertId();
	}

	/**
	 * Update a record in a database table
	 *
	 * @param string $table
	 * @param array $data
	 * @param string $primaryKey
	 * @return void
	 */
	private function updateRecord(string $table, array $data, string $primaryKey): void
	{
		$fields = array_keys($data);
		$sets = array_map(function ($field) {
			return "`{$field}` = ?";
		}, $fields);

		$sql = sprintf(
			"UPDATE %s SET %s WHERE %s = ?",
			$this->prefixTable($table),
			implode(', ', $sets),
			$primaryKey
		);

		$values = array_values($data);
		$values[] = $data[$primaryKey];

		$stmt = $this->db->prepare($sql);
		$stmt->execute($values);
	}

	/**
	 * Delete a record from a database table
	 *
	 * @param string $table
	 * @param array $data
	 * @param string $primaryKey
	 * @return void
	 */
	private function deleteRecord(string $table, array $data, string $primaryKey): void
	{
		$sql = sprintf(
			"DELETE FROM %s WHERE %s = ?",
			$this->prefixTable($table),
			$primaryKey
		);

		$stmt = $this->db->prepare($sql);
		$stmt->execute([$data[$primaryKey]]);
	}

	/**
	 * Prefix a table name with the XOOPS database prefix
	 *
	 * @param string $table
	 * @return string
	 */
	private function prefixTable(string $table): string
	{
		return XOOPS_DB_PREFIX . '_' . trim($table, '_');
	}

	/**
	 * Export current database configuration to a JSON string
	 *
	 * @return string A JSON string of the exported configuration
	 */
	public function exportConfiguration(): string
	{
		try {
			$forms = $this->exportForms();
			$config = [
				'version' => '1.0',
				'lastUpdated' => date('Y-m-d H:i:s'),
				'forms' => $forms,
				'metadata' => [
					'generated_by' => 'FormulizeConfigSync',
					'environment' => XOOPS_DB_NAME, // Assuming XOOPS_DB_NAME is defined
					'export_date' => date('Y-m-d H:i:s')
				]
			];

			return json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		} catch (\Exception $e) {
			error_log("Error exporting configuration: " . $e->getMessage());
			return '';
		}
	}

	/**
	 * Export forms and their elements
	 *
	 * @return array Array of form configurations
	 */
	private function exportForms(): array
	{
		$forms = [];
		$formRows = $this->loadDatabaseConfig('formulize_id');

		foreach ($formRows as $formRow) {
			$form = $this->prepareFormForExport($formRow);
			$form['elements'] = $this->exportElementsForForm($formRow['id_form']);
			$forms[] = $form;
		}

		return $forms;
	}

	/**
	 * Prepare a form row for export
	 *
	 * @param array $formRow Raw form data from database
	 * @return array Prepared form data for export
	 */
	private function prepareFormForExport(array $formRow): array
	{
		$preparedForm = [];
		$excludedFields = ['on_before_save', 'on_after_save', 'on_delete', 'custom_edit_check', 'defaultform', 'defaultlist'];
		foreach ($formRow as $field => $value) {
			if (!in_array($field, $excludedFields)) {
				$preparedForm[$field] = $value;
			}
		}
		// Remove not needed fields
		unset($preparedForm['id_form']);
		return $preparedForm;
	}

	/**
	 * Export elements for a specific form
	 *
	 * @param int $formId unique form identifier from the database
	 * @return array Array of element configurations
	 */
	private function exportElementsForForm(int $formId): array
	{
		$elements = [];
		$elementRows = $this->loadDatabaseConfig('formulize', "id_form = '$formId' ORDER BY ele_order");

		foreach ($elementRows as $elementRow) {
			$elements[] = $this->prepareElementForExport($elementRow);
		}

		return $elements;
	}

	/**
	 * Prepare an element row for export
	 *
	 * @param array $elementRow Raw element data from database
	 * @return array Prepared element data for export
	 */
	private function prepareElementForExport(array $elementRow): array
	{

		$elementObject = $this->elementHandler->get($elementRow['ele_handle']);
		$elementDataType = $elementObject->getDataTypeInformation();

		$serializeFields = ['ele_value', 'ele_filtersettings', 'ele_disabledconditions', 'ele_exportoptions'];
		$preparedElement = [];
		foreach ($elementRow as $field => $value) {
			if (in_array($field, $serializeFields)) {
				$unserialized = $value !== "" ? @unserialize($value) : [];
				if ($field == 'ele_value') {
					$preparedElement[$field] = $this->elementValueProcessor->processElementValueForExport($elementRow['ele_type'], $unserialized);
				} else {
					$preparedElement[$field] = $unserialized;
				}
			} else {
				$preparedElement[$field] = $value;
			}
		}
		// Add element Metadata
		$preparedElement['metadata'] = [
			'data_type' => $elementDataType['dataTypeString']
		];
		// Remove not needed fields
		unset($preparedElement['id_form']);
		unset($preparedElement['ele_id']);
		unset($preparedElement['form_handle']);
		unset($preparedElement['ele_order']);
		return $preparedElement;
	}

	/**
	 * Normalize a value for comparison
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	private function normalizeValue($value)
	{
		if (is_string($value) && unserialize($value) !== false) {
			$unserialized = unserialize($value);
			ksort($unserialized);
			return $unserialized;
		}
		if (is_array($value)) {
			return array_values($value);
		}
		if (is_bool($value)) {
			return (int) $value;
		}
		return (string) $value;
	}

	/**
	 * Strip key from an array
	 * @param array $configArray
	 * @param string $key
	 * @return array
	 */
	private function stripArrayKey(array $configArray, string $key): array
	{
		$strippedConfigArray = $configArray;
		unset($strippedConfigArray[$key]);
		return $strippedConfigArray;
	}


	/**
	 * Get the database table name for a configuration type
	 *
	 * @param string $type
	 * @return string
	 */
	private function getTableForType(string $type): string
	{
		switch ($type) {
			case 'forms':
				return 'formulize_id';
			case 'elements':
				return 'formulize';
			default:
				throw new \Exception("Unknown configuration type: {$type}");
		}
	}

	/**
	 * Get the primary key field for a configuration type
	 *
	 * @param string $type
	 * @return string
	 */
	private function getPrimaryKeyForType(string $type): string
	{
		switch ($type) {
			case 'forms':
				return 'form_handle';
			case 'elements':
				return 'ele_handle';
			default:
				throw new \Exception("Unknown configuration type: {$type}");
		}
	}
}
