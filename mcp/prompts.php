<?php

trait prompts {

	/**
	 * Register available MCP prompts
	 * Sets the prompts property of the FormulizeMCP class
	 * This method should be called in the constructor of the FormulizeMCP class
	 * @return void
	 */
	private function registerPrompts()
	{
		$this->prompts = [
			'analyze_form' => [
				'name' => 'analyze_form',
				'description' => 'Generate a comprehensive analysis of a form structure',
				'arguments' => [
					[
						'name' => 'form_id',
						'description' => 'The ID of the form to analyze',
						'required' => true
					]
				]
			],
			'generate_report' => [
				'name' => 'generate_report',
				'description' => 'Generate a data report from a form with customizable filters',
				'arguments' => [
					[
						'name' => 'form_id',
						'description' => 'The ID of the form to report on',
						'required' => true
					],
					[
						'name' => 'report_type',
						'description' => 'Type of report: summary, detailed, or statistical',
						'required' => false
					],
					[
						'name' => 'filters',
						'description' => 'Optional filters to apply to the data',
						'required' => false
					]
				]
			],
			'form_relationships' => [
				'name' => 'form_relationships',
				'description' => 'Analyze relationships between forms',
				'arguments' => [
					[
						'name' => 'form_id',
						'description' => 'The ID of the form to analyze relationships for (optional, analyzes all if not provided)',
						'required' => false
					]
				]
			],
			'sql_query' => [
				'name' => 'sql_query',
				'description' => 'Generate SQL queries for extracting data from Formulize',
				'arguments' => [
					[
						'name' => 'form_id',
						'description' => 'The ID of the form to query',
						'required' => true
					],
					[
						'name' => 'query_type',
						'description' => 'Type of query: select, join, aggregate',
						'required' => false
					],
					[
						'name' => 'elements',
						'description' => 'Element handles to include in the query',
						'required' => false
					]
				]
			],
			'data_validation' => [
				'name' => 'data_validation',
				'description' => 'Check data quality and validation issues in a form',
				'arguments' => [
					[
						'name' => 'form_id',
						'description' => 'The ID of the form to validate',
						'required' => true
					]
				]
			]
		];
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
	 * @throws Exception If the prompt cannot be generated or prompt is unknown
	 */
	private function handlePromptGet($params, $id)
	{
		$promptName = $params['name'] ?? '';
		$arguments = $params['arguments'] ?? [];

		if (!isset($this->prompts[$promptName])) {
			return $this->errorResponse('Unknown prompt: ' . $promptName, -32602, $id);
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
			return $this->errorResponse('Prompt generation failed: ' . $e->getMessage(), -32603, $id);
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
		switch ($promptName) {
			case 'analyze_form':
				return $this->generateAnalyzeFormPrompt($arguments);
			case 'generate_report':
				return $this->generateReportPrompt($arguments);
			case 'form_relationships':
				return $this->generateRelationshipsPrompt($arguments);
			case 'sql_query':
				return $this->generateSqlQueryPrompt($arguments);
			case 'data_validation':
				return $this->generateDataValidationPrompt($arguments);
			default:
				throw new Exception('Unknown prompt: ' . $promptName);
		}
	}

	/**
	 * Generate analyze form prompt
	 */
	private function generateAnalyzeFormPrompt($args)
	{
		$formId = $args['form_id'] ?? null;

		if (!$formId) {
			throw new Exception('form_id is required');
		}

		// Get form details
		$schema = $this->getFormSchema($formId);

		return [
			[
				'role' => 'user',
				'content' => sprintf(
					"Please analyze this Formulize form structure:\n\nForm: %s (ID: %d)\nElements: %d\n\nProvide insights about:\n1. Field types and their distribution\n2. Any potential data quality issues\n3. Suggestions for optimization\n4. Relationship opportunities with other forms",
					$schema['form']['title'],
					$formId,
					$schema['element_count']
				)
			],
			[
				'role' => 'assistant',
				'content' => "I'll analyze the form structure for you. Let me examine the form details and elements..."
			],
			[
				'role' => 'user',
				'content' => "Here's the form schema:\n" . json_encode($schema, JSON_PRETTY_PRINT)
			]
		];
	}

	/**
	 * Generate report prompt
	 */
	private function generateReportPrompt($args)
	{
		$formId = $args['form_id'] ?? null;
		$reportType = $args['report_type'] ?? 'summary';
		$filters = $args['filters'] ?? [];

		if (!$formId) {
			throw new Exception('form_id is required');
		}

		return [
			[
				'role' => 'user',
				'content' => sprintf(
					"Generate a %s report for form ID %d. %s",
					$reportType,
					$formId,
					!empty($filters) ? "Apply these filters: " . json_encode($filters) : "Include all data."
				)
			],
			[
				'role' => 'assistant',
				'content' => sprintf(
					"I'll generate a %s report for form %d. Let me gather the data and create the report based on your requirements.",
					$reportType,
					$formId
				)
			]
		];
	}

	/**
	 * Generate relationships prompt
	 */
	private function generateRelationshipsPrompt($args)
	{
		$formId = $args['form_id'] ?? null;

		$content = $formId
			? "Analyze the relationships for form ID $formId. Show all connected forms and the nature of their relationships."
			: "Analyze all form relationships in this Formulize instance. Provide a comprehensive overview of how forms are connected.";

		return [
			[
				'role' => 'user',
				'content' => $content
			],
			[
				'role' => 'assistant',
				'content' => "I'll analyze the form relationships for you. Let me examine the framework definitions and links..."
			]
		];
	}

	/**
	 * Generate SQL query prompt
	 */
	private function generateSqlQueryPrompt($args)
	{
		$formId = $args['form_id'] ?? null;
		$queryType = $args['query_type'] ?? 'select';
		$elements = $args['elements'] ?? [];

		if (!$formId) {
			throw new Exception('form_id is required');
		}

		return [
			[
				'role' => 'user',
				'content' => sprintf(
					"Generate a %s SQL query for form ID %d. %s",
					$queryType,
					$formId,
					!empty($elements) ? "Include these elements: " . implode(', ', $elements) : "Include all elements."
				)
			],
			[
				'role' => 'assistant',
				'content' => sprintf(
					"I'll generate a %s query for form %d. The table name is %s_formulize_%d.",
					$queryType,
					$formId,
					XOOPS_DB_PREFIX,
					$formId
				)
			]
		];
	}

	/**
	 * Generate data validation prompt
	 */
	private function generateDataValidationPrompt($args)
	{
		$formId = $args['form_id'] ?? null;

		if (!$formId) {
			throw new Exception('form_id is required');
		}

		return [
			[
				'role' => 'user',
				'content' => sprintf(
					"Check data quality and validation issues for form ID %d. Look for:\n- Required fields with missing data\n- Invalid data types\n- Duplicate entries\n- Referential integrity issues",
					$formId
				)
			],
			[
				'role' => 'assistant',
				'content' => "I'll perform a comprehensive data validation check on form $formId. Let me analyze the data quality..."
			]
		];
	}

}
