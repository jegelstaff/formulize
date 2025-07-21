<?php
/**
 * Formulize MCP HTTP Direct Server with Proper Formulize API Key Authentication
 *
 * Uses Formulize's existing API key system from managekeys.php/apikey.php
 */

// CRITICAL: Disable debug output
icms::$logger->disableLogger();
while (ob_get_level()) {
	ob_end_clean();
}

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';
include_once XOOPS_ROOT_PATH . '/mcp/mcpException.php';
include_once XOOPS_ROOT_PATH . '/mcp/tools.php';
include_once XOOPS_ROOT_PATH . '/mcp/resources.php';
include_once XOOPS_ROOT_PATH . '/mcp/prompts.php';

class FormulizeMCP
{
	use tools;
	use resources;
	use prompts;

	private $config;
	private $db;
	public $enabled = false;
	public $canBeEnabled = false;
	public $tools;
	public $resources;
	public $prompts;
	private $authenticatedUser = null;
	private $authenticatedUid = 0;
	public $userGroups = array();
	public $baseUrl;
	private $mcpRequest = array();

	public function __construct($config = null)
	{
		// Authenticate the request
		$path = $_SERVER['REQUEST_URI'];
		$method = $_SERVER['REQUEST_METHOD'];
		$this->authenticateRequest($path, $method);

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

		// Register tools, resources, and prompts
		$this->registerTools();
		$this->registerResources();
		$this->registerPrompts();
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
	public function sendResponse(array|string $body, int $httpResponseCode = 200, array $headers = [], bool $isJSON = true) {
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
		global $xoopsUser, $icmsUser;
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

		if (!$allowUnauthenticatedRequests || ($key && $key !== 'test-header-passthrough-check')) {
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
				$this->authenticatedUid = $uid;

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

		try {
			$result = [];
			$pathParts = explode('?', $path);
			$cleanPath = rtrim($pathParts[0], '/');

			writeToFormulizeLog([
				'formulize_event' => 'mcp-request-being-handled',
				'user_id' => $this->authenticatedUid,
				'mcp_params' => json_encode($this->mcpRequest['params'])
			]);

			// Route based on path - match end of line
			if (preg_match('/\/health$/', $cleanPath)) {
				$result = $this->handleHealthCheck();
			} elseif (preg_match('/\/capabilities$/', $cleanPath)) {
				$result = $this->handleCapabilities();
			} elseif (preg_match('/\/mcp$/', $cleanPath)) {
				$result = $this->handleMCPEndpoint($this->mcpRequest, $method);
			} else {
				throw new FormulizeMCPException(
					'Invalid endpoint: ' . $cleanPath,
					'method_not_found',
					-32601,
				);
			}

			$response = [
				'jsonrpc' => '2.0',
				'result' => $result,
				'id' => $this->mcpRequest['id']
			];
			$this->sendResponse($response);

		} catch (FormulizeMCPException $e) {
			$this->sendResponse([
				'jsonrpc' => '2.0',
				'error' => $e->toErrorResponse(),
				'id' => $this->mcpRequest['id']
			], $e->toHTTPStatusCode());
		}
	}

	/**
	 * Handle initialization request
	 *
	 * @return array The response for the initialization request
	 */
	private function handleInitialize()
	{
		return [
			'protocolVersion' => '2024-11-05',
			'capabilities' => [
				'tools' => [],
				'resources' => [],
				'prompts' => []
			],
			'serverInfo' => $this->system_info(),
			'instructions' => $this->getInitializeInstructions()
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
				-32600
			);
		}

		switch ($method) {
			case 'initialize':
				return $this->handleInitialize();
			case 'tools/list':
				return $this->handleToolsList();
			case 'tools/call':
				return $this->handleToolCall($params);
			case 'resources/list':
				return $this->handleResourcesList();
			case 'resources/read':
				return $this->handleResourceRead($params);
			case 'prompts/list':
				return $this->handlePromptsList();
			case 'prompts/get':
				return $this->handlePromptGet($params);
			default:
				throw new FormulizeMCPException(
					'Unknown method: ' . $method,
					'method_not_found',
					-32601
				);
		}
	}

	/**
	 * Handle capabilities endpoint (for MCP discovery)
	 */
	private function handleCapabilities()
	{
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
}
