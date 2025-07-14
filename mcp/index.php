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
include_once XOOPS_ROOT_PATH . '/mcp/tools.php';
include_once XOOPS_ROOT_PATH . '/mcp/resources.php';
include_once XOOPS_ROOT_PATH . '/mcp/prompts.php';

class FormulizeMCPException extends Exception
{
		private string $timestamp;
		private string $type;
		private array $context;

    public function __construct(string $message, string $type, array $context = [], int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->type = $type;
				$this->timestamp = date('Y-m-d H:i:s');
				$this->context = $context;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

		public function getType(): string
		{
			return $this->type;
		}

		public function getContext(): array
		{
			return $this->context;
		}
}

class FormulizeMCP
{
	use tools;
	use resources;
	use prompts;

	public $exceptionTypeToHTTPStatusCode = [
		'preflight_success' => 204,
		'authentication_error' => 401,
		'method_not_allowed' => 405,
		'database_error' => 500,
		'missing_method' => 500,
		'method_not_found' => 404,
		'server_disabled' => 503,
	];

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
	private $mcpRequest = array();

	public function __construct($config = null)
	{
		// Front load
		$this->db = $this->getFormulizeDatabase();
		$authHeader = $this->getAuthorizationHeader();

		if ($authHeader == 'Bearer test-header-passthrough-check') {
			$this->canBeEnabled = true;
		}

		if (isMCPServerEnabled()) {
			$this->enabled = true;
			$this->canBeEnabled = true;
		}

		// Get the raw http request contents
		$input = file_get_contents('php://input');
		// @todo should we throw if the input is empty?
		$request = !empty($input) ? json_decode($input, true) : '';
		$this->mcpRequest = [
			'id' => $request['id'] ?? null,
			'method' => $request['method'] ?? '',
			'params' => $request['params'] ?? [],
			'localServerName' => strtolower(FormulizeObject::sanitize_handle_name($request['localServerDetails']['name'] ?? 'formulize'))
		];
		$this->config = $config ?: $this->getDefaultConfig();
		$this->baseUrl = $this->getBaseUrl();
	}

	/**
	 * Send an HTTP response with headers and body
	 *
	 * @param array $body The response body, either JSON or HTML
	 * @param int $httpResponseCode The HTTP response code to send (default 200)
	 * @param array $headers Additional headers to set (default empty array)
	 * @param bool $isJSON Whether the body is JSON (default true)
	 * @return void
	 */
	public function sendResponse(array $body, int $httpResponseCode = 200, array $headers = [], bool $isJSON = true) {
		// Iterate throug the headers and set them
		foreach ($headers as $header) {
			header($header);
		}
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

		// Set the HTTP response code
		http_response_code($httpResponseCode);

		// Set the content type to JSON
		if ($isJSON) {
			header('Content-Type: application/json; charset=utf-8');
			// Encode the body as JSON
			echo json_encode($body, JSON_PRETTY_PRINT);
		} else {
			// If not JSON assume HTML
			header('Content-Type: text/html; charset=utf-8');
			echo $body;
		}
	}

	/**
	 * Get the base URL for this server
	 *
	 * @return string The base URL for the server
	 */
	private function getBaseUrl()
	{
		$scheme = $_SERVER['REQUEST_SCHEME'] ?? (($_SERVER['HTTPS'] ?? 'off') === 'on' ? 'https' : 'http');
		$host = $_SERVER['HTTP_HOST'];
		$script = $_SERVER['SCRIPT_NAME'];
		return $scheme . '://' . $host . $script;
	}

