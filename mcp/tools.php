<?php

trait tools {

	/**
	 * Register available MCP tools with proper JSON Schema validation
	 * Sets the tools property of the FormulizeMCP class
	 * This method should be called in the constructor of the FormulizeMCP class
	 * @return void
	 */
	private function registerTools()
	{
		$this->tools = [
			'test_connection' => [
				'name' => 'test_connection',
				'description' => 'Test the MCP server connection and database access',
				'inputSchema' => [
					'type' => 'object',
					'properties' => (object)[]
				]
			],
			'list_forms' => [
				'name' => 'list_forms',
				'description' => 'List all forms in this Formulize instance',
				'inputSchema' => [
					'type' => 'object',
					'properties' => (object)[]
				]
			],
			'list_connections' => [
				'name' => 'list_connections',
				'description' => 'List all connections between forms in this Formulize instance. This tool is used to get the connections between forms, which can be used to understand how forms are related to each other.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => (object)[]
				]
			],
			'get_form_details' => [
				'name' => 'get_form_details',
				'description' => 'Get detailed information about a specific form. You can get a list of all the forms and their IDs with the list_forms tool.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'form_id' => [
							'type' => 'integer',
							'description' => 'The ID of the form to retrieve details for'
						]
					],
					'required' => ['form_id']
				]
			],
			'get_element_details' => [
				'name' => 'get_element_details',
				'description' => 'Get detailed information about a specific element in a form. You can get a list of all the elements in a form with the get_form_details tool.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'form_identifier' => [
							'type' => [ 'integer', 'string' ],
							'description' => 'The ID number or the element handle, of the element to retrieve details for. If a number is provided, it must be an element ID. If a string is provided, it must be the element handle.'
						]
					],
					'required' => ['form_id']
				]
			],
			'create_entry' => [
				'name' => 'create_entry',
				'description' => 'Create a new entry in a Formulize form with the provided data',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'form_id' => [
							'type' => 'integer',
							'description' => 'The ID of the form to create an entry in. You can look up the forms with the list_forms tool.'
						],
						'data' => [
							'type' => 'object',
							'description' => 'Key-value pairs where keys are element handles and values are the data to store. Date elements store data in YYYY-mm-dd format. Time elements store data in 24 hour format. You can lookup the element handles in a form with the get_form_details tool.',
							'additionalProperties' => true
						],
						'relationship_id' => [
							'type' => 'integer',
							'description' => 'Relationship ID for derived value calculations (-1 for Primary Relationship, 0 for no relationship). Defaults to -1 for the Primary Relationship which includes all connected forms.'
						]
					],
					'required' => ['form_id', 'data']
				]
			],
			'update_entry' => [
				'name' => 'update_entry',
				'description' => 'Update an existing entry in a Formulize form with the provided data',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'form_id' => [
							'type' => 'integer',
							'description' => 'The ID of the form containing the entry to update. You can look up the forms with the list_forms tool.'
						],
						'entry_id' => [
							'type' => 'integer',
							'description' => 'The ID of the entry to update'
						],
						'data' => [
							'type' => 'object',
							'description' => 'Key-value pairs where keys are element handles and values are the data to store. Date elements store data in YYYY-mm-dd format. Time elements store data in 24 hour format. You can lookup the element handles in a form with the get_form_details tool.',
							'additionalProperties' => true
						],
						'relationship_id' => [
							'type' => 'integer',
							'description' => 'Relationship ID for derived value calculations (-1 for Primary Relationship, 0 for no relationship). Defaults to -1 for the Primary Relationship which includes all connected forms.'
						]
					],
					'required' => ['form_id', 'entry_id', 'data']
				]
			],
			'get_entries_from_form' => [
				'name' => 'get_entries_from_form',
				'description' => 'Get entries from a form, and from other forms that are connected by the specfied relationship (unless the Relationship ID is set to 0). Only returns entries that the user has permission to access. Some elements will store values in non-readable formats, that can be prepared for human readability using the prepare_database_values_for_human_readability tool.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'form_id' => [
							'type' => 'integer',
							'description' => 'The ID of the main form to get entries from. You can look up the forms with the list_forms tool.'
						],
						'elementHandles' => [
							'type' => 'array',
							'description' => 'Optional. Array of elements to include. Elements can be from the main form, and from any related form that is part of the relationship. Use a multidimensional array with form IDs as keys. If not specified, all elements will be included. You can lookup the element handles in a form with the get_form_details tool.',
							'items' => [
								'type' => 'array',
								'description' => 'Form ID to element handles mapping. The keys are the form IDs, the values are arrays of element handles to include from each form.',
								'items' => [
									'type' => 'string',
									'description' => 'Element handle to include'
								]
							]
						],
						'filter' => [
							'oneOf' => [
								[
									'type' => 'integer',
									'description' => 'Entry ID'
								],
								[
									'type' => 'string',
									'description' => 'Filter string taking the format: elementHandle/**/searchTerm/**/operator. Dates are stored in YYYY-mm-dd format. Times are stored in 24 hour format. Valid operators are =, >, <, >=, <=, !=, LIKE. The default operator is LIKE. (Multiple strings can be included, separated by ][ and the logical operator between them is determined by the andOr parameter.'
								]
							]
						],
						'andOr' => [
							'type' => 'string',
							'description' => 'The boolean operator to use (AND or OR) between multiple filter strings, if there were multiple filter strings in the filter parameter, separated by ][. Defaults to AND if not specified.',
							'enum' => ['AND', 'OR']
						],
						'currentView' => [
							'type' => 'string',
							'description' => 'The scope of entries to include, either "all" for all entries, "group" for entries belonging to the user\'s group(s), or "mine" for the user\'s own entries. Can also be comma-separated group IDs for a custom scope. Defaults to "all". Automatically downgraded if necessary to the level of the authenticated user\'s permissions on the form.',
						],
						'limitStart' => [
							'type' => ['integer', 'null'],
							'description' => 'Starting record for LIMIT statement'
						],
						'limitSize' => [
							'type' => ['integer', 'null'],
							'description' => 'Number of records to return. Defaults to 100. Set to null for no limit.'
						],
						'sortField' => [
							'type' => 'string',
							'description' => 'Element handle to sort by'
						],
						'sortOrder' => [
							'type' => 'string',
							'description' => 'Sort direction (ASC or DESC). Defaults to ASC.',
							'enum' => ['ASC', 'DESC']
						],
						'form_relationship_id' => [
							'type' => 'integer',
							'description' => 'Relationship ID to use (-1 for Primary Relationship, 0 for no relationship). Defaults to -1 for the Primary Relationship which includes all connected forms.'
						]
					],
					'required' => ['form_id']
				]
			],
			'prepare_database_values_for_human_readability'	=> [
				'name' => 'prepare_database_values_for_human_readability',
				'description' => 'Some database values are stored in a format that is not human-readable. This tool will convert those values to a human-readable format. For example, the values of linked elements are stored as foreign keys, but this tool will convert them to the actual values from the source form.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'value' => [
							'type' => ['integer', 'number', 'string'],
							'description' => 'The value from the database to prepare for human readability. This would often come from the results of the get_entries_from_form tool, but can be used independently as well.'
						],
						'element_handle' => [
							'type' => 'string',
							'description' => 'The element handle of the element that the value belongs to. Configuration properties of the element will be used to convert the raw database value into a human readable value. You can lookup the element handles in a form with the get_form_details tool.'
						],
						'entry_id' => [
							'type' => 'integer',
							'description' => 'Optional. The ID of the entry that the value belongs to. This is used to determine the context of the value in rare cases.'
						]
					],
					'required' => ['value', 'element_handle']
				]
			],
			'find_element_references' => [
				'name' => 'find_element_references',
				'description' => 'Find all references to a specific element throughout Formulize (screens, conditions, linked elements, calculations, etc.)',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'element_identifier' => [
							'type' => ['integer', 'string'],
							'description' => 'Element ID (number) or element handle (string) to search for'
						],
						'include_details' => [
							'type' => 'boolean',
							'description' => 'Include detailed context for each reference',
							'default' => true
						]
					],
					'required' => ['element_identifier']
				]
			],
		];
		$config_handler = xoops_gethandler('config');
		$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
		if($formulizeConfig['formulizeLoggingOnOff']) {
			$this->tools['read_system_activity_log'] = [
				'name' => 'read_system_activity_log',
				'description' => 'This Formulize system logs all activity. This tool will read up to the last 1000 lines from the activity log and return them as a array of JSON objects. There are several keys available in the objects, including microtime (a timestamp), user_id (the user who was active), request_id (which identifies log entries that were part of the same http request), session_id (which connects each request in a user\'s session), formulize_event (which is a short descriptor of the activity), as well as form_id, screen_id, and entry_id.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'form_id' => [
							'type' => 'integer',
							'description' => 'Optional. The ID of form that you want to find in the logs. Only log entries related to that form will be returned.'
						],
						'screen_id' => [
							'type' => 'integer',
							'description' => 'Optional. The ID of a screen that you want to find in the logs. Only log entries related to that screen will be returned.'
						],
						'entry_id' => [
							'type' => 'integer',
							'description' => 'Optional. The ID of a an entry that you want to find in the logs. Only log entries related to that entry will be returned. If an entry_id is specified, a form_id must be specified as well!'
						],
						'user_id' => [
							'type' => 'integer',
							'description' => 'Optional. The ID of a user that you want to find in the logs. Only log entries related to that user will be returned.'
						]
					]
				]
			];
		}

	}

	/**
	 * Handle tools list request
	 * @param string $id The request ID from the MCP client
	 * @return array The JSON-RPC response containing the list of tools
	 */
	private function handleToolsList($id)
	{
		return [
			'jsonrpc' => '2.0',
			'result' => [
				'tools' => array_values($this->tools)
			],
			'id' => $id
		];
	}

	/**
	 * Handle tool call request
	 * @param array $params The parameters from the MCP client, as parsed by the handleMCPRequest method
	 * @param string $id The request ID from the MCP client
	 * @return array The JSON-RPC response containing the result of the tool call
	 * @throws Exception If the tool is not found or if there is an error executing the tool
	 */
	private function handleToolCall($params, $id)
	{
		$toolName = $params['name'] ?? '';
		$arguments = $params['arguments'] ?? [];

		if (!isset($this->tools[$toolName])) {
			return $this->errorResponse('Unknown tool: ' . $toolName, -32602, $id);
		}

		try {
			$result = $this->executeTool($toolName, $arguments);
			return [
				'jsonrpc' => '2.0',
				'result' => [
					'content' => [
						[
							'type' => 'text',
							'text' => json_encode($result, JSON_PRETTY_PRINT)
						]
					]
				],
				'id' => $id
			];
		} catch (Exception $e) {
			return $this->errorResponse('Tool execution failed: ' . $e->getMessage(), -32603, $id);
		}
	}

	/**
	 * Execute a specific tool
	 * @param string $toolName The name of the tool to execute
	 * @param array $arguments The arguments for the tool, from the arguments parameter of the MCP request
	 * @return array The JSON-RPC response containing the result of the tool call
	 * @throws Exception If the tool is not implemented
	 */
	private function executeTool($toolName, $arguments)
	{
		switch ($toolName) {
			case 'test_connection':
				return $this->testConnection();
			case 'list_forms':
				return $this->listForms($arguments);
		case 'list_connections':
				return $this->listConnections($arguments);
			case 'get_form_details':
				return $this->getFormDetails($arguments);
			case 'get_element_details':
				return $this->getElementDetails($arguments);
			case 'create_entry':
    		return $this->writeFormEntry(intval($arguments['form_id']), 'new', $arguments['data'] ?? [], intval($arguments['relationship_id'] ?? -1));
			case 'update_entry':
    		return $this->writeFormEntry(intval($arguments['form_id']), intval($arguments['entry_id']), $arguments['data'] ?? [], intval($arguments['relationship_id'] ?? -1));
			case 'get_entries_from_form':
				return $this->getEntriesFromForm($arguments);
			case 'prepare_database_values_for_human_readability':
				return $this->prepareDatabaseValuesForHumanReadability($arguments);
			case 'find_element_references':
    		return $this->findElementReferences($arguments);
			case 'read_system_activity_log':
				if (isset($this->tools['read_system_activity_log'])) {
					return $this->readSystemActivityLog($arguments);
				} else {
					return ['message' => 'Logging is disabled on this Formulize system.' ];
				}
			default:
				throw new Exception('Tool not implemented: ' . $toolName);
		}
	}

	/**
	 * Test connection with proper authenticated user info
	 * Will only run if called directly by http. The Local Typescript MCP Server for Formulize has its own test_connection tool that takes precedence.
	 * This method is used to verify that the MCP server can connect to the Formulize database and that the Formulize authentication is working correctly.
	 * @return array An associative array containing connection information, capabilities, system info, and authenticated user details.
	 * @throws Exception If the database query fails or if the database connection is not successful.
	 */
	private function testConnection()
	{
		global $xoopsConfig;

		$testQuery = "SELECT 1 as test";
		$result = $this->db->query($testQuery);

		if (!$result) {
			throw new Exception('Database query failed');
		}

		$row = $this->db->fetchArray($result);

		$connectionInfo = [
			'message' => 'DB connection successful'.($this->authenticatedUser ? ' User authentication successful' : ''),
			'database_test' => $row['test'] == 1 ? 'passed' : 'failed',
			'authenticated_user' => false,
			'capabilities' => ['tools', 'resources', 'prompts'],
			'system_info' => $this->getSystemInfo(),
			'endpoints' => [
				'mcp' => $this->baseUrl . '/mcp',
				'capabilities' => $this->baseUrl . '/capabilities',
				'health' => $this->baseUrl . '/health'
			],
		];

		// Add authenticated user info
		if ($this->authenticatedUser) {
			$connectionInfo['authenticated_user'] = [
				'uid' => $this->authenticatedUid,
				'username' => $this->authenticatedUser->getVar('uname'),
				'name' => $this->authenticatedUser->getVar('name'),
				'email' => $this->authenticatedUser->getVar('email'),
				'groups' => $this->userGroups,
				'login_name' => $this->authenticatedUser->getVar('login_name')
			];
		}

		return $connectionInfo;
	}

	/**
	 * Gather data using Formulize's built-in function with proper permission scoping
	 * @param array $args An associative array containing the parameters for gathering data from a form.
	 * - 'form_id': The ID of the form to gather data from.
	 * - 'elementHandles': Optional. An array of element handles to include in the dataset. If not specified, all elements will be included.
	 * - 'filter': Optional. A filter string to apply to the dataset
	 * - 'andOr': Optional. The boolean operator to use between multiple filter strings, if there are multiple filters. Defaults to 'AND'.
	 * - 'currentView': Optional. The scope of entries to include, either 'all' for all entries, 'group' for entries belonging to the user's group(s), or 'mine' for the user's own entries. Defaults to 'all'. Automatically downgraded if necessary to the level of the authenticated user's permissions on the form.
	 * - 'limitStart': Optional. The starting record for the LIMIT statement. If not specified, no limit will be applied.
	 * - 'limitSize': Optional. The number of records to return. Defaults to 100. Set to null for no limit.
	 * - 'sortField': Optional. The element handle to sort the dataset by. If not specified, no sorting will be applied.
	 * - 'sortOrder': Optional. The sort direction, either 'ASC' or 'DESC'. Defaults to 'ASC'.
	 * - 'form_relationship_id': Optional. The ID of the relationship to use for gathering data. Defaults to -1 for the Primary Relationship which includes all connected forms.
	 * @return array An associative array containing the gathered dataset, total count, scope used, current view requested, current view actual, authenticated user details, and parameters used.
	 */
	private function getEntriesFromForm($args)
	{

		global $xoopsUser;

		$form_id = intval($args['form_id']);
		$elementHandles = $args['elementHandles'] ?? array();
		$filter = $args['filter'] ?? '';
		$andOr = $args['andOr'] ?? 'AND';
		$currentView = $args['currentView'] ?? 'all';
		$limitStart = $args['limitStart'] ?? null;
		$limitSize = $args['limitSize'] ?? 100;
		$sortField = $args['sortField'] ?? '';
		$sortOrder = $args['sortOrder'] ?? 'ASC';
		$form_relationship_id = intval($args['form_relationship_id'] ?? -1);

		try {
			// Build scope based on authenticated user and their permissions
			$scope = buildScope($currentView, $xoopsUser, $form_id);

			// The buildScope function returns an array with [scope, actualCurrentView]
			$actualScope = $scope[0];
			$actualCurrentView = $scope[1];

			// Call Formulize's gatherDataset function with all parameters
			$dataset = gatherDataset(
				$form_id,
				$elementHandles,
				$filter,
				$andOr,
				$actualScope,
				$limitStart,
				$limitSize,
				$sortField,
				$sortOrder,
				$form_relationship_id
			);

			return [
				'form_id' => $form_id,
				'dataset' => $dataset,
				'total_count' => count($dataset),
				'scope_used' => $actualScope,
				'current_view_requested' => $currentView,
				'current_view_actual' => $actualCurrentView,
				'authenticated_user' => [
					'uid' => $this->authenticatedUid,
					'username' => $this->authenticatedUser ? $this->authenticatedUser->getVar('uname') : 'anonymous'
				],
				'parameters' => [
					'elementHandles' => $elementHandles,
					'filter' => $filter,
					'andOr' => $andOr,
					'limitStart' => $limitStart,
					'limitSize' => $limitSize,
					'sortField' => $sortField,
					'sortOrder' => $sortOrder,
					'form_relationship_id' => $form_relationship_id
				]
			];
		} catch (Exception $e) {
			return [
				'error' => 'gatherDataset execution failed: ' . $e->getMessage(),
				'form_id' => $form_id,
				'requested_scope' => $currentView
			];
		}
	}

	/**
	 * Prepare raw database values for human consumption
	 * This function is used to convert raw data from the database into a more readable format.
	 * @param array $args An associative array containing the parameters for preparing the database values.
	 * - 'value': The raw value from the database, typically an integer or string.
	 * - 'element_handle': The handle of the element that the value belongs to, used to determine how to prepare the value.
	 * - 'entry_id': Optional. The ID of the entry that the value belongs to, used for context in some cases.
	 * @return array An array containing the prepared value(s) for human readability
	 */
	private function prepareDatabaseValuesForHumanReadability($args) {
		$value = intval($args['value']);
		$field = $args['element_handle'] ?? "";
		$entry_id = intval($args['entry_id'] ?? 0);
		$preppedValue = prepvalues($value, $field, $entry_id);
		return is_array($preppedValue) ? $preppedValue : [$preppedValue];
	}

	/**
	 * List all forms
	 * This function retrieves all forms from the Formulize database and returns them sorted by name.
	 * @param array $args An associative array containing any parameters for the request (not used in this case).
	 * @return array An array containing the list of forms.
	 */
	private function listForms($args)
	{
		$sql = "SELECT * FROM " . $this->db->prefix('formulize_id');

		$result = $this->db->query($sql);

		if (!$result) {
			return ['error' => 'Query failed', 'sql' => $sql];
		}

		$forms = [];
		$formTitles = [];
		while ($row = $this->db->fetchArray($result)) {
			// add element identifiers to the $row, not all element data because that would be too much when listing all forms
			$row['elements'] = [];
			$sql = "SELECT ele_handle as element_handle, ele_id as element_id FROM " . $this->db->prefix('formulize') . " WHERE id_form = " . intval($row['id_form']) . " ORDER BY ele_order";
			if($elementsResult = $this->db->query($sql)) {
				while($elementRow = $this->db->fetchArray($elementsResult)) {
					$row['elements'][] = $elementRow;
				}
			}
			$row['element_count'] = count($row['elements']);
			$formTitle = trans($row['form_title']);
			$row['form_title'] = $formTitle; // Use the translated title for display
			$row = $row + $this->getFormConnections($row['id_form']); // Add the form's connections
			$forms[] = $row;
			$formTitles[] = $formTitle;
		}

		array_multisort($formTitles, SORT_NATURAL, $forms);

		return [
			'forms' => $forms,
			'total_count' => count($forms)
		];
	}

	/**
	 * List all connections for a form. Tool level access for the connections list, since not all MCP clients can read resources. Duh.
	 * @param int $formId The ID of the form to get connections for
	 * @return array An associative array containing the connections for the form
	 */
	private function listConnections() {
		return $this->getFormConnections();
	}

	/**
	 * Get form details
	 */
	private function getFormDetails($args)
	{
		$formId = $args['form_id'];
		return $formId ? $this->getFormSchema($formId) : [];
	}

	/**
	 * Get form elements
	 */
	private function getElementDetails($args)
	{
		$formId = $args['form_id'];

		$sql = "SELECT * FROM " . $this->db->prefix('formulize') . " WHERE id_form = " . intval($formId) . " ORDER BY ele_order";
		$result = $this->db->query($sql);

		if (!$result) {
			return ['error' => 'Query failed', 'sql' => $sql];
		}

		$elements = [];
		while ($row = $this->db->fetchArray($result)) {
			$elements[] = $row;
		}

		return [
			'form_id' => $formId,
			'elements' => $elements,
			'total_count' => count($elements)
		];
	}


	/**
	 * Write entry data to a form (used by both create and update tools)
	 * @param int $formId The ID of the form to write the entry to
	 * @param int|string $entryId The ID of the entry to update, or 'new' to create a new entry
	 * @param array $data The data to write, where keys are element handles and values are the data to store.
	 * @param int $relationshipId The ID of the relationship to use for derived value calculations. Defaults to -1 for the Primary Relationship, which includes all connected forms.
	 * @return array An associative array with the result of the write operation, including success status, form ID, entry ID, action performed (created or updated), and any additional information such as new entry ID if created.
	 * @throws Exception If there is an error during the write operation, such as permission issues, form not found, invalid element handles, or failure to prepare data for storage.
	 */
	private function writeFormEntry($formId, $entryId, $data, $relationshipId = -1)
	{

		try {
			// Step 1: Check permissions
			if (!formulizePermHandler::user_can_edit_entry($formId, $this->authenticatedUid, $entryId)) {
				throw new Exception('Permission denied: cannot update entry '. $entryId . ' in form ' . $formId);
			}

			// Validate form exists and is active
			$formSql = "SELECT id_form FROM " . $this->db->prefix('formulize_id') . " WHERE id_form = " . intval($formId);
			$formResult = $this->db->query($formSql);
			$formData = $this->db->fetchArray($formResult);

			if (!$formData) {
				throw new Exception('Form not found: ' . $formId);
			}

			// Get form elements to validate handles
			$elementsSql = "SELECT ele_handle FROM " . $this->db->prefix('formulize'). " WHERE id_form = " . intval($formId);
			$elementsResult = $this->db->query($elementsSql);

			$validHandles = [];
			while ($row = $this->db->fetchArray($elementsResult)) {
				$validHandles[] = $row['ele_handle'];
			}

			// Step 2: Prepare the data
			$preparedData = [];
			foreach ($data as $elementHandle => $value) {
				// Validate element handle exists in this form
				if (!in_array($elementHandle, $validHandles)) {
					throw new Exception('Invalid element handle for this form: ' . $elementHandle);
				}

				// Prepare the value for database storage
				$preparedValue = prepareLiteralTextForDB($elementHandle, $value);
				if ($preparedValue === false) {
					throw new Exception('Failed to prepare data: ' . $value . ' for element: ' . $elementHandle);
				}

				$preparedData[$elementHandle] = $preparedValue;
			}

			if (empty($preparedData)) {
				throw new Exception('No valid data provided');
			}

			// Step 3: Write the entry
			$resultEntryId = formulize_writeEntry($preparedData, $entryId);

			if ($resultEntryId === null) {
				throw new Exception('No data was written (values may be unchanged)');
			}

			// For new entries, the function returns the new entry ID
			// For updates, it returns the existing entry ID
			$finalEntryId = ($entryId === 'new') ? $resultEntryId : $entryId;

			// Step 4: Update derived values
			formulize_updateDerivedValues($finalEntryId, $formId, $relationshipId);

			// Return success response
			$response = [
				'success' => true,
				'form_id' => $formId,
				'entry_id' => $finalEntryId,
				'action' => ($entryId === 'new') ? 'created' : 'updated',
				'elements_written' => array_keys($preparedData),
				'element_count' => count($preparedData)
			];

			if ($entryId === 'new') {
				$response['new_entry_id'] = $resultEntryId;
			}

			return $response;
		} catch (Exception $e) {
			return [
				'success' => false,
				'message' => ($entryId === 'new' ? 'Failed to create entry: ' : 'Failed to update entry: ') . $e->getMessage(),
				'code' => $e->getCode(),
				'form_id' => $formId,
				'entry_id' => $entryId === 'new' ? null : $entryId
			];
		}
	}

	/**
	 * Read the last 1000 lines of the system activity log
	 * This tool reads the system activity log and returns the last 1000 lines as an array of JSON objects.
	 * Each object contains keys such as microtime, user_id, request_id, session_id, formulize_event, form_id, screen_id, and entry_id.
	 * @param array $args An associative array containing optional parameters for filtering the log entries:
	 * - 'form_id': Optional. The ID of the form to filter log entries by
	 * - 'screen_id': Optional. The ID of the screen to filter log entries by
	 * - 'entry_id': Optional. The ID of the entry to filter log entries by. If specified, a form_id must also be provided.
	 * - 'user_id': Optional. The ID of the user to filter log entries by
	 * @return array An array containing each log line as a JSON object with keys such as microtime, user_id, request_id, session_id, formulize_event, form_id, screen_id, and entry_id.
	 */
	private function readSystemActivityLog($args) {

		$config_handler = xoops_gethandler('config');
		$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
		if($formulizeConfig['formulizeLoggingOnOff'] AND $formulizeLogFileLocation = $formulizeConfig['formulizeLogFileLocation']) {

			$form_id = intval($args['form_id'] ?? 0);
			$screen_id = intval($args['screen_id'] ?? 0);
			$entry_id = intval($args['entry_id'] ?? 0);
			$user_id = isset($args['user_id']) ? intval($args['user_id']) : null;

			$filename = $formulizeLogFileLocation.'/'.'formulize_log_active.log';
			$lineCount = 1000;
			$bufferSize = 8192;
			$handle = fopen($filename, 'r');
			if (!$handle) {
				throw new Exception("Cannot open file: $filename");
			}

      // Get file size
      fseek($handle, 0, SEEK_END);
      $fileSize = ftell($handle);
      if ($fileSize == 0) {
        fclose($handle);
      	return [];
      }

			$lines = [];
			$buffer = '';
			$pos = $fileSize;
			$linesFound = 0;

			// Read backwards in chunks
			while ($pos > 0 && $linesFound < $lineCount) {
					// Calculate chunk size (don't read past beginning of file)
					$chunkSize = min($bufferSize, $pos);
					$pos -= $chunkSize;

					// Read chunk from current position
					fseek($handle, $pos);
					$chunk = fread($handle, $chunkSize);

					// Prepend chunk to buffer
					$buffer = $chunk . $buffer;

					// Extract complete lines
					$parts = explode("\n", $buffer);

					// Keep the first part (incomplete line) in buffer for next iteration
					$buffer = array_shift($parts);

					// Process complete lines (in reverse order since we're reading backwards)
					while (!empty($parts) && $linesFound < $lineCount) {
							$line = array_pop($parts);
							if (trim($line) !== '') {
									if($form_id OR $screen_id OR $entry_id OR $user_id !== null) {
										// Filter log entries based on provided parameters
										$logEntry = json_decode($line, true);
										if ($logEntry) {
											if (($form_id && $logEntry['form_id'] != $form_id) ||
												($screen_id && $logEntry['screen_id'] != $screen_id) ||
												($entry_id && $logEntry['entry_id'] != $entry_id) ||
												($user_id !== null && $logEntry['user_id'] != $user_id)) {
												continue; // Skip this line if it doesn't match the filters
											}
										} else {
											continue; // Skip invalid JSON lines
										}
									}
									// Add to beginning of lines array to maintain original order
									array_unshift($lines, $line);
									$linesFound++;
							}
					}
			}

			// Handle any remaining content in buffer (happens when we reach file start)
			if ($pos == 0 && trim($buffer) !== '' && $linesFound < $lineCount) {
					array_unshift($lines, $buffer);
			}

			fclose($handle);

			// Return exactly the requested number of lines
			return array_slice($lines, -$lineCount);
    }
	}

	/**
	 * Find all references to a specific element throughout Formulize
	 */
	private function findElementReferences($args) {
			$elementIdentifier = $args['element_identifier'] ?? null;
			$includeDetails = $args['include_details'] ?? true;

			if (!$elementIdentifier) {
					throw new Exception('element_identifier is required');
			}

			// First, get the element info to have both ID and handle
			if($elementObject = _getElementObject($elementIdentifier)) {
				$elementId = $elementObject->getVar('ele_id');
				$elementHandle = $elementObject->getVar('ele_handle');
				$formId = $elementObject->getVar('fid');
				$form_handler = xoops_getmodulehandler('forms', 'formulize');
				$formObject = $form_handler->get($formId);
			} else {
				throw new Exception('Invalid element_identifier');
			}

			$references = [
					'element_info' => [
							'ele_id' => $elementId,
							'ele_handle' => $elementHandle,
							'ele_caption' => trans($elementObject->getVar('ele_handle')),
							'ele_type' => $elementObject->getVar('ele_type'),
							'form_id' => $formId,
							'form_title' => trans($formObject->getVar('form_title')),
					],
					'references' => []
			];

			// Search in screens
			$references['references']['screens'] = $this->findElementInScreens($elementId, $elementHandle, $includeDetails);

			// Search in other elements
			$references['references']['elements'] = $this->findElementInElements($elementId, $elementHandle, $includeDetails);

			// Search in saved views
			$references['references']['saved_views'] = $this->findElementInSavedViews($elementId, $elementHandle, $includeDetails);

			// Search in advanced calculations
			$references['references']['calculations'] = $this->findElementInCalculations($elementId, $elementHandle, $includeDetails);

			// Search in notification conditions
			$references['references']['notifications'] = $this->findElementInNotifications($elementId, $elementHandle, $includeDetails);

			// Search in other table
			$references['references']['other_text'] = $this->findElementInOtherTable($elementId, $includeDetails);

			// Search in framework links
			$references['references']['framework_links'] = $this->findElementInFrameworkLinks($elementId, $elementHandle, $includeDetails);

			// Search in group filters
			$references['references']['group_filters'] = $this->findElementInGroupFilters($elementId, $elementHandle, $includeDetails);

			// Search in form settings
			$references['references']['form_settings'] = $this->findElementInFormSettings($elementId, $elementHandle, $formId, $includeDetails);

			// Search in calendar screens
			$references['references']['calendar_screens'] = $this->findElementInCalendarScreens($elementId, $elementHandle, $includeDetails);

			// Search in digest data
			$references['references']['digest_data'] = $this->findElementInDigestData($elementId, $elementHandle, $includeDetails);

			// Search for on_before_save and on_after_save hooks
			$references['references']['form_hooks'] = $this->findElementInFormHooks($elementId, $elementHandle, $includeDetails);

			// Search in menu links and screen rewrite rules
			$references['references']['menu_and_urls'] = $this->findElementInMenuAndUrls($elementId, $elementHandle, $includeDetails);

			// Scan relevant PHP files for direct usage of the element ID or handle
			$references['references']['custom_php'] = $this->findElementInCustomPhp($elementId, $elementHandle, $includeDetails);

			// Count total references
			$totalCount = 0;
			foreach ($references['references'] as $category => $items) {
					$totalCount += count($items);
			}
			$references['total_references'] = $totalCount;

			return $references;
	}

	/**
	 * Find element references in screens
	 */
	private function findElementInScreens($elementId, $elementHandle, $includeDetails) {
			global $xoopsDB;
			$foundIn = [];

			// Check form screens
			$sql = "SELECT s.sid, s.title, s.type, sf.formelements, sf.elementdefaults
							FROM " . $xoopsDB->prefix("formulize_screen") . " s
							LEFT JOIN " . $xoopsDB->prefix("formulize_screen_form") . " sf ON s.sid = sf.sid
							WHERE s.type = 'form' AND (
								sf.formelements LIKE '%" . intval($elementId) . "%'
								OR sf.elementdefaults LIKE '%" . intval($elementId) . "%'
							)";

			$result = $xoopsDB->query($sql);
			while ($screen = $xoopsDB->fetchArray($result)) {
					$details = [];

					if(in_array(unserialize($screen['formelements'] ?? "a:0:{}"), $elementId)) {
							$details[] = "Displayed in this screen";
					}
					if(in_array(unserialize($screen['elementdefaults'] ?? "a:0:{}"), $elementId)) {
							$details[] = "Has a custom default value in this screen";
					}

					if (!empty($details)) {
							$foundIn[] = [
									'screen_id' => $screen['sid'],
									'screen_title' => trans($screen['title']),
									'screen_type' => 'Legacy Form',
									'details' => $includeDetails ? $details : null
							];
					}
			}

			// Check multipage screens
			$sql = "SELECT s.sid, s.title, s.type, sm.*
							FROM " . $xoopsDB->prefix("formulize_screen") . " s
							LEFT JOIN " . $xoopsDB->prefix("formulize_screen_multipage") . " sm ON s.sid = sm.sid
							WHERE s.type = 'multiPage' AND (
								sm.pages LIKE '%" . intval($elementId) . "%'
								OR sm.conditions LIKE '%" . intval($elementId) . "%'
								OR sm.conditions LIKE '%" . formulize_db_escape($elementHandle) . "%'
								OR sm.elementdefaults LIKE '%" . intval($elementId) . "%'
							)";


			$result = $xoopsDB->query($sql);
			while ($screen = $xoopsDB->fetchArray($result)) {
					$details = [];

					// Check pages
					if ($screen['pages']) {
							$pages = unserialize($screen['pages']);
							if (is_array($pages)) {
									foreach ($pages as $pageNum => $pageElements) {
											if (is_array($pageElements) && in_array($elementId, $pageElements)) {
													$details[] = "Displayed on page " . ($pageNum + 1);
											}
									}
							}
					}

					// Check conditions
					if ($screen['conditions']) {
							$conditions = unserialize($screen['conditions']);
							foreach($conditions as $pageNum => $theseConditions) {
								if($this->checkConditionForElementReferences($theseConditions, $elementId, $elementHandle)) {
									$details[] = "Referenced in conditions on page " . ($pageNum + 1);
								}
							}
					}

					// Check element defaults
					if(in_array(unserialize($screen['elementdefaults'] ?? "a:0:{}"), $elementId)) {
							$details[] = "Has a custom default value in this screen";
					}

					if (!empty($details)) {
							$foundIn[] = [
									'screen_id' => $screen['sid'],
									'screen_title' => $screen['title'],
									'screen_type' => 'Form',
									'details' => $includeDetails ? $details : null
							];
					}
			}

			// Check list screens
			$sql = "SELECT s.sid, s.title, s.type, sl.*
							FROM " . $xoopsDB->prefix("formulize_screen") . " s
							LEFT JOIN " . $xoopsDB->prefix("formulize_screen_listofentries") . " sl ON s.sid = sl.sid
							WHERE s.type = 'listOfEntries' AND (
								sl.hiddencolumms LIKE '%" . formulize_db_escape($elementHandle) . "%'
								OR sl.decolumns LIKE '%" . formulize_db_escape($elementHandle) . "%'
								OR sl.fundamental_filters LIKE '%" . intval($elementId) . "%'
								OR sl.fundamental_filters LIKE '%" . formulize_db_escape($elementHandle) . "%'
								OR sl.advanceview LIKE '%" . formulize_db_escape($elementHandle) . "%'
								OR sl.customactions LIKE '%" . intval($elementId) . "%'
							)	";

			$result = $xoopsDB->query($sql);
			while ($screen = $xoopsDB->fetchArray($result)) {
					$details = [];

					foreach(['hiddencolumms', 'decolumns'] as $field) {
							if ($screen[$field] AND $data = @unserialize($screen[$field])) {
								if (in_array($elementHandle, $data)) {
									if($field == 'hiddencolumms') {
										$details[] = "Included as a hidden HTML form element with each row in the list";
									} else {
										$details[] = "Displayed as a editable form element in the list";
									}
								}
							}
					}

					if($this->checkConditionForElementReferences($screen['fundamental_filter'], $elementId, $elementHandle)) {
						$details[] = "Referenced in the fundamental filters for the screen";
					}

					if ($screen['advanceview'] AND $data = @unserialize($screen['advanceview'])) {
						foreach($data as $columnDetails) {
							if (isset($columnDetails[0]) && $columnDetails[0] == $elementHandle) {
								$details[] = "Included in the advanced view options for the screen";
							}
						}
					}

					if ($screen['customactions'] AND $data = @unserialize($screen['customactions'])) {
						foreach($data as $customButtonDetails) {
							foreach($customButtonDetails as $effectProperties) {
								if(is_array($effectProperties) AND isset($effectProperties['element']) AND $effectProperties['element'] == $elementId) {
									$details[] = "Affected by a custom button on the screen";
								}
							}
						}
					}

					if (!empty($details)) {
							$foundIn[] = [
									'screen_id' => $screen['sid'],
									'screen_title' => $screen['title'],
									'screen_type' => 'List of Entries',
									'details' => $includeDetails ? $details : null
							];
					}
			}

			// Find all references in screen templates of any kind

			// Check template screens
			$sql = "SELECT s.sid, s.title, s.type, st.*
							FROM " . $xoopsDB->prefix("formulize_screen") . " s
							LEFT JOIN " . $xoopsDB->prefix("formulize_screen_template") . " st ON s.sid = st.sid
							WHERE s.type = 'template' AND (
								st.template LIKE '%" . formulize_db_escape($elementHandle) . "%'
								OR st.custom_code LIKE '%" . formulize_db_escape($elementHandle) . "%'
								OR st.template LIKE '%" . intval($elementId) . "%'
								OR st.custom_code LIKE '%" . intval($elementId) . "%'
							)";

			$result = $xoopsDB->query($sql);
			while ($screen = $xoopsDB->fetchArray($result)) {
					$found = false;
					$details = [];

					// Check template and custom code for element references
					if ($screen['template'] && (strpos($screen['template'], $elementHandle) !== false || strpos($screen['template'], "displayElement($elementId") !== false)) {
							$found = true;
							$details[] = "Element referenced in template code";
					}

					if ($screen['custom_code'] && (strpos($screen['custom_code'], $elementHandle) !== false || strpos($screen['custom_code'], (string)$elementId) !== false)) {
							$found = true;
							$details[] = "Element referenced in custom code";
					}

					if ($found) {
							$foundIn[] = [
									'screen_id' => $screen['sid'],
									'screen_title' => $screen['title'],
									'screen_type' => 'template',
									'details' => $includeDetails ? $details : null
							];
					}
			}

			return $foundIn;
	}

	/**
	 * Check conditions for element references
	 * key 0 is the elements, key 1 is the operators, key 2 is the search terms, key 3 is the all or oom flags (match all or match one or more)
	 * element is id, but was handle in the past, so check both
	 * search term could contain {elementHandle}
	 */
	private function checkConditionForElementReferences($conditions, $elementId, $elementHandle) {
		if (is_array($conditions)
			AND ((
				isset($conditions[0]) AND (
					in_array($elementId, $conditions[0])
					OR in_array($elementHandle, $conditions[0])
				)
			) OR (
				isset($conditions[2]) AND (
					in_array("{".$elementHandle."}", $conditions[2])
				)
			))
		) {
			return true;
		}
		return false;
	}

	/**
	 * Find element references in menu links and screen rewrite rules
	 */
	private function findElementInMenuAndUrls($elementId, $elementHandle, $includeDetails) {
			global $xoopsDB;
			$foundIn = [];

			// Check menu links
			$sql = "SELECT ml.*, a.name as app_name
							FROM " . $xoopsDB->prefix("formulize_menu_links") . " ml
							LEFT JOIN " . $xoopsDB->prefix("formulize_applications") . " a ON ml.appid = a.appid";

			$result = $xoopsDB->query($sql);
			while ($menu = $xoopsDB->fetchArray($result)) {
					$found = false;
					$details = [];

					// Check URL for element references
					if ($menu['url'] && (strpos($menu['url'], $elementHandle) !== false || strpos($menu['url'], 'ele=' . $elementId) !== false)) {
							$found = true;
							$details[] = "Element referenced in menu URL";
					}

					if ($found) {
							$foundIn[] = [
									'type' => 'menu_link',
									'menu_id' => $menu['menu_id'],
									'app_name' => $menu['app_name'],
									'link_text' => $menu['link_text'],
									'details' => $includeDetails ? $details : null
							];
					}
			}

			// Check screen rewrite rules
			$sql = "SELECT sid, title, rewriteruleElement
							FROM " . $xoopsDB->prefix("formulize_screen") . "
							WHERE rewriteruleElement = " . intval($elementId) . " AND rewriteruleElement != 0";

			$result = $xoopsDB->query($sql);
			while ($screen = $xoopsDB->fetchArray($result)) {
					$foundIn[] = [
							'type' => 'screen_rewrite_rule',
							'screen_id' => $screen['sid'],
							'screen_title' => $screen['title'],
							'details' => $includeDetails ? ["Element used for URL rewriting in this screen"] : null
					];
			}

			return $foundIn;
	}

	/**
	 * Find element references in other elements
	 */
	private function findElementInElements($elementId, $elementHandle, $includeDetails) {
			global $xoopsDB;
			$foundIn = [];

			$sql = "SELECT e.*, f.desc_form as form_title
							FROM " . $xoopsDB->prefix("formulize") . " e
							LEFT JOIN " . $xoopsDB->prefix("formulize_id") . " f ON e.id_form = f.id_form
							WHERE e.ele_id != " . intval($elementId);

			$result = $xoopsDB->query($sql);
			while ($element = $xoopsDB->fetchArray($result)) {
					$found = false;
					$details = [];

					// Check ele_value
					if ($element['ele_value']) {
							$eleValue = @unserialize($element['ele_value']);
							if ($eleValue && $this->searchInArray($eleValue, $elementId, $elementHandle)) {
									$found = true;

									// Specific checks for element types
									if ($element['ele_type'] == 'select' && isset($eleValue[2]) && is_string($eleValue[2])) {
											// Linked selectbox
											if (strpos($eleValue[2], '#*=:*' . $elementHandle) !== false) {
													$details[] = "This selectbox is linked to the element";
											}
									}

									if ($element['ele_type'] == 'text' && isset($eleValue[4]) && $eleValue[4] == $elementId) {
											$details[] = "This textbox has an associated element reference";
									}

									if ($element['ele_type'] == 'derived') {
											$details[] = "Referenced in derived value formula";
									}
							}
					}

					// Check filter settings
					if ($element['ele_filtersettings']) {
							$filters = @unserialize($element['ele_filtersettings']);
							if ($filters && $this->searchInArray($filters, $elementId, $elementHandle)) {
									$found = true;
									$details[] = "Referenced in display filter conditions";
							}
					}

					// Check disabled conditions
					if ($element['ele_disabledconditions']) {
							$conditions = @unserialize($element['ele_disabledconditions']);
							if ($conditions && $this->searchInArray($conditions, $elementId, $elementHandle)) {
									$found = true;
									$details[] = "Referenced in disabled conditions";
							}
					}

					// Check export options
					if ($element['ele_exportoptions']) {
							$exportOpts = @unserialize($element['ele_exportoptions']);
							if ($exportOpts && $this->searchInArray($exportOpts, $elementId, $elementHandle)) {
									$found = true;
									$details[] = "Referenced in export options";
							}
					}

					if ($found) {
							$foundIn[] = [
									'element_id' => $element['ele_id'],
									'element_handle' => $element['ele_handle'],
									'element_caption' => $element['ele_caption'],
									'element_type' => $element['ele_type'],
									'form_id' => $element['id_form'],
									'form_title' => $element['form_title'],
									'details' => $includeDetails ? $details : null
							];
					}
			}

			return $foundIn;
	}

	/**
	 * Find element references in saved views
	 */
	private function findElementInSavedViews($elementId, $elementHandle, $includeDetails) {
			global $xoopsDB;
			$foundIn = [];

			$sql = "SELECT sv.*, f.desc_form as form_title
							FROM " . $xoopsDB->prefix("formulize_saved_views") . " sv
							LEFT JOIN " . $xoopsDB->prefix("formulize_id") . " f ON sv.sv_mainform = f.id_form";

			$result = $xoopsDB->query($sql);
			while ($view = $xoopsDB->fetchArray($result)) {
					$found = false;
					$details = [];

					// Check various fields
					$fieldsToCheck = [
							'sv_oldcols' => 'column configuration',
							'sv_asearch' => 'advanced search',
							'sv_calc_cols' => 'calculation columns',
							'sv_quicksearches' => 'quick searches',
							'sv_currentview' => 'current view filter'
					];

					foreach ($fieldsToCheck as $field => $description) {
							if ($view[$field]) {
									$data = @unserialize($view[$field]);
									if (!$data) {
											// Try string search
											if (strpos($view[$field], $elementHandle) !== false || strpos($view[$field], (string)$elementId) !== false) {
													$found = true;
													$details[] = "Referenced in $description";
											}
									} elseif ($this->searchInArray($data, $elementId, $elementHandle)) {
											$found = true;
											$details[] = "Referenced in $description";
									}
							}
					}

					if ($found) {
							$foundIn[] = [
									'view_id' => $view['sv_id'],
									'view_name' => $view['sv_name'],
									'form_id' => $view['sv_mainform'],
									'form_title' => $view['form_title'],
									'details' => $includeDetails ? $details : null
							];
					}
			}

			return $foundIn;
	}

	/**
	 * Find element references in advanced calculations
	 */
	private function findElementInCalculations($elementId, $elementHandle, $includeDetails) {
			global $xoopsDB;
			$foundIn = [];

			$sql = "SELECT ac.*, f.desc_form as form_title
							FROM " . $xoopsDB->prefix("formulize_advanced_calculations") . " ac
							LEFT JOIN " . $xoopsDB->prefix("formulize_id") . " f ON ac.fid = f.id_form";

			$result = $xoopsDB->query($sql);
			while ($calc = $xoopsDB->fetchArray($result)) {
					$found = false;
					$details = [];

					// Check input, output, and steps
					$fieldsToCheck = [
							'input' => 'calculation input',
							'output' => 'calculation output',
							'steps' => 'calculation steps'
					];

					foreach ($fieldsToCheck as $field => $description) {
							if ($calc[$field]) {
									// These fields often contain element handles in calculations
									if (strpos($calc[$field], $elementHandle) !== false) {
											$found = true;
											$details[] = "Referenced in $description";
									}

									// Also check serialized data
									$data = @unserialize($calc[$field]);
									if ($data && $this->searchInArray($data, $elementId, $elementHandle)) {
											$found = true;
											$details[] = "Referenced in $description (structured data)";
									}
							}
					}

					if ($found) {
							$foundIn[] = [
									'calculation_id' => $calc['acid'],
									'calculation_name' => $calc['name'],
									'form_id' => $calc['fid'],
									'form_title' => $calc['form_title'],
									'details' => $includeDetails ? $details : null
							];
					}
			}

			return $foundIn;
	}

	/**
	 * Find element references in notification conditions
	 */
	private function findElementInNotifications($elementId, $elementHandle, $includeDetails) {
			global $xoopsDB;
			$foundIn = [];

			$sql = "SELECT nc.*, f.desc_form as form_title
							FROM " . $xoopsDB->prefix("formulize_notification_conditions") . " nc
							LEFT JOIN " . $xoopsDB->prefix("formulize_id") . " f ON nc.not_cons_fid = f.id_form";

			$result = $xoopsDB->query($sql);
			while ($notif = $xoopsDB->fetchArray($result)) {
					$found = false;
					$details = [];

					// Check specific element reference fields
					if ($notif['not_cons_elementuids'] == $elementId) {
							$found = true;
							$details[] = "Element supplies user IDs for notification";
					}

					if ($notif['not_cons_elementemail'] == $elementId) {
							$found = true;
							$details[] = "Element supplies email addresses for notification";
					}

					// Check conditions
					if ($notif['not_cons_con']) {
							$conditions = @unserialize($notif['not_cons_con']);
							if ($conditions && $this->searchInArray($conditions, $elementId, $elementHandle)) {
									$found = true;
									$details[] = "Referenced in notification conditions";
							}
					}

					// Check template for element placeholders
					if ($notif['not_cons_template'] && strpos($notif['not_cons_template'], '{' . $elementHandle . '}') !== false) {
							$found = true;
							$details[] = "Referenced in notification template";
					}

					if ($found) {
							$foundIn[] = [
									'notification_id' => $notif['not_cons_id'],
									'event' => $notif['not_cons_event'],
									'form_id' => $notif['not_cons_fid'],
									'form_title' => $notif['form_title'],
									'details' => $includeDetails ? $details : null
							];
					}
			}

			return $foundIn;
	}

	/**
	 * Find element references in formulize_other table
	 */
	private function findElementInOtherTable($elementId, $includeDetails) {
			global $xoopsDB;
			$foundIn = [];

			$sql = "SELECT COUNT(*) as count FROM " . $xoopsDB->prefix("formulize_other") . "
							WHERE ele_id = " . intval($elementId);

			$result = $xoopsDB->query($sql);
			$data = $xoopsDB->fetchArray($result);

			if ($data['count'] > 0) {
					$foundIn[] = [
							'table' => 'formulize_other',
							'count' => $data['count'],
							'details' => $includeDetails ? ["Element has {$data['count']} 'other' text entries"] : null
					];
			}

			return $foundIn;
	}

	/**
	 * Recursive search in arrays for element references
	 */
	private function searchInArray($array, $elementIdentifier) {
			if (!is_array($array)) {
					return false;
			}

			foreach ($array as $key => $value) {
					// Check if key matches
					if ($key === $elementId || $key === $elementHandle || $key === (string)$elementId) {
							return true;
					}

					// Check if value matches
					if ($value === $elementId || $value === $elementHandle || $value === (string)$elementId) {
							return true;
					}

					// If value is string, check for element handle in various formats
					if (is_string($value)) {
							// Check for element handle in various contexts
							$patterns = [
									'{' . $elementHandle . '}',     // Placeholder format
									'[' . $elementHandle . ']',     // Alternative placeholder
									'element_' . $elementId,        // Common prefix format
									'#' . $elementId,               // ID reference format
									'displayElement(' . $elementId, // Function call format
									'"' . $elementHandle . '"',     // Quoted handle
									"'" . $elementHandle . "'",     // Single quoted handle
							];

							foreach ($patterns as $pattern) {
									if (strpos($value, $pattern) !== false) {
											return true;
									}
							}
					}

					// Recurse if value is array
					if (is_array($value) && $this->searchInArray($value, $elementId, $elementHandle)) {
							return true;
					}
			}

			return false;
	}

	/**
	 * Find element references in framework links
	 */
	private function findElementInFrameworkLinks($elementId, $elementHandle, $includeDetails) {
			global $xoopsDB;
			$foundIn = [];

			$sql = "SELECT fl.*, f.frame_name,
							f1.desc_form as form1_title, f2.desc_form as form2_title
							FROM " . $xoopsDB->prefix("formulize_framework_links") . " fl
							LEFT JOIN " . $xoopsDB->prefix("formulize_frameworks") . " f ON fl.fl_frame_id = f.frame_id
							LEFT JOIN " . $xoopsDB->prefix("formulize_id") . " f1 ON fl.fl_form1_id = f1.id_form
							LEFT JOIN " . $xoopsDB->prefix("formulize_id") . " f2 ON fl.fl_form2_id = f2.id_form
							WHERE fl.fl_key1 = " . intval($elementId) . " OR fl.fl_key2 = " . intval($elementId);

			$result = $xoopsDB->query($sql);
			while ($link = $xoopsDB->fetchArray($result)) {
					$details = [];

					if ($link['fl_key1'] == $elementId) {
							$details[] = "Element is key1 linking form '{$link['form1_title']}' to '{$link['form2_title']}'";
					}
					if ($link['fl_key2'] == $elementId) {
							$details[] = "Element is key2 linking form '{$link['form2_title']}' to '{$link['form1_title']}'";
					}

					$foundIn[] = [
							'framework_id' => $link['fl_frame_id'],
							'framework_name' => $link['frame_name'],
							'link_id' => $link['fl_id'],
							'form1' => $link['form1_title'],
							'form2' => $link['form2_title'],
							'details' => $includeDetails ? $details : null
					];
			}

			return $foundIn;
	}

	/**
	 * Find element references in group filters
	 */
	private function findElementInGroupFilters($elementId, $elementHandle, $includeDetails) {
			global $xoopsDB;
			$foundIn = [];

			$sql = "SELECT gf.*, f.desc_form as form_title, g.name as group_name
							FROM " . $xoopsDB->prefix("formulize_group_filters") . " gf
							LEFT JOIN " . $xoopsDB->prefix("formulize_id") . " f ON gf.fid = f.id_form
							LEFT JOIN " . $xoopsDB->prefix("groups") . " g ON gf.groupid = g.groupid";

			$result = $xoopsDB->query($sql);
			while ($filter = $xoopsDB->fetchArray($result)) {
					$found = false;
					$details = [];

					// Check filter text for element references
					if ($filter['filter']) {
							// Filters often use element handles in format: handle/**/value/**/operator
							if (strpos($filter['filter'], $elementHandle) !== false) {
									$found = true;
									$details[] = "Element referenced in group filter condition";
							}

							// Also check for element ID references
							$filterData = @unserialize($filter['filter']);
							if ($filterData && $this->searchInArray($filterData, $elementId, $elementHandle)) {
									$found = true;
									$details[] = "Element referenced in filter structure";
							}
					}

					if ($found) {
							$foundIn[] = [
									'filter_id' => $filter['filterid'],
									'form_id' => $filter['fid'],
									'form_title' => $filter['form_title'],
									'group_id' => $filter['groupid'],
									'group_name' => $filter['group_name'],
									'details' => $includeDetails ? $details : null
							];
					}
			}

			return $foundIn;
	}

	/**
	 * Find element references in form settings
	 */
	private function findElementInFormSettings($elementId, $elementHandle, $formId, $includeDetails) {
			global $xoopsDB;
			$foundIn = [];

			// Check the form's own settings
			$sql = "SELECT * FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form = " . intval($formId);
			$result = $xoopsDB->query($sql);

			if ($form = $xoopsDB->fetchArray($result)) {
					$found = false;
					$details = [];

					// Check headerlist
					if ($form['headerlist']) {
							$headerlist = @unserialize($form['headerlist']);
							if (!$headerlist) {
									// Sometimes headerlist is a comma-separated string
									if (strpos($form['headerlist'], $elementHandle) !== false) {
											$found = true;
											$details[] = "Element in default list columns";
									}
							} elseif (is_array($headerlist) && in_array($elementHandle, $headerlist)) {
									$found = true;
									$details[] = "Element in default list columns";
							}
					}

					if ($found) {
							$foundIn[] = [
									'location' => 'form_settings',
									'form_id' => $formId,
									'form_title' => $form['desc_form'],
									'details' => $includeDetails ? $details : null
							];
					}
			}

			return $foundIn;
	}

	/**
	 * Find element references in calendar screens
	 */
	private function findElementInCalendarScreens($elementId, $elementHandle, $includeDetails) {
			global $xoopsDB;
			$foundIn = [];

			$sql = "SELECT s.sid, s.title, sc.*
							FROM " . $xoopsDB->prefix("formulize_screen") . " s
							LEFT JOIN " . $xoopsDB->prefix("formulize_screen_calendar") . " sc ON s.sid = sc.sid
							WHERE s.type = 'calendar' AND sc.datasets IS NOT NULL";

			$result = $xoopsDB->query($sql);
			while ($calendar = $xoopsDB->fetchArray($result)) {
					$found = false;
					$details = [];

					// Check datasets configuration
					if ($calendar['datasets']) {
							$datasets = @unserialize($calendar['datasets']);
							if ($datasets && $this->searchInArray($datasets, $elementId, $elementHandle)) {
									$found = true;
									$details[] = "Element referenced in calendar dataset configuration";
							}

							// Calendar screens often reference date elements
							if (!$datasets && strpos($calendar['datasets'], $elementHandle) !== false) {
									$found = true;
									$details[] = "Element possibly referenced in calendar configuration";
							}
					}

					if ($found) {
							$foundIn[] = [
									'screen_id' => $calendar['sid'],
									'screen_title' => $calendar['title'],
									'screen_type' => 'calendar',
									'calendar_type' => $calendar['caltype'],
									'details' => $includeDetails ? $details : null
							];
					}
			}

			return $foundIn;
	}

	/**
	 * Find element references in digest data
	 */
	private function findElementInDigestData($elementId, $elementHandle, $includeDetails) {
			global $xoopsDB;
			$foundIn = [];

			$sql = "SELECT dd.*, f.desc_form as form_title
							FROM " . $xoopsDB->prefix("formulize_digest_data") . " dd
							LEFT JOIN " . $xoopsDB->prefix("formulize_id") . " f ON dd.fid = f.id_form";

			$result = $xoopsDB->query($sql);
			while ($digest = $xoopsDB->fetchArray($result)) {
					$found = false;
					$details = [];

					// Check mail template for element placeholders
					if ($digest['mailTemplate'] && strpos($digest['mailTemplate'], '{' . $elementHandle . '}') !== false) {
														$found = true;
							$details[] = "Element referenced in digest email template";
					}

					// Check extra tags
					if ($digest['extra_tags']) {
							$tags = @unserialize($digest['extra_tags']);
							if ($tags && $this->searchInArray($tags, $elementId, $elementHandle)) {
									$found = true;
									$details[] = "Element referenced in digest extra tags";
							}
					}

					if ($found) {
							$foundIn[] = [
									'digest_id' => $digest['digest_id'],
									'email' => $digest['email'],
									'event' => $digest['event'],
									'form_id' => $digest['fid'],
									'form_title' => $digest['form_title'],
									'details' => $includeDetails ? $details : null
							];
					}
			}

			return $foundIn;
	}

	/**
	 * Find element references in form hooks (on_before_save, on_after_save)
	 */
	private function findElementInFormHooks($elementId, $elementHandle, $includeDetails) {
			global $xoopsDB;
			$foundIn = [];

			// Check for custom form files that might contain hooks
			$formHandler = xoops_getmodulehandler('forms', 'formulize');
			$allForms = $formHandler->getAllForms();

			foreach ($allForms as $form) {
					$formId = $form->getVar('id_form');
					$details = [];

					// Check if there's a custom class file for this form
					$customFormFile = XOOPS_ROOT_PATH . "/modules/formulize/class/form_" . $formId . "Form.php";
					if (file_exists($customFormFile)) {
							$content = file_get_contents($customFormFile);

							// Search for element references in the code
							if (strpos($content, $elementHandle) !== false || strpos($content, (string)$elementId) !== false) {
									$details[] = "Element referenced in custom form class";

									// Try to identify specific methods
									if (strpos($content, 'on_before_save') !== false &&
											(strpos($content, $elementHandle) !== false || strpos($content, (string)$elementId) !== false)) {
											$details[] = "Possibly referenced in on_before_save hook";
									}

									if (strpos($content, 'on_after_save') !== false &&
											(strpos($content, $elementHandle) !== false || strpos($content, (string)$elementId) !== false)) {
											$details[] = "Possibly referenced in on_after_save hook";
									}

									if (strpos($content, 'customValidation') !== false &&
											(strpos($content, $elementHandle) !== false || strpos($content, (string)$elementId) !== false)) {
											$details[] = "Possibly referenced in custom validation";
									}
							}

							if (!empty($details)) {
									$foundIn[] = [
											'form_id' => $formId,
											'form_title' => $form->getVar('title'),
											'file' => "form_{$formId}Form.php",
											'details' => $includeDetails ? $details : null
									];
							}
					}
			}

			return $foundIn;
	}

	/**
	 * Scan relevant PHP files for direct usage of the element ID or handle
	 */
	private function findElementInCustomPhp($elementId, $elementHandle, $includeDetails) {

    global $xoopsConfig;
    $theme = $xoopsConfig['theme_set'];

		$foundIn = [];
		$searchPaths = [
			__DIR__ . '/../modules/formulize/code/',
			__DIR__ . '/../modules/formulize/templates/screens/'.$theme.'/',
		];
		$patterns = [
			preg_quote((string)$elementId, '/'),
			preg_quote($elementHandle, '/')
		];
		foreach ($searchPaths as $dir) {
			if (!is_dir($dir)) continue;
			$files = glob($dir . '*.php');
			foreach ($files as $file) {
				$contents = file_get_contents($file);
				foreach ($patterns as $pattern) {
					if (preg_match('/' . $pattern . '/', $contents)) {
						$foundIn[] = [
							'file' => basename($file),
							'details' => $includeDetails ? "Direct reference to element ID or handle found in file" : null
						];
						break;
					}
				}
			}
		}
		return $foundIn;
	}
}
