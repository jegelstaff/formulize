<?php
/**
 * OAuth 2.0 Protected Resource Metadata (RFC 9728)
 * Catch-all endpoint for /oauth-protected-resource/mcp requests
 * 
 * Some MCP clients may append /mcp to the resource metadata URL
 * This endpoint redirects to the correct metadata
 */

// Handle CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(200);
    exit;
}

// Include the main protected resource metadata handler
include dirname(dirname(__FILE__)) . '/oauth-protected-resource/index.php';
?>
