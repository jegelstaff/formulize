<?php

/**
 * Formulize MCP Server - Test Version
 * Add this to your Formulize installation to test MCP functionality
 */

// Include Formulize's initialization
error_reporting(0);
ini_set('display_errors', 0);
require_once dirname(__FILE__) . '/mainfile.php';

icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}

error_reporting(0);
ini_set('display_errors', 0);

// Set no-cache headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

class FormulizeMCPServer {

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
     * Handle MCP request
     */
    public function handleRequest($input) {
        try {
            $request = json_decode($input, true);

            if (!$request) {
                return $this->errorResponse('Invalid JSON input');
            }

            $method = $request['method'] ?? '';
            $params = $request['params'] ?? [];

            switch ($method) {
                case 'initialize':
                    return $this->handleInitialize($params);
                case 'tools/list':
                    return $this->handleToolsList();
                case 'tools/call':
                    return $this->handleToolCall($params);
                default:
                    return $this->errorResponse('Unknown method: ' . $method);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Request handling failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle initialization request
     */
    private function handleInitialize($params) {
        return [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [
                'tools' => []
            ],
            'serverInfo' => [
                'name' => 'formulize-mcp-server',
                'version' => '1.0.0-test'
            ]
        ];
    }

    /**
     * Handle tools list request
     */
    private function handleToolsList() {
        return [
            'tools' => array_values($this->tools)
        ];
    }

    /**
     * Handle tool call request
     */
    private function handleToolCall($params) {
        $toolName = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];

        if (!isset($this->tools[$toolName])) {
            return $this->errorResponse('Unknown tool: ' . $toolName);
        }

        try {
            $result = $this->executeTool($toolName, $arguments);
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode($result, JSON_PRETTY_PRINT)
                    ]
                ]
            ];
        } catch (Exception $e) {
            return $this->errorResponse('Tool execution failed: ' . $e->getMessage());
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
                'timestamp' => date('Y-m-d H:i:s')
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

    private function getDefaultConfig() {
        return [
            'debug' => true,
            'max_results' => 100
        ];
    }

    private function errorResponse($message) {
        return [
            'error' => [
                'code' => -1,
                'message' => $message
            ]
        ];
    }
}

// Test modes
if (php_sapi_name() === 'cli') {
    // CLI mode
    /*echo "Formulize MCP Server - CLI Mode\n";
    echo "Send JSON-RPC requests, one per line\n";
    echo "Example: " . json_encode(['method' => 'tools/list', 'params' => []]) . "\n\n";
*/
    $server = new FormulizeMCPServer();

    while (true) {
        $input = fgets(STDIN);
        if ($input === false) break;

        $response = $server->handleRequest(trim($input));
        echo json_encode($response, JSON_PRETTY_PRINT) . "\n";
        flush();
    }
} elseif (isset($_GET['test'])) {
    // Web test mode
    header('Content-Type: application/json');

    $server = new FormulizeMCPServer();

    // Run a simple test
    $testRequest = json_encode([
        'method' => 'tools/call',
        'params' => [
            'name' => 'test_connection',
            'arguments' => []
        ]
    ]);

    $response = $server->handleRequest($testRequest);
    echo json_encode($response, JSON_PRETTY_PRINT);

} else {
    // Regular MCP server mode
    /*if ($_SERVER['REQUEST_METHOD'] === 'POST') {*/
        $server = new FormulizeMCPServer();
        $input = file_get_contents('php://input');
				$input = $input ? $input : $_GET['input'];
        $response = $server->handleRequest($input);

        header('Content-Type: application/json');
        echo json_encode($response);
    /*} else {
        echo "Formulize MCP Server - Send POST requests with JSON-RPC format";
    }*/
}
exit();
