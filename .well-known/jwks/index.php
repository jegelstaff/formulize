<?php
/**
 * JSON Web Key Set (JWKS) endpoint
 * Location: /.well-known/jwks
 * 
 * This is optional for Bearer token OAuth but included for completeness
 */

// Handle CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET, OPTIONS');
    exit;
}

// For now, return empty JWKS since we're using bearer tokens, not JWT
$jwks = [
    'keys' => []
];

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

// Output the JWKS
echo json_encode($jwks, JSON_PRETTY_PRINT);
?>
