<?php

/**
 * Formulize MCP SSE (Server-Sent Events) Bridge
 *
 * HTTP-to-stdio bridge - does NOT bootstrap XOOPS
 * This is a raw PHP file that only handles HTTP protocol translation
 */

class MCPSSEBridge {

    private $mcpServerPath;
    private $debug;
    private $process;
    private $pipes;

    public function __construct($mcpServerPath = null, $debug = false) {
        $this->mcpServerPath = $mcpServerPath ? $mcpServerPath : dirname(__FILE__) . '/formulize_mcp_oneshot.php';
        $this->debug = $debug;
    }

		/**
     * Set comprehensive no-cache headers - critical for bridge files
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
        header('Access-Control-Allow-Headers: Content-Type, Authorization, Last-Event-ID');
        header('Access-Control-Max-Age: 0'); // No preflight caching
    }

    /**
     * Handle SSE connection
     */
    public function handleSSE() {
        // Set no-cache headers first
        $this->setNoCacheHeaders();

        // Set SSE specific headers
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Connection: keep-alive');

        // Handle preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        // Start the MCP server process
        $this->startMCPProcess();

        // Send initial connection event
        $this->sendSSEEvent('connected', ['status' => 'ready']);

        // Keep connection alive and handle messages
        while (connection_status() === CONNECTION_NORMAL) {
            // Check for incoming HTTP messages (POST to different endpoint)
            // For now, we'll implement a simple ping mechanism

            $this->sendSSEEvent('ping', ['timestamp' => time()]);

            // Flush output
            if (ob_get_level()) {
                ob_flush();
            }
            flush();

            // Wait a bit
            sleep(1);

            // Break after reasonable time to prevent infinite connections
            // In real implementation, this would be event-driven
            static $counter = 0;
            if (++$counter > 300) { // 5 minutes
                break;
            }
        }

        $this->closeMCPProcess();
    }

