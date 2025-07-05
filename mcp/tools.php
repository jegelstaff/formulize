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
			]
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

}
