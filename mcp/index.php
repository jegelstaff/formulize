<?php

/**
 * Formulize MCP HTTP Direct Server with Proper Formulize API Key Authentication
 *
 * Uses Formulize's existing API key system from managekeys.php/apikey.php
 */

require_once '../mainfile.php';

// CRITICAL: Disable debug output
icms::$logger->disableLogger();
while (ob_get_level()) {
	ob_end_clean();
}

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';

class FormulizeMCP
{

	private $config;
	private $db;
	public $enabled = false;
	public $canBeEnabled = false;
	private $tools;
	private $resources;
	private $prompts;
	private $authenticatedUser = null;
	private $authenticatedUid = 0;
	private $userGroups = array();
	private $baseUrl;

	public function __construct($config = null)
	{
		if (isMCPServerEnabled()) {
			$this->enabled = true;
			$this->canBeEnabled = true;
		} else {
			$this->enabled = false;
			$authHeader = $this->getAuthorizationHeader();
			if ($authHeader == 'Bearer test-header-passthrough-check') {
				$this->canBeEnabled = true;
			}
		}
		$this->config = $config ?: $this->getDefaultConfig();
		$this->initializeDatabase();
		$this->registerTools();
		$this->registerResources();
		$this->registerPrompts();
		$this->baseUrl = $this->getBaseUrl();
	}

	/**
	 * Get the base URL for this server
	 */
	private function getBaseUrl()
	{
		$scheme = $_SERVER['REQUEST_SCHEME'] ?? (($_SERVER['HTTPS'] ?? 'off') === 'on' ? 'https' : 'http');
		$host = $_SERVER['HTTP_HOST'];
		$script = $_SERVER['SCRIPT_NAME'];
		return $scheme . '://' . $host . $script;
	}

	/**
	 * Initialize database connection using Formulize's existing connection
	 */
	private function initializeDatabase()
	{
		global $xoopsDB;
		if (!$xoopsDB) {
			throw new Exception('Formulize database connection not available');
		}
		$this->db = $xoopsDB;
	}

	/**
	 * Authenticate using Formulize's existing API key system
	 */
	private function authenticateRequest()
	{
		$path = $_SERVER['REQUEST_URI'];
		$method = $_SERVER['REQUEST_METHOD'];

		// Allow unauthenticated GET requests for documentation and health
		if ($method === 'GET' && (
			strpos($path, '/health') !== false ||
			strpos($path, '/capabilities') !== false ||
			strpos($path, '/docs') !== false ||
			!strpos($path, '/mcp')
		)) {
			return true;
		}

		// Handle OPTIONS preflight
		if ($method === 'OPTIONS') {
			return true;
		}

		// Check for Authorization header
		$authHeader = $this->getAuthorizationHeader();

		if (empty($authHeader)) {
			$this->sendAuthError('Missing Authorization header');
			return false;
		}

		// Extract API key from "Bearer {api_key}" format
		if (!preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
			$this->sendAuthError('Invalid Authorization header format. Use: Bearer {api_key}');
			return false;
		}

		$key = trim($matches[1]);

		// Validate API key using Formulize's exact system
		if (!$this->validateFormulizeApiKey($key)) {
			$this->sendAuthError('Invalid or expired API key');
			return false;
		}

		return true;
	}