	/**
	 * Get details about the authenticated user
	 *
	 * @return array|false An array with user details or false if not authenticated
	 */
	private function getAuthenticatedUserDetails() {
		$details = false;
		if($this->authenticatedUser) {
			$details = [
				'uid' => $this->authenticatedUser->getVar('uid'),
				'username' => $this->authenticatedUser->getVar('login_name'),
				'full_name' => $this->authenticatedUser->getVar('uname'),
				'timezone' => $this->authenticatedUser->getVar('timezone_offset'),
				'local_time' => date('Y-m-d H:i:s', time() + formulize_getUserUTCOffsetSecs()),
				'email' => $this->authenticatedUser->getVar('email'),
				'groups' => $this->userGroups
			];
		}
		return $details;
	}

	/**
	 * Get the initialized Formulize database
	 *
	 * @return XoopsDatabase The initialized Formulize database connection
	 */
	public function getFormulizeDatabase()
	{
		global $xoopsDB;
		if (!$xoopsDB) {
			throw new Exception('Formulize database connection not available');
		}
		return $xoopsDB;
	}

	/**
	 * Get default configuration settings
	 *
	 * @return array Default configuration settings
	 */
	private function getDefaultConfig()
	{
		return [
			'debug' => false,
			'max_results' => 100
		];
	}

	/**
	 * Authenticate using Formulize's existing API key system
	 *
	 * @return bool True if authentication is successful, throws FormulizeMCPException on failure
	 */
	private function authenticateRequest(string $path, string $method)
	{
		// Handle CORS preflight
		if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
			throw new FormulizeMCPException(
				'Preflight request successful',
				'preflight_success',
			);
		}

		// Only authenticate requests for mcp
		$allowUnauthenticatedRequests = false;
		if ($method === 'GET' && (
			substr($path, -7) === '/health' ||
			substr($path, -13) === '/capabilities' ||
			substr($path, -4) === '/docs' ||
			substr($path, -4) !== '/mcp')
		) {
			$allowUnauthenticatedRequests = true;
		}

		// Check for Authorization header
		$authHeader = $this->getAuthorizationHeader();

		if (empty($authHeader) AND !$allowUnauthenticatedRequests) {
			throw new FormulizeMCPException(
				'Missing Authorization header',
				'authentication_error',
			);
		}

		// Extract API key from "Bearer {api_key}" format
		if (!preg_match('/Bearer\s+(.+)/', $authHeader, $matches) AND !$allowUnauthenticatedRequests) {
			throw new FormulizeMCPException(
				'Invalid Authorization header format. Use: Bearer {api_key}',
				'authentication_error',
			);
		}

		// Isolate the key
		$key = trim($matches[1]);

		if (!$allowUnauthenticatedRequests) {
			$uid = $this->getUidFromAPIKey($key);
			if ($uid === false) {
				throw new FormulizeMCPException(
					'Invalid or expired API key',
					'authentication_error',
				);
			}
			$member_handler = xoops_gethandler('member');
			if ($uidObject = $member_handler->getUser($uid)) {
				$this->userGroups = $uidObject->getGroups();
				$this->authenticatedUser = $uidObject;

				// Set global user context as Formulize does
				$xoopsUser = $uidObject;
				$icmsUser = $uidObject;
			} else {
				$this->authenticatedUser = null;
				$this->authenticatedUid = 0;
				$this->userGroups = array(XOOPS_GROUP_ANONYMOUS);
				return false;
			}
		}

		return true;
	}

	/**
	 * Get Authorization header across different server configurations
	 *
	 * @return string The Authorization header value or an empty string if not set
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
	 * Get the User ID from the API key
	 *
	 * @param string $key The API key to validate
	 * @return string|false The user ID if the key is valid, false otherwise
	 */
	private function getUidFromAPIKey($key)
	{
		global $xoopsUser, $icmsUser;

		// Get the API key handler exactly as Formulize does
		$apiKeyHandler = xoops_getmodulehandler('apikey', 'formulize');

		// Clear out expired keys
		$apiKeyHandler->delete();

		$this->authenticatedUid = 0;

		if ($key && $apikey = $apiKeyHandler->get($key)) {
			return $apikey->getVar('uid');
		}
		return false;
	}

