<?php
/**
 * OAuth 2.0 Authorization Server Metadata (RFC 8414)
 * Location: /.well-known/oauth-authorization-server
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

$metadata = [
    'issuer' => $baseUrl,
    'authorization_endpoint' => $baseUrl . '/mcp/?action=authorize',
    'token_endpoint' => $baseUrl . '/mcp/?action=token',
    'registration_endpoint' => $baseUrl . '/mcp/?action=register',
    'response_types_supported' => ['code'],
    'grant_types_supported' => ['authorization_code'],
    'code_challenge_methods_supported' => ['S256', 'plain'],
    'token_endpoint_auth_methods_supported' => ['none'], // Public clients
    'scopes_supported' => ['read', 'write', 'read_data', 'write_data', 'claudeai'],
    'resource_parameter_supported' => true, // RFC 8707 support
    'authorization_response_iss_parameter_supported' => false
];

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

// Output the metadata
echo json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
