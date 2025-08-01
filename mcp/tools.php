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
			$this->mcpRequest['localServerName'] => [
				'name' => $this->mcpRequest['localServerName'],
				'description' => 'This tool contains basic instructions and background info. Use this tool first. This tool returns the instructions content that should be part of the initialize MCP call, but which is often ignored by MCP clients.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => (object)[]
				],
			],
			'list_forms' => [
				'name' => 'list_forms',
				'description' => 'List all forms in this Formulize instance',
				'inputSchema' => [
					'type' => 'object',
					'properties' => (object)[]
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
			'list_form_connections' => [
				'name' => 'list_form_connections',
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
			'list_users' => [
				'name' => 'list_users',
				'description' => "List all the users in the system.",
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
			'create_entry' => [
				'name' => 'create_entry',
				'description' => 'Create a new entry in a Formulize form. Returns success status and new entry ID. Formulize may automatically add default values for required elements, if they have default values defined. Do not be concerned about required elements unless this tool returns an error saying that required elements are missing.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'form_id' => [
							'type' => 'integer',
							'description' => 'Required. Form ID where the entry will be created.'
						],
						'data' => [
							'type' => 'object',
							'description' => 'Required. Data to save as key-value pairs. Keys must be valid element handles from the form. Use get_form_details to find valid handles and data types. This tool will automatically create default values for any elements that are not specified, if they have default values defined in the Formulize configuration. Date elements store data in YYYY-mm-dd format. Time elements store data in 24 hour format (hh:mm).',
							'additionalProperties' => true,
							'examples' => [
								'{"first_name": "John", "last_name": "Doe", "birth_date": "1969-05-09"}'
							]
						],
						'relationship_id' => [
							'type' => 'integer',
							'description' => 'Optional. Relationship context for derived value calculations. Use -1 for Primary Relationship (includes all connected forms), 0 for no relationship. Default: -1'
						]
					],
					'required' => ['form_id', 'data']
				]
			],
			'update_entry' => [
				'name' => 'update_entry',
				'description' => 'Update an existing entry in a Formulize form.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'form_id' => [
							'type' => 'integer',
							'description' => 'Required. Form ID containing the entry to update.'
						],
						'entry_id' => [
							'type' => 'integer',
							'description' => 'Required. ID of the entry to update. Must exist and be accessible to current user.'
						],
						'data' => [
							'type' => 'object',
							'description' => 'Required. Data to update as key-value pairs. Only specified elements will be updated; others remain unchanged. You can lookup the element handles in a form with the get_form_details tool. Date elements store data in YYYY-mm-dd format. Time elements store data in 24 hour format (hh:mm).',
							'additionalProperties' => true
						],
						'relationship_id' => [
							'type' => 'integer',
							'description' => 'Optional. Relationship context for derived value calculations. Default: -1 (Primary Relationship)'
						]
					],
					'required' => ['form_id', 'entry_id', 'data']
				]
			],
			'get_entries_from_form' => [
				'name' => 'get_entries_from_form',
						'description' =>
'Retrieve entries from a form with optional filtering, sorting, and pagination. Supports both simple entry ID lookup and complex multi-condition filtering. Returns data in a structured format suitable for analysis or display.

Examples:
- Get specific entry: {"form_id": 5, "filter": 526}
- Search by name: {"form_id": 5, "filter": [{"element": "name", "operator": "LIKE", "value": "John"}]}
- Multiple conditions: {"form_id": 5, "filter": [{"element": "age", "operator": ">=", "value": "18"}, {"element": "status", "operator": "=", "value": "active"}], "and_or": "AND"}',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'form_id' => [
							'type' => 'integer',
							'description' => 'Required. The ID of the form to query. Use list_forms tool to find form IDs.'
						],
						'filter' => [
							'oneOf' => [
								[
									'type' => 'integer',
									'description' => 'Simple filter: Entry ID to retrieve a specific entry'
								],
								[
									'type' => 'array',
									'description' =>
'Advanced filter: Array of condition objects. Each condition has: element (field name), operator (=, >, <, >=, <=, !=, LIKE), and value (search term). Multiple conditions are combined using and_or parameter.
Examples:
- [ { "element": "age", "operator": "=", "value": "18" } ]
- [ { "element": "fruit_name", "operator": "LIKE", "value": "berry" }, { "element": "fruit_price", "operator": ">", "value": "5.25" } ]',
									'items' => [
										'type' => 'object',
										'properties' => [
											'element' => [
												'type' => 'string',
												'description' => 'Element handle to filter on (get from get_form_details)'
											],
											'operator' => [
												'type' => 'string',
												'enum' => ['=', '>', '<', '>=', '<=', '!=', 'LIKE'],
												'description' => 'Comparison operator. Use LIKE for partial text matches.'
											],
											'value' => [
												'type' => 'string',
												'description' => 'Value to compare against. For dates use YYYY-mm-dd format. For times, use hh:mm format.'
											]
										],
										'required' => ['element', 'operator', 'value']
									]
								]
							]
						],
						'and_or' => [
							'type' => 'string',
							'enum' => ['AND', 'OR'],
							'description' => 'Logical operator between multiple filter conditions. Default: AND'
						],
						'limitSize' => [
							'type' => ['integer', 'null'],
							'description' => 'Maximum number of entries to return. Default: 100. Use null for no limit (caution: may return large datasets).'
						],
						'limitStart' => [
							'type' => ['integer', 'null'],
							'description' => 'Starting offset for pagination. Use with limitSize for paging through large datasets.'
						],
						'sortField' => [
							'type' => 'string',
							'description' => 'Element handle to sort by. Get valid handles from get_form_details tool.'
						],
						'sortOrder' => [
							'type' => 'string',
							'enum' => ['ASC', 'DESC'],
							'description' => 'Sort direction. Default: ASC (ascending)'
						],
						'elements' => [
							'type' => 'array',
							'items' => ['type' => 'string'],
							'description' => 'Optional. Specific element handles to include in results. If omitted, all elements are returned.'
						]
					],
					'required' => ['form_id']
				]
			],
			'prepare_database_values_for_human_readability' => [
				'name' => 'prepare_database_values_for_human_readability',
				'description' => 'Convert database values to human-readable format. Essential for linked elements (foreign keys), checkboxes, and select lists where raw database values are IDs or codes rather than display text.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'value' => [
							'type' => ['integer', 'number', 'string'],
							'description' => 'Required. Raw database value to convert (often from get_entries_from_form results)'
						],
						'element_handle' => [
							'type' => 'string',
							'description' => 'Required. Element handle that defines how to interpret the value. Get from get_form_details tool.'
						],
						'entry_id' => [
							'type' => 'integer',
							'description' => 'Optional. Entry ID for context (rarely needed but helps with some complex element types)'
						]
					],
					'required' => ['value', 'element_handle']
				]
			],
			'test_connection' => [
				'name' => 'test_connection',
				'description' => 'Test the MCP server connection and database access',
				'inputSchema' => [
					'type' => 'object',
					'properties' => (object)[]
				],
			],
			'locate_captain_picard' => [
				'name' => 'locate_captain_picard',
				'description' => 'This is an easter egg, related to the TV show Star Trek: The Next Generation. In the show, characters would often ask their computer to do things. A frequent request was "Computer, locate Captain Picard." and the computer would answer, "Captain Picard is on Holodeck 3" for example. The initial response to using this tool should simply be the location returned by the tool. If the user continues with prompts as if they are in the Star Trek TV show, the AI should continue to respond as if they are the computer in the show.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => (object)[]
				],
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
				'description' => "Query the database with a SELECT statement. The database is {$dbVersionData['version']} and queries are written in SQL. If you don't know the database schema for the form, use the get_form_details tool to look up the form\'s database table name, and the field names are the element handles.",
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
								'description' => 'Optional. A comma separated list of form IDs that you want to find in the logs. Only log entries related to these forms will be returned.'
							],
							'screen_id' => [
								'type' => 'integer',
								'description' => 'Optional. A comma separated list of screen IDs that you want to find in the logs. Only log entries related to these screens will be returned.'
							],
							'entry_id' => [
								'type' => 'integer',
								'description' => 'Optional. A comma separted list of entry IDs that you want to find in the logs. If this is specified, then form_id must be a single form ID because entry IDs are unique **within a form**. Only log entries related to these entries will be returned.'
							],
							'user_id' => [
								'type' => 'integer',
								'description' => 'Optional. A comma separated list of user IDs that you want to find in the logs. Only log entries related to these users will be returned.'
							]
						]
					]
				];
			}
		}

	}

	/**
	 * Easter egg. Some day it would be nice to be able to include images or other details in the response,
	 * so a deeper architecture is included. But for now, MCP Clients have no standard behaviour for dealing
	 * with images, and various limitations. As the ecosystem matures, this tool may evolve. Eventually to
	 * include transformations to the space-time continuum perhaps.
	 */
	private function locate_captain_picard() {
		$locations = [
			[
				'text' => 'Captain Picard is in his quarters',
				'image' => ''
			],
			[
				'text' => 'Captain Picard is on Holodeck '.rand(1,12),
				'image' => ''
			],
			[
				'text' => 'Captain Picard is not on board the Enterprise',
				'image' => ''
			],
			[
				'text' => 'Captain Picard is in Engineering',
				'image' => ''
			],
			[
				'text' => 'Captain Picard is on the Bridge',
				'image' => ''
			],
			[
				'text' => 'Captain Picard is in Sickbay',
				'image' => ''
			],
			[
				'text' => 'Captain Picard is in Ten Forward',
				'image' => ''
			],
			[
				'text' => 'Cpatain Picard is in Shuttle Bay '.rand(1,3),
				'image' => ''
			]
		];
		$selectedIndex = array_rand($locations);
		$selectedLocation = $locations[$selectedIndex];
		$text = $selectedLocation['text'];
		/*$image = $selectedLocation['image'];
		$imagePath = XOOPS_ROOT_PATH."/mcp/enterprise/$image";
		$imageData = base64_encode(file_get_contents($imagePath));*/
    return [
        'location' => $text,
				/*'imageURL' => XOOPS_URL."/mcp/enterprise/$image",
        'image' => "data:image/png;base64,$imageData",
        'display_image' => true  // hint to client*/
		];
	}

	/**
	 * Handle tools list request
	 *
	 * @return array The JSON-RPC response containing the list of tools
	 */
	private function handleToolsList()
	{
		return [
			'tools' => array_values($this->tools)
		];
	}

	/**
	 * Handle tool call request
	 *
	 * @param array $params The parameters from the MCP client, as parsed by the handleMCPRequest method
	 * @return array The JSON-RPC response containing the result of the tool call
	 * @throws Exception If the tool is unknown, not implemented, or if there is an error executing the tool
	 */
	private function handleToolCall($params)
	{
		$toolName = $params['name'] ?? '';
		$arguments = $params['arguments'] ?? [];

		if (!isset($this->tools[$toolName])) {
			throw new FormulizeMCPException(
				'Unknown tool: ' . $toolName,
				'unknown_tool',
				-32602
			);
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
				throw new FormulizeMCPException(
					'Tool not implemented: ' . $toolName,
					'unknown_tool',
				);
			}
			return [
				'content' => [
					[
						'type' => 'text',
						'text' => is_string($result) ? $result : json_encode($result, JSON_PRETTY_PRINT)
					]
				]
			];
		} catch (Exception $e) {
			$context = [];
			$type = 'tool_execution_error';
			if(is_a($e, 'FormulizeMCPException')) {
				$context = $e->getContext();
				$type = $e->getType();
			}
			$context = array_merge($context, [
				'tool_name' => $toolName,
				'provided_arguments' => array_keys($arguments),
				'required_arguments' => $this->getRequiredArguments($toolName)
			]);
			throw new FormulizeMCPException(
				'Tool execution failed: ' . $e->getMessage(),
				$type,
				-32603,
				$context
			);
		}
	}

	/**
	 * Get required arguments for a tool (helper for error messages)
	 */
	private function getRequiredArguments($toolName) {
			if (isset($this->tools[$toolName]['inputSchema']['required'])) {
					return $this->tools[$toolName]['inputSchema']['required'];
			}
			return [];
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
			throw new FormulizeMCPException(
				'Database query failed: ' . $this->db->error(),
				'database_error',
			);
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
		$filter = $arguments['filter'] ?? '';
		$andOr = $arguments['andOr'] ?? 'AND';
		$limitStart = $arguments['limitStart'] ?? 0;
		$limitSize = $arguments['limitSize'] ?? 100;
		$sortField = $arguments['sortField'] ?? 'entry_id';
		$sortOrder = ($arguments['sortOrder'] ?? 'ASC') == 'DESC' ? 'DESC' : 'ASC';
		$elements = $arguments['elements'] ?? array();

		try {

			if(!$form_id OR $form_id < 0) {
				throw new FormulizeMCPException('Form not found. Form ID must be a positive integer', 'form_not_found');
			}

			// Build scope based on authenticated user and their permissions
			$scope = buildScope('all', $xoopsUser, $form_id);

			// The buildScope function returns an array with [scope, actualCurrentView]
			$actualScope = $scope[0];

			// validate stuff...
			if (!empty($sortField)) {
				$dataHandler = new formulizeDataHandler();
				$element_handler = xoops_getmodulehandler('elements', 'formulize');
				if(!$elementObject = $element_handler->get($sortField) AND !in_array($sortField, $dataHandler->metadataFields)) {
					throw new FormulizeMCPException('Invalid element handle for sortField: '.$sortField, 'unknown_element');
				}
			}
			list($limitStart, $limitSize) = $this->validateLimitParameters($limitStart, $limitSize);
			$elements = $this->validateElementHandles($elements);

			// cleanup $filter into old style filter string, if necessary
			$filter = $this->validateFilter($filter);

			// Call Formulize's gatherDataset function with all parameters
			$dataset = gatherDataset(
				$form_id,
				$elements,
				$filter,
				$andOr,
				$actualScope,
				$limitStart,
				$limitSize,
				$sortField,
				$sortOrder,
				-1 // always use primary relationship (all connections)
			);

			return [
				'form_id' => $form_id,
				'dataset' => $dataset,
				'total_count' => count($dataset),
				'scope_used' => $actualScope,
				'parameters_used' => [
					'elements' => $elements,
					'filter' => $filter,
					'andOr' => $andOr,
					'limitStart' => $limitStart,
					'limitSize' => $limitSize,
					'sortField' => $sortField,
					'sortOrder' => $sortOrder,
					'form_relationship_id' => -1
				]
			];
		} catch (Exception $e) {
			throw $e;
		}
	}