	/**
	 * Handle HTTP request routing with authentication
	 *
	 * @return void
	 */
	public function handleHTTPRequest()
	{
	  $path = $_SERVER['REQUEST_URI'];
		$method = $_SERVER['REQUEST_METHOD'];

		// Authenticate request
		if (!$this->authenticateRequest($path, $method)) {
			return; // Authentication error already sent
		}

		// @todo could this be part of the constructor?
		$this->registerTools();
		$this->registerResources();
		$this->registerPrompts();

		$pathParts = explode('?', $path);
		$cleanPath = rtrim($pathParts[0], '/');
		$content = [];

		writeToFormulizeLog([
			'formulize_event' => 'mcp-request-being-handled',
			'user_id' => $this->authenticatedUid,
			'mcp_params' => json_encode($this->mcpRequest['params'])
		]);

		try {
			// Route based on path - match end of line
			if (preg_match('/\/health$/', $cleanPath)) {
				$content = $this->handleHealthCheck();
			} elseif (preg_match('/\/capabilities$/', $cleanPath)) {
				$content = $this->handleCapabilities();
			} elseif (preg_match('/\/mcp$/', $cleanPath)) {
				$content = $this->handleMCPEndpoint($this->mcpRequest, $method);
			} else {
				$content = $this->handleDocumentation();
			}
			$this->sendResponse($content);
		} catch (FormulizeMCPException $e) {
			$this->sendResponse([
				'jsonrpc' => '2.0',
				'error' => $e->getContext(),
				'id' => $this->mcpRequest['id']
			], $exceptionTypeToHTTPStatusCode[$e->getType()] ?? 500);
		}
	}

	/**
	 * Handle initialization request
	 *
	 * @return array The response for the initialization request
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
				'serverInfo' => $this->system_info(),
				'instructions' => $this->getInitializeInstructions()
			],
			'id' => $id
		];
	}

	/**
	 * Gathers the initialize instructions
	 *
	 * @return string The instructions for initializing the MCP server
	 */
	private function getInitializeInstructions() {
		$systemSepecificInstructions = '';
		$config_handler = xoops_gethandler('config');
		$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
		if($systemSepecificInstructions = $formulizeConfig['system_specific_instructions'] ?? '') {
			$breaks = array("\r\n", "\n", "\r");
			$systemSepecificInstructions = "**Details about this system:** The administrators of this particular Formulize system have provided this information about what it is used for: ". str_replace($breaks, '', trim($systemSepecificInstructions));
		}
		return "**About this server:** This is a Formulize MCP server. Formulize is an open source data management system based on forms. Each Formulize instance is an independent web application with its own URL. **Users and Permissions:** The users of the system are organized into groups. Each user can be a member of multiple groups. Each group has its own permissions for interacting with each form and the entries that have been made in it. **Authentication** You connect to this server as an authenticated user, because all requests automatically include a pre-defined API key. All operations on this server automatically respect the authenticated user's permissions and group memberships. **Form structure and connections:** Forms have one or more elements, such as textboxes, checkboxes, dropdown lists, etc. Forms can be connected together to make complex workflows. **Screens:** Each form can have multiple screens based on it. A screen is a way of presenting the form, or the entries that have been made in the form. The two main kinds of screens are 'list screens' (showing lists of entries), and 'form screens' (showing the form's elements across one or more pages). If a form has connections to other forms, that form's screens will have configuration options related to the connected forms. **Applications:** Forms can be collected together into applications as an organizing principle, but any form in the system can be connected to and work with any other form, regardless of application. $systemSepecificInstructions **Next step hint:** Use the tool called list_forms to get a basic overview of this particular Formulize system.";
	}

