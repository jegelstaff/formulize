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

class FormulizeMCP
{

	use tools;
	use resources;
	use prompts;

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
		$input = file_get_contents('php://input');
		$request = !empty($input) ? json_decode($input, true) : '';
		$this->mcpRequest = [
			'id' => $request['id'] ?? null,
			'method' => $request['method'] ?? '',
			'params' => $request['params'] ?? [],
			'localServerName' => strtolower(FormulizeObject::sanitize_handle_name($request['localServerDetails']['name'] ?? 'formulize'))
		];
		$this->config = $config ?: $this->getDefaultConfig();
		$this->initializeDatabase();
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
	 * Get details about the authenticated user
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
	 * Get default configuration settings
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
	 */
	private function authenticateRequest()
	{
		$path = $_SERVER['REQUEST_URI'];
		$method = $_SERVER['REQUEST_METHOD'];

		// Handle CORS preflight
		if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
			http_response_code(204);
			exit;
		}

		// Allow unauthenticated GET requests for documentation and health
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
			$this->sendAuthError('Missing Authorization header'); // method will exit, actually
			return false;
		}

		// Extract API key from "Bearer {api_key}" format
		if (!preg_match('/Bearer\s+(.+)/', $authHeader, $matches) AND !$allowUnauthenticatedRequests) {
			$this->sendAuthError('Invalid Authorization header format. Use: Bearer {api_key}'); // method will exit, actually
			return false;
		}

		$key = trim($matches[1]);

		// Validate API key using Formulize's exact system
		if (!$this->validateFormulizeApiKey($key) AND !$allowUnauthenticatedRequests) {
			$this->sendAuthError('Invalid or expired API key'); // method will exit, actually
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
	private function sendAuthError($message, $code = 401)
	{
		$this->setNoCacheHeaders();
		http_response_code($code);
		echo json_encode([
			'error' => [
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

		// Authenticate request
		if (!$this->authenticateRequest()) {
			return; // Authentication error already sent
		}

		$this->registerTools();
		$this->registerResources();
		$this->registerPrompts();

		$path = $_SERVER['REQUEST_URI'];
		$pathParts = explode('?', $path);
		$cleanPath = rtrim($pathParts[0], '/');

		writeToFormulizeLog([
			'formulize_event' => 'mcp-request-being-handled',
			'user_id' => $this->authenticatedUid,
			'mcp_params' => json_encode($this->mcpRequest['params'])
		]);

		try {
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
		} catch (Exception $e) {
			http_response_code(500);
			echo json_encode([
				'jsonrpc' => '2.0',
				'error' => [
					'code' => -32603,
					'message' => 'Internal error: ' . $e->getMessage()
				],
				'id' => $this->mcpRequest['id']
			]);
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
				'serverInfo' => $this->system_info(),
				'instructions' => $this->getInitializeInstructions()
			],
			'id' => $id
		];
	}

	/**
	 * Gathers the initialize instructions
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
	 * Enhanced health check with Formulize auth info
	 */
	private function handleHealthCheck()
	{
		// Test database connection
		$testQuery = "SELECT 1 as test";
		$result = $this->db->query($testQuery);
		if (!$result) {
			throw new Exception('Database query failed');
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

		echo json_encode($health, JSON_PRETTY_PRINT);

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
		$response = $this->handleMCPRequest();
		echo json_encode($response);
	}

	/**
	 * Handle capabilities endpoint (for MCP discovery)
	 */
	private function handleCapabilities()
	{
		$module_handler = xoops_gethandler('module');
		$formulizeModule = $module_handler->getByDirname("formulize");
		$metadata = $formulizeModule->getInfo();

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
	 * Check if the user is a webmaster and return an error response if they are not
	 * Called by items that are only accessible to webmasters
	 * @param string itemName - a string identifying the thing we're verifying them for, typically the name of the tool function or resource function, etc
	 * @return void
	 */
	private function verifyUserIsWebmaster($itemName) {
		if(!in_array(XOOPS_GROUP_ADMIN, $this->userGroups)) {
			$this->sendAuthError("Permission denied: Only webmasters can access $itemName.", 403);
		}
	}

	/**
	 * Get a list of groups for which the authenticated user has access to entries those users have made
	 * This means the group is either one they have groupscope on in relation to a form they have access to
	 * or they have globalscope on a particular form, in which case it's everyone who has view_form.
	 * Then, after all that, have to check who can add_own_entry because the premise is that users would
	 * know of this user's existence through the system, potentially. Users who can view a form, but can't make
	 * entries would be lurking and the authenticated user would never know who they are.
	 * @return array The groupids that the authenticated user can see entries from
	 */
	private function groupsAuthenticatedUserCanSeeDataFrom() {
		$forms = $this->list_forms();
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

	/**
	 * Handle MCP request (same as before)
	 */
	public function handleMCPRequest()
	{

		$method = $this->mcpRequest['method'];
		$params = $this->mcpRequest['params'];
		$id = $this->mcpRequest['id'];

		if (!$method) {
			return $this->JSONerrorResponse('Invalid JSON input', -32600);
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
				return $this->JSONerrorResponse('Unknown method: ' . $method, -32601, $id);
		}
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