	/**
	 * Get Authorization header across different server configurations
	 */
	private function getAuthorizationHeader()
	{
		$allHeaders = getAllHeaders();
		if (isset($allHeaders['Authorization'])) {
			return $allHeaders['Authorization'];
		} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
			return $_SERVER['HTTP_AUTHORIZATION'];
		} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
			return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
		}
		return '';
	}

	/**
	 * Validate key using Formulize's API key system
	 */
	private function validateFormulizeApiKey($key)
	{
		global $xoopsUser, $icmsUser;

		// Get the API key handler exactly as Formulize does
		$apiKeyHandler = xoops_getmodulehandler('apikey', 'formulize');

		// Clear out expired keys
		$apiKeyHandler->delete();

		$this->authenticatedUid = 0;

		if ($key and $apikey = $apiKeyHandler->get($key)) {
			$this->authenticatedUid = $apikey->getVar('uid');

			$member_handler = xoops_gethandler('member');
			if ($uidObject = $member_handler->getUser($this->authenticatedUid)) {
				$this->userGroups = $uidObject->getGroups();
				$this->authenticatedUser = $uidObject;

				// Set global user context as Formulize does
				$xoopsUser = $uidObject;
				$icmsUser = $uidObject;

				return true;
			} else {
				$this->authenticatedUser = null;
				$this->authenticatedUid = 0;
				$this->userGroups = array(XOOPS_GROUP_ANONYMOUS);
				return false;
			}
		}

		return false;
	}

	/**
	 * Send authentication error response
	 */
	private function sendAuthError($message)
	{
		$this->setNoCacheHeaders();
		http_response_code(401);
		echo json_encode([
			'error' => [
				'code' => 401,
				'message' => $message,
				'type' => 'authentication_error'
			],
			'timestamp' => date('Y-m-d H:i:s')
		]);
		exit;
	}

	/**
	 * Handle HTTP request routing with authentication
	 */
	public function handleHTTPRequest()
	{
		$this->setNoCacheHeaders();

		// Handle CORS preflight
		if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
			http_response_code(204);
			exit;
		}

		// Authenticate request
		if (!$this->authenticateRequest()) {
			return; // Authentication error already sent
		}

		$path = $_SERVER['REQUEST_URI'];
		$pathParts = explode('?', $path);
		$cleanPath = rtrim($pathParts[0], '/');

		// Route based on path - match end of line
		if (preg_match('/\/health$/', $cleanPath)) {
			$this->handleHealthCheck();
		} elseif (preg_match('/\/capabilities$/', $cleanPath)) {
			$this->handleCapabilities();
		} elseif (preg_match('/\/mcp$/', $cleanPath)) {
			$this->handleMCPEndpoint();
		} else {
			$this->handleDocumentation();
		}
	}

	/**
	 * Set comprehensive no-cache headers
	 */
	private function setNoCacheHeaders()
	{
		// Prevent ALL caching at every level
		header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
		header('Pragma: no-cache');
		header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('ETag: "' . uniqid() . '"');
		header('Vary: *');

		// CORS headers
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
		header('Access-Control-Max-Age: 0');

		header('Content-Type: application/json; charset=utf-8');
	}

	/**
	 * Register available MCP tools with proper JSON Schema validation
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
					'properties' => [
						'include_inactive' => [
							'type' => 'boolean',
							'description' => 'Include inactive forms',
							'default' => false
						]
					]
				]
			],
			'get_form_details' => [
				'name' => 'get_form_details',
				'description' => 'Get detailed information about a specific form',
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
			'get_form_elements' => [
				'name' => 'get_form_elements',
				'description' => 'Get all elements for a specific form',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'form_id' => [
							'type' => 'integer',
							'description' => 'The ID of the form'
						],
						'include_relationships' => [
							'type' => 'boolean',
							'description' => 'Include element relationships and references',
							'default' => true
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
							'description' => 'The ID of the form to create an entry in'
						],
						'data' => [
							'type' => 'object',
							'description' => 'Key-value pairs where keys are element handles and values are the data to store',
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
							'description' => 'The ID of the form containing the entry to update'
						],
						'entry_id' => [
							'type' => 'integer',
							'description' => 'The ID of the entry to update'
						],
						'data' => [
							'type' => 'object',
							'description' => 'Key-value pairs where keys are element handles and values are the data to store',
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
				'description' => 'Get entries from a form, and from other forms that are connected by the specfied relationship (unless the Relationship ID is set to 0). Only returns entries that the user has permission to access.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'fid' => [
							'type' => 'integer',
							'description' => 'The ID of the main form to get entries from'
						],
						'elementHandles' => [
							'type' => 'array',
							'description' => 'Optional. Array of elements to include. Elements can be from the main form, and from any related form that is part of the relationship. Use a multidimensional array with form IDs as keys. If not specified, all elements will be included.',
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
									'description' => 'Filter string taking the format: elementHandle/**/searchTerm/**/operator. Multiple strings can be included, separated by ][ and the logical operator between them is determined by the andOr parameter.'
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
							'description' => 'The scope of entries to include, either "all" for all entries, "group" for entries belonging to the user\'s group(s), or "mine" for the user\'s own entries. Can also be comma-separated group IDs for a custom scope. Defaults to "all".',
						],
						'limitStart' => [
							'type' => ['integer', 'null'],
							'description' => 'Starting record for LIMIT statement'
						],
						'limitSize' => [
							'type' => ['integer', 'null'],
							'description' => 'Number of records to return'
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
						'frid' => [
							'type' => 'integer',
							'description' => 'Relationship ID to use (-1 for Primary Relationship, 0 for no relationship). Defaults to -1 for the Primary Relationship which includes all connected forms.'
						]
					],
					'required' => ['fid']
				]
			]
		];
	}

	/**
	 * Register available MCP resources
	 */
	private function registerResources()
	{
		$this->resources = [];

		// Dynamically add form schema resources
		$forms = $this->getFormsList();
		foreach ($forms as $form) {
			$formId = $form['id_form'];
			$formTitle = trans($form['desc_form']) ?? "Form $formId";

			// Form schema resource
			$this->resources["form_schema_$formId"] = [
				'uri' => "formulize://form/schema/$formId",
				'name' => "Schema: $formTitle",
				'description' => "Complete schema and element definitions for form: $formTitle",
				'mimeType' => 'application/json'
			];

			// Form data resource
			$this->resources["form_data_$formId"] = [
				'uri' => "formulize://form/data/$formId",
				'name' => "Data: $formTitle",
				'description' => "Export all data from form: $formTitle",
				'mimeType' => 'application/json'
			];
		}

		// System-level resources
		$this->resources['system_info'] = [
			'uri' => 'formulize://system/info',
			'name' => 'System Information',
			'description' => 'Formulize system configuration and status',
			'mimeType' => 'application/json'
		];

		$this->resources['user_groups'] = [
			'uri' => 'formulize://system/user_groups',
			'name' => 'Users and Groups',
			'description' => 'List of all users and groups in the system',
			'mimeType' => 'application/json'
		];

		$this->resources['form_relationships'] = [
			'uri' => 'formulize://system/relationships',
			'name' => 'Form Relationships',
			'description' => 'All form relationships and framework definitions',
			'mimeType' => 'application/json'
		];
	}

	/**
	 * Register available MCP prompts
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
	 * Get list of forms for resource registration
	 */
	private function getFormsList()
	{
		try {
			$sql = "SELECT id_form, desc_form FROM " . $this->db->prefix('formulize_id') . " WHERE active = 1 ORDER BY desc_form";
			$result = $this->db->query($sql);

			$forms = [];
			while ($row = $this->db->fetchArray($result)) {
				$forms[] = $row;
			}
			return $forms;
		} catch (Exception $e) {
			return [];
		}
	}

	/**
	 * Enhanced test connection with proper authenticated user info
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
			'status' => 'success',
			'message' => 'MCP Server connection successful with Formulize authentication',
			'database_test' => $row['test'] == 1 ? 'passed' : 'failed',
			'xoops_config' => [
				'sitename' => $xoopsConfig['sitename'] ?? 'Unknown',
				'version' => XOOPS_VERSION ?? 'Unknown'
			],
			'server_info' => [
				'php_version' => PHP_VERSION,
				'timestamp' => date('Y-m-d H:i:s'),
				'execution_mode' => 'http_with_formulize_auth',
				'mcp_version' => FORMULIZE_MCP_VERSION,
				'capabilities' => ['tools', 'resources', 'prompts']
			]
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
	 * Enhanced health check with Formulize auth info
	 */
	private function handleHealthCheck()
	{
		try {
			// Test database connection
			$testQuery = "SELECT 1 as test";
			$result = $this->db->query($testQuery);

			if (!$result) {
				throw new Exception('Database query failed');
			}

			$row = $this->db->fetchArray($result);

			// Check API key table
			$apiKeyTableExists = false;
			$apiKeyCount = 0;

			$checkTableSql = "SHOW TABLES LIKE '" . $this->db->prefix('formulize_apikeys') . "'";
			$tableResult = $this->db->query($checkTableSql);

			if ($this->db->fetchArray($tableResult)) {
				$apiKeyTableExists = true;

				// Count active keys
				$countSql = "SELECT COUNT(*) as count FROM " . $this->db->prefix('formulize_apikeys') . " WHERE expiry IS NULL OR expiry > NOW()";
				$countResult = $this->db->query($countSql);
				if ($countResult) {
					$countRow = $this->db->fetchArray($countResult);
					$apiKeyCount = $countRow['count'];
				}
			}

			$health = [
				'status' => 'healthy',
				'database_test' => $row['test'] == 1 ? 'passed' : 'failed',
				'mcp_server' => 'direct_http_with_formulize_api_keys',
				'mcp_version' => FORMULIZE_MCP_VERSION,
				'tools_count' => count($this->tools),
				'resources_count' => count($this->resources),
				'prompts_count' => count($this->prompts),
				'authentication' => [
					'system' => 'formulize_api_keys',
					'api_key_table_exists' => $apiKeyTableExists,
					'active_keys_count' => $apiKeyCount,
					'table_name' => $this->db->prefix('formulize_apikeys')
				],
				'endpoints' => [
					'mcp' => $this->baseUrl . '/mcp',
					'capabilities' => $this->baseUrl . '/capabilities',
					'health' => $this->baseUrl . '/health'
				],
				'timestamp' => date('Y-m-d H:i:s')
			];

			echo json_encode($health, JSON_PRETTY_PRINT);
		} catch (Exception $e) {
			http_response_code(500);
			echo json_encode([
				'status' => 'unhealthy',
				'error' => $e->getMessage(),
				'timestamp' => date('Y-m-d H:i:s')
			], JSON_PRETTY_PRINT);
		}
	}

	/**
	 * Handle MCP endpoint (/mcp)
	 */
	private function handleMCPEndpoint()
	{
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(405);
			echo json_encode(['error' => 'Method not allowed. Use POST for MCP requests.']);
			return;
		}

		$input = file_get_contents('php://input');

		if (empty($input)) {
			http_response_code(400);
			echo json_encode(['error' => 'Empty request body']);
			return;
		}

		try {
			$response = $this->handleRequest($input);
			echo json_encode($response);
		} catch (Exception $e) {
			http_response_code(500);
			echo json_encode([
				'jsonrpc' => '2.0',
				'error' => [
					'code' => -32603,
					'message' => 'Internal error: ' . $e->getMessage()
				],
				'id' => null
			]);
		}
	}

	/**
	 * Handle capabilities endpoint (for MCP discovery)
	 */
	private function handleCapabilities()
	{
		$capabilities = [
			'protocolVersion' => '2024-11-05',
			'capabilities' => [
				'tools' => array_values($this->tools),
				'resources' => array_values($this->resources),
				'prompts' => array_values($this->prompts)
			],
			'serverInfo' => [
				'name' => 'formulize-mcp-server',
				'version' => FORMULIZE_MCP_VERSION
			],
			'authentication' => [
				'type' => 'Formulize API Keys',
				'discovery_enabled' => false,
			],
			'endpoints' => [
				'mcp' => $this->baseUrl . '/mcp',
				'health' => $this->baseUrl . '/health'
			]
		];

		echo json_encode($capabilities, JSON_PRETTY_PRINT);
	}

	/**
	 * Handle documentation page
	 */
	private function handleDocumentation()
	{
		if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
			http_response_code(405);
			echo json_encode(['error' => 'Method not allowed']);
			return;
		}

		header('Content-Type: text/html; charset=utf-8');

