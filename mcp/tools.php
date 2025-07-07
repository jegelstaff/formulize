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
				],
			],
			$this->mcpRequest['localServerName'] => [
				'name' => $this->mcpRequest['localServerName'],
				'description' => 'This tool contains basic instructions and background info. Use this tool first. This tool returns the instructions content that should be part of the initialize MCP call, but which is often ignored by MCP clients.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => (object)[]
				],
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
				],
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
			'list_applications' => [
				'name' => 'list_applications',
				'description' => "List all the applications and the forms that are part of each one.",
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
				'description' => "List all the connections between forms, which can explain how forms are related to one another. Connection are based pairs of elements, one in each form, that have matching values. Entries in the forms are connected when they have the same value in the paired elements, or when one element is 'linked' to the other, in which case the values in the linked element will be entry_ids in the other form (foreign keys).",
				'inputSchema' => [
					'type' => 'object',
					'properties' => (object)[]
				]
			],
			'list_screens' => [
				'name' => 'list_screens',
				'description' => "List all the screens for all forms.",
				'inputSchema' => [
					'type' => 'object',
					'properties' => (object)[]
				]
			],
			'get_form_details' => [
				'name' => 'get_form_details',
				'description' => 'Get detailed information about a specific form, including its elements, screens, and connections to other forms. You can get a list of all the forms and their IDs with the list_forms tool.',
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
			'get_screen_details' => [
				'name' => 'get_screen_details',
				'description' => "Get detailed information about a specific screen. Lookup screens by their ID number, also known as 'sid'",
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'screen_id' => [
							'type' => 'integer',
							'description' => 'The ID of the screen to retrieve details for'
						]
					],
					'required' => ['screen_id']
				]
			],
			'list_users' => [
				'name' => 'list_users',
				'description' => "List all the users in the system.",
				'inputSchema' => [
					'type' => 'object',
					'properties' => (object)[]
				]
			]

		];

		// only webmasters can access certain tools
		if(in_array(XOOPS_GROUP_ADMIN, $this->userGroups)) {

			// check the version of mariadb or mysql
			$dbVersionSQL = "SELECT @@version as version";
			$dbVersionResult = $this->db->query($dbVersionSQL);
			$dbVersionData = $this->db->fetchArray($dbVersionResult);

			$this->tools['query_the_database_directly'] = [
				'name' => 'query_the_database_directly',
				'description' => "Query the database with a SELECT statement. The database is {$dbVersionData['version']} and queries are written in SQL. Use the get_form_details tool to lookup the form\'s database table name and the field names of all its elements, if you don\'t know them already.",
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'sql' => [
							'type' => 'string',
							'description' => 'The SQL statement to run on the database. Must be a SELECT statement.'
						]
					],
					'required' => ['sql']
				]
			];

			// Logging tool only available if logging is enabled
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
	 * @throws Exception If the tool is unknown, not implemented, or if there is an error executing the tool
	 */
	private function handleToolCall($params, $id)
	{
		$toolName = $params['name'] ?? '';
		$arguments = $params['arguments'] ?? [];

		if (!isset($this->tools[$toolName])) {
			return $this->JSONerrorResponse('Unknown tool: ' . $toolName, -32602, $id);
		}

		try {
			if($toolName == $this->mcpRequest['localServerName']) {
				$result = [
					'instructions' => $this->getInitializeInstructions(),
					'authenticated_user' => $this->getAuthenticatedUserDetails()
				];
			} elseif(method_exists($this, $toolName)) {
				$result = $this->$toolName($arguments);
			} else {
				throw new Exception('Tool not implemented: ' . $toolName);
			}
			return [
				'jsonrpc' => '2.0',
				'result' => [
					'content' => [
						[
							'type' => 'text',
							'text' => is_string($result) ? $result : json_encode($result, JSON_PRETTY_PRINT)
						]
					]
				],
				'id' => $id
			];
		} catch (Exception $e) {
			return $this->JSONerrorResponse('Tool execution failed: ' . $e->getMessage(), -32603, $id);
		}
	}

	/**
	 * Test connection with proper authenticated user info
	 * Will only run if called directly by http. The Local Typescript MCP Server for Formulize has its own test_connection tool that takes precedence.
	 * This method is used to verify that the MCP server can connect to the Formulize database and that the Formulize authentication is working correctly.
	 * @return array An associative array containing connection information, capabilities, system info, and authenticated user details.
	 * @throws Exception If the database query fails or if the database connection is not successful.
	 */
	private function test_connection()
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
			'capabilities' => ['tools', 'resources', 'prompts'],
			'system_info' => $this->system_info(),
			'endpoints' => [
				'mcp' => $this->baseUrl . '/mcp',
				'capabilities' => $this->baseUrl . '/capabilities',
				'health' => $this->baseUrl . '/health'
			],
		];

		return $connectionInfo;
	}

	/**
	 * Gather data using Formulize's built-in function with proper permission scoping
	 * @param array $arguments An associative array containing the parameters for gathering data from a form.
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
	private function get_entries_from_form($arguments)
	{

		global $xoopsUser;

		$form_id = intval($arguments['form_id']);
		$elementHandles = $arguments['elementHandles'] ?? array();
		$filter = $arguments['filter'] ?? '';
		$andOr = $arguments['andOr'] ?? 'AND';
		$currentView = $arguments['currentView'] ?? 'all';
		$limitStart = $arguments['limitStart'] ?? null;
		$limitSize = $arguments['limitSize'] ?? 100;
		$sortField = $arguments['sortField'] ?? '';
		$sortOrder = $arguments['sortOrder'] ?? 'ASC';
		$form_relationship_id = intval($arguments['form_relationship_id'] ?? -1);

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
	 * @param array $arguments An associative array containing the parameters for preparing the database values.
	 * - 'value': The raw value from the database, typically an integer or string.
	 * - 'element_handle': The handle of the element that the value belongs to, used to determine how to prepare the value.
	 * - 'entry_id': Optional. The ID of the entry that the value belongs to, used for context in some cases.
	 * @return array An array containing the prepared value(s) for human readability
	 */
	private function prepare_database_values_for_human_readability($arguments) {
		$value = intval($arguments['value']);
		$field = $arguments['element_handle'] ?? "";
		$entry_id = intval($arguments['entry_id'] ?? 0);
		$preppedValue = prepvalues($value, $field, $entry_id);
		return is_array($preppedValue) ? $preppedValue : [$preppedValue];
	}

	/**
	 * List all forms
	 * This function retrieves all forms from the Formulize database and returns them sorted by name.
	 * @param array $arguments An associative array containing any parameters for the request (not used in this case).
	 * @return array An array containing the list of forms.
	 */
	private function list_forms()
	{

		$sql = "SELECT * FROM " . $this->db->prefix('formulize_id');

		$result = $this->db->query($sql);

		if (!$result) {
			return ['error' => 'Query failed', 'sql' => $sql];
		}

		$forms = [];
		$formTitles = [];
		while ($row = $this->db->fetchArray($result)) {
			$formId = $row['id_form'];
			if(security_check($formId)) {
				// add element identifiers to the $row, not all element data because that would be too much when listing all forms
				$row['elements'] = [];
				$sql = "SELECT ele_handle as element_handle, ele_id as element_id, ele_display FROM " . $this->db->prefix('formulize') . " WHERE id_form = " . intval($formId) . " ORDER BY ele_order";
				if($elementsResult = $this->db->query($sql)) {
					while($elementRow = $this->db->fetchArray($elementsResult)) {
						if($elementRow['ele_display'] == 1
							OR in_array(XOOPS_GROUP_ADMIN, $this->userGroups)
							OR (
								strstr($elementRow['ele_display'], ",")
								AND array_intersect($this->userGroups, explode(",", $elementRow['ele_display']))
							)) {
								$row['elements'][] = $elementRow;
						}
					}
				}
				$row['element_count'] = count($row['elements']);
				$formTitle = trans($row['form_title']);
				$row['form_title'] = $formTitle; // Use the translated title for display
				$forms[] = $row + $this->all_form_connections($formId) + $this->screens_list($formId, simple: true);
				$formTitles[] = $formTitle;
			}
		}

		array_multisort($formTitles, SORT_NATURAL, $forms);

		return [
			'forms' => $forms,
			'form_count' => count($forms)
		];
	}

	/**
	 * List all the applications - tool version of the resource
	 */
	private function list_applications() {
		return $this->applications_list();
	}

	/**
	 * List all the users - tool version of the resource
	 */
	private function list_users() {
		return $this->users_list();
	}

	/**
	 * List all connections for a form. Tool level access for the connections list, since not all MCP clients can read resources. Duh.
	 * @return array An associative array containing the connections for the form
	 */
	private function list_connections() {
		return $this->all_form_connections();
	}

	/**
	 * List the screens - tool version of the resource
	 */
	private function list_screens() {
		return $this->screens_list();
	}

	/**
	 * Get form details
	 */
	private function get_form_details($arguments)
	{
		$formId = $arguments['form_id'];
		return $formId ? $this->form_schemas($formId) : [];
	}

	/**
	 * Get the details about a single screen
	 */
	private function get_screen_details($arguments) {
		$screen_id = $arguments['screen_id'];
		$screens_list = $this->screens_list(screenId: $screen_id);
		return $screens_list['screens'][0];
	}

	/**
	 * Create a new entry in a Formulize form
	 * @param array $arguments An associative array containing the parameters for creating the entry.
	 * - 'form_id': The ID of the form to create an entry in.
	 * - 'data': An associative array of key-value pairs where keys are element handles and values are the data to store.
	 * - 'relationship_id': Optional. The ID of the relationship to use for derived value calculations. Defaults to -1 for the Primary Relationship which includes all connected forms.
	 * @return array An associative array with the result of the create operation, including success status, form ID, entry ID, action performed, and any additional information such as new entry ID if created.
	 */
	private function create_entry($arguments) {
		return $this->writeFormEntry(intval($arguments['form_id']), 'new', $arguments['data'] ?? [], intval($arguments['relationship_id'] ?? -1));
	}

	/**
	 * Update an entry in a Formulize form
	 * @param array $arguments An associative array containing the parameters for creating the entry.
	 * - 'form_id': The ID of the form to create an entry in.
	 * - 'entry_id': The ID of the entry to update.
	 * - 'data': An associative array of key-value pairs where keys are element handles and values are the data to store.
	 * - 'relationship_id': Optional. The ID of the relationship to use for derived value calculations. Defaults to -1 for the Primary Relationship which includes all connected forms.
	 * @return array An associative array with the result of the create operation, including success status, form ID, entry ID, action performed, and any additional information such as new entry ID if created.
	 */
	private function update_entry($arguments) {
		return $this->writeFormEntry(intval($arguments['form_id']), $arguments['entry_id'], $arguments['data'] ?? [], intval($arguments['relationship_id'] ?? -1));
	}

	/**
	 * Write entry data to a form (used by both create and update tools)
	 * The form id is not actually required in the underlying formulize_writeEntry function, because the element references are globally unique and the form can be derived from them.
	 * However, this method still validates that the form exists and that the elements are part of the form, which is useful since the AI assistant might have hallucinated elements!
	 * @param int $formId The ID of the form to write the entry to
	 * @param int|string $entryId The ID of the entry to update, or 'new' to create a new entry
	 * @param array $data The data to write, where keys are element handles and values are the data to store.
	 * @param int $relationshipId The ID of the relationship to use for derived value calculations. Defaults to -1 for the Primary Relationship, which includes all connected forms.
	 * @return array An associative array with the result of the write operation, including success status, form ID, entry ID, action performed (created or updated), and any additional information such as new entry ID if created.
	 * @throws Exception If there is an error during the write operation, such as permission issues, form not found, invalid element handles, or failure to prepare data for storage.
	 */
	private function writeFormEntry($formId, $entryId, $data, $relationshipId = -1)
	{

		$resultEntryId = null;
		try {
			// Step 1: Check permissions
			if (!formulizePermHandler::user_can_edit_entry($formId, $this->authenticatedUid, $entryId)) {
				$this->sendAuthError('Permission denied: cannot update entry '. $entryId . ' in form ' . $formId, 403);
			}

			// Validate form exists
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
			// a null result means nothing was written, likely because the submitted data was not different from what's in the database already
			$resultEntryId = formulize_writeEntry($preparedData, $entryId);

			// For new entries, the function returns the new entry ID
			// For updates, it returns the existing entry ID
			$finalEntryId = ($entryId === 'new') ? $resultEntryId : $entryId;

			// Step 4: Update derived values
			formulize_updateDerivedValues($finalEntryId, $formId, $relationshipId);

			$response = [
				'success' => true,
				'form_id' => $formId,
				'entry_id' => $finalEntryId,
				'action' => $resultEntryId === null ? 'No data was written (submitted values may be the same as current values in the database)' : ($entryId === 'new' ? 'created' : 'updated'),
				'elements_written' => $resultEntryId === null ? 0 : array_keys($preparedData),
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
				'entry_id' => $entryId === 'new' ? $resultEntryId : $entryId
			];
		}
	}

	/**
	 * Read the last 1000 lines of the system activity log
	 * This tool reads the system activity log and returns the last 1000 lines as an array of JSON objects.
	 * Each object contains keys such as microtime, user_id, request_id, session_id, formulize_event, form_id, screen_id, and entry_id.
	 * @param array $arguments An associative array containing optional parameters for filtering the log entries:
	 * - 'form_id': Optional. The ID of the form to filter log entries by
	 * - 'screen_id': Optional. The ID of the screen to filter log entries by
	 * - 'entry_id': Optional. The ID of the entry to filter log entries by. If specified, a form_id must also be provided.
	 * - 'user_id': Optional. The ID of the user to filter log entries by
	 * @return array An array containing each log line as a JSON object with keys such as microtime, user_id, request_id, session_id, formulize_event, form_id, screen_id, and entry_id.
	 */
	private function read_system_activity_log($arguments) {

		$this->verifyUserIsWebmaster(__FUNCTION__); // returns 403 to non webmasters

		$config_handler = xoops_gethandler('config');
		$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
		if($formulizeConfig['formulizeLoggingOnOff'] AND $formulizeLogFileLocation = $formulizeConfig['formulizeLogFileLocation']) {

			$form_id = intval($arguments['form_id'] ?? 0);
			$screen_id = intval($arguments['screen_id'] ?? 0);
			$entry_id = intval($arguments['entry_id'] ?? 0);
			$user_id = isset($arguments['user_id']) ? intval($arguments['user_id']) : null;

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
			// decode them from JSON, so we don't end up double or triple encoding later, since this has a few more hoops to go through!
			return array_map('json_decode', array_slice($lines, -$lineCount));

    } else {
			return ['message' => 'Logging is disabled on this Formulize system.' ];
		}
	}

	/**
	 * Query the database directly
	 */
	private function query_the_database_directly($arguments) {
		$sql = trim($arguments['sql'] ?? '');
		try {
			// Sanitize the SQL
			$safeSql = $this->sanitizeFormulizeSQL($sql, ['SELECT', 'SHOW', 'DESCRIBE']);
			if(!$res = $this->db->query($safeSql)) {
				throw new Exception('SQL query failed: ' . $this->db->error());
			}

			$results = [];
			while($row = $this->db->fetchArray($res)) {
				$results[] = $row;
			}
			return [
				'sql' => $safeSql,
				'query_results' => $results,
				'number_of_records_returned' => count($results)
			];
    } catch (Exception $e) {
        throw new Exception('SQL execution failed: ' . $e->getMessage());
    }
	}

	private function sanitizeFormulizeSQL($sql, $allowedOperations = ['SELECT', 'SHOW', 'DESCRIBE']) {
		// Remove multiple statements
		$sql = $this->sanitizeToFirstStatement($sql);

		// Validate operation type
		$sql = trim($sql);
		$operation = strtoupper(strtok($sql, ' '));

		if (!in_array($operation, $allowedOperations)) {
			throw new Exception("Operation '$operation' not allowed");
		}

		// Blacklist approach: Block specific dangerous functions
    $dangerousFunctions = [
        // File operations
        'LOAD_FILE', 'LOAD_DATA', 'INTO OUTFILE', 'INTO DUMPFILE',

        // System functions
        'SYSTEM', 'SHELL', 'EXEC', 'EXECUTE',

        // User-defined functions (common dangerous ones)
        'UDF_EXEC', 'LIB_MYSQLUDF_SYS_EXEC',

        // Information gathering
        'USER', 'CURRENT_USER', 'SESSION_USER', 'SYSTEM_USER',
        'CONNECTION_ID', 'VERSION',

        // Custom dangerous functions (add your own)
        'DROP_ALL_TABLES', 'DELETE_ALL_DATA', // example dangerous UDFs
    ];

    // Check for dangerous function patterns
    foreach ($dangerousFunctions as $func) {
        if (preg_match('/\b' . preg_quote($func, '/') . '\s*\(/i', $sql)) {
            throw new Exception("Dangerous function '$func' not allowed");
        }
    }

    // Block dangerous SQL patterns
    $dangerousPatterns = [
        '/\bINTO\s+(OUTFILE|DUMPFILE)\b/i',
        '/\bLOAD\s+DATA\b/i',
        '/\b(CREATE|DROP|ALTER)\s+(FUNCTION|PROCEDURE|TRIGGER)\b/i',
        '/\bCALL\s+/i',
        '/\bEXECUTE\s+/i',
    ];

    foreach ($dangerousPatterns as $pattern) {
        if (preg_match($pattern, $sql)) {
            throw new Exception('SQL contains dangerous patterns');
        }
    }

		// Additional Formulize-specific validations
		if ($operation === 'SELECT') {
			// Ensure it includes the XOOPS prefix for Formulize tables
			if (preg_match('/\bformulize(_\w+)?\b/i', $sql) &&
				!preg_match('/\b' . preg_quote(XOOPS_DB_PREFIX) . '_formulize/i', $sql)) {
				throw new Exception('Formulize table queries must use proper prefix');
			}
		}

		return $sql;
	}

	private function sanitizeToFirstStatement($sql) {
			$sql = trim($sql);
			if (empty($sql)) {
					return '';
			}

			// Remove comments first
			$sql = preg_replace('/--.*$/m', '', $sql); // Remove line comments
			$sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove block comments

			// Find the first semicolon not inside quotes
			$inSingleQuote = false;
			$inDoubleQuote = false;
			$escaped = false;

			for ($i = 0; $i < strlen($sql); $i++) {
					$char = $sql[$i];

					if ($escaped) {
							$escaped = false;
							continue;
					}

					if ($char === '\\') {
							$escaped = true;
							continue;
					}

					if ($char === "'" && !$inDoubleQuote) {
							$inSingleQuote = !$inSingleQuote;
					} elseif ($char === '"' && !$inSingleQuote) {
							$inDoubleQuote = !$inDoubleQuote;
					} elseif ($char === ';' && !$inSingleQuote && !$inDoubleQuote) {
							// Found unquoted semicolon - truncate here
							return trim(substr($sql, 0, $i));
					}
			}

			// No semicolon found, return the whole string
			return trim($sql);
	}

}
