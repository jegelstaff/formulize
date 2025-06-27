<?php

/**
 * Formulize MCP One-Shot Server
 *
 * Simplified version for bridge calls - handles one request and exits
 */

require_once dirname(__FILE__) . '/mainfile.php';

// CRITICAL: Disable debug output
icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}

class FormulizeMCPOneShot {

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
     * Handle single MCP request and exit
     */
    public function handleOneShot() {
        // Read single line from stdin
        $input = fgets(STDIN);

        if ($input === false || empty(trim($input))) {
            // No input, return error
            echo json_encode([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32600,
                    'message' => 'No input provided'
                ],
                'id' => null
            ]) . "\n";
            return;
        }

        $input = trim($input);

        try {
            $response = $this->handleRequest($input);
            echo json_encode($response) . "\n";
        } catch (Exception $e) {
            echo json_encode([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32603,
                    'message' => 'Internal error: ' . $e->getMessage()
                ],
                'id' => null
            ]) . "\n";
        }
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
                'execution_mode' => 'one-shot'
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
            'execution_mode' => 'one-shot'
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

// Entry point - force CLI detection
if (isset($argc) || php_sapi_name() === 'cli' || defined('STDIN')) {
    // Definitely CLI mode: read one request, send one response, exit
    ini_set('display_errors', 0);
    ini_set('log_errors', 0);

    try {
        $server = new FormulizeMCPOneShot();
        $server->handleOneShot();
    } catch (Exception $e) {
        echo json_encode([
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32603,
                'message' => 'Server startup failed: ' . $e->getMessage()
            ],
            'id' => null
        ]) . "\n";
    }
    exit(0);
} else {
    // Web mode - just return simple message
    header('Content-Type: text/plain');
    echo "Formulize MCP One-Shot Server - CLI only";
    exit(0);
}