?>
		<!DOCTYPE html>
		<html>

		<head>
			<title>Formulize MCP HTTP Server v<?php echo FORMULIZE_MCP_VERSION; ?></title>
			<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
			<style>
				body {
					font-family: Arial, sans-serif;
					max-width: 800px;
					margin: 0 auto;
					padding: 20px;
				}

				.endpoint {
					background: #f5f5f5;
					padding: 15px;
					margin: 10px 0;
					border-radius: 5px;
				}

				.method {
					color: #007cba;
					font-weight: bold;
				}

				pre {
					background: #eee;
					padding: 10px;
					border-radius: 3px;
					overflow-x: auto;
				}

				.success {
					color: #2e7d32;
				}

				.feature {
					background: #e3f2fd;
					padding: 10px;
					margin: 5px 0;
					border-radius: 3px;
				}
			</style>
		</head>

		<body>
			<h1>Formulize MCP HTTP Server v<?php echo FORMULIZE_MCP_VERSION; ?></h1>
			<p class="success">✅ Featuring Tools, Resources, and Prompts!</p>

			<h2>Endpoints:</h2>

			<div class="endpoint">
				<h3><span class="method">POST</span> /mcp</h3>
				<p>Main MCP endpoint - send JSON-RPC requests like this:</p>
				<pre>curl -X POST <?php echo $this->baseUrl; ?>/mcp \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_FORMULIZE_API_KEY" \
  -d '{"jsonrpc":"2.0","method":"tools/list","params":{},"id":1}'</pre>
			</div>

			<div class="endpoint">
				<h3><span class="method">GET</span> /capabilities</h3>
				<p>MCP server capabilities and authentication info</p>
				<p><a href="<?php echo $this->baseUrl; ?>/capabilities">View capabilities</a></p>
			</div>

			<div class="endpoint">
				<h3><span class="method">GET</span> /health</h3>
				<p>Health check endpoint</p>
				<p><a href="<?php echo $this->baseUrl; ?>/health">Check health</a></p>
			</div>

			<h2>Authentication:</h2>
			<div class="highlight">
				<p><strong>How to get your API key:</strong></p>
				<ol>
					<li>Login to your Formulize system</li>
					<li>Go to Admin → Manage API Keys</li>
					<li>Create or copy your API key</li>
					<li>Use it as: <code>Authorization: Bearer YOUR_API_KEY</code></li>
				</ol>
			</div>

			<h2>Available Capabilities:</h2>

			<div class="feature">
				<h3>🔧 Tools (<?php echo count($this->tools); ?> available)</h3>
				<ul>
					<?php foreach ($this->tools as $tool): ?>
						<li><strong><?php echo htmlspecialchars($tool['name']); ?></strong> - <?php echo htmlspecialchars($tool['description']); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="feature">
				<h3>📄 Resources (<?php echo count($this->resources); ?> available)</h3>
				<ul>
					<?php foreach ($this->resources as $resource): ?>
						<li><strong><?php echo htmlspecialchars($resource['name']); ?></strong> - <?php echo htmlspecialchars($resource['description']); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="feature">
				<h3>💬 Prompts (<?php echo count($this->prompts); ?> available)</h3>
				<ul>
					<?php foreach ($this->prompts as $prompt): ?>
						<li><strong><?php echo htmlspecialchars($prompt['name']); ?></strong> - <?php echo htmlspecialchars($prompt['description']); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<p><small>Formulize MCP HTTP Server v<?php echo FORMULIZE_MCP_VERSION; ?> | <?php echo date('Y-m-d H:i:s'); ?></small></p>
		</body>

		</html>