    /**
     * Handle MCP message via POST
     */
    public function handleMessage() {
        // Set no-cache headers first
        $this->setNoCacheHeaders();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $input = file_get_contents('php://input');

        if (empty($input)) {
            http_response_code(400);
            echo json_encode(['error' => 'Empty request body']);
            return;
        }

        try {
            // NEVER cache responses from MCP server
            $response = $this->bridgeToStdio($input);

            // Set fresh no-cache headers for each response
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            echo $response;
        } catch (Exception $e) {
            http_response_code(500);

            // Set no-cache headers for error responses too
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');

            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * MCP handshake endpoint
     */
    public function handleHandshake() {
        // Set no-cache headers first
        $this->setNoCacheHeaders();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        // Return MCP server capabilities
        $capabilities = [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [
                'tools' => [],
                'prompts' => [],
                'resources' => []
            ],
            'serverInfo' => [
                'name' => 'formulize-mcp-server',
                'version' => '1.0.0'
            ],
            'transport' => [
                'type' => 'sse',
                'endpoint' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/sse'
            ]
        ];

        echo json_encode($capabilities);
    }

    /**
     * Start MCP server process
     */
    private function startMCPProcess() {
        $command = 'php ' . escapeshellarg($this->mcpServerPath);

        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w']  // stderr
        ];

        $this->process = proc_open($command, $descriptors, $this->pipes);

        if (!is_resource($this->process)) {
            throw new Exception('Failed to start MCP server process');
        }

        // Make stdout non-blocking
        stream_set_blocking($this->pipes[1], false);
    }

    /**
     * Close MCP server process
     */
    private function closeMCPProcess() {
        if ($this->pipes) {
            foreach ($this->pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
        }

        if (is_resource($this->process)) {
            proc_close($this->process);
        }
    }

    /**
     * Send SSE event
     */
    private function sendSSEEvent($event, $data) {
        echo "event: $event\n";
        echo "data: " . json_encode($data) . "\n\n";
    }

    /**
     * Bridge to stdio MCP server (from original bridge)
     */
    private function bridgeToStdio($requestBody) {

        $command = 'php ' . escapeshellarg($this->mcpServerPath);

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

        // Wait for process to finish
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new Exception("MCP server exited with code $exitCode: " . $errors);
        }

        if (empty($response)) {
            throw new Exception('Empty response from MCP server');
        }

        return $response;
    }

    /**
     * Health check
     */
    public function healthCheck() {
        // Set no-cache headers first
        $this->setNoCacheHeaders();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $testRequest = json_encode([
                'jsonrpc' => '2.0',
                'method' => 'initialize',
                'params' => [],
                'id' => 1
            ]);

            // NEVER cache health check responses
            $response = $this->bridgeToStdio($testRequest);
            $responseData = json_decode($response, true);

            $health = [
                'status' => 'healthy',
                'mcp_server_path' => $this->mcpServerPath,
                'mcp_server_accessible' => file_exists($this->mcpServerPath),
                'mcp_response_valid' => isset($responseData['result']['protocolVersion']),
								'debug_raw_response' => $response,
								'debug_decoded_response' => $responseData,
								'debug_json_error' => json_last_error_msg(),
                'transport' => 'sse',
                'no_cache_enforced' => true,
                'endpoints' => [
                    'sse' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/sse',
                    'message' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/message',
                    'handshake' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/handshake'
                ],
                'timestamp' => date('Y-m-d H:i:s'),
                'cache_headers_set' => true
            ];

            echo json_encode($health, JSON_PRETTY_PRINT);

        } catch (Exception $e) {
            http_response_code(500);
            $health = [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'mcp_server_path' => $this->mcpServerPath,
                'mcp_server_accessible' => file_exists($this->mcpServerPath),
                'no_cache_enforced' => true,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            echo json_encode($health, JSON_PRETTY_PRINT);
        }
    }
}

// Route requests based on path
$path = $_SERVER['REQUEST_URI'];
$pathParts = explode('?', $path);
$cleanPath = $pathParts[0];

$debug = isset($_GET['debug']);
$mcpServerPath = $_GET['mcp_path'] ?? dirname(__FILE__) . '/formulize_mcp_oneshot.php';
$bridge = new MCPSSEBridge($mcpServerPath, $debug);

// Simple routing
if (strpos($cleanPath, '/health') !== false) {
    $bridge->healthCheck();
} elseif (strpos($cleanPath, '/sse') !== false) {
    $bridge->handleSSE();
} elseif (strpos($cleanPath, '/message') !== false) {
    $bridge->handleMessage();
} elseif (strpos($cleanPath, '/handshake') !== false) {
    $bridge->handleHandshake();
} else {
    // Documentation/info page
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Set no-cache headers for documentation too
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        ?>
<!DOCTYPE html>
<html>
<head>
    <title>Formulize MCP SSE Bridge</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .endpoint { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .method { color: #007cba; font-weight: bold; }
        pre { background: #eee; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <h1>Formulize MCP SSE Bridge</h1>
    <p>This bridge implements MCP over HTTP using Server-Sent Events (SSE) transport.</p>

    <div class="warning">
        <strong>⚠️ Important:</strong> This bridge enforces NO-CACHE at all levels to prevent stale data issues.
        All responses include aggressive cache-prevention headers.
    </div>

    <h2>Endpoints:</h2>

    <div class="endpoint">
        <h3><span class="method">GET</span> /handshake</h3>
        <p>MCP handshake endpoint - returns server capabilities</p>
        <p><strong>Cache Policy:</strong> No caching enforced</p>
    </div>

    <div class="endpoint">
        <h3><span class="method">GET</span> /sse</h3>
        <p>SSE endpoint for real-time communication</p>
        <p><strong>Cache Policy:</strong> No caching enforced</p>
    </div>

    <div class="endpoint">
        <h3><span class="method">POST</span> /message</h3>
        <p>Message endpoint for sending MCP requests</p>
        <p><strong>Cache Policy:</strong> Responses are never cached</p>
    </div>

    <div class="endpoint">
        <h3><span class="method">GET</span> /health</h3>
        <p>Health check endpoint</p>
        <p><strong>Cache Policy:</strong> Fresh status on every request</p>
        <p><a href="<?php echo $_SERVER['REQUEST_URI']; ?>health?v=<?php echo time(); ?>">Check health status</a></p>
    </div>

    <h2>For Claude Desktop Integration:</h2>
    <p>Use this URL in Settings > Integrations:</p>
    <pre><?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>handshake</pre>

    <h2>Bridge Architecture:</h2>
    <ul>
        <li><strong>No XOOPS Bootstrap:</strong> This bridge file does not include mainfile.php</li>
        <li><strong>No Caching:</strong> All responses include comprehensive no-cache headers</li>
        <li><strong>Fresh Data:</strong> Each request creates a new MCP server process</li>
        <li><strong>Transport Only:</strong> Pure HTTP-to-stdio translation</li>
    </ul>

    <p><small>Last updated: <?php echo date('Y-m-d H:i:s'); ?> | No-cache enforced: ✅</small></p>
</body>
</html>
        <?php
    }
}