	/**
	 * Enhanced health check with Formulize auth info
	 *
	 * @return array Health check details including database connection, API keys, and system info
	 * @throws FormulizeMCPException if the database query fails
	 */
	private function handleHealthCheck()
	{
		// Test database connection
		$testQuery = "SELECT 1 as test";
		$result = $this->db->query($testQuery);
		if (!$result) {
			throw new FormulizeMCPException('Database query failed', 'database_error');
		}
		// Count active keys
		$apiKeyCount = 0;
		if($dbConnected = $this->db->fetchArray($result)) {
			$countSql = "SELECT COUNT(*) as count FROM " . $this->db->prefix('formulize_apikeys') . " WHERE expiry IS NULL OR expiry > NOW()";
			$countResult = $this->db->query($countSql);
			if ($countResult) {
				$countRow = $this->db->fetchArray($countResult);
				$apiKeyCount = $countRow['count'];
			}
		}

		$health = [
			'status' => 'healthy',
			'database_connected' => $dbConnected ? 'true' : 'false',
			'mcp_server' => 'direct_http_with_formulize_api_keys',
			'system_info' => $this->system_info(),
			'tools_count' => count($this->tools),
			'resources_count' => count($this->resources),
			'prompts_count' => count($this->prompts),
			'authentication' => [
				'system' => 'formulize_api_keys',
				'active_keys_count' => $apiKeyCount
			],
			'endpoints' => [
				'mcp' => $this->baseUrl . '/mcp',
				'capabilities' => $this->baseUrl . '/capabilities',
				'health' => $this->baseUrl . '/health'
			]
		];

		return $health;
	}