<?php
	}

	/**
	 * Handle MCP request (same as before)
	 */
	public function handleRequest($input)
	{
		$request = json_decode($input, true);

		if (!$request) {
			return $this->errorResponse('Invalid JSON input');
		}

		$method = $request['method'] ?? '';
		$params = $request['params'] ?? [];
		$id = $request['id'] ?? null;

		switch ($method) {
			case 'initialize':
				return $this->handleInitialize($params, $id);
			case 'tools/list':
				return $this->handleToolsList($id);
			case 'tools/call':
				return $this->handleToolCall($params, $id);
			case 'resources/list':
				return $this->handleResourcesList($id);
			case 'resources/read':
				return $this->handleResourceRead($params, $id);
			case 'prompts/list':
				return $this->handlePromptsList($id);
			case 'prompts/get':
				return $this->handlePromptGet($params, $id);
			default:
				return $this->errorResponse('Unknown method: ' . $method, -32601, $id);
		}
	}

	/**
	 * Handle initialization request
	 */
	private function handleInitialize($params, $id)
	{
		return [
			'jsonrpc' => '2.0',
			'result' => [
				'protocolVersion' => '2024-11-05',
				'capabilities' => [
					'tools' => [],
					'resources' => [],
					'prompts' => []
				],
				'serverInfo' => [
					'name' => 'formulize-mcp-server',
					'version' => FORMULIZE_MCP_VERSION
				]
			],
			'id' => $id
		];
	}

	/**
	 * Handle tools list request
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
	 * Handle resources list request
	 */
	private function handleResourcesList($id)
	{
		// Re-register resources to ensure fresh data
		$this->registerResources();

		return [
			'jsonrpc' => '2.0',
			'result' => [
				'resources' => array_values($this->resources)
			],
			'id' => $id
		];
	}

	/**
	 * Handle resource read request
	 */
	private function handleResourceRead($params, $id)
	{
		$uri = $params['uri'] ?? '';

		if (!$uri) {
			return $this->errorResponse('Missing required parameter: uri', -32602, $id);
		}

		try {
			$result = $this->readResource($uri);
			return [
				'jsonrpc' => '2.0',
				'result' => [
					'contents' => [
						[
							'uri' => $uri,
							'mimeType' => 'application/json',
							'text' => json_encode($result, JSON_PRETTY_PRINT)
						]
					]
				],
				'id' => $id
			];
		} catch (Exception $e) {
			return $this->errorResponse('Resource read failed: ' . $e->getMessage(), -32603, $id);
		}
	}

	/**
	 * Handle prompts list request
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
	 * Read a resource by URI
	 */
	private function readResource($uri)
	{
		// Parse the URI (formulize://type/subtype/id)
		if (!preg_match('/^formulize:\/\/([^\/]+)\/([^\/]+)(?:\/(.+))?$/', $uri, $matches)) {
			throw new Exception('Invalid resource URI format');
		}

		$type = $matches[1];
		$subtype = $matches[2];
		$resourceId = $matches[3] ?? null;

		switch ($type) {
			case 'form':
				if ($subtype === 'schema') {
					return $this->getFormSchema($resourceId);
				} elseif ($subtype === 'data') {
					return $this->getFormData($resourceId);
				}
				break;

			case 'system':
				if ($subtype === 'info') {
					return $this->getSystemInfo();
				} elseif ($subtype === 'user_groups') {
					return $this->getUserGroups();
				} elseif ($subtype === 'relationships') {
					return $this->getFormRelationships();
				}
				break;
		}

		throw new Exception('Unknown resource type: ' . $uri);
	}

	/**
	 * Get form schema
	 */
	private function getFormSchema($formId)
	{
		// Get form details
		$formSql = "SELECT * FROM " . $this->db->prefix('formulize_id') . " WHERE id_form = " . intval($formId);
		$formResult = $this->db->query($formSql);
		$formData = $this->db->fetchArray($formResult);

		if (!$formData) {
			throw new Exception('Form not found');
		}

		// Get form elements
		$elementsSql = "SELECT * FROM " . $this->db->prefix('formulize') . " WHERE id_form = " . intval($formId) . " ORDER BY ele_order";
		$elementsResult = $this->db->query($elementsSql);

		$elements = [];
		while ($row = $this->db->fetchArray($elementsResult)) {
			$elements[] = $row;
		}

		// count entries
		$entryCountSql = "SELECT COUNT(*) as count FROM " . $this->db->prefix('formulize_' . $formData['form_handle']);
		$entryCountResult = $this->db->query($entryCountSql);
		$entryCount = $this->db->fetchArray($entryCountResult)['count'];

		return [
			'form' => $formData,
			'elements' => $elements,
			'element_count' => count($elements),
			'data_table' => $this->db->prefix('formulize_' . $formData['form_handle']),
			'entry_count' => $entryCount,
		];
	}

	/**
	 * Get form data
	 */
	private function getFormData($formId)
	{
		global $xoopsUser;

		// Use gatherDataset with proper permissions
		$scope = buildScope('all', $xoopsUser, $formId);

		$dataset = gatherDataset(
			$formId,
			array(), // all elements
			'', // no filter
			'AND',
			$scope[0],
			null,
			100, // limit to 100 entries for resource
			'',
			'DESC'
		);

		return [
			'form_id' => $formId,
			'entry_count' => count($dataset),
			'entries' => $dataset,
			'limited_to' => 100,
			'scope' => $scope[0]
		];
	}

	/**
	 * Get system information
	 */
	private function getSystemInfo()
	{
		global $xoopsConfig;

		// Count forms
		$formCountSql = "SELECT COUNT(*) as count FROM " . $this->db->prefix('formulize_id');
		$formCountResult = $this->db->query($formCountSql);
		$formCount = $this->db->fetchArray($formCountResult)['count'];

		// Count users
		$userCountSql = "SELECT COUNT(*) as count FROM " . $this->db->prefix('users');
		$userCountResult = $this->db->query($userCountSql);
		$userCount = $this->db->fetchArray($userCountResult)['count'];

		$module_handler = xoops_gethandler('module');
		$formulizeModule = $module_handler->getByDirname("formulize");
		$metadata = $formulizeModule->getInfo();

		// server time zone is used by DB, so NOW() returns actual server time.
		// PHP is set to UTC
		$timeSQL = "SELECT NOW() as server_time";
		$timeResult = $this->db->query($timeSQL);
		$timeRow = $this->db->fetchArray($timeResult);

		return [
			'site_name' => $xoopsConfig['sitename'] ?? 'Unknown',
			'formulize_version' => $metadata['version'] ?? 'Unknown',
			'php_version' => PHP_VERSION,
			'form_count' => $formCount,
			'user_count' => $userCount,
			'server_time' => $timeRow['server_time'] ?? 'Unknown'
		];
	}

	/**
	 * Get users and groups
	 */
	private function getUserGroups()
	{
		// Get groups
		$groupsSql = "SELECT groupid, name, description FROM " . $this->db->prefix('groups') . " ORDER BY name";
		$groupsResult = $this->db->query($groupsSql);

		$groups = [];
		while ($row = $this->db->fetchArray($groupsResult)) {
			$groups[] = $row;
		}

		// Get users (limited to 100)
		$usersSql = "SELECT uid, uname, name, email FROM " . $this->db->prefix('users') . " WHERE uid > 0 ORDER BY uname LIMIT 100";
		$usersResult = $this->db->query($usersSql);

		$users = [];
		while ($row = $this->db->fetchArray($usersResult)) {
			$users[] = $row;
		}

		return [
			'groups' => $groups,
			'group_count' => count($groups),
			'users' => $users,
			'user_count' => count($users),
			'users_limited_to' => 100
		];
	}

	/**
	 * Get form relationships
	 */
	private function getFormRelationships()
	{
		// Get frameworks
		$frameworksSql = "SELECT * FROM " . $this->db->prefix('formulize_frameworks') . " ORDER BY fr_name";
		$frameworksResult = $this->db->query($frameworksSql);

		$frameworks = [];
		while ($row = $this->db->fetchArray($frameworksResult)) {
			$frameworks[] = $row;
		}

		// Get framework links
		$linksSql = "SELECT * FROM " . $this->db->prefix('formulize_framework_links');
		$linksResult = $this->db->query($linksSql);

		$links = [];
		while ($row = $this->db->fetchArray($linksResult)) {
			$links[] = $row;
		}

		return [
			'frameworks' => $frameworks,
			'framework_count' => count($frameworks),
			'links' => $links,
			'link_count' => count($links)
		];
	}

	/**
	 * Generate prompt messages
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

	/**
	 * Handle tool call request
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
	 */
	private function executeTool($toolName, $arguments)
	{
		switch ($toolName) {
			case 'test_connection':
				return $this->testConnection();
			case 'list_forms':
				return $this->listForms($arguments);
			case 'get_form_details':
				return $this->getFormDetails($arguments);
			case 'get_form_elements':
				return $this->getFormElements($arguments);
			case 'create_entry':
    		return $this->writeFormEntry(intval($arguments['form_id']), 'new', $arguments['data'] ?? [], intval($arguments['relationship_id'] ?? -1));
			case 'update_entry':
    		return $this->writeFormEntry(intval($arguments['form_id']), intval($arguments['entry_id']), $arguments['data'] ?? [], intval($arguments['relationship_id'] ?? -1));
			case 'gatherDataset':
				return $this->getEntriesFromForm($arguments);
			default:
				throw new Exception('Tool not implemented: ' . $toolName);
		}
	}

	/**
	 * Write entry data to a form (used by both create and update tools)
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
	 * Gather dataset using Formulize's built-in function with proper permission scoping
	 */
	private function getEntriesFromForm($args)
	{

		global $xoopsUser;

		$fid = intval($args['fid']);
		$elementHandles = $args['elementHandles'] ?? array();
		$filter = $args['filter'] ?? '';
		$andOr = $args['andOr'] ?? 'AND';
		$currentView = $args['currentView'] ?? 'all';
		$limitStart = $args['limitStart'] ?? null;
		$limitSize = $args['limitSize'] ?? null;
		$sortField = $args['sortField'] ?? '';
		$sortOrder = $args['sortOrder'] ?? 'ASC';
		$frid = intval($args['frid'] ?? -1);

		try {
			// Build scope based on authenticated user and their permissions
			$scope = buildScope($currentView, $xoopsUser, $fid);

			// The buildScope function returns an array with [scope, actualCurrentView]
			$actualScope = $scope[0];
			$actualCurrentView = $scope[1];

			// Call Formulize's gatherDataset function with all parameters
			$dataset = gatherDataset(
				$fid,
				$elementHandles,
				$filter,
				$andOr,
				$actualScope,
				$limitStart,
				$limitSize,
				$sortField,
				$sortOrder,
				$frid
			);

			return [
				'fid' => $fid,
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
					'frid' => $frid
				]
			];
		} catch (Exception $e) {
			return [
				'error' => 'gatherDataset execution failed: ' . $e->getMessage(),
				'fid' => $fid,
				'requested_scope' => $currentView
			];
		}
	}

	/**
	 * List all forms
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
			$forms[] = $row;
			$formTitles[] = trans($row['desc_form']);
		}

		array_multisort($formTitles, SORT_NATURAL, $forms);

		return [
			'forms' => $forms,
			'total_count' => count($forms)
		];
	}

	/**
	 * Get form details
	 */
	private function getFormDetails($args)
	{
		$formId = $args['form_id'];

		$sql = "SELECT * FROM " . $this->db->prefix('formulize_id') . " WHERE id_form = " . intval($formId);
		$result = $this->db->query($sql);

		if (!$result) {
			return ['error' => 'Query failed', 'sql' => $sql];
		}

		$formData = $this->db->fetchArray($result);

		if (!$formData) {
			return ['error' => 'Form not found', 'form_id' => $formId];
		}

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
			'form_data' => $formData,
			'elements' => $elements,
			'total_elements' => count($elements)
		];
	}

	/**
	 * Get form elements
	 */
	private function getFormElements($args)
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

	private function getDefaultConfig()
	{
		return [
			'debug' => false,
			'max_results' => 100
		];
	}

	private function errorResponse($message, $code = -32603, $id = null)
	{
		return [
			'jsonrpc' => '2.0',
			'error' => [
				'code' => $code,
				'message' => $message
			],
			'id' => $id
		];
	}
}

