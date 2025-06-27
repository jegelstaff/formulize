<?php

/**
 * Formulize MCP HTTP-to-stdio Bridge
 *
 * This script receives HTTP requests and bridges them to the stdio MCP server
 * Allows Docker/HTTP access to the existing MCP server code
 *
 * NOTE: This bridge file does NOT bootstrap XOOPS - it's a raw PHP file
 * that only handles HTTP protocol translation to the actual MCP server
 */

class MCPHttpBridge {

    private $mcpServerPath;
    private $debug;

    public function __construct($mcpServerPath = null, $debug = false) {
        $this->mcpServerPath = $mcpServerPath ?: dirname(__FILE__) . '/formulize_mcp_test.php';
        $this->debug = $debug;
    }

    /**
     * Handle HTTP request and bridge to stdio MCP server
     */
    public function handleRequest() {
        // Set no-cache headers FIRST
        $this->setCorsHeaders();

        // Handle preflight OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        // Only accept POST requests for MCP
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError(405, 'Method not allowed. Use POST for MCP requests.');
            return;
        }

        // Get request body
        $requestBody = file_get_contents('php://input');

        if (empty($requestBody)) {
            $this->sendError(400, 'Empty request body');
            return;
        }

        // Validate JSON
        $requestData = json_decode($requestBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendError(400, 'Invalid JSON: ' . json_last_error_msg());
            return;
        }

        if ($this->debug) {
            error_log("MCP Bridge - Incoming request: " . $requestBody);
        }

        try {
            // Bridge to stdio MCP server
            $response = $this->bridgeToStdio($requestBody);

            if ($this->debug) {
                error_log("MCP Bridge - Response: " . $response);
            }

            // Send response
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            echo $response;

        } catch (Exception $e) {
            $this->sendError(500, 'MCP server error: ' . $e->getMessage());
        }
    }

    /**
     * Bridge HTTP request to stdio MCP server
     */
    private function bridgeToStdio($requestBody) {
        // Prepare command to run MCP server
        $command = 'php ' . escapeshellarg($this->mcpServerPath);

        if ($this->debug) {
            error_log("MCP Bridge - Running command: " . $command);
        }

        // Open process with pipes
        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w']  // stderr
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new Exception('Failed to start MCP server process');
        }

        // Write request to stdin
        fwrite($pipes[0], $requestBody . "\n");
        fclose($pipes[0]);

        // Read response from stdout
        $response = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        // Read any errors from stderr
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        // Wait for process to finish and get exit code
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            $errorMsg = "MCP server exited with code $exitCode";
            if (!empty($errors)) {
                $errorMsg .= ": " . $errors;
            }
            throw new Exception($errorMsg);
        }

        if (!empty($errors) && $this->debug) {
            error_log("MCP Bridge - Stderr: " . $errors);
        }

        if (empty($response)) {
            throw new Exception('Empty response from MCP server');
        }

        // Validate response is valid JSON
        $responseData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from MCP server: ' . json_last_error_msg() . "\nResponse: " . $response);
        }

        return $response;
    }

    /**
     * Set CORS headers for HTTP access
     */
    private function setCorsHeaders() {
        // Prevent ALL caching
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('ETag: "' . uniqid() . '"');

        // Allow requests from Claude Desktop
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Max-Age: 0'); // No preflight caching

        // Set content type
        header('Content-Type: application/json; charset=utf-8');

        // Prevent browser caching
        header('Vary: *');
    }

    /**
     * Send error response
     */
    private function sendError($code, $message) {
        http_response_code($code);
        $error = [
            'error' => [
                'code' => $code,
                'message' => $message,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
        echo json_encode($error);
    }

    /**
     * Health check endpoint
     */
    public function healthCheck() {
        // Set no-cache headers
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        try {
            // Test if we can run the MCP server
            $testRequest = json_encode([
                'method' => 'initialize',
                'params' => []
            ]);

            $response = $this->bridgeToStdio($testRequest);
            $responseData = json_decode($response, true);

            $health = [
                'status' => 'healthy',
                'mcp_server_path' => $this->mcpServerPath,
                'mcp_server_accessible' => file_exists($this->mcpServerPath),
                'mcp_response_valid' => isset($responseData['protocolVersion']),
                'timestamp' => date('Y-m-d H:i:s')
            ];

            header('Content-Type: application/json');
            echo json_encode($health, JSON_PRETTY_PRINT);

        } catch (Exception $e) {
            http_response_code(500);
            $health = [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'mcp_server_path' => $this->mcpServerPath,
                'mcp_server_accessible' => file_exists($this->mcpServerPath),
                'timestamp' => date('Y-m-d H:i:s')
            ];

            header('Content-Type: application/json');
            echo json_encode($health, JSON_PRETTY_PRINT);
        }
    }
}

// Handle different endpoints
$path = $_SERVER['REQUEST_URI'];
$pathParts = explode('?', $path);
$cleanPath = $pathParts[0];

// Configuration
$debug = isset($_GET['debug']) || (defined('MCP_BRIDGE_DEBUG') && MCP_BRIDGE_DEBUG);
$mcpServerPath = $_GET['mcp_path'] ?? dirname(__FILE__) . '/formulize_mcp_test.php';

$bridge = new MCPHttpBridge($mcpServerPath, $debug);

// Route requests
if (strpos($cleanPath, '/health') !== false) {
    // Health check endpoint
    $bridge->healthCheck();
} elseif (strpos($cleanPath, '/mcp') !== false || $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Main MCP endpoint
    $bridge->handleRequest();
} else {
    // Documentation/info page
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        ?>
<!DOCTYPE html>
<html>
<head>
    <title>Formulize MCP HTTP Bridge</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .endpoint { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .method { color: #007cba; font-weight: bold; }
        pre { background: #eee; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Formulize MCP HTTP Bridge</h1>
    <p>This bridge allows HTTP access to the Formulize MCP server.</p>

    <h2>Endpoints:</h2>

    <div class="endpoint">
        <h3><span class="method">POST</span> /mcp</h3>
        <p>Main MCP endpoint - send JSON-RPC requests here</p>
        <pre>curl -X POST <?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>mcp \
  -H "Content-Type: application/json" \
  -d '{"method": "tools/list", "params": {}}'</pre>
    </div>

    <div class="endpoint">
        <h3><span class="method">GET</span> /health</h3>
        <p>Health check endpoint</p>
        <p><a href="<?php echo $_SERVER['REQUEST_URI']; ?>health">Check health status</a></p>
    </div>

    <div class="endpoint">
        <h3>Debug Mode</h3>
        <p>Add <code>?debug=1</code> to enable debug logging</p>
        <p><a href="<?php echo $_SERVER['REQUEST_URI']; ?>health?debug=1">Health check with debug</a></p>
    </div>

    <h2>Configuration:</h2>
    <ul>
        <li><strong>MCP Server Path:</strong> <?php echo htmlspecialchars($mcpServerPath); ?></li>
        <li><strong>Server Accessible:</strong> <?php echo file_exists($mcpServerPath) ? 'Yes' : 'No'; ?></li>
        <li><strong>Debug Mode:</strong> <?php echo $debug ? 'Enabled' : 'Disabled'; ?></li>
    </ul>
</body>
</html>
        <?php
    }
}
