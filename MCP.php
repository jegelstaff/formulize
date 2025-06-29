<?php

/**
 * Enhanced Formulize MCP HTTP Server with OAuth 2.1 Discovery Support
 *
 * Implements proper authentication discovery flow for VSCode MCP extension
 * Based on MCP 2025-03-26 specification and OAuth 2.1 Resource Server patterns
 */

require_once dirname(__FILE__) . '/mainfile.php';

// CRITICAL: Disable debug output
icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';

class FormulizeMCP {

    private $config;
    private $db;
		public $enabled = false;
		public $canBeEnabled = false;
    private $tools;
    private $authenticatedUser = null;
    private $authenticatedUid = 0;
    private $userGroups = array();
    private $baseUrl;

    public function __construct($config = null) {
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
			$this->baseUrl = $this->getBaseUrl();
    }

    /**
     * Get the base URL for this server
     */
    private function getBaseUrl() {
        $scheme = $_SERVER['REQUEST_SCHEME'] ?? (($_SERVER['HTTPS'] ?? 'off') === 'on' ? 'https' : 'http');
        $host = $_SERVER['HTTP_HOST'];
        $script = $_SERVER['SCRIPT_NAME'];
        return $scheme . '://' . $host . $script;
    }

    /**
     * Initialize database connection using Formulize's existing connection
     */
    private function initializeDatabase() {
        global $xoopsDB;
        if (!$xoopsDB) {
            throw new Exception('Formulize database connection not available');
        }
        $this->db = $xoopsDB;
    }

    /**
     * Enhanced authentication with OAuth 2.1 discovery support
     */
    private function authenticateRequest() {
        $path = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];

