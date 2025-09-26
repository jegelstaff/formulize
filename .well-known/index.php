<?php
/**
 * Catch-all handler for .well-known OAuth requests
 * This handles any OAuth-related requests that might have extra path components
 */

// Get the request URI and clean it up
$requestUri = $_SERVER['REQUEST_URI'];
$parsedUrl = parse_url($requestUri);
$path = $parsedUrl['path'] ?? '';

// Handle CORS for all requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(200);
    exit;
}

// Remove any trailing /mcp or other path components from well-known URLs
if (strpos($path, '/.well-known/oauth-protected-resource') !== false) {
    // Redirect to the correct protected resource metadata endpoint
    include dirname(__FILE__) . '/oauth-protected-resource/index.php';
    exit;
}

if (strpos($path, '/.well-known/oauth-authorization-server') !== false) {
    // Redirect to the correct authorization server metadata endpoint
    include dirname(__FILE__) . '/oauth-authorization-server/index.php';
    exit;
}

if (strpos($path, '/.well-known/jwks') !== false) {
    // Redirect to the JWKS endpoint
    include dirname(__FILE__) . '/jwks/index.php';
    exit;
}

if (strpos($path, '/.well-known/docs') !== false) {
    // Redirect to the documentation endpoint
    include dirname(__FILE__) . '/docs/index.php';
    exit;
}

// If we get here, it's an unknown .well-known request
http_response_code(404);
header('Content-Type: application/json');
echo json_encode([
    'error' => 'not_found',
    'error_description' => 'Unknown .well-known endpoint: ' . $path,
    'available_endpoints' => [
        '/.well-known/oauth-protected-resource',
        '/.well-known/oauth-authorization-server',
        '/.well-known/jwks',
        '/.well-known/docs'
    ]
]);
?>
