<?php

/**
 * Formulize MCP Server - Persistent Connection Version
 *
 * Uses techniques from php-mcp/server for proper persistent stdio communication
 */

require_once dirname(__FILE__) . '/mainfile.php';

// CRITICAL: Disable debug output
icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}

class FormulizeMCPServer {

    private $config;
    private $db;
    private $tools;
    private $running = false;

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
     * Set no-cache headers to prevent response caching
     */
    private function setNoCacheHeaders() {
        // Prevent ALL caching - critical for MCP responses
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('ETag: "' . uniqid() . '"');
        header('Content-Type: application/json; charset=utf-8');
        header('Vary: *');
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
                    'properties' => []
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
                    'properties' => []
                ]
            ]
        ];
    }

    /**
     * Main server run loop - handles persistent stdio communication
     */
    public function run() {
        $this->running = true;

        // Set up signal handlers for graceful shutdown
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'shutdown']);
            pcntl_signal(SIGINT, [$this, 'shutdown']);
        }

        // Log server start to stderr (not stdout)
        error_log("Formulize MCP Server starting...");

        // Main message processing loop
        while ($this->running) {
            // Allow signal processing
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            // Read input with timeout to allow periodic signal checking
            $read = [STDIN];
            $write = $except = [];
            $timeout = 1; // 1 second timeout

            $ready = stream_select($read, $write, $except, $timeout);

            if ($ready === false) {
                error_log("Formulize MCP Server: stream_select failed");
                break;
            }

            if ($ready === 0) {
                // Timeout - continue loop to check signals
                continue;
            }

            // Read available input
            $input = fgets(STDIN);

            if ($input === false) {
                error_log("Formulize MCP Server: stdin closed");
                break;
            }

            $input = trim($input);

            // Skip empty lines
            if (empty($input)) {
                continue;
            }

            try {
                $response = $this->handleRequest($input);

                // Send response immediately
                echo json_encode($response) . "\n";
                flush();

            } catch (Exception $e) {
                error_log("Formulize MCP Server Error: " . $e->getMessage());

                // Send error response
                $errorResponse = [
                    'jsonrpc' => '2.0',
                    'error' => [
                        'code' => -32603,
                        'message' => 'Internal error: ' . $e->getMessage()
                    ],
                    'id' => $this->extractRequestId($input)
                ];

                echo json_encode($errorResponse) . "\n";
                flush();
            }
        }

        error_log("Formulize MCP Server: shutting down gracefully");
        return 0;
    }

    /**
     * Graceful shutdown handler
     */
    public function shutdown($signal = null) {
        if ($signal) {
            error_log("Formulize MCP Server: received signal $signal");
        }
        $this->running = false;
    }

    /**
     * Extract request ID from JSON-RPC request for error responses
     */
    private function extractRequestId($input) {
        $data = json_decode($input, true);
        return isset($data['id']) ? $data['id'] : null;
    }

    /**
     * Handle MCP request
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

        // Test database connection
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
                'process_id' => getmypid()
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
     * List all forms - simplified for testing
     */
    private function listForms($args) {
        $includeInactive = $args['include_inactive'] ?? false;

        // First, let's see what tables exist
        $tableCheckSql = "SHOW TABLES LIKE '" . XOOPS_DB_PREFIX . "_formulize_id'";
        $tableResult = $this->db->query($tableCheckSql);

        if (!$this->db->fetchArray($tableResult)) {
            return [
                'error' => 'formulize_id table not found',
                'checked_table' => XOOPS_DB_PREFIX . "_formulize_id",
                'suggestion' => 'Please verify your Formulize installation and table structure'
            ];
        }

        // Get column info first
        $columnsSql = "DESCRIBE " . XOOPS_DB_PREFIX . "_formulize_id";
        $columnsResult = $this->db->query($columnsSql);
        $columns = [];

        while ($row = $this->db->fetchArray($columnsResult)) {
            $columns[] = $row['Field'];
        }

        // Build query based on available columns
        $sql = "SELECT * FROM " . XOOPS_DB_PREFIX . "_formulize_id";

        if (in_array('active', $columns) && !$includeInactive) {
            $sql .= " WHERE active = 1";
        }

        $sql .= " ORDER BY " . (in_array('title', $columns) ? 'title' : (in_array('id_form', $columns) ? 'id_form' : '1'));

        $result = $this->db->query($sql);

        if (!$result) {
            return [
                'error' => 'Query failed',
                'sql' => $sql,
                'available_columns' => $columns
            ];
        }

        $forms = [];
        while ($row = $this->db->fetchArray($result)) {
            $forms[] = $row;
        }

        return [
            'forms' => $forms,
            'total_count' => count($forms),
            'available_columns' => $columns,
            'query_used' => $sql
        ];
    }

    /**
     * Get form details - simplified for testing
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
     * Get form elements - simplified for testing
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

// Entry point for CLI execution
if (php_sapi_name() === 'cli') {
    // Set up proper error handling for CLI
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', 'php://stderr');

    // Disable time limit for long-running process
    set_time_limit(0);

    // Create and run server
    try {
        $server = new FormulizeMCPServer();
        $exitCode = $server->run();
        exit($exitCode);
    } catch (Exception $e) {
        error_log("Formulize MCP Server startup failed: " . $e->getMessage());
        exit(1);
    }

} elseif (isset($_GET['test'])) {
    // Web test mode
    $server = new FormulizeMCPServer();

    // Set no-cache headers for web test
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Run a simple test
    $testRequest = json_encode([
        'jsonrpc' => '2.0',
        'method' => 'tools/call',
        'params' => [
            'name' => 'test_connection',
            'arguments' => []
        ],
        'id' => 1
    ]);

    $response = $server->handleRequest($testRequest);
    echo json_encode($response, JSON_PRETTY_PRINT);

} else {
    // Regular MCP server mode
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $server = new FormulizeMCPServer();
        $input = file_get_contents('php://input');
        $response = $server->handleRequest($input);

        // Set no-cache headers
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo json_encode($response);
    } else {
        // Set no-cache headers for info page
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        echo "Formulize MCP Server - Send POST requests with JSON-RPC format";
    }
}
