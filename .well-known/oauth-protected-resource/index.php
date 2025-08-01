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

// Get the base URL dynamically
function getBaseUrl() {
    $scheme = $_SERVER['REQUEST_SCHEME'] ?? (($_SERVER['HTTPS'] ?? 'off') === 'on' ? 'https' : 'http');
    $host = $_SERVER['HTTP_HOST'];

    // For OAuth metadata, always return the domain root
    return $scheme . '://' . $host;
}

$baseUrl = getBaseUrl();
$authServerUrl = $baseUrl; // Same server acts as authorization server

$metadata = [
    'resource' => $baseUrl . '/mcp',
    'authorization_servers' => [$authServerUrl],
    'bearer_methods_supported' => ['header'],
    'resource_documentation' => $baseUrl . '/.well-known/docs'
];

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

// Output the metadata
echo json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
