<?php
/**
 * OAuth 2.0 Protected Resource Metadata (RFC 9728)
 * Location: /.well-known/oauth-protected-resource
 */

// Handle CORS - set headers before any other output
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 3600');

// Set JSON content type
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET, OPTIONS');
    exit;
}
// Get the server's base URL
function getServerBaseUrl() {
    $scheme = $_SERVER['REQUEST_SCHEME'] ?? (($_SERVER['HTTPS'] ?? 'off') === 'on' ? 'https' : 'http');
    $host = $_SERVER['HTTP_HOST'];
    return $scheme . '://' . $host;
}

$baseUrl = getServerBaseUrl();

// RFC 9728 Protected Resource Metadata
$metadata = [
    'resource' => $baseUrl . '/mcp',
    'authorization_servers' => [
        $baseUrl . '/oauth'
    ],
    'scopes_supported' => [
        'read',
        'write',
        'read_data',
        'write_data',
        'claudeai'
    ],
    'bearer_methods_supported' => [
        'header'
    ],
    'resource_documentation' => $baseUrl . '/.well-known/docs'
];

echo json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
