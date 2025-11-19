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
	private $deferredElementChanges = [];
	private $appliedElementChanges = [];
	private $queuedElementChanges = [];

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
		$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

		// ele_delim will become the system default if it has no value, so sub that in for comparison purposes later
		// This is a stub of behaviour that could/should be expanded/generalized, and moved into the form and/or elements classes
		// knowledge and handling of the semantics of the configuration needs to exist somewhere
		if($table === $this->prefixTable('formulize')) {
			$config_handler = xoops_gethandler('config');
			$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
			foreach($result as $index => $row) {
				if(isset($row['ele_delim']) AND (is_null($row['ele_delim']) OR $row['ele_delim'] === '')) {
					$result[$index]['ele_delim'] = $formulizeConfig['delimeter'];
				}
			}
		}

		return $result;
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
			list($dbForm, $dbOrdinal) = $this->findInArray($dbForms, 'form_handle', $configForm['form_handle']);

			// Remove elements so we can just compare the formConfig aginst the DB
			$strippedFormConfig = $this->stripArrayKey($configForm, 'elements');

			if (!$dbForm) {
				$this->addChange('forms', 'create', $strippedFormConfig['form_handle'], $strippedFormConfig);
			} else {
				$differences = $this->compareFormFields($strippedFormConfig, $dbForm, $formExcludedFields);
				if (!empty($differences)) {
					$this->addChange('forms', 'update', $strippedFormConfig['form_handle'], $strippedFormConfig, $differences);
				}
			}

			if (isset($configForm['elements'])) {
				if ($dbForm) {
					$dbElements = $this->loadDatabaseConfig('formulize', "id_form = {$dbForm['id_form']} ORDER BY ele_order ASC");
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
				$dbElements = $this->loadDatabaseConfig('formulize', "id_form = {$dbForm['id_form']} ORDER BY ele_order ASC");
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
		$elementsAreInSameOrder = true;
		$eleOrder = 0;
		foreach ($configElements as $index => $element) {

			list($dbElement, $dbOrdinal) = $this->findInArray($dbElements, 'ele_handle', $element['ele_handle']);

			// if the element is in the same position, use the ele_order from the DB, so that this is not marked as a change
			if($elementsAreInSameOrder AND $dbElement AND $dbOrdinal === $index) {
				$eleOrder = $dbElement['ele_order'];

			// element in config is not in same position it currently has in the database (or does not exist in DB)
			// so we now increment element order from the last value it had
			} else {
				$elementsAreInSameOrder == false;
				$eleOrder = $eleOrder + 1;
			}
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
				'data_type' => $formulizeDbElementDataType['dataTypeCompleteString']
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

	/**
	 * Find an item in an array by key and value
	 *
	 * @param array $array
	 * @param string $key
	 * @param mixed $value
	 * @return array An array containing the item, and the index of that item in the array
	 */
	private function findInArray(array $array, string $key, $value)
	{
		foreach ($array as $index =>$item) {
			if ($item[$key] === $value) {
				return array($item, $index);
			}
		}
		return array();
	}

	/**
	 * Generic field comparison
	 *
	 * @param array $configObject Config object
	 * @param array $dbObject Database object
	 * @param array $excludeFields
	 * @return array
	 */
	private function compareFormFields(array $configObject, array $dbObject, array $excludeFields = []): array
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
					'config_value' => json_encode($normalizedJSONValue),
					'db_value' => json_encode($normalizedDBValue)
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
				$dbEleValue = $dbElement['ele_value'] !== "" ? unserialize($dbElement['ele_value']) : [];
				foreach ($value as $key => $val) {
					if (!array_key_exists($key, $dbEleValue) || $val !== $dbEleValue[$key]) {
						$eleValueDiff[$key] = [
							'config_value' => json_encode($val),
							'db_value' => json_encode($dbEleValue[$key]) ?? null
						];
					}
				}
				if (!empty($eleValueDiff)) {
					$differences['ele_value'] = $eleValueDiff;
				}
			} elseif (!array_key_exists($field, $dbElement) || $this->normalizeValue($value) !== $this->normalizeValue($dbElement[$field])) {
				$differences[$field] = [
					'config_value' => json_encode($value),
					'db_value' => json_encode($dbElement[$field]) ?? null
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
					$preparedElement[$key] = $this->elementValueProcessor->processElementValueForImport($element['ele_type'], $value);
				} else {
					$preparedElement[$key] = $value;
				}
			} elseif(is_string($value)) {

				// ele_delim will become the system default if it has no value, so sub that in
				if($key == 'ele_delim' AND (is_null($value) OR $value === '')) {
					$config_handler = xoops_gethandler('config');
					$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
					$preparedElement[$key] = $formulizeConfig['delimeter'];

				// if the string is a serialized array, use the unserialized array as the value
				} else {
					$unserialized = unserialize($value);
					if($unserialized !== false AND is_array($unserialized)) {
						ksort($unserialized);
						$preparedElement[$key] = $unserialized;
					}
				}

			} elseif(is_bool($value)) {
				$preparedElement[$key] = (int) $value;
			} elseif(!is_null($value)) {
				$preparedElement[$key] = (string) $value;
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
		switch($type) {
			case 'forms':
				$dataIdentifier = $data['form_handle'];
				break;
			case 'elements':
				$dataIdentifier = $data['ele_handle'];
				$metadata = $this->gatherElementDependencies($data, $metadata);
				break;
			default:
				throw new \Exception("Unknown change type: $type");
		}
		$this->changes[$dataIdentifier] = [
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
	 * Gather dependencies for an element change
	 */
	private function gatherElementDependencies(array $elementData, array $metadata): array
	{
		$dependencies = [];
		$elementType = $elementData['ele_type'] ?? '';
		if(file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$elementType."Element.php")) {
	    $elementTypeHandler = xoops_getmodulehandler($elementType."Element", 'formulize');
			$dependencies = $elementTypeHandler->getElementDependencies($elementData);
		}
		$metadata['dependencies'] = $dependencies;
		return $metadata;
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
			foreach ($this->changes as $dataIdentifier => $change) {
				if (in_array($change['id'], $changeIds)) {
					$changes[$dataIdentifier] = $change;
				}
			}
		}
		// Apply all form changes
		foreach ($changes as $dataIdentifier => $change) {
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
		$this->deferredElementChanges = [];
		$this->appliedElementChanges = [];
		$this->queuedElementChanges = array_filter($changes, function($item) {
			return $item['type'] === 'elements';
		});
		foreach ($this->queuedElementChanges as $dataIdentifier => $change) {
			try {
				if($this->applyElementChange($change)) {
					$results['success'][] = $change;
					$this->appliedElementChanges[] = $change['data']['ele_handle'];
				}
			} catch (\Exception $e) {
				$results['failure'][] = ['error' => $e->getMessage(), 'change' => $change];
			}
		}
		while(count($this->deferredElementChanges) > 0) {
			$beforeLoopDefferredCount = count($this->deferredElementChanges);
			foreach($this->deferredElementChanges as $elementHandle => $deferredChange) {
				try {
					if($this->applyElementChange($deferredChange)) {
						$results['success'][] = $deferredChange;
						$this->appliedElementChanges[] = $elementHandle;
					}
				} catch (\Exception $e) {
					$results['failure'][] = ['error' => $e->getMessage(), 'change' => $deferredChange];
				}
			}
			if(count($this->deferredElementChanges) == $beforeLoopDefferredCount) {
				// no progress made, likely due to unresolvable dependencies
				// we could/should give the user more information, or dig deeper to resolve these somehow
				// probably by writing the element and then rewriting the dependent element refs afterwards
				foreach($this->deferredElementChanges as $elementHandle => $deferredChange) {
					$results['failure'][] = ['error' => "Unresolvable dependencies for $elementHandle. Depends on " . implode(', ', $deferredChange['metadata']['dependencies']), 'change' => $deferredChange];
				}
				break;
			}
		}

		return $results;
	}

	private function applyFormChange(array $change): void
	{
		switch ($change['operation']) {
			case 'create':
				// Ensure the form does not exist
				$existingForm = $this->formHandler->getByHandle($change['data']['form_handle']);
				if ($existingForm AND is_object($existingForm) AND $existingForm->getVar('form_handle') == $change['data']['form_handle']) {
					throw new \Exception("Form handle {$change['data']['form_handle']} already exists");
				}
				// Setup the form
				$applicationIds = array(0); // forms with no application
				$groupsThatCanEditForm = array(XOOPS_GROUP_ADMIN); // only webmasters can edit forms initially
				$change['data']['fid'] = 0; // ensure it's treated as a new form
				formulizeHandler::upsertFormSchemaAndResources($change['data'], $groupsThatCanEditForm, $applicationIds);
				break;

			case 'update':
				// Ensure the fom exists
				$existingForm = $this->formHandler->getByHandle($change['data']['form_handle']);
				if (!$existingForm OR !is_object($existingForm) OR $existingForm->getVar('form_handle') != $change['data']['form_handle']) {
					throw new \Exception("Form handle {$change['data']['form_handle']} does not exist");
				}
				if(isset($change['data']['id_form'])) {
					unset($change['data']['id_form']);
				}
				$change['data']['fid'] = $existingForm->getVar('id_form');
				formulizeHandler::upsertFormSchemaAndResources($change['data']);
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

	private function applyElementChange(array $change): ?string
	{
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
				if (!$form OR !is_object($form) OR $form->getVar('form_handle') != $formHandle) {
					throw new \Exception("Form handle $formHandle not found");
				}
				if($this->deferElementChangeIfNecessary($change) === false) {
					$formId = $form->getVar('id_form');
					$change['data']['fid'] = $formId;
					if(formulizeHandler::upsertElementSchemaAndResources($change['data'], dataType: $dataType)) {
						return true;
					}
				}
				break;

			case 'update':
				// Ensure the element exists
				$existingElement = $this->elementHandler->get($change['data']['ele_handle']);
				if (!$existingElement) {
					throw new \Exception("Element handle {$change['data']['ele_handle']} does not exists");
				}
				if($this->deferElementChangeIfNecessary($change) === false) {
					$change['data']['ele_id'] = $existingElement->getVar('ele_id');
					if(formulizeHandler::upsertElementSchemaAndResources($change['data'], dataType: $dataType)) {
						return true;
					}
				}
				break;

			case 'delete':
				if ($element = _getElementObject($change['data']['ele_id'])
					AND $this->elementHandler->delete($element)
					AND $this->formHandler->deleteElementField($element)) {
						return true;
				}
				break;
		}

		if(!isset($this->deferredElementChanges[$change['data']['ele_handle']])) {
			throw new \Exception("Failed to perform ".$change['operation']." for element handle {$change['data']['ele_handle']}");
		}
		return false;
	}

	/**
	 * If a change has dependencies, defer the change for later processing
	 */
	private function deferElementChangeIfNecessary(array $change): bool
	{
		// if this change has dependencies, check that they are met
		if(!empty($change['metadata']['dependencies'])) {
			$elementHandle = $change['data']['ele_handle'];
			// if this change was already applied, throw an exception
			if(in_array($elementHandle, $this->appliedElementChanges)) {
				throw new Exception("Checking if we need to defer a change that was already applied: $elementHandle");
			} else {
				// check that all the required elements are in the database already
				foreach($change['metadata']['dependencies'] as $requiredElementHandle) {
					if(!$requiredElementObject = $this->elementHandler->get($requiredElementHandle)) {
						// if required element is not in the database but will be created in a queued change, then defer this change
						if(isset($this->queuedElementChanges[$requiredElementHandle])
							AND $this->queuedElementChanges[$requiredElementHandle]['operation'] == 'create'
							AND !in_array($requiredElementHandle, $this->appliedElementChanges)) {
								$this->deferredElementChanges[$elementHandle] = $change;
								return true;
						// otherwise, the dependency is will never be met
						} else {
							throw new \Exception("Element $elementHandle has unmet dependency on missing element $requiredElementHandle");
						}
					}
				}
			}
		}
		if(isset($this->deferredElementChanges[$change['data']['ele_handle']])) {
			unset($this->deferredElementChanges[$change['data']['ele_handle']]);
		}
		return false;
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
				'version' => '1.1',
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
			throw new Exception("Error exporting configuration: " . $e->getMessage());
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
		$formsForExport = [];
		$formRows = $this->loadDatabaseConfig('formulize_id');

		foreach ($formRows as $formRow) {
			$formForExport = $this->prepareFormForExport($formRow);
			$formForExport['elements'] = $this->exportElementsForForm($formRow['id_form']);
			$formsForExport[] = $formForExport;
		}

		return $formsForExport;
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
		$excludedFields = ['on_before_save', 'on_after_save', 'on_delete', 'custom_edit_check', 'defaultform', 'defaultlist', 'id_form'];
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
	 * @param int $formId unique form identifier from the database
	 * @return array Array of element configurations
	 */
	private function exportElementsForForm(int $formId): array
	{
		$elementsForExport = [];
		$elementRows = $this->loadDatabaseConfig('formulize', "id_form = '$formId' ORDER BY ele_order ASC");

		foreach ($elementRows as $elementRow) {
			$elementsForExport[] = $this->prepareElementForExport($elementRow);
		}

		return $elementsForExport;
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

		$serializeFields = ['ele_value', 'ele_uitext','ele_filtersettings', 'ele_disabledconditions', 'ele_exportoptions'];
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
			'data_type' => $elementDataType['dataTypeCompleteString']
		];
		// Remove not needed fields
		unset($preparedElement['id_form']);
		unset($preparedElement['ele_id']);
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
		if(is_string($value)) {
			$unserialized = unserialize($value);
			if($unserialized !== false AND is_array($unserialized)) {
				ksort($unserialized);
				return empty($unserialized) ? null : $unserialized;
			}
			return $value === "" ? null : $value;
		}
		if (is_array($value)) {
			return empty($value) ? null : $value;
		}
		if (is_bool($value)) {
			return (int) $value;
		}
		if(is_null($value)) {
			return null;
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

}