        // Allow unauthenticated access to discovery endpoints
        if (strpos($path, '/.well-known/') !== false) {
            return true;
        }

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
            $this->sendOAuthChallenge('Missing Authorization header');
            return false;
        }

        // Extract bearer token from "Bearer {token}" format
        if (!preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            $this->sendOAuthChallenge('Invalid Authorization header format. Use: Bearer {token}');
            return false;
        }

        $token = trim($matches[1]);

        // Validate token using Formulize's API key system
        if (!$this->validateBearerToken($token)) {
            $this->sendOAuthChallenge('Invalid or expired bearer token');
            return false;
        }

        return true;
    }

    /**
     * Get Authorization header across different server configurations
     */
    private function getAuthorizationHeader() {
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
     * Validate bearer token using Formulize's API key system
     */
    private function validateBearerToken($token) {
        global $xoopsUser, $icmsUser;

        // Get the API key handler exactly as Formulize does
        $apiKeyHandler = xoops_getmodulehandler('apikey', 'formulize');

        // Clear out expired keys
        $apiKeyHandler->delete();

        $this->authenticatedUid = 0;

        if ($token AND $apikey = $apiKeyHandler->get($token)) {
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
                $this->authenticatedUid = 0;
                $this->userGroups = array(XOOPS_GROUP_ANONYMOUS);
                return false;
            }
        }

        return false;
    }

    /**
     * Send OAuth 2.1 authentication challenge with proper discovery
     */
    private function sendOAuthChallenge($message, $error = null) {
        $this->setNoCacheHeaders();

        $resourceMetadataUrl = $this->baseUrl . '/.well-known/oauth-protected-resource';

        $wwwAuthenticateHeader = 'Bearer realm="formulize-mcp-server"';
        $wwwAuthenticateHeader .= ', resource_metadata="' . $resourceMetadataUrl . '"';

        if ($error) {
            $wwwAuthenticateHeader .= ', error="' . $error . '"';
            $wwwAuthenticateHeader .= ', error_description="' . $message . '"';
        }

        header('WWW-Authenticate: ' . $wwwAuthenticateHeader);
        http_response_code(401);

        echo json_encode([
            'error' => 'unauthorized',
            'message' => $message,
            'code' => 401,
            'type' => 'authentication_required',
            'timestamp' => date('Y-m-d H:i:s'),
            'discovery' => [
                'resource_metadata' => $resourceMetadataUrl,
                'instructions' => 'Use the resource_metadata URL to discover authentication requirements'
            ]
        ], JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Handle OAuth 2.0 Protected Resource Metadata (RFC 9728)
     */
    private function handleProtectedResourceMetadata() {
        $this->setNoCacheHeaders();

        $metadata = [
            'resource' => $this->baseUrl . '/mcp',
            'authorization_servers' => [
                $this->baseUrl  // Self-hosted authorization for API key system
            ],
            'scopes_supported' => [
                'mcp:read',
                'mcp:write',
                'mcp:admin'
            ],
            'bearer_methods_supported' => ['header'],
            'token_formats_supported' => ['opaque'],
            'token_endpoint' => $this->baseUrl . '/oauth/token',
            'token_introspection_endpoint' => $this->baseUrl . '/oauth/introspect',
            'description' => 'Formulize MCP Server - Use your Formulize API key as the bearer token',
            'authentication_methods' => [
                'api_key' => [
                    'description' => 'Use your Formulize API key from the managekeys.php interface',
                    'format' => 'Bearer {your-formulize-api-key}',
                    'location' => 'Authorization header'
                ]
            ]
        ];

        echo json_encode($metadata, JSON_PRETTY_PRINT);
    }

    /**
     * Handle OAuth authorization server metadata for VSCode compatibility
     */
    private function handleAuthorizationServerMetadata() {
        $this->setNoCacheHeaders();

        $metadata = [
            'issuer' => $this->baseUrl,
            'authorization_endpoint' => $this->baseUrl . '/oauth/authorize',
            'token_endpoint' => $this->baseUrl . '/oauth/token',
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'client_credentials'],
            'code_challenge_methods_supported' => ['S256'],
            'scopes_supported' => ['mcp:read', 'mcp:write', 'mcp:admin'],
            'token_endpoint_auth_methods_supported' => ['none', 'client_secret_post'],
            'description' => 'Simplified OAuth endpoint for Formulize API key authentication',
            'notes' => [
                'This server uses Formulize API keys as bearer tokens',
                'No actual OAuth flow needed - just use your API key directly',
                'Get your API key from the Formulize admin interface'
            ]
        ];

        echo json_encode($metadata, JSON_PRETTY_PRINT);
    }

    /**
     * Handle simplified token endpoint (for compatibility)
     */
    private function handleTokenEndpoint() {
        $this->setNoCacheHeaders();
        http_response_code(400);

        echo json_encode([
            'error' => 'unsupported_grant_type',
            'error_description' => 'This server uses Formulize API keys directly as bearer tokens. No token exchange needed.',
            'instructions' => [
                'Get your API key from Formulize admin panel',
                'Use it directly in Authorization header: Bearer {your-api-key}',
                'No OAuth flow required'
            ]
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Handle token introspection endpoint
     */
    private function handleTokenIntrospection() {
        $this->setNoCacheHeaders();

        $token = $_POST['token'] ?? '';
        if (empty($token)) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_request', 'error_description' => 'Missing token parameter']);
            return;
        }

        $isValid = $this->validateBearerToken($token);

        $response = [
            'active' => $isValid
        ];

        if ($isValid && $this->authenticatedUser) {
            $response['scope'] = 'mcp:read mcp:write mcp:admin';
            $response['username'] = $this->authenticatedUser->getVar('uname');
            $response['uid'] = $this->authenticatedUid;
            $response['exp'] = time() + 3600; // 1 hour from now
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    /**
     * Handle HTTP request routing with OAuth discovery
     */
    public function handleHTTPRequest() {
        $this->setNoCacheHeaders();

        // Handle CORS preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        $path = $_SERVER['REQUEST_URI'];
        $pathParts = explode('?', $path);
        $cleanPath = $pathParts[0];

        // OAuth 2.0 discovery endpoints
        if (strpos($cleanPath, '/.well-known/oauth-protected-resource') !== false) {
            $this->handleProtectedResourceMetadata();
            return;
        }

        if (strpos($cleanPath, '/.well-known/oauth-authorization-server') !== false) {
            $this->handleAuthorizationServerMetadata();
            return;
        }

        // OAuth endpoints
        if (strpos($cleanPath, '/oauth/token') !== false) {
            $this->handleTokenEndpoint();
            return;
        }

        if (strpos($cleanPath, '/oauth/introspect') !== false) {
            $this->handleTokenIntrospection();
            return;
        }

        // Authenticate request (will send challenge if needed)
        if (!$this->authenticateRequest()) {
            return; // Authentication challenge already sent
        }

        // Route to existing endpoints
        if (strpos($cleanPath, '/mcp') !== false) {
            $this->handleMCPEndpoint();
        } elseif (strpos($cleanPath, '/health') !== false) {
            $this->handleHealthCheck();
        } elseif (strpos($cleanPath, '/capabilities') !== false) {
            $this->handleCapabilities();
        } else {
            $this->handleDocumentation();
        }
    }

    /**
     * Set comprehensive no-cache headers
     */
    private function setNoCacheHeaders() {
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
     * Register available MCP tools
     */
    private function registerTools() {
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
            'gatherDataset' => [
                'name' => 'gatherDataset',
                'description' => 'Gather a dataset from a form using Formulize\'s built-in gatherDataset function with proper permission scoping',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'fid' => [
                            'type' => 'integer',
                            'description' => 'The ID of the main form in the dataset'
                        ],
                        'elementHandles' => [
                            'type' => 'array',
                            'description' => 'Optional. Array of element handles to include. Use multidimensional array with form IDs as keys',
                            'default' => []
                        ],
                        'filter' => [
                            'type' => ['integer', 'string', 'array'],
                            'description' => 'Optional. Entry ID, filter string, or array of filter strings using /**/ separators',
                            'default' => ''
                        ],
                        'andOr' => [
                            'type' => 'string',
                            'description' => 'Boolean operator between multiple filters (AND or OR)',
                            'default' => 'AND'
                        ],
                        'currentView' => [
                            'type' => 'string',
                            'description' => 'Scope type: "mine", "group", "all", or comma-separated group IDs',
                            'default' => 'all'
                        ],
                        'limitStart' => [
                            'type' => ['integer', 'null'],
                            'description' => 'Starting record for LIMIT statement',
                            'default' => null
                        ],
                        'limitSize' => [
                            'type' => ['integer', 'null'],
                            'description' => 'Number of records to return',
                            'default' => null
                        ],
                        'sortField' => [
                            'type' => 'string',
                            'description' => 'Element handle to sort by',
                            'default' => ''
                        ],
                        'sortOrder' => [
                            'type' => 'string',
                            'description' => 'Sort direction (ASC or DESC)',
                            'default' => 'ASC'
                        ],
                        'frid' => [
                            'type' => 'integer',
                            'description' => 'Relationship ID to use (-1 for Primary Relationship, 0 for no relationship)',
                            'default' => -1
                        ]
                    ],
                    'required' => ['fid']
                ]
            ],
            'search_entries' => [
                'name' => 'search_entries',
                'description' => 'Search for entries in a form based on criteria',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'form_id' => [
                            'type' => 'integer',
                            'description' => 'The ID of the form to search'
                        ],
                        'search_term' => [
                            'type' => 'string',
                            'description' => 'Text to search for in entries'
                        ],
                        'element_handle' => [
                            'type' => 'string',
                            'description' => 'Specific element handle to search within (optional)'
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Maximum number of results',
                            'default' => 20
                        ]
                    ],
                    'required' => ['form_id', 'search_term']
                ]
            ],
            'debug_tables' => [
                'name' => 'debug_tables',
                'description' => 'Debug: List all Formulize-related database tables',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => (object)[]
                ]
            ]
        ];
    }

    /**
     * Enhanced test connection with proper authenticated user info
     */
    private function testConnection() {
        global $xoopsConfig;

        $testQuery = "SELECT 1 as test";
        $result = $this->db->query($testQuery);

        if (!$result) {
            throw new Exception('Database query failed');
        }

        $row = $this->db->fetchArray($result);

        $connectionInfo = [
            'status' => 'success',
            'message' => 'MCP Server connection successful with OAuth-enhanced Formulize authentication',
            'database_test' => $row['test'] == 1 ? 'passed' : 'failed',
            'xoops_config' => [
                'sitename' => $xoopsConfig['sitename'] ?? 'Unknown',
                'version' => XOOPS_VERSION ?? 'Unknown'
            ],
            'server_info' => [
                'php_version' => PHP_VERSION,
                'timestamp' => date('Y-m-d H:i:s'),
                'execution_mode' => 'oauth_enhanced_http_with_formulize_auth',
                'oauth_discovery' => 'enabled'
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
     * Enhanced health check with OAuth discovery info
     */
    private function handleHealthCheck() {
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

            $checkTableSql = "SHOW TABLES LIKE '" . XOOPS_DB_PREFIX . "_formulize_apikeys'";
            $tableResult = $this->db->query($checkTableSql);

            if ($this->db->fetchArray($tableResult)) {
                $apiKeyTableExists = true;

                // Count active keys
                $countSql = "SELECT COUNT(*) as count FROM " . XOOPS_DB_PREFIX . "_formulize_apikeys WHERE expiry IS NULL OR expiry > NOW()";
                $countResult = $this->db->query($countSql);
                if ($countResult) {
                    $countRow = $this->db->fetchArray($countResult);
                    $apiKeyCount = $countRow['count'];
                }
            }

            $health = [
                'status' => 'healthy',
                'database_test' => $row['test'] == 1 ? 'passed' : 'failed',
                'mcp_server' => 'oauth_enhanced_http_with_formulize_api_keys',
                'tools_count' => count($this->tools),
                'authentication' => [
                    'system' => 'oauth_enhanced_formulize_api_keys',
                    'oauth_discovery' => 'enabled',
                    'api_key_table_exists' => $apiKeyTableExists,
                    'active_keys_count' => $apiKeyCount,
                    'table_name' => XOOPS_DB_PREFIX . '_formulize_apikeys'
                ],
                'oauth_endpoints' => [
                    'protected_resource_metadata' => $this->baseUrl . '/.well-known/oauth-protected-resource',
                    'authorization_server_metadata' => $this->baseUrl . '/.well-known/oauth-authorization-server',
                    'token_endpoint' => $this->baseUrl . '/oauth/token',
                    'introspection_endpoint' => $this->baseUrl . '/oauth/introspect'
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
    private function handleMCPEndpoint() {
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
    private function handleCapabilities() {
        $capabilities = [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [
                'tools' => array_values($this->tools),
                'prompts' => [],
                'resources' => []
            ],
            'serverInfo' => [
                'name' => 'formulize-mcp-server-oauth-enhanced',
                'version' => '1.1.0'
            ],
            'authentication' => [
                'type' => 'oauth2_enhanced',
                'discovery_enabled' => true,
                'endpoints' => [
                    'protected_resource_metadata' => $this->baseUrl . '/.well-known/oauth-protected-resource',
                    'authorization_server_metadata' => $this->baseUrl . '/.well-known/oauth-authorization-server'
                ]
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
    private function handleDocumentation() {
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
    <title>Formulize MCP OAuth-Enhanced HTTP Server</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .endpoint { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .method { color: #007cba; font-weight: bold; }
        pre { background: #eee; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .success { color: #2e7d32; }
        .oauth { color: #ff6600; }
        .highlight { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Formulize MCP OAuth-Enhanced HTTP Server</h1>
    <p class="success">✅ OAuth 2.1 Resource Server with authentication discovery</p>
    <p class="oauth">🔐 Compatible with VSCode MCP extension authentication flow</p>

    <div class="highlight">
        <h3>🎯 For VSCode Integration:</h3>
        <p>Use this configuration in your VSCode MCP settings:</p>
        <pre>{
  "servers": {
    "Formulize": {
      "type": "http",
      "url": "<?php echo $this->baseUrl; ?>/mcp"
    }
  }
}</pre>
        <p><strong>Authentication:</strong> When prompted, use your Formulize API key as the bearer token.</p>
    </div>

    <h2>OAuth 2.1 Discovery Endpoints:</h2>

    <div class="endpoint">
        <h3><span class="method oauth">GET</span> /.well-known/oauth-protected-resource</h3>
        <p>Protected Resource Metadata (RFC 9728)</p>
        <p><a href="<?php echo $this->baseUrl; ?>/.well-known/oauth-protected-resource">View metadata</a></p>
    </div>

    <div class="endpoint">
        <h3><span class="method oauth">GET</span> /.well-known/oauth-authorization-server</h3>
        <p>Authorization Server Metadata for VSCode compatibility</p>
        <p><a href="<?php echo $this->baseUrl; ?>/.well-known/oauth-authorization-server">View metadata</a></p>
    </div>

    <h2>MCP Endpoints:</h2>

    <div class="endpoint">
        <h3><span class="method">POST</span> /mcp</h3>
        <p>Main MCP endpoint - requires authentication</p>
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
            <li>Log into your Formulize admin panel</li>
            <li>Go to System Admin → Formulize → Manage API Keys</li>
            <li>Create or copy your API key</li>
            <li>Use it as: <code>Authorization: Bearer YOUR_API_KEY</code></li>
        </ol>
    </div>

    <h2>Available Tools:</h2>
    <ul>
        <?php foreach ($this->tools as $tool): ?>
        <li><strong><?php echo htmlspecialchars($tool['name']); ?></strong> - <?php echo htmlspecialchars($tool['description']); ?></li>
        <?php endforeach; ?>
    </ul>

    <p><small>OAuth-Enhanced MCP Server v1.1.0 | VSCode Compatible | <?php echo date('Y-m-d H:i:s'); ?></small></p>
</body>
</html>
        <?php
    }

    // [Include all the existing MCP request handling methods here - handleRequest, handleInitialize, etc.]
    // [Include all the existing tool execution methods - testConnection, listForms, etc.]
    // [These methods remain unchanged from the original implementation]

    /**
     * Handle MCP request (same as before)
     */
    public function handleRequest($input) {
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
            default:
                return $this->errorResponse('Unknown method: ' . $method, -32601, $id);
        }
    }

    /**
     * Handle initialization request
     */
    private function handleInitialize($params, $id) {
        return [
            'jsonrpc' => '2.0',
            'result' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [
                    'tools' => []
                ],
                'serverInfo' => [
                    'name' => 'formulize-mcp-server-oauth-enhanced',
                    'version' => '1.1.0'
                ]
            ],
            'id' => $id
        ];
    }

    /**
     * Handle tools list request
     */
    private function handleToolsList($id) {
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
     */
    private function handleToolCall($params, $id) {
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
    private function executeTool($toolName, $arguments) {
        switch ($toolName) {
            case 'test_connection':
                return $this->testConnection();
            case 'list_forms':
                return $this->listForms($arguments);
            case 'get_form_details':
                return $this->getFormDetails($arguments);
            case 'get_form_elements':
                return $this->getFormElements($arguments);
            case 'gatherDataset':
                return $this->gatherDataset($arguments);
            case 'search_entries':
                return $this->searchEntries($arguments);
            case 'debug_tables':
                return $this->debugTables();
            default:
                throw new Exception('Tool not implemented: ' . $toolName);
        }
    }

    /**
     * Gather dataset using Formulize's built-in function with proper permission scoping
     */
    private function gatherDataset($args) {

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
                ],
                'execution_mode' => 'gatherDataset_with_permissions_oauth_enhanced'
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
     * Debug: List all Formulize-related tables
     */
    private function debugTables() {
        $sql = "SHOW TABLES LIKE '" . XOOPS_DB_PREFIX . "_formulize%'";
        $result = $this->db->query($sql);

        $tables = [];
        while ($row = $this->db->fetchArray($result)) {
            $tableName = array_values($row)[0];
            $tables[] = $tableName;
        }

        return [
            'formulize_tables' => $tables,
            'table_count' => count($tables),
            'prefix' => XOOPS_DB_PREFIX
        ];
    }

    /**
     * List all forms
     */
    private function listForms($args) {
        $includeInactive = $args['include_inactive'] ?? false;

        $tableCheckSql = "SHOW TABLES LIKE '" . XOOPS_DB_PREFIX . "_formulize_id'";
        $tableResult = $this->db->query($tableCheckSql);

        if (!$this->db->fetchArray($tableResult)) {
            return [
                'error' => 'formulize_id table not found',
                'checked_table' => XOOPS_DB_PREFIX . "_formulize_id"
            ];
        }

        $columnsSql = "DESCRIBE " . XOOPS_DB_PREFIX . "_formulize_id";
        $columnsResult = $this->db->query($columnsSql);
        $columns = [];

        while ($row = $this->db->fetchArray($columnsResult)) {
            $columns[] = $row['Field'];
        }

        $sql = "SELECT * FROM " . XOOPS_DB_PREFIX . "_formulize_id";

        if (in_array('active', $columns) AND !$includeInactive) {
            $sql .= " WHERE active = 1";
        }

        $sql .= " ORDER BY " . (in_array('title', $columns) ? 'title' : 'id_form');

        $result = $this->db->query($sql);

        if (!$result) {
            return ['error' => 'Query failed', 'sql' => $sql];
        }

        $forms = [];
        while ($row = $this->db->fetchArray($result)) {
            $forms[] = $row;
        }

        return [
            'forms' => $forms,
            'total_count' => count($forms),
            'execution_mode' => 'oauth_enhanced_http'
        ];
    }

    /**
     * Get form details
     */
    private function getFormDetails($args) {
        $formId = $args['form_id'];

        $sql = "SELECT * FROM " . XOOPS_DB_PREFIX . "_formulize_id WHERE id_form = " . intval($formId);
        $result = $this->db->query($sql);

        if (!$result) {
            return ['error' => 'Query failed', 'sql' => $sql];
        }

        $row = $this->db->fetchArray($result);

        if (!$row) {
            return ['error' => 'Form not found', 'form_id' => $formId];
        }

        return [
            'form_data' => $row,
            'form_id' => $formId
        ];
    }

    /**
     * Get form elements
     */
    private function getFormElements($args) {
        $formId = $args['form_id'];

        $sql = "SELECT * FROM " . XOOPS_DB_PREFIX . "_formulize WHERE id_form = " . intval($formId) . " ORDER BY ele_order";
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

    /**
     * Search entries in a form
     */
    private function searchEntries($args) {
        $formId = intval($args['form_id']);
        $searchTerm = $args['search_term'];
        $elementHandle = $args['element_handle'] ?? null;
        $limit = intval($args['limit'] ?? 20);

        // Get the form's data table name
        $dataTable = XOOPS_DB_PREFIX . "_formulize_" . $formId;

        // Check if data table exists
        $tableCheckSql = "SHOW TABLES LIKE '$dataTable'";
        $tableResult = $this->db->query($tableCheckSql);

        if (!$this->db->fetchArray($tableResult)) {
            return [
                'error' => 'Form data table not found',
                'expected_table' => $dataTable,
                'form_id' => $formId
            ];
        }

        // Build search query
        $searchTerm = $this->db->escape($searchTerm);

        if ($elementHandle) {
            // Search in specific element
            $elementHandle = $this->db->escape($elementHandle);
            $sql = "SELECT * FROM $dataTable WHERE `$elementHandle` LIKE '%$searchTerm%' ORDER BY entry_id DESC LIMIT $limit";
        } else {
            // Search across all text columns
            $columnsSql = "DESCRIBE $dataTable";
            $columnsResult = $this->db->query($columnsSql);

            $textColumns = [];
            while ($row = $this->db->fetchArray($columnsResult)) {
                $type = strtolower($row['Type']);
                if (strpos($type, 'varchar') !== false OR strpos($type, 'text') !== false) {
                    $textColumns[] = "`" . $row['Field'] . "` LIKE '%$searchTerm%'";
                }
            }

            if (empty($textColumns)) {
                return ['error' => 'No searchable text columns found'];
            }

            $whereClause = implode(' OR ', $textColumns);
            $sql = "SELECT * FROM $dataTable WHERE ($whereClause) ORDER BY entry_id DESC LIMIT $limit";
        }

        $result = $this->db->query($sql);

        if (!$result) {
            return ['error' => 'Search query failed', 'sql' => $sql];
        }

        $entries = [];
        while ($row = $this->db->fetchArray($result)) {
            $entries[] = $row;
        }

        return [
            'form_id' => $formId,
            'search_term' => $searchTerm,
            'element_handle' => $elementHandle,
            'results' => $entries,
            'result_count' => count($entries),
            'limit' => $limit
        ];
    }

    private function getDefaultConfig() {
        return [
            'debug' => false,
            'max_results' => 100
        ];
    }

    private function errorResponse($message, $code = -32603, $id = null) {
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

// Handle the HTTP request with OAuth-enhanced Formulize authentication
try {
    $server = new FormulizeMCP();
		if($server->enabled) {
    	$server->handleHTTPRequest();
		} elseif($server->canBeEnabled) {
			// if the MCP server passed the canBeEnabled check, but is not enabled, return a 200 OK response with a JSON payload indicating that the server is not enabled
			header('Content-Type: application/json; charset=utf-8');
			header('Cache-Control: no-cache, no-store, must-revalidate');
			http_response_code(200);
			echo json_encode([
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
