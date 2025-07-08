<?php

trait prompts {

	/**
	 * Register available MCP prompts
	 * Sets the prompts property of the FormulizeMCP class
	 * @return void
	 */
	private function registerPrompts()
	{
		$this->prompts = [
			'look_up_data' => [
				'name' => 'look_up_data',
				'description' => 'Ask the AI to look up data in the system. The AI will try various approaches to retrieve the information based on what you enter here, and summarize it for you.',
				'arguments' => [
					[
						'name' => 'form',
						'description' => 'What form are you looking up data in? Put the ID or the name of the form. IDs are slightly preferred.',
						'required' => true
					],
					[
						'name' => 'searches',
						'description' => 'Do you want to search for anything specific? ie: "daily_pulse > 100" or "Community = Norris Point" or "creation date is January this year or newer" Use element handles if you know them, or wording that very closely matches the element captions in the forms.',
						'required' => false
					],
					[
						'name' => 'limit',
						'description' => 'How many entries do you want to retrieve? The default is up to 100, but if you put something different here the AI will be instructed to use that instead. Put 0 to try and get all entries, but if there are a lot that might take too long or be too much for the AI to read.',
						'required' => false
					],
					[
						'name' => 'sortDetails',
						'description' => 'Do you want the data sorted a certain way? ie: "footwear_size" Use the element handle if you know it. You can also specify the sorting direction (ascending or descending). It should default to ascending order.',
						'required' => false
					],
					[
						'name' => 'elements',
						'description' => 'If your form is big and you only want to see data from certain elements, provide a comma separated list of the them here. Element handles are preferred, but IDs should work.',
						'required' => false
					]
				]
			],
			'generate_a_report_about_a_form' => [
				'name' => 'generate_a_report_about_a_form',
				'description' => 'Ask for a report about the data in a form. Give direction to the AI about the form, the level of detail, and what data to focus on.',
				'arguments' => [
					[
						'name' => 'form',
						'description' => 'The ID or name of the form to report on. IDs are preferred.',
						'required' => true
					],
					[
						'name' => 'report_type',
						'description' => '"Create a _______ report" ie: summary, detailed, statistical...',
						'required' => false
					],
					[
						'name' => 'elements',
						'description' => 'Which elements in the form should the report focus on? Provide a comma separated list. Element IDs or handles are best, but captions can work too if they\'re unique.',
						'required' => false
					]
				]
			]

		];

		// only webmasters can access certain prompts
		if(in_array(XOOPS_GROUP_ADMIN, $this->userGroups)) {
			// Logging tool only available if logging is enabled
			$config_handler = xoops_gethandler('config');
			$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
			if($formulizeConfig['formulizeLoggingOnOff']) {
					$this->prompts['check_the_activity_logs'] = [
						'name' => 'check_the_activity_logs',
						'description' => 'Ask for a report on recent user activity, optionally focusing on certain user(s), and/or certain form(s).',
						'arguments' => [
							[
								'name' => 'users',
								'description' => 'The user IDs or names of users to focus on. Provide a comma separated list. User IDs are best, but names may work as well.',
								'required' => false
							],
							[
								'name' => 'forms',
								'description' => 'The form IDs or names of forms to focus on. Provide a comma separated list. Form IDs are best, but names may work as well.',
								'required' => false
							]
						]
					];
				}
			}

	}

	/**
	 * Handle prompts list request
	 * @param string $id The JSON-RPC request ID from the MCP client
	 * @return array JSON-RPC response with list of prompts
	 */
	private function handlePromptsList($id)
	{
		return [
			'jsonrpc' => '2.0',
			'result' => [
				'prompts' => array_values($this->prompts)
			],
			'id' => $id
		];
	}

	/**
	 * Handle prompt get request
	 * @param array $params Parameters from the JSON-RPC request
	 * @param string $id The JSON-RPC request ID from the MCP client
	 * @return array JSON-RPC response with prompt messages or error
	 */
	private function handlePromptGet($params, $id)
	{
		$promptName = $params['name'] ?? '';
		$arguments = $params['arguments'] ?? [];

		if (!isset($this->prompts[$promptName])) {
			return $this->JSONerrorResponse('Unknown prompt: ' . $promptName, -32602, $id);
		}

		try {
			$messages = $this->generatePrompt($promptName, $arguments);
			return [
				'jsonrpc' => '2.0',
				'result' => [
					'messages' => $messages
				],
				'id' => $id
			];
		} catch (Exception $e) {
			return $this->JSONerrorResponse('Prompt generation failed: ' . $e->getMessage(), -32603, $id);
		}
	}

	/**
	 * Generate prompt messages
	 * @param string $promptName The name of the prompt to generate
	 * @param array $arguments The arguments for the prompt
	 * @return array Array of messages for the prompt
	 * @throws Exception If the prompt is unknown
	 */
	private function generatePrompt($promptName, $arguments)
	{
		if(method_exists($this, $promptName)) {
			return $this->$promptName($arguments);
		}
		throw new Exception('Unknown prompt: ' . $promptName);
	}

