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

// RFC 8414 Authorization Server Metadata
$metadata = [
    'issuer' => $baseUrl,
    'authorization_endpoint' => $baseUrl . '/oauth/authorize',
    'token_endpoint' => $baseUrl . '/oauth/token',
    'registration_endpoint' => $baseUrl . '/oauth/register',
    'scopes_supported' => [
        'read',
        'write',
        'read_data',
        'write_data',
        'claudeai'
    ],
    'response_types_supported' => [
        'code'
    ],
    'grant_types_supported' => [
        'authorization_code'
    ],
    'token_endpoint_auth_methods_supported' => [
        'none'  // Public clients only
    ],
    'code_challenge_methods_supported' => [
        'S256',
        'plain'
    ],
    'service_documentation' => $baseUrl . '/.well-known/docs',
    'ui_locales_supported' => [
        'en-US',
        'en'
    ],
    'op_policy_uri' => $baseUrl . '/privacy',
    'op_tos_uri' => $baseUrl . '/terms',
    'authorization_response_iss_parameter_supported' => false,

    // RFC 8707 Resource Indicators support
    'resource_parameter_supported' => true,

    // RFC 7591 Dynamic Client Registration support
    'registration_endpoint' => $baseUrl . '/oauth/register',

    // PKCE support (required for OAuth 2.1)
    'require_pushed_authorization_requests' => false,
    'pushed_authorization_request_endpoint' => null,

    // Additional security features
    'tls_client_certificate_bound_access_tokens' => false,
    'dpop_signing_alg_values_supported' => [],

    // MCP-specific metadata
    'mcp_server_info' => [
        'name' => 'Formulize MCP Server',
        'version' => '1.0',
        'mcp_endpoint' => $baseUrl . '/mcp',
        'health_endpoint' => $baseUrl . '/mcp/health',
        'capabilities_endpoint' => $baseUrl . '/mcp/capabilities'
    ]
];

echo json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

