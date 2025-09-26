<?php
/**
 * Simple test endpoint to verify .well-known is working
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

echo json_encode([
    'status' => 'ok',
    'message' => '.well-known directory is working',
    'timestamp' => date('c'),
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'available_endpoints' => [
        '/.well-known/test',
        '/.well-known/oauth-protected-resource',
        '/.well-known/oauth-authorization-server',
        '/.well-known/jwks',
        '/.well-known/docs'
    ]
], JSON_PRETTY_PRINT);
?>