	/**
	 * Generate report prompt
	 */
	private function generate_a_report_about_a_form($args)
	{
		$form = $args['form'] ?? null;
		$reportType = $args['report_type'] ?? 'summary';
		$elements = $args['elements'] ?? '';

		if (!$form) {
			throw new Exception('A form identifier is required');
		}

		if(is_numeric($form) AND !security_check(intval($form))) {
			$this->sendAuthError("Permission denied: user does not have access to form ".intval($form), 403);
		}

		return [
			[
				'role' => 'user',
				'content' => [
					'type' => 'text',
					'text' => sprintf(
						"Generate a %s report for this form: %s. %s You can use the get_form_details tool to lookup the schema for the form and its elements, and you can get data from the form using the get_entries_from_form tool. %s",
						$reportType,
						$form,
						$elements ? "Focus on these elements in the form: $elements." : "",
						in_array(XOOPS_GROUP_ADMIN, $this->userGroups) ? "You can also lookup data directly using SQL with the query_the_database_directly tool. Some data might be foreign keys to other forms. You can turn those into readable, meaningful values with the prepare_database_values_for_human_readability tool." : ""
					)
				]
			],
			[
				'role' => 'assistant',
				'content' => [
					'type' => 'text',
					'text' => sprintf(
						"I'll generate a %s report for form %s. I'll start by looking up details about the form with the get_form_details tool, and the data in the form, with the get_entries_from_form tool. %s",
						$reportType,
						$form,
						in_array(XOOPS_GROUP_ADMIN, $this->userGroups) ? " I might also use the query_the_database_directly tool for more flexibility, if get_entries_from_form is not providing enough detail." : ""
					)
				]
			]
		];
	}

	/**
	 * Generate report prompt for recent activity
	 */
	private function check_the_activity_logs($args)
	{
		if(!in_array(XOOPS_GROUP_ADMIN, $this->userGroups)) {
			$this->sendAuthError("Permission denied: user cannot access this prompt", 403);
		}

		// Logging tool only available if logging is enabled
		$config_handler = xoops_gethandler('config');
		$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
		if(!$formulizeConfig['formulizeLoggingOnOff']) {
			return [
				[
					'role' => 'user',
					'content' => [
						'type' => 'text',
						'text' => 'Logging is disabled on this Formulize system, so activity logs are not available.'
					]
				]
			];
		}

		$users = $args['users'] ?? null;
		$forms = $args['forms'] ?? null;

		return [
			[
				'role' => 'user',
				'content' => [
					'type' => 'text',
					'text' => sprintf(
						"Look in the system's logs for recent activity. %s %s You can use the read_system_activity_log tool to get the most recent 1000 lines from the activity log. Each line in the log is a JSON object. Critical keys in each line are: formulize_event, a short string explaining what the log entry is about. user_id, the ID number of the user. form_id, the ID number of a form if one was involved in the activity. You can use the list_users tool to get a list of all users and their ID numbers. To get more information about a form, you can use the get_form_details tool.",
						$users ? "Pay special attention to these users: $users" : "",
						$forms ? ($users ? "and to these forms: $forms." : "Pay special attention to these forms: $forms.") : "."
					)
				]
			],
			[
				'role' => 'assistant',
				'content' => [
					'type' => 'text',
					'text' => sprintf(
						"I'll lookup the recent activity logs with the read_system_activity_log tool. %s",
						($users OR $forms) ? "I'll pay special attention to ".($users ? "the users: $users" : "").(($users AND $forms) ? " and " : "").($forms ? "the forms: $forms" : "")."." : ""
					)
				]
			]
		];
	}

	/**
	 * Look up data prompt
	 * Get info from the user to craft into a prompt that will give the AI what it needs to effectively use the get_entries_from_form tool
	 */
	private function look_up_data($args)
	{
		$form = $args['form'] ?? null;
		$searches = $args['searches'] ?? null;
		$limit = $args['limit'] ?? null;
		$sortDetails = $args['sortDetails'] ?? null;
		$elements = $args['elements'] ?? null;

		if (!$form) {
			throw new Exception('A form identifier is required');
		}

		if(is_numeric($form) AND !security_check(intval($form))) {
			$this->sendAuthError("Permission denied: user does not have access to form ".intval($form), 403);
		}

		return [
			[
				'role' => 'user',
				'content' => [
					'type' => 'text',
					'text' => sprintf(
						"Use ".$this->mcpRequest['localServerName']." (MCP Server) to lookup entries in this form: %s. Use the get_form_details tool to see the schema of the form. Use the get_entries_from_form tool to read the data. %s %s %s %s Give a general summary of the information, and create a spreadsheet with the data itself.",
						$form,
						$searches ? "I want to filter the entries in this way: $searches." : "",
						$limit ? "I want a LIMIT to restrict the number of entries returned: $limit." : "",
						$sortDetails ? "I want the entries sorted a certain way: $sortDetails." : "",
						$elements ? "I want only certain elements included in the query: $elements." : ""
					)
				]
			],
			[
				'role' => 'assistant',
				'content' => [
					'type' => 'text',
					'text' => sprintf(
						"I will look up this data from the form now, and take these instructions into account when composing the parameters for the get_entries_from_form tool."
					)
				]
			]
		];
	}

}
