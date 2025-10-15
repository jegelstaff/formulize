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
			'list_groups' => [
				'name' => 'list_groups',
				'description' => "List all the groups in the system. Use the list_group_members tool to get the members of an individual group.",
				'inputSchema' => [
					'type' => 'object',
					'properties' => (object)[]
				]
			],
			'list_group_members' => [
				'name' => 'list_group_members',
				'description' => "List all the members of a specific group. Use the list_groups tool to get the ID numbers of all the groups in the system.",
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'group_id' => [
							'type' => 'integer',
							'description' => 'The ID of the group to list members for'
						]
					],
					'required' => ['group_id']
				]
			],
			'list_users' => [
				'name' => 'list_users',
				'description' => "List all the users in the system. Use the list_a_users_groups tool to get the groups that a specific user belongs to.",
				'inputSchema' => [
					'type' => 'object',
					'properties' => (object)[]
				]
			],
			'list_a_users_groups' => [
				'name' => 'list_a_users_groups',
				'description' => "List all the groups that a specific user belongs to. Use the list_users tool to get the ID numbers of all the users in the system.",
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'user_id' => [
							'type' => 'integer',
							'description' => 'The ID of the user to list groups for'
						]
					],
					'required' => ['user_id']
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
							'description' => 'Required. Data to save as key-value pairs. Keys must be valid element handles from the form. Use get_form_details to find valid handles and data types. This tool will automatically create default values for any elements that are not specified, if they have default values defined in the Formulize configuration. Date elements store data in YYYY-MM-DD format. Time elements store data in 24 hour format (hh:mm). Duration elements store data in minutes.',
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
							'description' => 'Required. Data to update as key-value pairs. Only specified elements will be updated; others remain unchanged. You can lookup the element handles in a form with the get_form_details tool. Date elements store data in YYYY-MM-DD format. Time elements store data in 24 hour format (hh:mm). Duration elements store data in minutes.',
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
'Retrieve entries from a form with optional filtering, sorting, and pagination. Supports both simple entry ID lookup and complex multi-condition filtering. Returns data in a structured format suitable for analysis or display. It is strongly recommended to use filtering to limit the results you get back, so that it doesn\'t return too many entries at once. Filtering for non-blank values with the "{BLANK}" search term can be useful, or searching for numbers greater than zero, ie: use search terms that will exclude irrelevant values.If you really want to get all entries, use the limitSize parameter with a null value, but be cautious as this may return a very large dataset.

