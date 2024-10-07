<?php

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formulizeConfigSyncElementValueProcessor.php";

class FormulizeConfigSync
{
	private $db;
	private $configPath;
	private $elementValueProcessor;
	private $changes = [];
	private $diffLog = [];

	private $configFiles = [
		'forms' => 'forms.json'
	];

	/**
	 * Constructor
	 *
	 * @param string $configPath
	 */
	public function __construct(string $configPath)
	{
		$this->configPath = rtrim($configPath, '/');
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
	 * Compare configurations from JSON files with the database
	 *
	 * @return array
	 */
	public function compareConfigurations(): array
	{
		$this->changes = [];
		$this->diffLog = [];

		foreach ($this->configFiles as $type => $filename) {
			$jsonConfig = $this->loadJsonConfig($filename);
			if (!$jsonConfig) {
				continue;
			}

			// @todo additional cases for future expansion
			switch ($type) {
				case 'forms':
					$this->compareFormsConfig($jsonConfig);
					break;
			}
		}

		return [
			'changes' => $this->changes,
			'log' => $this->diffLog
		];
	}

	/**
	 * Load a JSON configuration file
	 *
	 * @param string $filename
	 * @return array
	 */
	private function loadJsonConfig(string $filename): array
	{
		$filepath = XOOPS_ROOT_PATH . '/modules/formulize/' . $this->configPath . '/' . $filename;
		if (!file_exists($filepath)) {
			$this->diffLog[] = "Warning: Configuration file not found: {$filepath}";
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
	 * Compare form configurations from JSON with the database
	 *
	 * @param array $jsonConfig
	 * @return void
	 */
	private function compareFormsConfig(array $jsonConfig): void
	{
		$dbForms = $this->loadDatabaseConfig('formulize_id');

		foreach ($jsonConfig['forms'] as $formConfig) {
			$this->compareForm($formConfig, $dbForms);
			if (isset($formConfig['elements'])) {
				$this->compareElements($formConfig['elements'], $formConfig['id_form']);
			}
		}

		// Check for forms in DB that are not in JSON
		foreach ($dbForms as $dbForm) {
			$found = false;
			foreach ($jsonConfig['forms'] as $formConfig) {
				if ($formConfig['form_handle'] === $dbForm['form_handle']) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				$this->addChange('forms', 'delete', $dbForm);
			}
		}
	}

	/**
	 * Strip key from an array
	 *
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
	 * Compare a form configuration from JSON with the database
	 *
	 * @param array $formConfig
	 * @param array $dbForms
	 * @return void
	 */
	private function compareForm(array $formConfig, array $dbForms): void
	{
		$dbForm = $this->findInArray($dbForms, 'form_handle', $formConfig['form_handle']);
		$strippedFormConfig = $this->stripArrayKey($formConfig, 'elements');

		if (!$dbForm) {
			$this->addChange('forms', 'create', $strippedFormConfig);
			return;
		}

		$differences = $this->compareFields($strippedFormConfig, $dbForm);
		if (!empty($differences)) {
			$this->addChange('forms', 'update', $strippedFormConfig, $differences);
		}
	}

	/**
	 * Compare elements for a form
	 *
	 * @param array $elements
	 * @param string $formHandle
	 * @return void
	 */
	private function compareElements(array $elements, string $formHandle): void
	{
		$dbElements = $this->loadDatabaseConfig('formulize', "id_form = $formHandle");

		foreach ($elements as $element) {
			$dbElement = $this->findInArray($dbElements, 'ele_handle', $element['ele_handle']);
			$preparedElement = $this->prepareElementForDb($element);

			if (!$dbElement) {
				$this->addChange('elements', 'create', $preparedElement);
				continue;
			}

			$differences = $this->compareElementFields($element, $dbElement);
			if (!empty($differences)) {
				$this->addChange('elements', 'update', $preparedElement, $differences);
			}
		}

		// Check for elements in DB that are not in JSON
		foreach ($dbElements as $dbElement) {
			$found = false;
			foreach ($elements as $element) {
				if ($element['ele_handle'] === $dbElement['ele_handle']) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				$this->addChange('elements', 'delete', $dbElement);
			}
		}
	}

	/**
	 * Load configuration data from a database table
	 *
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
	 * General field comparison
	 *
	 * @param array $config
	 * @param array $dbItem
	 * @param array $excludeFields
	 * @return array
	 */
	private function compareFields(array $jsonObject, array $dbObject, array $excludeFields = []): array
	{
		$differences = [];
		foreach ($jsonObject as $field => $value) {
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
	 * Compare a JSON and DB element and return the differences
	 *
	 * @param array $jsonElement
	 * @param array $dbElement
	 * @return array
	 */
	private function compareElementFields(array $jsonElement, array $dbElement): array
	{
		$differences = [];

		foreach ($jsonElement as $field => $value) {
			$eleValueDiff = [];
			if ($field === 'ele_value') {
				$convertedJsonEleValue = $this->elementValueProcessor->processElementValueForImport(
					$jsonElement['ele_type'],
					$value
				);
				$dbEleValue = $dbElement['ele_value'] !== "" ? unserialize($dbElement['ele_value']) : [];
				foreach($convertedJsonEleValue as $key => $val) {
					if (!array_key_exists($key, $dbEleValue) || $val !== $dbEleValue[$key]) {
						$eleValueDiff[$key] = [
							'json_value' => $val,
							'db_value' => $dbEleValue[$key] ?? null
						];
					}
				}
				if (!empty($eleValueDiff)) {
					$differences['ele_value'] = $eleValueDiff;
				}
			} elseif (!array_key_exists($field, $dbElement) || $this->normalizeValue($value) !== $this->normalizeValue($dbElement[$field])) {
				$differences[$field] = [
					'json_value' => $value,
					'db_value' => $dbElement[$field] ?? null
				];
			}
		}

		return $differences;
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
	 * Prepare an element for database storage
	 *
	 * @param array $element
	 * @return array
	 */
	private function prepareElementForDb(array $element): array
	{
		$preparedElement = $element;
		foreach ($preparedElement as $key => $value) {
			if (is_object($value) || is_array($value)) {
				$preparedElement[$key] = serialize($value);
			}
		}
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
	private function addChange(string $type, string $operation, array $data, array $differences = []): void
	{
		$this->changes[] = [
			'type' => $type,
			'operation' => $operation,
			'data' => $data,
			'differences' => $differences
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
	public function applyChanges(): array
	{
		$results = ['success' => [], 'failure' => []];

		try {
			$this->db->beginTransaction();

			foreach ($this->changes as $change) {
				try {
					$this->applyChange($change);
					$results['success'][] = $change;
				} catch (\Exception $e) {
					$results['failure'][] = ['change' => $change, 'error' => $e->getMessage()];
				}
			}

			$this->db->commit();
		} catch (\Exception $e) {
			$this->db->rollBack();
			throw new \Exception("Failed to apply changes: " . $e->getMessage());
		}

		return $results;
	}

	/**
	 * Apply a single change to the database
	 *
	 * @param array $change
	 * @return void
	 */
	private function applyChange(array $change): void
	{
		$table = $this->getTableForType($change['type']);
		$primaryKey = $this->getPrimaryKeyForType($change['type']);

		switch ($change['operation']) {
			case 'create':
				$this->insertRecord($table, $change['data']);
				break;
			case 'update':
				$this->updateRecord($table, $change['data'], $primaryKey);
				break;
			case 'delete':
				$this->deleteRecord($table, $change['data'], $primaryKey);
				break;
		}
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
				return 'id_form';
			case 'elements':
				return 'ele_id';
			default:
				throw new \Exception("Unknown configuration type: {$type}");
		}
	}

	/**
	 * Insert a record into a database table
	 *
	 * @param string $table
	 * @param array $data
	 * @return void
	 */
	private function insertRecord(string $table, array $data): void
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
	 * Export current database configuration to a forms.json file
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
			$form['elements'] = $this->exportElementsForForm($form['id_form']);
			$forms[] = $form;
		}

		return $forms;
	}

	/**
	 * Prepare a form row for export
	 *
	 * @param array $formRow Raw form data from database
	 * @return array Prepared form data for JSON
	 */
	private function prepareFormForExport(array $formRow): array
	{
		$preparedForm = [];
		$excludedFields = ['on_before_save', 'on_after_save', 'on_delete', 'custom_edit_check'];
		foreach ($formRow as $field => $value) {
			if (!in_array($field, $excludedFields)) {
				$preparedForm[$field] = $value;
			}
		}

		return $preparedForm;
	}

	/**
	 * Export elements for a specific form
	 *
	 * @param int $formId Form ID
	 * @return array Array of element configurations
	 */
	private function exportElementsForForm(int $formId): array
	{
		$elements = [];
		$elementRows = $this->loadDatabaseConfig('formulize', "id_form = $formId ORDER BY ele_order");

		foreach ($elementRows as $elementRow) {
			$elements[] = $this->prepareElementForExport($elementRow);
		}

		return $elements;
	}

	/**
	 * Prepare an element row for export
	 *
	 * @param array $elementRow Raw element data from database
	 * @return array Prepared element data for JSON
	 */
	private function prepareElementForExport(array $elementRow): array
	{
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
		return $preparedElement;
	}
}
