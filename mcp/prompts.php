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
				'description' => 'Ask the AI to look up data in the system.',
				'arguments' => [
					[
						'name' => 'form',
						'description' => 'The ID of the form, or the name.',
						'required' => true
					],
					[
						'name' => 'searches',
						'description' => 'Optional. "pulse > 100", "Community is Norris Point", etc',
						'required' => false
					],
					[
						'name' => 'limit',
						'description' => 'Optional. 100 entries by default. Enter 0 for no limit.',
						'required' => false
					],
					[
						'name' => 'sortDetails',
						'description' => 'Optional. Specify how to sort results, and the direction.',
						'required' => false
					],
					[
						'name' => 'elements',
						'description' => 'Optional. Specify elements to include. Leave blank for all.',
						'required' => false
					]
				]
			],
			'generate_a_report_about_a_form' => [
				'name' => 'generate_a_report_about_a_form',
				'description' => 'Ask the AI to write a report about the data in a form.',
				'arguments' => [
					[
						'name' => 'form',
						'description' => 'The ID of the form, or the name.',
						'required' => true
					],
					[
						'name' => 'report_type',
						'description' => '"Create a ____ report" ie: summary, detailed, statistical...',
						'required' => true
					],
					[
						'name' => 'focus',
						'description' => 'Optional. Enter any elements or other details to focus on.',
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
						'description' => 'Ask the AI for a report on recent user activity',
						'arguments' => [
							[
								'name' => 'users',
								'description' => 'Any user IDs or names of users to focus on.',
								'required' => false
							],
							[
								'name' => 'forms',
								'description' => 'Any form IDs or names of forms to focus on.',
								'required' => false
							]
						]
					];
				}
			}

	}

	/**
	 * Handle prompts list request
	 * @return array JSON-RPC response with list of prompts
	 */
	private function handlePromptsList()
	{
		return [
			'result' => [
				'prompts' => array_values($this->prompts)
			]
		];
	}

	/**
	 * Handle prompt get request
	 * @param array $params Parameters from the JSON-RPC request
	 * @return array JSON-RPC response with prompt messages or error
	 */
	private function handlePromptGet($params)
	{
		$promptName = $params['name'] ?? '';
		$arguments = $params['arguments'] ?? [];

		if (!isset($this->prompts[$promptName])) {
			throw new FormulizeMCPException(
				'Unknown prompt: ' . $promptName,
				'unknown_prompt',
				-32602,
			);
		}

		try {
			$messages = $this->generatePrompt($promptName, $arguments);
			return [
				'result' => [
					'messages' => $messages
				]
			];
		} catch (Exception $e) {
			throw new FormulizeMCPException(
				'Prompt generation failed: ' . $e->getMessage(),
				'prompt_generation_error',
				-32603
			);
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
	 * Generates a prompt for the AI to create a report about a form
	 * @return array
	 */
	private function generate_a_report_about_a_form($args)
	{
		$form = $args['form'] ?? null;
		$reportType = $args['report_type'] ?? 'summary';
		$focus = $args['focus'] ?? '';

		if (!$form) {
			throw new Exception('A form identifier is required');
		}

		if(is_numeric($form) AND !security_check(intval($form))) {
			throw new FormulizeMCPException(
				'Permission denied: user does not have access to form ' . intval($form),
				'permission_denied',
			);
		}

		return [
			[
				'role' => 'user',
				'content' => [
					'type' => 'text',
					'text' => sprintf(
						"Generate a %s report for this form: %s. %s If you don't know the ID number for this form in the system, you can use the list_forms tool to see all the forms. You can use the get_form_details tool to lookup the schema for the form and its elements, and you can get data from the form using the get_entries_from_form tool. %s",
						$reportType,
						$form,
						$focus ? "The report should focus on: $focus." : "",
						in_array(XOOPS_GROUP_ADMIN, $this->userGroups) ? "You can also lookup data directly using SQL with the query_the_database_directly tool. Some data might be foreign keys to other forms. You can turn those into readable, meaningful values with the prepare_database_values_for_human_readability tool." : ""
					)
				]
			],
			[
				'role' => 'assistant',
				'content' => [
					'type' => 'text',
					'text' => sprintf(
						"I'll generate a %s report for form %s. With the proper ID number for the form, I will start by looking up details about the form with the get_form_details tool, and the data in the form, with the get_entries_from_form tool. %s If I don't know the proper ID number for the form, I will look up the forms with the list_forms tool.",
						$reportType,
						$focus ? "$form, focusing on: $focus" : $form,
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
			throw new FormulizeMCPException(
				'Permission denied: user does not have access to activity logs',
				'permission_denied'
			);
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
						"Look in the system's logs for recent activity. %s %s You can use the read_system_activity_log tool to get the most recent 1000 lines from the activity log. Each line in the log is a JSON object. Critical keys in each line are: formulize_event, a short string explaining what the log entry is about. user_id, the ID number of the user. form_id, the ID number of a form if one was involved in the activity. You can use the list_users tool to get a list of all users and their ID numbers. You can use the list_forms tool to get a list of all the forms and their ID numbers. To get more information about a form, you can use the get_form_details tool.",
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
						"I'll lookup the recent activity logs with the read_system_activity_log tool. If I need to figure out the user ids and form ids, I will use the list_users and list_forms tools. %s",
						($users OR $forms) ? "In the activity logs, I'll pay special attention to ".($users ? "the users: $users" : "").(($users AND $forms) ? " and " : "").($forms ? "the forms: $forms" : "")."." : ""
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
		$limit = is_numeric($args['limit'] ?? null) ? intval($args['limit']) : 0;
		$sortDetails = $args['sortDetails'] ?? null;
		$elements = $args['elements'] ?? null;

		if (!$form) {
			throw new Exception('A form identifier is required');
		}

		if(is_numeric($form) AND !security_check(intval($form))) {
			throw new FormulizeMCPException(
				'Permission denied: user does not have access to form ' . intval($form),
				'permission_denied',
			);
		}

		return [
			[
				'role' => 'user',
				'content' => [
					'type' => 'text',
					'text' => sprintf(
						"Use ".$this->mcpRequest['localServerName']." (MCP Server) to lookup entries in this form: %s. Use the get_form_details tool to see the schema of the form. Use the get_entries_from_form tool to read the data. %s %s %s %s After querying the data, provide two things: **1.** A summary of key findings **2.** The raw data in plain text format as comma-separated values (CSV), with column headers, that I can copy and paste directly into a spreadsheet. No HTML tables, no markdown formatting, just plain CSV text. Present the data in a code block so it's easy to select and copy.",
						$form,
						$searches ? "Filter the entries in this way: $searches." : "",
						$limit ? "I don't want all the entries, I just want $limit." : "",
						$sortDetails ? "I want the entries sorted a certain way: $sortDetails." : "",
						$elements ? "Only include certain elements in the query: $elements." : ""
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
