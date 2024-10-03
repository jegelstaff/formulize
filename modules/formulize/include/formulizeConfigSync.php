<?php

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formulizeConfigSyncElementValueProcessor.php";

class FormulizeConfigSync {
	private $db;
	private $configPath;
	private $elementValueProcessor;
	private $changes = [];
	private $diffLog = [];

	private $configFiles = [
		'forms' => 'forms.json',
		// 'relationships' => 'relationships.json',
		// 'global_settings' => 'global_settings.json'
	];

	public function __construct(string $configPath) {
		$this->configPath = rtrim($configPath, '/');
		$this->elementValueProcessor = new FormulizeConfigSyncElementValueProcessor();
		$this->initializeDatabase();
	}

	private function initializeDatabase() {
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

	public function compareConfigurations(): array {
		$this->changes = [];
		$this->diffLog = [];

		foreach ($this->configFiles as $type => $filename) {
			$jsonConfig = $this->loadJsonConfig($filename);
			if (!$jsonConfig) {
				continue;
			}

			switch ($type) {
				case 'forms':
					$this->compareFormsConfig($jsonConfig);
					break;
				case 'relationships':
					$this->compareRelationshipsConfig($jsonConfig);
					break;
				case 'global_settings':
					$this->compareGlobalSettings($jsonConfig);
					break;
			}
		}

		return [
			'changes' => $this->changes,
			'log' => $this->diffLog
		];
	}

	private function loadJsonConfig(string $filename): array {
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

	private function compareFormsConfig(array $jsonConfig): void {
		$dbForms = $this->loadDatabaseConfig('formulize_id');
		$dbElements = $this->loadDatabaseConfig('formulize');

		foreach ($jsonConfig['forms'] as $formConfig) {
			$this->compareForm($formConfig, $dbForms);
			if (isset($formConfig['elements'])) {
				$this->compareElements($formConfig['elements'], $dbElements, $formConfig['id_form']);
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

	private function stripArrayKey(array $configArray, string $key): array {
		$strippedConfigArray = $configArray;
		unset($strippedConfigArray[$key]);
		return $strippedConfigArray;
	}

	private function compareForm(array $formConfig, array $dbForms): void {
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

	private function compareElements(array $elements, array $dbElements, string $formHandle): void {
		// Find only the DB elements that belong to the current form
		// Currently this is done via ID because the elements array does not contain the form_handle
		// But we should change the elements in the DB to include the form_handle
		$formHandle = intval($formHandle);
		$formDbElements = array_filter($dbElements, function ($element) use ($formHandle) {
			return $element['id_form'] === $formHandle;
		});

		foreach ($elements as $element) {
			$dbElement = $this->findInArray($formDbElements, 'ele_handle', $element['ele_handle']);
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
		foreach ($formDbElements as $dbElement) {
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

	private function compareRelationshipsConfig(array $jsonConfig): void {
		$dbRelationships = $this->loadDatabaseConfig('formulize_relationships');

		foreach ($jsonConfig['relationships'] as $relationshipConfig) {
			$dbRelationship = $this->findInArray($dbRelationships, 'rel_handle', $relationshipConfig['rel_handle']);

			if (!$dbRelationship) {
				$this->addChange('relationships', 'create', $relationshipConfig);
			} else {
				$differences = $this->compareFields($relationshipConfig, $dbRelationship);
				if (!empty($differences)) {
					$this->addChange('relationships', 'update', $relationshipConfig, $differences);
				}
			}
		}

		// Check for relationships in DB that are not in JSON
		foreach ($dbRelationships as $dbRelationship) {
			$found = false;
			foreach ($jsonConfig['relationships'] as $relationshipConfig) {
				if ($relationshipConfig['rel_handle'] === $dbRelationship['rel_handle']) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				$this->addChange('relationships', 'delete', $dbRelationship);
			}
		}
	}

	private function compareGlobalSettings(array $jsonConfig): void {
		// Implementation depends on how global settings are stored in the database
		// This is a placeholder for the actual implementation
		$this->diffLog[] = "Global settings comparison not implemented yet.";
	}

	private function loadDatabaseConfig(string $table): array {
		$table = $this->prefixTable($table);
		$stmt = $this->db->prepare("SELECT * FROM {$table}");
		$stmt->execute();
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	private function findInArray(array $array, string $key, $value) {
		foreach ($array as $item) {
			if ($item[$key] === $value) {
				return $item;
			}
		}
		return null;
	}

	private function compareFields(array $config, array $dbItem, array $excludeFields = []): array {
		$differences = [];
		foreach ($config as $field => $value) {
			if (in_array($field, $excludeFields)) {
				continue;
			}
			$normalizedValue = $this->normalizeValue($value);
			$normalizedDBValue = $this->normalizeValue($dbItem[$field]);
			if ($normalizedValue !== $normalizedDBValue) {
				$differences[$field] = [
					'config_value' => $normalizedValue,
					'db_value' => $normalizedDBValue
				];
			}
		}
		return $differences;
	}

	private function compareElementFields(array $jsonElement, array $dbElement): array {
		$differences = [];

		foreach ($jsonElement as $field => $value) {
			if ($field === 'ele_value') {
				$eleDifferences = $this->elementValueProcessor->processElementValue(
					$jsonElement['ele_type'],
					$value,
					$dbElement[$field]
				);
				if (!empty($eleDifferences)) {
					$differences['ele_value'] = $eleDifferences;
				}
			} elseif (!isset($dbElement[$field]) || $this->normalizeValue($value) !== $this->normalizeValue($dbElement[$field])) {
				$differences[$field] = [
					'json_value' => $value,
					'db_value' => $dbElement[$field] ?? null
				];
			}
		}

		return $differences;
}

	private function normalizeValue($value) {
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

	private function prepareElementForDb(array $element): array {
    $preparedElement = $element;
    foreach ($preparedElement as $key => $value) {
			if (is_object($value) || is_array($value)) {
				$preparedElement[$key] = serialize($value);
			}
    }
    return $preparedElement;
	}

	private function addChange(string $type, string $operation, array $data, array $differences = []): void {
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

	public function applyChanges(): array {
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

	private function applyChange(array $change): void {
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

	private function getTableForType(string $type): string {
		switch ($type) {
			case 'forms':
				return 'formulize_id';
			case 'elements':
				return 'formulize';
			case 'relationships':
				return 'formulize_relationships';
			default:
				throw new \Exception("Unknown configuration type: {$type}");
		}
	}

	private function getPrimaryKeyForType(string $type): string {
		switch ($type) {
			case 'forms':
				return 'id_form';
			case 'elements':
				return 'ele_id';
			case 'relationships':
				return 'rel_id';
			default:
				throw new \Exception("Unknown configuration type: {$type}");
		}
	}

	private function insertRecord(string $table, array $data): void {
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

	private function updateRecord(string $table, array $data, string $primaryKey): void {
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

	private function deleteRecord(string $table, array $data, string $primaryKey): void {
		$sql = sprintf(
			"DELETE FROM %s WHERE %s = ?",
			$this->prefixTable($table),
			$primaryKey
		);

		$stmt = $this->db->prepare($sql);
		$stmt->execute([$data[$primaryKey]]);
	}

	private function prefixTable(string $table): string {
		return XOOPS_DB_PREFIX . '_' . trim($table, '_');
	}
}