// Handle the HTTP request with proper Formulize authentication
try {
	$server = new FormulizeMCP();
	if ($server->enabled) {
		$server->handleHTTPRequest();
	} elseif ($server->canBeEnabled) {
		// if the MCP server passed the canBeEnabled check, but is not enabled, return a 200 OK response with a JSON payload indicating that the server is not enabled
		header('Content-Type: application/json; charset=utf-8');
		header('Cache-Control: no-cache, no-store, must-revalidate');
		http_response_code(200);
		echo json_encode([
			'status' => 'canBeEnabled',
			'message' => 'MCP Server can be enabled',
			'code' => 200,
			'timestamp' => date('Y-m-d H:i:s')
		]);
	} else {
		// If the MCP server is disabled, return a 503 Service Unavailable response
		header('Content-Type: application/json; charset=utf-8');
		header('Cache-Control: no-cache, no-store, must-revalidate');
		http_response_code(503);
		echo json_encode([
			'error' => 'MCP Server is disabled',
			'message' => 'Please enable the MCP Server in the Formulize settings.',
			'code' => 503,
			'timestamp' => date('Y-m-d H:i:s')
		]);
	}
} catch (Exception $e) {
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-cache, no-store, must-revalidate');
	http_response_code(500);
	echo json_encode([
		'error' => 'Server initialization failed: ' . $e->getMessage(),
		'timestamp' => date('Y-m-d H:i:s')
	]);
}