	/**
	 * Handle MCP endpoint (/mcp)
	 *
	 * @return array The response from the MCP request
	 * @throws FormulizeMCPException if the request method is not POST
	 */
	private function handleMCPEndpoint(array $mcpRequest, string $requestMethod)
	{
		$params = $mcpRequest['params'] ?? [];
		$id = $mcpRequest['id'] ?? null;
		$method = $mcpRequest['method'] ?? null;

		if ($requestMethod !== 'POST') {
			throw new FormulizeMCPException(
				'Method not allowed. Use POST for MCP requests.',
				'method_not_allowed'
			);
		}

		if (!$method) {
			throw new FormulizeMCPException(
				'Invalid JSON input',
				'missing_method',
				$this->JSONerrorResponse('Invalid JSON input', -32600)
			);
		}

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
				throw new FormulizeMCPException(
					'Unknown method: ' . $method,
					'method_not_found',
					$this->JSONerrorResponse('Unknown method: ' . $method, -32601, $id)
				);
		}
	}

	/**
	 * Handle capabilities endpoint (for MCP discovery)
	 */
	private function handleCapabilities()
	{
		$module_handler = xoops_gethandler('module');
		$formulizeModule = $module_handler->getByDirname("formulize");

		$capabilities = [
			'capabilities' => [
				'tools' => array_values($this->tools),
				'resources' => array_values($this->resources),
				'prompts' => array_values($this->prompts)
			],
			'serverInfo' => $this->system_info(),
			'authentication' => [
				'type' => 'Formulize API Keys',
				'discovery_enabled' => false,
			],
			'endpoints' => [
				'mcp' => $this->baseUrl . '/mcp',
				'health' => $this->baseUrl . '/health',
				'capabilities' => $this->baseUrl . '/capabilities'
			]
		];
		return $capabilities;
	}

	/**
	 * Handle documentation page
	 *
	 * @return string HTML content for the documentation page
	 * @throws FormulizeMCPException if the request method is not GET
	 */
	private function handleDocumentation()
	{
		if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
			throw new FormulizeMCPException(
				'Method not allowed. Use GET for documentation.',
				'method_not_allowed',
			);
		}

		$html = '
		<!DOCTYPE html>
		<html>
			<head>
				<title>Formulize MCP HTTP Server v' . FORMULIZE_MCP_VERSION . '</title>
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
				<h1>Formulize MCP HTTP Server v' . FORMULIZE_MCP_VERSION . '</h1>
				<p class="success">✅ Featuring Tools, Resources, and Prompts!</p>

				<h2>Endpoints:</h2>

				<div class="endpoint">
					<h3><span class="method">POST</span> /mcp</h3>
					<p>Main MCP endpoint - send JSON-RPC requests like this:</p>
					<pre>curl -X POST ' . $this->baseUrl . '/mcp \
				-H "Content-Type: application/json" \
				-H "Authorization: Bearer YOUR_FORMULIZE_API_KEY" \
				-d \'{"jsonrpc":"2.0","method":"tools/list","params":{},"id":1}\'</pre>
				</div>

				<div class="endpoint">
					<h3><span class="method">GET</span> /capabilities</h3>
					<p>MCP server capabilities and authentication info</p>
					<p><a href="' . $this->baseUrl . '/capabilities">View capabilities</a></p>
				</div>

				<div class="endpoint">
					<h3><span class="method">GET</span> /health</h3>
					<p>Health check endpoint</p>
					<p><a href="' . $this->baseUrl . '/health">Check health</a></p>
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
					<h3>🔧 Tools (' . count($this->tools) . ' available)</h3>
					<ul>';

			foreach ($this->tools as $tool) {
				$html .= '<li><strong>' . htmlspecialchars($tool['name']) . '</strong> - ' . htmlspecialchars($tool['description']) . '</li>';
			}

			$html .= '		</ul>
				</div>

				<div class="feature">
					<h3>📄 Resources (' . count($this->resources) . ' available)</h3>
					<ul>';

			foreach ($this->resources as $resource) {
				$html .= '<li><strong>' . htmlspecialchars($resource['name']) . '</strong> - ' . htmlspecialchars($resource['description']) . '</li>';
			}

			$html .= '		</ul>
				</div>

				<div class="feature">
					<h3>💬 Prompts (' . count($this->prompts) . ' available)</h3>
					<ul>';

			foreach ($this->prompts as $prompt) {
				$html .= '<li><strong>' . htmlspecialchars($prompt['name']) . '</strong> - ' . htmlspecialchars($prompt['description']) . '</li>';
			}

			$html .= '		</ul>
				</div>

				<p><small>Formulize MCP HTTP Server v' . FORMULIZE_MCP_VERSION . ' | ' . date('Y-m-d H:i:s') . '</small></p>
			</body>

		</html>';
		return $html;
	}

	/**
	 * Check if the user is a webmaster and return an error response if they are not
	 * Called by items that are only accessible to webmasters
	 * @param string itemName - a string identifying the thing we're verifying them for, typically the name of the tool function or resource function, etc
	 * @return void
	 */
	private function verifyUserIsWebmaster($itemName) {
		// @todo this really shouldn't be a method and we should use the boolean based check isUserAWebmaster() instead
		if(!in_array(XOOPS_GROUP_ADMIN, $this->userGroups)) {
			throw new FormulizeMCPException(
				"Permission denied: Only webmasters can access $itemName.",
				'authentication_error',
			);
		}
	}

	/**
	 * Check if the user is a webmaster
	 *
	 * @return bool True if the user is a webmaster, false otherwise
	 */
	private function isUserAWebmaster() {
		if(!in_array(XOOPS_GROUP_ADMIN, $this->userGroups)) {
			return false;
		}
		return true;
	}


	/**
	 * Return an array of metadata fields, all keyed with 'element_handle' so that we can start the elements lists that way when appropriate
	 *
	 * @return array The array of metadata fields found in the database (creator_email and owner_groups are removed)
	 */
	private function metadataFields() {
		$dataHandler = new formulizeDataHandler();
		$metadataFields = array();
		foreach($dataHandler->metadataFields as $metadataField) {
			if($metadataField != 'creator_email' AND $metadataField != 'owner_groups') {
				$metadataFields[] = array(
					'element_handle' => $metadataField
				);
			}
		}
		return $metadataFields;
	}

	/**
	 * Get a list of groups for which the authenticated user has access to entries those users have made
	 * This means the group is either one they have groupscope on in relation to a form they have access to
	 * or they have globalscope on a particular form, in which case it's everyone who has view_form.
	 * Then, after all that, have to check who can add_own_entry because the premise is that users would
	 * know of this user's existence through the system, potentially. Users who can view a form, but can't make
	 * entries would be lurking and the authenticated user would never know who they are.
	 *
	 * @return array The groupids that the authenticated user can see entries from
	 */
	private function groupsAuthenticatedUserCanSeeDataFrom() {
		$forms = $this->forms_list();
		$gperm_handler = xoops_gethandler('groupperm');
		$groupsTheUserCanSee = array();
		$groupsThatCanMakeEntries = array();
		foreach($forms['forms'] as $form) {
			$groupsTheUserCanSee = array_merge($groupsTheUserCanSee, getGroupScopeGroups($form['id_form']));
			if($gperm_handler->checkRight("view_globalscope", $form['id_form'], $this->userGroups, getFormulizeModId())) {
				$groupsTheUserCanSee = array_merge($groupsTheUserCanSee, $gperm_handler->getGroupIds("view_form", $form['id_form'], getFormulizeModId()));
			}
			$groupsThatCanMakeEntries = array_merge($groupsThatCanMakeEntries, $gperm_handler->getGroupIds('add_own_entry', $form['id_form'], getFormulizeModId()));
		}
		$groupsTheUserCanSee = array_unique($groupsTheUserCanSee);
		$groupsThatCanMakeEntries = array_unique($groupsThatCanMakeEntries);
		return array_intersect($groupsTheUserCanSee, $groupsThatCanMakeEntries);
	}

	private function JSONerrorResponse($message, $code = -32603, $id = null, $context = []) {
    $error = [
        'code' => $code,
        'message' => $message
    ];

    // Add helpful context for common errors
    if (stripos($message, 'Permission denied') !== false) {
        $error['troubleshooting'] = [
            'issue' => 'Insufficient permissions',
            'solutions' => [
                'Check if user has required permission for this form/entry',
                'Use list_forms to see accessible forms',
                'Verify user is member of correct groups'
            ]
        ];
    } elseif (stripos($message, 'Form not found') !== false) {
        $error['troubleshooting'] = [
            'issue' => 'Invalid form ID',
            'solutions' => [
                'Use list_forms tool to get valid form IDs',
                'Verify the form exists and is accessible'
            ]
        ];
    } elseif (stripos($message, 'Invalid element handle') !== false) {
        $error['troubleshooting'] = [
            'issue' => 'Element handle does not exist in form',
            'solutions' => [
                'Use get_form_details tool to get valid element handles',
                'Check spelling of element handle',
                'Verify element is part of the specified form'
            ]
        ];
    }

    if (!empty($context)) {
        $error['context'] = $context;
    }

    return [
        'jsonrpc' => '2.0',
        'error' => $error,
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
		$content = [
			'status' => 'canBeEnabled',
			'message' => 'MCP Server can be enabled',
			'code' => 200,
			'timestamp' => date('Y-m-d H:i:s')
		];
		$server->sendResponse($content);
	} else {
		// If the MCP server is disabled, return a 503 Service Unavailable response
		throw new FormulizeMCPException('MCP Server is disabled', 'server_disabled', [
			'status' => 'disabled',
			'message' => 'MCP Server is currently disabled. Please enable it to use the MCP features.',
		]);
	}
} catch (FormulizeMCPException $e) {
	$server->sendResponse([
		'jsonrpc' => '2.0',
		'error' => [
			'message' => $e->getMessage(),
			'type' => $e->getType(),
			'timestamp' => $e->getTimestamp()
		]
	], $exceptionTypeToHTTPStatusCode[$e->getType()] ?? 500);
}
