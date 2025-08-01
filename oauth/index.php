<?php
/**
 * OAuth 2.1 Endpoint Router for Formulize MCP Server
 *
 * This file handles all OAuth endpoints at the site root level:
 * - /oauth/authorize
 * - /oauth/token
 * - /oauth/register
 * - /oauth/resource
 * - /oauth/status
 */

require_once '../mainfile.php';

// Set CORS headers for OAuth requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Max-Age: 86400');
header('Access-Control-Allow-Credentials: false');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// Include the OAuth handler
include_once XOOPS_ROOT_PATH . '/mcp/oauth.php';

// Parse the request path to determine the OAuth action
$requestUri = $_SERVER['REQUEST_URI'];
$parsedUrl = parse_url($requestUri);
$path = $parsedUrl['path'] ?? '';

// Remove any trailing slashes and get the last segment
$pathSegments = array_filter(explode('/', trim($path, '/')));
$action = end($pathSegments);

// Map paths to actions
switch ($action) {
    case 'authorize':
        handleAuthorizationRequest();
        break;

    case 'consent':
        handleConsentSubmission();
        break;

    case 'token':
        handleTokenExchange();
        break;

    case 'register':
        handleDynamicClientRegistration();
        break;

    case 'resource':
        handleProtectedResource();
        break;

    case 'status':
        handleStatusCheck();
        break;

    default:
        // Default behavior - if no specific action, try to determine from parameters
        if (isset($_GET['response_type']) && $_GET['response_type'] === 'code') {
            handleAuthorizationRequest();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grant_type'])) {
            handleTokenExchange();
        } else {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'not_found',
                'error_description' => 'OAuth endpoint not found',
                'available_endpoints' => [
                    'authorize' => '/oauth/authorize',
                    'token' => '/oauth/token',
                    'register' => '/oauth/register',
                    'resource' => '/oauth/resource',
                    'status' => '/oauth/status'
                ]
            ]);
        }
}
