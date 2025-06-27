<?php

/**
 * Formulize MCP HTTP Direct Server
 *
 * Direct HTTP endpoint for MCP - no bridge needed
 */

require_once dirname(__FILE__) . '/mainfile.php';

// CRITICAL: Disable debug output
icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}

class FormulizeMCPHTTPDirect {

    private $config;
    private $db;
    private $tools;

    public function __construct($config = null) {
        $this->config = $config ?: $this->getDefaultConfig();
        $this->initializeDatabase();
        $this->registerTools();
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
                    'properties' => (object)[]  // Empty object, not empty array
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
            'debug_tables' => [
                'name' => 'debug_tables',
                'description' => 'Debug: List all Formulize-related database tables',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => (object)[]  // Empty object, not empty array
                ]
            ]
        ];
    }

    /**
     * Handle HTTP request routing
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

        // Route based on path
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
                'tools' => array_values($this->tools),  // ← Include the actual tools!
                'prompts' => [],
                'resources' => []
            ],
            'serverInfo' => [
                'name' => 'formulize-mcp-server',
                'version' => '1.0.0'
            ],
            'endpoints' => [
                'mcp' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/mcp',
                'health' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/health'
            ]
        ];

        echo json_encode($capabilities, JSON_PRETTY_PRINT);
    }

    /**
     * Handle health check
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

            $health = [
                'status' => 'healthy',
                'database_test' => $row['test'] == 1 ? 'passed' : 'failed',
                'mcp_server' => 'direct_http',
                'tools_count' => count($this->tools),
                'no_cache_enforced' => true,
                'endpoints' => [
                    'mcp' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/mcp',
                    'capabilities' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/capabilities',
                    'health' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/health'
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
    <title>Formulize MCP HTTP Direct Server</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .endpoint { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .method { color: #007cba; font-weight: bold; }
        pre { background: #eee; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .success { color: #2e7d32; }
    </style>
</head>
<body>
    <h1>Formulize MCP HTTP Direct Server</h1>
    <p class="success">✅ Direct HTTP MCP server - no bridge needed!</p>

    <h2>Endpoints:</h2>

    <div class="endpoint">
        <h3><span class="method">POST</span> /mcp</h3>
        <p>Main MCP endpoint - send JSON-RPC requests here</p>
        <pre>curl -X POST <?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"tools/list","params":{},"id":1}'</pre>
    </div>

    <div class="endpoint">
        <h3><span class="method">GET</span> /capabilities</h3>
        <p>MCP server capabilities and endpoints</p>
        <p><a href="<?php echo $_SERVER['REQUEST_URI']; ?>capabilities">View capabilities</a></p>
    </div>

    <div class="endpoint">
        <h3><span class="method">GET</span> /health</h3>
        <p>Health check endpoint</p>
        <p><a href="<?php echo $_SERVER['REQUEST_URI']; ?>health">Check health</a></p>
    </div>

    <h2>For Claude Desktop Integration:</h2>
    <p>Use this URL in Settings > Integrations:</p>
    <pre><?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>capabilities</pre>

    <h2>Available Tools:</h2>
    <ul>
        <?php foreach ($this->tools as $tool): ?>
        <li><strong><?php echo htmlspecialchars($tool['name']); ?></strong> - <?php echo htmlspecialchars($tool['description']); ?></li>
        <?php endforeach; ?>
    </ul>

    <p><small>Direct HTTP MCP Server | No caching enforced | <?php echo date('Y-m-d H:i:s'); ?></small></p>
</body>
</html>
        <?php
    }

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
                    'name' => 'formulize-mcp-server',
                    'version' => '1.0.0'
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
            case 'debug_tables':
                return $this->debugTables();
            default:
                throw new Exception('Tool not implemented: ' . $toolName);
        }
    }

    /**
     * Test database connection and basic functionality
     */
    private function testConnection() {
        global $xoopsConfig;

        $testQuery = "SELECT 1 as test";
        $result = $this->db->query($testQuery);

        if (!$result) {
            throw new Exception('Database query failed');
        }

        $row = $this->db->fetchArray($result);

        return [
            'status' => 'success',
            'message' => 'MCP Server connection successful',
            'database_test' => $row['test'] == 1 ? 'passed' : 'failed',
            'xoops_config' => [
                'sitename' => $xoopsConfig['sitename'] ?? 'Unknown',
                'version' => XOOPS_VERSION ?? 'Unknown'
            ],
            'server_info' => [
                'php_version' => PHP_VERSION,
                'timestamp' => date('Y-m-d H:i:s'),
                'execution_mode' => 'direct_http'
            ]
        ];
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

        if (in_array('active', $columns) && !$includeInactive) {
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
            'execution_mode' => 'direct_http'
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

// Handle the HTTP request
try {
    $server = new FormulizeMCPHTTPDirect();
    $server->handleHTTPRequest();
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    http_response_code(500);
    echo json_encode([
        'error' => 'Server initialization failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