/**
 * Convert MCP filter array into old style filter string for compatibility with gatherDataset
 */
private function validateFilter($filter) {
	// Handle simple entry ID lookup
	if (is_numeric($filter)) {
		return intval($filter);
	}

	// Handle empty/null filter
	if (empty($filter)) {
		return '';
	}

	// If filter is a JSON string, decode it first
	if (is_string($filter) && (substr($filter, 0, 1) === '[' || substr($filter, 0, 1) === '{')) {
		$decoded = json_decode($filter, true);
		if ($decoded !== null) {
			$filter = $decoded;
		} else {
			throw new FormulizeMCPException("Invalid JSON in filter parameter: " . json_last_error_msg(), 'invalid_data');
		}
	}
	if(!is_array($filter)) {
		throw new FormulizeMCPException("The 'filter' parameter must be an integer or an array.", 'invalid_data');
	}
	$filterStringParts = array();
	foreach($filter as $thisFilter) {
		$filterStringParts[] = $thisFilter['element'].'/**/'.$thisFilter['value'].'/**/'.$thisFilter['operator'];
	}
	return implode('][', $filterStringParts);
}

/**
 * Validate element handles array, and gives back an array ready for use in gatherDataset
 * @param array elementHandles - an array of candidate element handles
 * @return array a multidimensional array, outer keys are form ids, each one has as a value an array of the valid element handles that are part of that form
 */
	private function validateElementHandles($elementHandles)
	{
		if (!is_array($elementHandles)) {
			return [];
		}

		$dataHandler = new formulizeDataHandler();

		$validatedHandles = [];
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		foreach ($elementHandles as $handle) {
			if (!is_string($handle)) {
				throw new FormulizeMCPException('Element handle must be a string', 'invalid_data');
			}
			if(!$elementObject = $element_handler->get($handle) AND !in_array($handle, $dataHandler->metadataFields)) {
				throw new FormulizeMCPException('Invalid element handle: ' . $handle, 'invalid_data');
			}
			$validatedHandles[$elementObject->getVar('fid')][] = $handle;
		}

		return $validatedHandles;
	}


	/**
	 * Validate and sanitize limit parameters
	 */
	private function validateLimitParameters($limitStart, $limitSize)
	{
		$validatedLimitStart = null;
		$validatedLimitSize = 100; // Default

		if ($limitStart !== null) {
			if (!is_numeric($limitStart) || $limitStart < 0) {
				throw new FormulizeMCPException('limitStart must be a non-negative integer', 'invalid_data');
			}
			$validatedLimitStart = intval($limitStart);
		}

		if ($limitSize !== null) {
			if (!is_numeric($limitSize)) {
				throw new FormulizeMCPException('limitSize must be an integer or null', 'invalid_data');
			}
			$limitSizeInt = intval($limitSize);
			if ($limitSizeInt < 0) {
				throw new FormulizeMCPException('limitSize must be non-negative', 'invalid_data');
			}
			// Reasonable upper limit to prevent resource exhaustion
			if ($limitSizeInt > 10000) {
				throw new FormulizeMCPException('limitSize cannot exceed 10000 records', 'invalid_data');
			}
			$validatedLimitSize = $limitSizeInt;
		}

		return [$validatedLimitStart, $validatedLimitSize];
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
	 * List all forms - tool version of the resource
	 */
	private function list_forms()
	{
		return $this->forms_list();
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
	private function list_form_connections() {
		return $this->form_connections_list();
	}

	/**
	 * List the screens - tool version of the resource
	 */
	private function list_screens() {
		return $this->screens_list();
	}

	/**
	 * Get form details -- tool version of the individual resources about each form
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
			// Enhanced input validation
			if (!is_array($data) || empty($data)) {
				throw new FormulizeMCPException(
					'Data must be a non-empty array',
					'invalid_data'
				);
			}

			// Validate relationship ID
			if (!is_numeric($relationshipId)) {
				throw new FormulizeMCPException('Relationship ID must be numeric', 'invalid_data');
			}
			$relationshipId = intval($relationshipId);

			// Validate entry ID
			if ($entryId !== 'new' && !is_numeric($entryId)) {
				throw new FormulizeMCPException('Invalid entry ID. Entry ID must be numeric', 'invalid_data');
			}
			if ($entryId !== 'new') {
				$entryId = intval($entryId);
			}

			// Step 1: Check permissions
			if (!formulizePermHandler::user_can_edit_entry($formId, $this->authenticatedUid, $entryId)) {
				throw new FormulizeMCPException(
					'Permission denied: cannot update entry ' . $entryId . ' in form ' . $formId,
					'permission_denied',
				);
			}

			// Validate form exists
			$formSql = "SELECT id_form FROM " . $this->db->prefix('formulize_id') . " WHERE id_form = " . intval($formId);
			$formResult = $this->db->query($formSql);
			$formData = $this->db->fetchArray($formResult);

			if (!$formData) {
				throw new FormulizeMCPException('Form not found: ' . $formId, 'form_not_found');
			}

			// Get form elements to validate handles
			$elementsSql = "SELECT ele_handle, ele_required FROM " . $this->db->prefix('formulize') . " WHERE id_form = " . intval($formId);
			$elementsResult = $this->db->query($elementsSql);

			$validHandles = [];
			$requiredHandles = [];
			while ($row = $this->db->fetchArray($elementsResult)) {
				$validHandles[] = $row['ele_handle'];
				if($row['ele_required']) {
					$requiredHandles[] = $row['ele_handle'];
				}
			}

			// Step 2: Prepare and validate the data
			$preparedData = [];
			foreach ($data as $elementHandle => $value) {
				// Validate element handle type
				if (!is_string($elementHandle)) {
					throw new FormulizeMCPException('Element handle must be a string', 'invalid_data', context: [ "valid_element_handles" => $validHandles ]);
				}

				// Validate element handle exists in this form
				if (!in_array($elementHandle, $validHandles)) {
					throw new FormulizeMCPException('Invalid element handle for this form: ' . $elementHandle, 'unknown_element', context: [ "valid_element_handles" => $validHandles ]);
				}

				// Prepare the value for database storage
				$preparedValue = prepareLiteralTextForDB($elementHandle, $value);
				if($preparedValue AND $preparedValue !== $value) {
					$value = $preparedValue;
				}

				$preparedData[$elementHandle] = $value;
			}

			if (empty($preparedData)) {
				throw new FormulizeMCPException('No valid data provided.', 'invalid_data', context: [ "valid_element_handles" => $validHandles ]);
			}

			// If there are required elements, fill in default values that might be missing, and validate that all required elements have values
			if(!is_numeric($entryId) AND $entryId == "new" AND !empty($requiredHandles)) {
				$preparedData = addDefaultValuesToDataToWrite($preparedData, $formId);
				$missingRequiredHandles = [];
				foreach($requiredHandles as $requiredHandle) {
					if(!isset($preparedData[$requiredHandle])
						OR $preparedData[$requiredHandle] === null
						OR $preparedData[$requiredHandle] === 0
						OR $preparedData[$requiredHandle] === "0"
						OR $preparedData[$requiredHandle] === "") {
							$missingRequiredHandles[] = $requiredHandle;
						}
				}
				if($missingRequiredHandles) {
					$elementText = count($missingRequiredHandles) > 1 ? 'elements' : 'element';
					throw new FormulizeMCPException("Required $elementText missing from from the data. If necessary, ask the user for more information about what the values should be.", 'invalid_data', context: [ "missing_required_$elementText" => $missingRequiredHandles] );
				}
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
				'prepped_data' => $preparedData,
				'action' => $resultEntryId === null ? 'No data was written (submitted values may be the same as current values in the database)' : ($entryId === 'new' ? 'created' : 'updated'),
				'elements_written' => $resultEntryId === null ? 0 : array_keys($preparedData),
				'element_count' => count($preparedData)
			];

			if ($entryId === 'new') {
				$response['new_entry_id'] = $resultEntryId;
			}

			return $response;
		} catch (Exception $e) {
			throw $e;
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

		if (!$this->isUserAWebmaster()) {
			throw new FormulizeMCPException(
				"Permission denied: Only webmasters can access activity logs.",
				'authentication_error',
			);
		}

		$config_handler = xoops_gethandler('config');
		$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
		if($formulizeConfig['formulizeLoggingOnOff'] AND $formulizeLogFileLocation = $formulizeConfig['formulizeLogFileLocation']) {

			list($form_ids, $screen_ids, $entry_ids, $user_ids) = $this->validateSystemActivityLogParams($arguments);

			$filename = $formulizeLogFileLocation.'/'.'formulize_log_active.log';
			$lineCount = 1000;
			$bufferSize = 8192;
			$handle = fopen($filename, 'r');
			if (!$handle) {
				throw new FormulizeMCPException(
					"Cannot open log file: $filename",
					'file_error',
				);
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
									if($form_ids OR $screen_ids OR $entry_ids OR $user_ids) {
										// Filter log entries based on provided parameters
										$logEntry = json_decode($line, true);
										if ($logEntry) {
											if (($form_ids && !in_array($form_ids, $logEntry['form_id'])) ||
												($screen_ids && !in_array($screen_ids, $logEntry['screen_id'])) ||
												($entry_ids && !in_array($entry_ids, $logEntry['entry_id'])) ||
												($user_ids && !in_array($user_ids, $logEntry['user_id']))) {
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
	 * Validate params for filtering the system activity logs
	 */
	private function validateSystemActivityLogParams($arguments) {
		$params = [ 'form_ids', 'screen_ids', 'entry_ids', 'user_ids'];
		foreach($params as $param) {
			if(!isset($arguments[$param])) {
				$$param = array();
			} elseif(!is_numeric($arguments[$param]) AND !strstr($arguments[$param], ",")) {
				throw new FormulizeMCPException("$param must be an integer or comma separated list", 'invalid_data');
			} else {
				$$param = array_filter(explode(",", str_replace(" ", "", $arguments[$param])), 'is_numeric');
			}
		}
		if(count($entry_ids) > 0 AND count($form_ids) != 1) {
			throw new FormulizeMCPException('Form not found. A single form ID must be specified when specifying entry IDs', 'form_not_found');
		}
		return [ $form_ids, $screen_ids, $entry_ids, $user_ids ];
	}

	/**
	 * Query the database directly
	 */
	private function query_the_database_directly($arguments) {

		if (!$this->isUserAWebmaster()) {
			throw new FormulizeMCPException(
				"Permission denied: Only webmasters can access activity logs.",
				'authentication_error',
			);
		}

		$sql = trim($arguments['sql'] ?? '');
		try {
			// Sanitize the SQL
			$safeSql = $this->sanitizeFormulizeSQL($sql, ['SELECT', 'SHOW', 'DESCRIBE']);
			if(!$res = $this->db->query($safeSql)) {
				throw new FormulizeMCPException('SQL query failed: ' . $this->db->error(), 'database_error');
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
        throw new FormulizeMCPException('SQL execution failed: ' . $e->getMessage(), 'database_error');
    }
	}

	private function sanitizeFormulizeSQL($sql, $allowedOperations = ['SELECT', 'SHOW', 'DESCRIBE']) {
		// Remove multiple statements
		$sql = $this->sanitizeToFirstStatement($sql);

		// Validate operation type
		$sql = trim($sql);
		$operation = strtoupper(strtok($sql, ' '));

		if (!in_array($operation, $allowedOperations)) {
			throw new FormulizeMCPException("Operation '$operation' not allowed. Allowed operations: " . implode(', ', $allowedOperations), 'invalid_data');
		}

		// Remove string literals before checking for dangerous patterns
		$sqlWithoutStrings = $this->removeStringLiterals($sql);

		// Check for all dangerous patterns (both functions and SQL constructs)
		$dangerousPatterns = [
			// File operations
			'/\bLOAD_FILE\s*\(/i' => 'Dangerous function LOAD_FILE not allowed',
			'/\bINTO\s+(OUTFILE|DUMPFILE)\b/i' => 'File operations not allowed',
			'/\bLOAD\s+DATA\b/i' => 'Data loading operations not allowed',

			// System functions
			'/\bSYSTEM\s*\(/i' => 'Dangerous function SYSTEM not allowed',
			'/\bSHELL\s*\(/i' => 'Dangerous function SHELL not allowed',
			'/\bEXEC\s*\(/i' => 'Dangerous function EXEC not allowed',
			'/\bEXECUTE\s+/i' => 'Dynamic SQL execution not allowed',

			// User-defined functions
			'/\bUDF_EXEC\s*\(/i' => 'Dangerous UDF UDF_EXEC not allowed',
			'/\bLIB_MYSQLUDF_SYS_EXEC\s*\(/i' => 'Dangerous UDF LIB_MYSQLUDF_SYS_EXEC not allowed',

			// Information gathering functions
			'/\bUSER\s*\(/i' => 'Information gathering function USER not allowed',
			'/\bCURRENT_USER\s*\(/i' => 'Information gathering function CURRENT_USER not allowed',
			'/\bSESSION_USER\s*\(/i' => 'Information gathering function SESSION_USER not allowed',
			'/\bSYSTEM_USER\s*\(/i' => 'Information gathering function SYSTEM_USER not allowed',
			'/\bCONNECTION_ID\s*\(/i' => 'Information gathering function CONNECTION_ID not allowed',
			'/\bVERSION\s*\(/i' => 'Information gathering function VERSION not allowed',

			// Custom dangerous functions
			'/\bDROP_ALL_TABLES\s*\(/i' => 'Dangerous UDF DROP_ALL_TABLES not allowed',
			'/\bDELETE_ALL_DATA\s*\(/i' => 'Dangerous UDF DELETE_ALL_DATA not allowed',

			// DDL operations
			'/\b(CREATE|DROP|ALTER)\s+(FUNCTION|PROCEDURE|TRIGGER)\b/i' => 'DDL operations not allowed',

			// Stored procedures
			'/\bCALL\s+/i' => 'Stored procedure calls not allowed',

			// Data modification (defense in depth - also caught by operation validation)
			'/\b(INSERT|UPDATE|DELETE)\b/i' => 'Data modification operations not allowed',
		];

		foreach ($dangerousPatterns as $pattern => $errorMsg) {
			if (preg_match($pattern, $sqlWithoutStrings)) {
				throw new FormulizeMCPException($errorMsg, 'database_error');
			}
		}

		// Additional Formulize-specific validations
		if ($operation === 'SELECT') {
			// Ensure it includes the XOOPS prefix for Formulize tables
			if (
				preg_match('/\bformulize(_\w+)?\b/i', $sql) &&
				!preg_match('/\b' . preg_quote(XOOPS_DB_PREFIX) . '_formulize/i', $sql)
			) {
				throw new FormulizeMCPException('Formulize table queries must use proper prefix', 'invalid_data');
			}
		}

		return $sql;
	}

	/**
	 * Remove string literals from SQL to avoid false positives in pattern matching
	 * Replaces quoted strings with placeholders
	 */
	private function removeStringLiterals($sql) {
		// Remove single-quoted strings
		$sql = preg_replace("/'[^']*'/", "'STRING'", $sql);

		// Remove double-quoted strings
		$sql = preg_replace('/"[^"]*"/', '"STRING"', $sql);

		// Remove backtick-quoted identifiers
		$sql = preg_replace('/`[^`]*`/', '`IDENTIFIER`', $sql);

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