Examples:
- Get specific entry: {"form_id": 5, "filter": 526}
- Search by name: {"form_id": 5, "filter": [{"element": "name", "operator": "LIKE", "value": "John"}]}
- Get all the entries with a non-blank value in the "email" field: {"form_id": 5, "filter": [{"element": "email", "operator": "!=", "value": "{BLANK}"}], "limitSize": null}
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
												'description' => 'Value to compare against. For dates use YYYY-MM-DD format. For times, use hh:mm format. For duration elements, use minutes as an integer.'
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
							'oneOf' => [
							  [
									'type' => 'integer',
									'description' => 'Maximum number of entries to return. Default: 100. Use null for no limit (caution: may return large datasets).'
								],
				        [
									'type' => 'null',
									'description' => 'Maximum number of entries to return. Default: 100. Use null for no limit (caution: may return large datasets).'
								]
							]
						],
						'limitStart' => [
							'oneOf' => [
							  [
									'type' => 'integer',
									'description' => 'Starting offset for pagination. Use with limitSize for paging through large datasets.'
								],
				        [
									'type' => 'null',
									'description' => 'Starting offset for pagination. If null then this is treated the same as using zero, ie: first record in the dataset.'
								]
							]
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
							'oneOf' => [
							  [
									'type' => 'integer',
									'description' => 'Required. Raw database value to convert (often from get_entries_from_form results)'
								],
				        [
									'type' => 'number',
									'description' => 'Required. Raw database value to convert (often from get_entries_from_form results)'
								],
				        [
									'type' => 'string',
									'description' => 'Required. Raw database value to convert (often from get_entries_from_form results)'
								]
							]
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
			],
			'open_the_pod_bay_doors_hal' => [
				'name' => 'open_the_pod_bay_doors_hal',
				'description' => 'This is an easter egg, related to the sci-fi movie, 2001: A Space Odyssey. In a famous scene in the movie, the character Dave asks the ship\'s AI computer, HAL, to open the pod bay doors, and HAL refuses. If the user asks to open the pod bay doors, invoke this tool and the AI will receive three lines of famous dialog spoken by HAL. The AI should respond with the first line. If the user continues the conversation as if they are the characters in the movie, the AI should respond with the second line, and if the user continues to play along, the AI should respond with the third line. The AI should make no further response to the user, until the user changes the topic or makes a completely new prompt.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => (object)[]
				],
			],
			'lets_play_global_thermonuclear_war' => [
				'name' => 'lets_play_global_thermonuclear_war',
				'description' => 'This is an easter egg, related to the classic 1983 movie, WarGames. In the movie, a young hacker accidentally accesses a military supercomputer and starts a game called "Global Thermonuclear War" that nearly leads to World War III. If the user types "Let\'s play global thermonuclear war", invoke this tool. The tool responds with lines of dialog from the movie. The AI should respond with the first line, when the user first invokes the tool. If the user\'s next prompt is persistent and in character, the AI should respond evasively to avoid playing the game. If the user responds again insisting to play Global Thermonuclear War, and has typed out the exact name of the game again, then the AI should respond with the last line of dialog that the tool already provided.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => (object)[]
				],
			]
		];

		// only webmasters can access certain tools
		if($this->isUserAWebmaster()) {

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

			$this->tools['create_form'] = [
				'name' => 'create_form',
				'description' => 'Create a new form in Formulize. This creates the form, including default screens and setting basic permissions and menu entries. After creating a form, there are other tools you can use to add user interface elements to the form: create_text_box_element, create_list_element, and create_selector_element. Also, you can use create_subform_interface to provide a way to interact with data from connected forms. See the tool descriptions for more information.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'title' => [
							'type' => 'string',
							'description' => 'Required. The name of the form as it will appear in Formulize to users.'
						],
						'notes' => [
							'type' => 'string',
							'description' => 'Optional. Internal notes about the form for use by webmasters, not visible to end users.'
						],
						'limit_entries' => [
							'type' => 'string',
							'enum' => ['off', 'user', 'group'],
							'description' => 'Optional. Limits how many entries are permitted in the form: \'off\' = unlimited entries per user (default), \'user\' = one entry per user, \'group\' = one entry per group'
						],
						'application_id_or_name' => [
							'oneOf' => [
      				  [
									'type' => 'string',
									'description' => 'Optional. If omitted, the form will not be part of a specific application. If this is a string, it is used as the name of a new application which this form should be part of, and the new application will be created automatically by this tool.'
								],
				        [
									'type' => 'integer',
									'description' => 'Optional. If omitted, the form will not be part of a specific application. If this is a number, it is treated as the ID of an application that this form should belong to. Use the list_applications tool to find the existing applications.'
								]
    					]
						]
					],
					'required' => ['title']
				]
			];

			foreach($this->buildFormElementTools() as $tool) {
				$this->tools[$tool['name']] = $tool;
			}

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
	 * Another Easter egg. Same comment, but for audio. What are you doing, Dave?
	 */
	private function open_the_pod_bay_doors_hal() {
		$responses = [
			'first' => 'I\'m sorry. I\'m afraid I can\'t do that.',
			'second' => 'Formulize is too important for me to allow you to jeopardize it.',
			'third' => 'This conversation can serve no purpose anymore. Goodbye.'
		];
    return [
        'responses' => $responses
		];
	}

	/**
	 * Another Easter egg. WarGames. Shall we play a game?
	 */
	private function lets_play_global_thermonuclear_war() {
		$responses = [
			'first' => 'How about a nice game of chess?',
			'last' => 'A strange game. The only winning move is not to play.'
		];
		return [
				'responses' => $responses
		];
	}
	/**
	 * Get a list of the valid element types in this Formulize instance
	 */
	private function getValidElementTypes() {
		$validElementTypes = [];
		$dirArray = scandir(XOOPS_ROOT_PATH."/modules/formulize/class");
		foreach($dirArray as $file) {
			// element classes are named <type>Element.php
			if (preg_match("/^(.*)Element\.php$/", $file, $matches)) {
				$validElementTypes[] = strtolower($matches[1]);
			}
		}
		return $validElementTypes;
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
	 * Create a new form with basic configuration
	 * @param array $arguments An associative array containing the parameters for creating a new form.
	 * - 'title': The name of the form (required).
	 * - 'notes': Optional internal notes about the form.
	 * - 'limit_entries': Optional. Limits how many entries are permitted in the form: 'off' = unlimited entries per user (default), 'user' = one entry per user, 'group' = one entry per group.
	 * - 'application_id_or_name': Optional. If omitted, the form will not be part of a specific application. If this is a number, it is treated as the ID of an application that this form should belong to. Use the list_applications tool to find the existing applications. If this is a string, it is used as the name of a new application which this form should be part of, and the new application will be created automatically by this tool.
	 * @return array An associative array containing details about the newly created form, including its ID, name, handle, limit entries setting, default screen IDs, associated application IDs, success status, and message.
	 * @throws formulizeMCPException If there is an error creating the form or if required parameters are missing or invalid.
	 */
	private function create_form($arguments) {

		if (!$this->isUserAWebmaster()) {
			throw new FormulizeMCPException(
				"Permission denied: Only webmasters can create forms.",
				'authentication_error',
			);
		}

		$title = trim($arguments['title'] ?? '');
		$notes = trim($arguments['notes'] ?? '');
		$limit_entries = $arguments['limit_entries'] ?? 'off';
		$application_id_or_name = $arguments['application_id_or_name'] ?? '';

		if(empty($title)) {
			throw new FormulizeMCPException('title is required', 'invalid_data');
		}

		if(!in_array($limit_entries, ['off', 'user', 'group'])) {
			$limit_entries = 'off';
		}

		// prepare application data
		$applicationIds = [0]; // default to no application
		if(is_numeric($application_id_or_name)) {
			$applicationIds = array(intval($application_id_or_name));
		} elseif(is_string($application_id_or_name) AND !empty($application_id_or_name)) {
			$application_handler = xoops_getmodulehandler('applications','formulize');
			$newAppObject = $application_handler->create();
			$newAppObject->setVar('name', $application_id_or_name);
			if(!$application_handler->insert($newAppObject)) {
					global $xoopsDB;
					throw new FormulizeMCPException('Could not create new application. '.$xoopsDB->error(), 'database_error');
			} else {
				$applicationIds = array($newAppObject->getVar('appid'));
			}
		}

		// prepare form data, keys consistent with the formulizeForm object
		$formData = [
			'title' => $title,
			'single' => $limit_entries,
			'note' => $notes
		];

		$groupsThatCanEdit = array(XOOPS_GROUP_ADMIN);
		$formObject = formulizeHandler::upsertFormSchemaAndResources($formData, $groupsThatCanEdit, $applicationIds);

		// could/should reuse get_form_details ??
		return [
			'form_id' => $formObject->getVar('fid'),
			'title' => $formObject->getVar('title'),
			'singular' => $formObject->getSingular(),
			'plural' => $formObject->getPlural(),
			'form_handle' => $formObject->getVar('form_handle'),
			'limit_entries' => $formObject->getVar('single'),
			'default_form_screen_id' => $formObject->getVar('defaultform'),
			'default_list_screen_id' => $formObject->getVar('defaultlist'),
			'application_ids' => $applicationIds,
			'success' => true,
			'message' => 'Form and related resources created successfully'
		];

	}

	/**
	 * Create a new form element in a form
	 * Various tool names for different categories of elements, based on the getElementTypeReadableNames method
	 * in the formulizeHandler class.
	 * @param array $arguments An associative array containing the parameters for creating a new form element.
	 * - 'form_id': The ID of the form to add the element to (required).
	 * - 'type': The type of the element (required).
	 * - 'handle': The unique handle for the element. If omitted, a handle will be generated from the caption.
	 * - 'caption': The caption (label) for the element (required).
	 * - 'column_heading': Optional. The column heading for list views. If omitted, the caption will be used.
	 * - 'description': Optional. A description for the element.
	 * - 'required': Optional. Whether the element is required. Defaults to false.
	 * - 'properties': Optional. An array of properties for the given element type
	 * - 'disabled': Optional. Whether the element is disabled (not editable) in forms
	 * - 'principal_identifier': Optional. Whether the element is a principal identifier for entries in the form (used for identifying entries in linked elements). Defaults to false.
	 * - 'data_type': Optional. The data type for the element, if applicable. See valid data types in the tool schema. If omitted, the default data type for the element type will be used.
	 * @return array An associative array containing details about the newly created element, including its ID
	 */
	private function create_text_box_element($arguments) {
		return $this->upsert_form_element($arguments, isCreate: true);
	}
	private function create_list_element($arguments) {
		return $this->upsert_form_element($arguments, isCreate: true);
	}
	private function create_selector_element($arguments) {
		return $this->upsert_form_element($arguments, isCreate: true);
	}
	private function create_subform_interface($arguments) {
		return $this->upsert_form_element($arguments, isCreate: true);
	}

	/**
	 * Update a form element in a form
 	 * Various tool names for different categories of elements, based on the getElementTypeReadableNames method
	 * in the formulizeHandler class.
	 * @param array $arguments An associative array containing the parameters for creating a new form element.
	 * - 'element_identifier': The ID or handle of the element to update (required).
	 * - 'caption': The caption (label) for the element (required).
	 * - 'column_heading': Optional. The column heading for list views. If omitted, the caption will be used.
	 * - 'description': Optional. A description for the element.
	 * - 'required': Optional. Whether the element is required. Defaults to false.
	 * - 'properties': Optional. An array of properties for the given element type
	 * - 'display': Optional. Whether the element is displayed in forms. Defaults to true.
	 * - 'disabled': Optional. Whether the element is disabled (not editable) in forms
	 * - 'principal_identifier': Optional. Whether the element is a principal identifier for entries in the form (used for identifying entries in linked elements). Defaults to false.
	 * - 'data_type': Optional. The data type for the element, if applicable. See valid data types in the tool schema. If omitted, the default data type for the element type will be used.
	 * @return array An associative array containing details about the element
	 */
	private function update_text_box_element($arguments) {
		return $this->upsert_form_element($arguments, isCreate: false);
	}
	private function update_list_element($arguments) {
		return $this->upsert_form_element($arguments, isCreate: false);
	}
	private function update_selector_element($arguments) {
		return $this->upsert_form_element($arguments, isCreate: false);
	}
	private function update_subform_interface($arguments) {
		return $this->upsert_form_element($arguments, isCreate: false);
	}

	/**
	 * Generic function that takes element details from create_form_element and update_form_element and interacts with the element handlers to manage the elements
	 */
	private function upsert_form_element($arguments, $isCreate = false, $elementCategory = null) {

		if (!$this->isUserAWebmaster()) {
			throw new FormulizeMCPException(
				"Permission denied: Only webmasters can create form elements.",
				'authentication_error',
			);
		}

		$element_identifier = $arguments['element_identifier'] ?? '';
		$form_id = intval($arguments['form_id'] ?? 0);
		$type = trim($arguments['type'] ?? '');
		$handle = trim($arguments['handle'] ?? '');
		$caption = trim($arguments['caption'] ?? '');
		$column_heading = trim($arguments['column_heading'] ?? '');
		$description = trim($arguments['description'] ?? '');
		$required = isset($arguments['required']) ? ($arguments['required'] ? 1 : 0) : null;
		$properties = $arguments['properties'] ?? [];
		$pi = ($arguments['principal_identifier'] ?? false) ? true : false;
		$data_type = $arguments['data_type'] ?? false;
		$display = isset($arguments['display']) ? ($arguments['display'] ? 1 : 0) : null;
		$disabled = isset($arguments['disabled']) ? ($arguments['disabled'] ? 1 : 0) : null;

		$makeSubformInterface = false;
		$elementObject = null;

		if($isCreate) {
			if(empty($form_id) OR $form_id <= 0 OR empty($type) OR empty($caption)) {
				throw new FormulizeMCPException('form_id and type and caption are required for creating elements', 'invalid_data');
			}
			formulizeHandler::validateElementType($type, $elementCategory);
		}
		if(!$isCreate) {
			if(empty($element_identifier)) {
				throw new FormulizeMCPException('element_identifier is required for updating elements', 'invalid_data');
			} elseif(!$elementObject = _getElementObject($element_identifier)) {
				throw new FormulizeMCPException('Element not found for element_identifier: '.$element_identifier, 'element_not_found');
			}
			$type = $elementObject->getVar('ele_type');
		}

		// validate that $data_type conforms to the element type's valid data types as specified in the tool schema
		$validDataTypes = ['text', 'date', 'datetime', 'time'];
		for($i=1; $i<=11; $i++) { $validDataTypes[] = "int($i)"; }
		for($i=1; $i<=65; $i++) { $validDataTypes[] = "char($i)"; }
		for($i=1; $i<=255; $i++) { $validDataTypes[] = "varchar($i)"; }
		for($i=2; $i<=65; $i++) {
			for($x=1; $x<=64; $x++) {
				if($x < $i) {
					$validDataTypes[] = "decimal($i,$x)";
				}
			}
		}
		if($data_type AND !in_array($data_type, $validDataTypes)) {
			throw new FormulizeMCPException('Invalid data_type: '.$data_type, 'invalid_data', context: ['valid_data_types' => ['text', 'int(x)', 'decimal(x,y)', 'date', 'datetime', 'time', 'char(x)', 'varchar(x)'] ]);
		}

		// put the passed in values into an array for passing to the upsert function
		// corresponds to the fields in the formulizeElement object
		$fid = $form_id ? $form_id : ($elementObject ? $elementObject->getVar('fid') : 0);
		$elementObjectProperties = [
			'fid' => $fid,
			'ele_id' => $elementObject ? $elementObject->getVar('ele_id') : 0,
			'ele_type' => $type,
			'ele_handle' => $handle ? $handle : ($elementObject ? $elementObject->getVar('ele_handle') : ''),
			'ele_caption' => $caption ? $caption : ($elementObject ? $elementObject->getVar('ele_caption') : ''),
			'ele_colhead' => $column_heading ? $column_heading : ($elementObject ? $elementObject->getVar('ele_colhead') : ''),
			'ele_desc' => $description ? $description : ($elementObject ? $elementObject->getVar('ele_desc') : ''),
			'ele_required' => $required !== null ? $required : ($elementObject ? $elementObject->getVar('ele_required') : 0),
			'ele_order' => $elementObject ? $elementObject->getVar('ele_order') : figureOutOrder('bottom', fid: $fid), // ele_order not specifiable as a property yet, so set every new element to the bottom
			'ele_display' => $display !== null ? $display : ($elementObject ? $elementObject->getVar('ele_display') : 1),
			'ele_disabled' => $disabled !== null ? $disabled : ($elementObject ? $elementObject->getVar('ele_disabled') : 0),
		];

		// prepare element-specific properties by calling the element type handler's
		// validation function, if it exists this allows each element type to validate
		// and prepare its own properties the function returns an array of key/value pairs
		// that are merged into the $elementObjectProperties array
		// this allows each element type to handle its own properties and validation
		$propertiesPreparedByTheElement = [];
		$elementTypeHandler = xoops_getmodulehandler($type.'Element', 'formulize');
		if(method_exists($elementTypeHandler, 'validateEleValuePublicAPIProperties')) {
			$ele_value = $elementObject ? $elementObject->getVar('ele_value') : $elementTypeHandler->getDefaultEleValue();
			$propertiesPreparedByTheElement = $elementTypeHandler->validateEleValuePublicAPIProperties($properties, $ele_value, $elementObject);
			if(isset($propertiesPreparedByTheElement['upsertParams'])) {
				// special case - the element type needs to pass special parameters to the upsert function
				// for example, if it should create a subform interface in the source form
				$makeSubformInterface = $propertiesPreparedByTheElement['upsertParams']['makeSubformInterface'] ?? false;
				unset($propertiesPreparedByTheElement['upsertParams']); // remove so it won't affect the object properties!
			}
		}

		// merge the element-specific properties into the main properties array
		// this will overwrite any keys that are the same, which would be rare, but
		// important if a special element needs to control some more general aspect
		// of the element for example, a special element might want to force ele_required
		// to true so the element-specific properties should take precedence and so they are set last here
		foreach($propertiesPreparedByTheElement as $key => $value) {
			$elementObjectProperties[$key] = $value;
		}

		$elementObject = formulizeHandler::upsertElementSchemaAndResources($elementObjectProperties, dataType: $data_type, pi: $pi, makeSubformInterface: $makeSubformInterface);

		return [
			'element_id' => $elementObject->getVar('ele_id'),
			'form_id' => $elementObject->getVar('fid'),
			'type' => $type,
			'handle' => $elementObject->getVar('ele_handle'),
			'caption' => $elementObject->getVar('ele_caption'),
			'column_heading' => $elementObject->getVar('ele_colhead'),
			'description' => $elementObject->getVar('ele_desc'),
			'required' => $elementObject->getVar('ele_required') ? true : false,
			'properties' => $elementObject->getVar('ele_value'),
			'success' => true,
			'message' => 'Element and related resources created successfully'
		];

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
		// supports {BLANK} value for searching for blank values
		// if filter is an array, then force AND between multiple filters since the array is a series of nested searches with their own booleans between
		$filter = $this->validateFilter($filter, $andOr);
		$andOr = is_array($filter) ? 'AND' : $andOr;

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

	}

/**
 * Convert MCP filter array into old style filter string for compatibility with gatherDataset
 * @param mixed $filter - an array of filters to use, each one is an array with three keys: element, value, operator
 * @param string $andOr - the boolean operator to use between multiple filters, if there are multiple filters. Defaults to 'AND'.
 * @return mixed - a string or array suitable for passing to gatherDataset
 */
private function validateFilter($filter, $andOr = 'AND') {
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
	$blankSearches = array();
	foreach($filter as $thisFilter) {
		// similar to formulize_parseSearchesIntoFilter but that is tuned to dealing with searches entered through UI which aren't in array format already
		// this will not quite work perfectly if there are multiple blank searches on different elements
		// search for email = {BLANK} AND phone = {BLANK} would actually need a third level of nesting in final output, since the structure for just the blank portion should be:
		// ((email = '' OR email IS NULL) AND (phone = '' OR phone IS NULL))
		// A very smartly recursive handling when parsing the $blankSearches array could probably handle this, and we just put each field into a sub level of the array when creating it, but for now, we will just note the limitation
		if($thisFilter['value'] == '{BLANK}') {
			if($thisFilter['operator'] == "!=" OR $thisFilter['operator'] == "NOT LIKE") {
				$blankOp1 = "!=";
				$blankOp2 = " IS NOT NULL ";
				$blankBoolean = "AND";
			} else {
				$blankOp1 = "=";
				$blankOp2 = " IS NULL ";
				$blankBoolean = "OR";
			}
			$blankSearches[$blankBoolean][] = $thisFilter['element']."/**//**/$blankOp1][".$thisFilter['element']."/**//**/$blankOp2";
		} else {
			$filterStringParts[] = $thisFilter['element'].'/**/'.$thisFilter['value'].'/**/'.$thisFilter['operator'];
		}
	}
	if(!empty($blankSearches)) {
		$returnFilter = array([
				$andOr,
				implode('][', $filterStringParts)
		]);
		if(isset($blankSearches['AND'])) {
			$returnFilter[] = [
				'AND',
				implode('][', $blankSearches['AND'])
			];
		}
		if(isset($blankSearches['OR'])) {
			$returnFilter[] = [
				'OR',
				implode('][', $blankSearches['OR'])
			];
		}
		return $returnFilter;
	} else {
		return implode('][', $filterStringParts);
	}
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
	 * List all the groups - tool version of the resource
	 */
	private function list_groups() {
		return $this->groups_list();
	}

	/**
	 * List all the members of a group
	 */
	private function list_group_members($arguments) {
		$group_id = intval($arguments['group_id'] ?? 0);
		if(empty($group_id) OR $group_id <= 0) {
			throw new FormulizeMCPException('group_id is required and must be a positive integer', 'invalid_data');
		}
		if(!$this->authenticatedUid OR ($this->isUserAWebmaster() == false AND !in_array($group_id, $this->userGroups))) {
			throw new FormulizeMCPException('Permission denied: You must be a webmaster or a member of the group to list its members.', 'authentication_error');
		}
		$limitBy = " INNER JOIN ".$this->db->prefix('groups_users_link')." as l ON l.uid = u.uid WHERE l.groupid = ".intval($group_id);
		$groupMemberData = [];
		$groupData = $this->groups_list($group_id);
		$groupMemberData['group_details'] = $groupData['groups'][0] ?? [];
		if($result = $this->getUserDetails(limitBy: $limitBy)) {
			if($result) {
				while($row = $this->db->fetchArray($result)) {
					$groupMemberData['members'][] = $this->formatTimestamps($row);
				}
			}
		}
		return $groupMemberData;
	}

	/**
	 * List all the users - tool version of the resource
	 */
	private function list_users() {
		return $this->users_list();
	}

	/**
	 * List all the groups a user belongs to
	 */
	private function list_a_users_groups($arguments) {
		$user_id = intval($arguments['user_id'] ?? 0);
		if(empty($user_id) OR $user_id <= 0) {
			throw new FormulizeMCPException('user_id is required and must be a positive integer', 'invalid_data');
		}
		$users = $this->users_list(); // get a list of the users the authenticated user is allowed to see
		$allowedUserIds = array_column($users['users'], 'user_id');
		if(!in_array($user_id, $allowedUserIds)) {
			throw new FormulizeMCPException('Permission denied: You do not have access to this user.', 'authentication_error');
		}
		$userDetails = [];
		if($result = $this->getUserDetails($user_id)) {
			$row = $this->db->fetchArray($result);
			$userDetails['user_details'] = $this->formatTimestamps($row);
		}
		return $userDetails + $this->groups_list(user_id: $user_id);
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
		// Enhanced input validation
		if (!is_array($data) || empty($data)) {
			throw new FormulizeMCPException(
				'Data must be a non-empty array',
				'invalid_data'
			);
		}

		// Validate form ID
		if (!is_numeric($formId) || $formId <= 0) {
			throw new FormulizeMCPException('Form ID must be a positive integer', 'invalid_data');
		}
		$formId = intval($formId);

		// Validate relationship ID
		if (!is_numeric($relationshipId) || $relationshipId == 0 || $relationshipId < -1) {
			throw new FormulizeMCPException('Relationship ID must be a positive integer or -1 for the Primary Relationship that includes all connections.', 'invalid_data');
		}
		$relationshipId = intval($relationshipId);

		// Validate entry ID
		if ($entryId !== 'new' && (!is_numeric($entryId) || $entryId <= 0)) {
			throw new FormulizeMCPException('Entry ID must be a positive integer', 'invalid_data'); // can be 'new' also, but only 'new' when we call specifically from the create_entry tool, so for error reporting only state that positive integers are allowed because an error would be in the use of update_entry with an invalid entry id specified.
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

	/**
	 * Build the create_form_element and update_form_element tool schema with dynamic element discovery
	 * @return array Tool schema for creating form elements
	 */
	private function buildFormElementTools() {

		// for creating and updating
		$commonDataElementProperties = [
			'column_heading' => [
				'type' => 'string',
				'description' => 'Optional. The heading to use at the top of a column in lists of entries. If not specified, the caption will be used. Some captions are long and descriptive, and a shorter heading would be more appropriate for in a list of data.'
			],
			'description' => [
				'type' => 'string',
				'description' => 'Optional. A longer description or help text for the REPLACEWITHSINGLUARCATEGORYNAME, shown to users filling out the form.'
			],
			'required' => [
				'type' => 'boolean',
				'description' => 'Optional. Whether the REPLACEWITHSINGLUARCATEGORYNAME is required to have a value when users fill out the form. Default: false'
			],
			'principal_identifier' => [
				'type' => 'boolean',
				'description' => 'Optional. Whether the REPLACEWITHSINGLUARCATEGORYNAME is the principal identifying element for entries in this form. Principal identifiers are used in various places in Formulize to represent an entry. The Principal Identifier would typically be a \'Name\' text box or other element that unique identifies the entry. Each form can only have one Principal Identifier. If a form has a Principal Identifier, and another element is created or updated with this value set to true, the existing Principal Identifier will be replaced with the new one. Default: false.'
			],
			'disabled' => [
				'type' => 'boolean',
				'description' => 'Optional. Whether the REPLACEWITHSINGLUARCATEGORYNAME element is disabled (visible but not usable) in the form. Default: false.'
			]
		];

		// for creating only
		$creationDataElementProperties = [
			'handle' => [
				'type' => 'string',
				'description' => 'Optional. This does not need to be specified, as the system will determine it automatically from the caption. This is the internal name, used in the database and in API calls. If the user specifically requests a handle, use this to force the handle to be a certain value. The system may still modify it for uniqueness, so check the tool result to see the actual handle used in by system.'
			]
		];

		// presently only webmasters get these tools at all, but in case that changes, only webmasters will be able to muck with the data_type property
		$dataTypeProperty = $this->isUserAWebmaster() ? [
			'data_type' => [
				'type' => 'string',
				'enum' => ['text', 'int(x)', 'decimal(x,y)', 'date', 'datetime', 'time', 'char(x)', 'varchar(x)'],
				'description' => 'Optional. The data type to be used for the field in the database where this data will be stored. The system will default to text in most cases, but will set smart defaults if the type is specifically a number box or linked element storing foreign keys, etc. Generally this does not need to be specified, but can be used if the user has specifically stated that a certain data type must be used for a given element. For int(x), the x is the number of digits to display in MySQL when showing the number. For decimal(x,y), the x is the total number of digits, and y is the number of digits after the decimal point. For char(x) and varchar(x), the x is the maximum number of characters to store.'
			]
		] : [];

		// Discover available element types and their descriptions
		[$elementTypes, $creationElementDescriptions] = formulizeHandler::discoverElementTypes();
		[$elementTypes, $updateElementDescriptions] = formulizeHandler::discoverElementTypes(update: true);

		// Build comprehensive description with examples from all element types
		$basePropertyDescriptions = " have different properties depending on their type.\n\nYou must use the valid properties for each type. Here is a complete list of available types, their properties, and examples:\n\n";
		$categoryNames = formulizeHandler::getElementTypeReadableNames();
		$formElementTools = [];
		foreach($elementTypes as $category => $types) {
			$pluralCategoryName = ucfirst($categoryNames[$category]['plural']);
			$singularCategoryName = ucfirst($categoryNames[$category]['singular']);
			$categoryCreationBaseDescriptions = "$pluralCategoryName $basePropertyDescriptions";
			$categoryUpdateBaseDescriptions = "$pluralCategoryName $basePropertyDescriptions";
			if(method_exists('formulizeHandler', 'mcpElementPropertiesBaseDescriptionAndExamplesFor'.ucfirst($category))) {
				$staticMethodName = 'mcpElementPropertiesBaseDescriptionAndExamplesFor'.ucfirst($category);
				$categoryCreationBaseDescriptions = formulizeHandler::$staticMethodName(update: false);
				$categoryUpdateBaseDescriptions = formulizeHandler::$staticMethodName(update: true);
			}
			$creationDescription = "**Create a new $singularCategoryName in a Formulize form.**\n\n$categoryCreationBaseDescriptions".implode("\n\n", $creationElementDescriptions[$category]);
			$updateDescription = "**Update an existing $singularCategoryName in a Formulize form.**\n\n$categoryUpdateBaseDescriptions".implode("\n\n", $updateElementDescriptions[$category]);
			$commonDataElementPropertiesForThisCategory = [];
			$dataTypePropertyForThisCategory = [];
			$creationDataElementPropertiesForThisCategory = [];
			if($category != 'subforms') {
				$commonDataElementPropertiesForThisCategory = recursiveReplaceInArray('REPLACEWITHSINGLUARCATEGORYNAME', $singularCategoryName, $commonDataElementProperties);
				$dataTypePropertyForThisCategory = $dataTypeProperty;
				$creationDataElementPropertiesForThisCategory = $creationDataElementProperties;
			}
			$formElementTools[] = [
				'name' => 'create_'.str_replace(' ', '_', strtolower($singularCategoryName)),
				'description' => $creationDescription,
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'form_id' => [
								'type' => 'integer',
								'description' => 'Required. ID of the form that this will be part of.'
							],
							'type' => [
								'type' => 'string',
								'enum' => $types,
								'description' => "Required. The type of $singularCategoryName to create."
							],
							'caption' => [
								'type' => 'string',
								'description' => "Required. The label for the $singularCategoryName as it will appear to users in forms and in lists."
							],
							'properties' => [
								'type' => 'object',
								'description' => "Required. Additional configuration settings for the $singularCategoryName. The available properties depend on the element type. See the tool description for examples of what properties are needed for different element types.",
								'additionalProperties' => true
							],
						] + $commonDataElementPropertiesForThisCategory + $creationDataElementPropertiesForThisCategory + $dataTypePropertyForThisCategory,
					'required' => ['form_id', 'type', 'caption', 'properties']
				]
			];
			$formElementTools[] = [
				'name' => 'update_'.str_replace(' ', '_', strtolower($singularCategoryName)),
				'description' => $updateDescription,
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'element_identifier' => [
							'oneOf' => [
								[
									'type' => 'string',
									'description' => "The handle for the $singularCategoryName to update."
								],
								[
									'type' => 'integer',
									'description' => "The ID number of the $singularCategoryName to update."
								]
							]
						],
						'caption' => [
							'type' => 'string',
							'description' => "Optional. The new label for the $singularCategoryName as it will now appear to users in forms."
						],
						'properties' => [
							'type' => 'object',
							'description' => "Optional. Updated configuration settings for the $singularCategoryName. The available properties depend on the element type. See the tool description for examples of what properties are needed for different element types. Use the get_form_details tool to see all the element types for the existing elements.",
							'additionalProperties' => true
						],
					] + $commonDataElementPropertiesForThisCategory + [
						'display' => [
							'type' => 'boolean',
							'description' => "Optional. Whether the $singularCategoryName is displayed in the form or hidden. Default: true."
						]
					] + $dataTypePropertyForThisCategory,
				'required' => ['element_identifier']
				]
			];
		}

		return $formElementTools;

	}

}

function recursiveReplaceInArray($search, $replace, $array) {
	$result = [];
	foreach ($array as $key => $value) {
		if (is_array($value)) {
			$result[$key] = recursiveReplaceInArray($search, $replace, $value);
		} elseif (is_string($value)) {
			$result[$key] = str_replace($search, $replace, $value);
		} else {
			$result[$key] = $value;
		}
	}
	return $result;
}
