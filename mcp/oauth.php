<?php
// OAuth 2.1 Server for MCP Clients with PKCE and Resource Indicators (RFC 8707)
// Compliant with RFC 9728 (Protected Resource Metadata) and RFC 8414 (Authorization Server Metadata)

// Configuration for MCP clients
$storage_dir = XOOPS_ROOT_PATH.'/mcp/oauth_storage/'; // Directory for storing OAuth data

// Debug: Ensure storage directory is properly set
if (empty($storage_dir) || $storage_dir === '/mcp/oauth_storage/') {
    error_log('Storage directory issue! XOOPS_ROOT_PATH: ' . (XOOPS_ROOT_PATH ?? 'undefined'));
    $storage_dir = sys_get_temp_dir() . '/formulize_oauth_storage/'; // Fallback
}

// Allowed redirect URI patterns for MCP clients
// Production-ready patterns that allow real-world usage
$allowed_redirect_patterns = [
    '/^https:/', // Any HTTPS URL (production apps)
    '/^http:\/\/localhost/', // localhost for development (any port/path)
    '/^http:\/\/127\.0\.0\.1/', // 127.0.0.1 for development
    '/^mcp:/', // Custom MCP protocol for desktop apps
];

// Create storage directory if it doesn't exist
if (!is_dir($storage_dir)) {
    mkdir($storage_dir, 0755, true);
}

// File paths for persistent storage

global $auth_codes_file, $access_tokens_file, $pkce_challenges_file, $resource_bindings_file;

$auth_codes_file = $storage_dir . 'authorization_codes.json';
$access_tokens_file = $storage_dir . 'access_tokens.json';
$pkce_challenges_file = $storage_dir . 'pkce_challenges.json';
$resource_bindings_file = $storage_dir . 'resource_bindings.json';

// Helper functions for persistence
function loadData($file) {
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    return $data ?: [];
}

function saveData($file, $data) {
    if (empty($file)) {
        error_log('saveData called with empty file path');
        return false;
    }
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

function cleanExpiredData($data, $time_field = 'expires') {
    $current_time = time();
    return array_filter($data, function($item) use ($current_time, $time_field) {
        return isset($item[$time_field]) && $item[$time_field] > $current_time;
    });
}

// Load persistent data functions
function getAuthorizationCodes() {
    global $auth_codes_file;
    $codes = loadData($auth_codes_file);
    $codes = cleanExpiredData($codes);
    saveData($auth_codes_file, $codes);
    return $codes;
}

function saveAuthorizationCodes($codes) {
    global $auth_codes_file;
    saveData($auth_codes_file, $codes);
}

function getAccessTokens() {
    global $access_tokens_file;
    $tokens = loadData($access_tokens_file);
    $tokens = cleanExpiredData($tokens);
    saveData($access_tokens_file, $tokens);
    return $tokens;
}

function saveAccessTokens($tokens) {
    global $access_tokens_file;
    saveData($access_tokens_file, $tokens);
}

function getPkceChallenges() {
    global $pkce_challenges_file;
    $challenges = loadData($pkce_challenges_file);
    $challenges = cleanExpiredData($challenges);
    saveData($pkce_challenges_file, $challenges);
    return $challenges;
}

function savePkceChallenges($challenges) {
    global $pkce_challenges_file;
    saveData($pkce_challenges_file, $challenges);
}

function getResourceBindings() {
    global $resource_bindings_file;
    $bindings = loadData($resource_bindings_file);
    $bindings = cleanExpiredData($bindings);
    saveData($resource_bindings_file, $bindings);
    return $bindings;
}

function saveResourceBindings($bindings) {
    global $resource_bindings_file;
    saveData($resource_bindings_file, $bindings);
}

// Helper functions
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

function isValidRedirectUri($uri) {
    // Define patterns directly in function to avoid scope issues
    $patterns = [
        '/^https:/', // Any HTTPS URL (production apps)
        '/^http:\/\/localhost/', // localhost for development (any port/path)
        '/^http:\/\/127\.0\.0\.1/', // 127.0.0.1 for development
        '/^mcp:/', // Custom MCP protocol for desktop apps
    ];

    // Debug logging
    error_log("Validating redirect URI: $uri");

    foreach ($patterns as $i => $pattern) {
        $matches = preg_match($pattern, $uri);
        error_log("Pattern $i: $pattern - " . ($matches ? 'MATCH' : 'no match'));

        if ($matches) {
            error_log("URI $uri is VALID (matched pattern $i)");
            return true;
        }
    }

    error_log("URI $uri is INVALID (no patterns matched)");
    return false;
}

function verifyPKCE($code_verifier, $code_challenge, $code_challenge_method = 'S256') {
    if ($code_challenge_method === 'S256') {
        $computed_challenge = base64UrlEncode(hash('sha256', $code_verifier, true));
        return hash_equals($code_challenge, $computed_challenge);
    } elseif ($code_challenge_method === 'plain') {
        return hash_equals($code_challenge, $code_verifier);
    }
    return false;
}

/**
 * Validate and normalize resource parameter according to RFC 8707
 */
function validateAndNormalizeResource($resource) {
    if (empty($resource)) {
        return null;
    }

    // Parse the resource URI
    $parsed = parse_url($resource);
    if ($parsed === false) {
        return false; // Invalid URI
    }

    // Must have scheme and host
    if (!isset($parsed['scheme']) || !isset($parsed['host'])) {
        return false;
    }

    // Normalize scheme and host to lowercase
    $normalized_scheme = strtolower($parsed['scheme']);
    $normalized_host = strtolower($parsed['host']);

    // Only allow https for production, http for localhost
    if ($normalized_scheme !== 'https' && !($normalized_scheme === 'http' &&
        ($normalized_host === 'localhost' || $normalized_host === '127.0.0.1'))) {
        return false;
    }

    // Reconstruct the canonical URI
    $canonical = $normalized_scheme . '://' . $normalized_host;

    if (isset($parsed['port'])) {
        $canonical .= ':' . $parsed['port'];
    }

    if (isset($parsed['path']) && $parsed['path'] !== '/') {
        $canonical .= rtrim($parsed['path'], '/');
    }

    // RFC 8707: Must not contain query string or fragment
    if (isset($parsed['query']) || isset($parsed['fragment'])) {
        return false;
    }

    return $canonical;
}

/**
 * Check if a resource is valid for this MCP server
 */
function isValidResourceForServer($resource) {
    $canonicalMcpUrl = XOOPS_URL . '/mcp'; // Canonical form without trailing slash (matches normalization)
    $canonicalMcpUrlWithSlash = XOOPS_URL . '/mcp/'; // Also accept with trailing slash
    // Accept the canonical MCP URL (with or without trailing slash) or the base URL
    return ($resource === $baseUrl || $resource === $canonicalMcpUrl || $resource === $canonicalMcpUrlWithSlash);
}

function handleAuthorizationRequest() {
    // Get parameters
    $client_id = $_GET['client_id'] ?? '';
    $redirect_uri = $_GET['redirect_uri'] ?? '';
    $response_type = $_GET['response_type'] ?? '';
    $scope = $_GET['scope'] ?? 'read';
    $state = $_GET['state'] ?? '';
    $code_challenge = $_GET['code_challenge'] ?? '';
    $code_challenge_method = $_GET['code_challenge_method'] ?? 'S256';
    $resource = $_GET['resource'] ?? '';

    // URL decode the resource parameter if needed (should be automatic, but just in case)
    if ($resource) {
        $resource = urldecode($resource);
    }

    // Validate response type
    if ($response_type !== 'code') {
        redirectWithError($redirect_uri, 'unsupported_response_type', 'Only authorization code flow is supported', $state);
        return;
    }

    // Validate redirect URI
    if (!isValidRedirectUri($redirect_uri)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'invalid_request',
            'error_description' => 'Invalid redirect URI. Must be HTTPS for production or localhost for development.'
        ]);
        return;
    }

    // Validate and normalize resource parameter (RFC 8707)
    $normalized_resource = validateAndNormalizeResource($resource);

    if ($resource && $normalized_resource === false) {
        error_log("OAuth Error: Resource normalization failed");
        redirectWithError($redirect_uri, 'invalid_request', 'Invalid resource parameter format', $state);
        return;
    }

    if ($normalized_resource && !isValidResourceForServer($normalized_resource)) {
        redirectWithError($redirect_uri, 'invalid_target', 'Resource not valid for this authorization server', $state);
        return;
    }

    // For MCP clients, we require PKCE for security
    if (empty($code_challenge)) {
        redirectWithError($redirect_uri, 'invalid_request', 'PKCE code_challenge is required for MCP clients', $state);
        return;
    }

    // Validate PKCE challenge method
    if (!in_array($code_challenge_method, ['S256', 'plain'])) {
        redirectWithError($redirect_uri, 'invalid_request', 'Unsupported code_challenge_method', $state);
        return;
    }

    // Store PKCE challenge
    $challenge_key = generateRandomString();
    $pkce_challenges = getPkceChallenges();
    $pkce_challenges[$challenge_key] = [
        'code_challenge' => $code_challenge,
        'code_challenge_method' => $code_challenge_method,
        'expires' => time() + 600 // 10 minutes
    ];
    savePkceChallenges($pkce_challenges);

    // Store request parameters in session
    $_SESSION['oauth_request'] = [
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri,
        'scope' => $scope,
        'state' => $state,
        'challenge_key' => $challenge_key,
        'resource' => $normalized_resource // Store normalized resource
    ];

    // Show consent screen
    showConsentScreen($client_id, $scope, $state, $normalized_resource);
}

function showConsentScreen($client_id, $scope, $state, $resource = null) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>OAuth Authorization - MCP Client</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                max-width: 500px; margin: 50px auto; padding: 20px;
                background-color: #f5f5f5;
            }
            .consent-box {
                border: 1px solid #ddd; padding: 30px; border-radius: 8px;
                background-color: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .app-info {
                background-color: #e8f4ff; padding: 20px; border-radius: 6px;
                margin-bottom: 20px; border-left: 4px solid #007acc;
            }
            .permissions {
                background-color: #f8f9fa; padding: 15px; border: 1px solid #e9ecef;
                border-radius: 5px; margin: 15px 0;
            }
            .resource-info {
                background-color: #f0f8e8; padding: 15px; border-radius: 5px;
                border-left: 4px solid #28a745; margin: 15px 0; font-size: 14px;
            }
            .security-note {
                background-color: #fff3cd; padding: 15px; border-radius: 5px;
                border-left: 4px solid #ffc107; margin: 15px 0; font-size: 14px;
            }
            .buttons { text-align: center; margin-top: 25px; }
            button {
                padding: 12px 24px; margin: 0 10px; border: none;
                border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: 500;
            }
            .approve { background-color: #28a745; color: white; }
            .approve:hover { background-color: #218838; }
            .deny { background-color: #6c757d; color: white; }
            .deny:hover { background-color: #5a6268; }
            h2 { color: #333; margin-bottom: 10px; }
            h3 { color: #007acc; margin: 0 0 10px 0; }
            code { background-color: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
        </style>
    </head>
    <body>
        <div class="consent-box">
            <h2>🔐 Authorization Request</h2>

            <div class="app-info">
                <h3>MCP Client Application</h3>
                <p><strong>Client ID:</strong> <code><?php echo htmlspecialchars($client_id); ?></code></p>
                <p>An MCP (Model Context Protocol) client is requesting access to connect with this server.</p>
            </div>

            <?php if ($resource): ?>
            <div class="resource-info">
                <h4>🎯 Target Resource:</h4>
                <p><code><?php echo htmlspecialchars($resource); ?></code></p>
                <p>This token will be bound to the specific resource above and cannot be used elsewhere.</p>
            </div>
            <?php endif; ?>

            <div class="permissions">
                <h4>📋 Requested Permissions:</h4>
                <ul>
                    <?php
                    $scopes = array_filter(explode(' ', $scope));
                    if (empty($scopes)) {
                        echo "<li>Basic access</li>";
                    } else {
                        foreach ($scopes as $s) {
                            $scope_descriptions = [
                                'read' => 'Read your data',
                                'write' => 'Modify your data',
                                'read_data' => 'Read your data',
                                'write_data' => 'Write and modify your data',
                                'admin' => 'Administrative access',
								'claudeai' => 'Access to Claude AI integration',
                            ];
                            $description = $scope_descriptions[$s] ?? ucfirst(str_replace('_', ' ', $s));
                            echo "<li><strong>" . htmlspecialchars($s) . "</strong> - " . htmlspecialchars($description) . "</li>";
                        }
                    }
                    ?>
                </ul>
            </div>

            <div class="security-note">
                <strong>🔒 Security Features:</strong>
                <ul>
                    <li>PKCE (Proof Key for Code Exchange) for enhanced security</li>
                    <?php if ($resource): ?>
                    <li>Resource binding (RFC 8707) - token limited to specific resource</li>
                    <?php endif; ?>
                    <li>No client secrets transmitted or stored</li>
                </ul>
            </div>

            <form method="POST" action="<?php echo XOOPS_URL ?>/oauth/consent">
                <input type="hidden" name="state" value="<?php echo htmlspecialchars($state); ?>">
                <div class="buttons">
                    <button type="submit" name="decision" value="approve" class="approve">
                        ✅ Authorize Connection
                    </button>
                    <button type="submit" name="decision" value="deny" class="deny">
                        ❌ Cancel
                    </button>
                </div>
            </form>
        </div>

        <div style="text-align: center; margin-top: 20px; color: #666; font-size: 14px;">
            <p>This authorization follows OAuth 2.1 security best practices with PKCE and resource binding.</p>
        </div>
    </body>
    </html>
    <?php
}

function handleConsentSubmission() {
    if (!isset($_SESSION['oauth_request'])) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_request', 'error_description' => 'No pending authorization request']);
        return;
    }

    $oauth_request = $_SESSION['oauth_request'];
    $decision = $_POST['decision'] ?? '';
    $state = $_POST['state'] ?? '';

    if ($decision === 'deny') {
        // User denied authorization
        redirectWithError($oauth_request['redirect_uri'], 'access_denied', 'User denied authorization', $state);
        unset($_SESSION['oauth_request']);
        return;
    }

    if ($decision === 'approve') {
        // Generate authorization code
        $auth_code = generateRandomString();

        // Load existing authorization codes and add new one
        $authorization_codes = getAuthorizationCodes();
        $authorization_codes[$auth_code] = [
            'client_id' => $oauth_request['client_id'],
            'redirect_uri' => $oauth_request['redirect_uri'],
            'scope' => $oauth_request['scope'],
            'challenge_key' => $oauth_request['challenge_key'],
            'resource' => $oauth_request['resource'], // Store resource binding
            'expires' => time() + 300, // 5 minutes
            'used' => false
        ];
        saveAuthorizationCodes($authorization_codes);

        // Redirect back to client with authorization code
        $redirect_url = $oauth_request['redirect_uri'] . '?' . http_build_query([
            'code' => $auth_code,
            'state' => $state
        ]);

        header('Location: ' . $redirect_url);
        unset($_SESSION['oauth_request']);
        return;
    }

    http_response_code(400);
    echo json_encode(['error' => 'invalid_request']);
}

function handleTokenExchange() {
    // This endpoint expects POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'invalid_request', 'error_description' => 'Method not allowed']);
        return;
    }

    // Get parameters from POST body
    $grant_type = $_POST['grant_type'] ?? '';
    $code = $_POST['code'] ?? '';
    $client_id = $_POST['client_id'] ?? '';
    $redirect_uri = $_POST['redirect_uri'] ?? '';
    $code_verifier = $_POST['code_verifier'] ?? '';
    $resource = $_POST['resource'] ?? '';

    // Validate grant type
    if ($grant_type !== 'authorization_code') {
        http_response_code(400);
        echo json_encode(['error' => 'unsupported_grant_type']);
        return;
    }

    // Load authorization codes from storage
    $authorization_codes = getAuthorizationCodes();

    // Validate authorization code exists
    if (!isset($authorization_codes[$code])) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant', 'error_description' => 'Invalid authorization code']);
        return;
    }

    $auth_data = $authorization_codes[$code];

    // Check if code is expired
    if (time() > $auth_data['expires']) {
        unset($authorization_codes[$code]);
        saveAuthorizationCodes($authorization_codes);
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant', 'error_description' => 'Authorization code expired']);
        return;
    }

    // Check if code was already used
    if ($auth_data['used']) {
        unset($authorization_codes[$code]);
        saveAuthorizationCodes($authorization_codes);
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant', 'error_description' => 'Authorization code already used']);
        return;
    }

    // Validate client_id matches
    if ($client_id !== $auth_data['client_id']) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_client', 'error_description' => 'Client ID mismatch']);
        return;
    }

    // Validate redirect URI matches
    if ($redirect_uri !== $auth_data['redirect_uri']) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant', 'error_description' => 'Redirect URI mismatch']);
        return;
    }

    // Validate resource parameter (RFC 8707)
    $normalized_resource = validateAndNormalizeResource($resource);
    if ($resource && $normalized_resource === false) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_request', 'error_description' => 'Invalid resource parameter format']);
        return;
    }

    // Check resource consistency between authorization and token requests
    if ($auth_data['resource'] !== $normalized_resource) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant', 'error_description' => 'Resource parameter mismatch between authorization and token requests']);
        return;
    }

    // Verify PKCE challenge
    if (empty($code_verifier)) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_request', 'error_description' => 'PKCE code_verifier is required']);
        return;
    }

    // Load PKCE challenges from storage
    $pkce_challenges = getPkceChallenges();
    $challenge_key = $auth_data['challenge_key'];

    if (!isset($pkce_challenges[$challenge_key])) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant', 'error_description' => 'PKCE challenge not found']);
        return;
    }

    $challenge_data = $pkce_challenges[$challenge_key];

    // Check PKCE challenge expiration
    if (time() > $challenge_data['expires']) {
        unset($pkce_challenges[$challenge_key]);
        savePkceChallenges($pkce_challenges);
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant', 'error_description' => 'PKCE challenge expired']);
        return;
    }

    // Verify PKCE
    if (!verifyPKCE($code_verifier, $challenge_data['code_challenge'], $challenge_data['code_challenge_method'])) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant', 'error_description' => 'PKCE verification failed']);
        return;
    }

    // Mark code as used and save
    $authorization_codes[$code]['used'] = true;
    saveAuthorizationCodes($authorization_codes);

    // Generate access token
    $access_token = "6ad4699588621d6a4fff14df40778ae3"; // Example fixed string for testing // generateRandomString();

    // Load existing access tokens and add new one
    $access_tokens = getAccessTokens();
    $access_tokens[$access_token] = [
        'client_id' => $client_id,
        'scope' => $auth_data['scope'],
        'resource' => $auth_data['resource'], // Bind token to resource
        'expires' => time() + 3600, // 1 hour
        'token_type' => 'Bearer'
    ];
    saveAccessTokens($access_tokens);

    // Return token response
    header('Content-Type: application/json');
    $response = [
        'access_token' => $access_token,
        'token_type' => 'Bearer',
        'expires_in' => 3600,
        'scope' => $auth_data['scope']
    ];

    // Include resource in response if it was bound (RFC 8707)
    if ($auth_data['resource']) {
        $response['resource'] = $auth_data['resource'];
    }

    echo json_encode($response);

    // Clean up used authorization code and PKCE challenge
    unset($authorization_codes[$code]);
    unset($pkce_challenges[$challenge_key]);
    saveAuthorizationCodes($authorization_codes);
    savePkceChallenges($pkce_challenges);
}

function handleDynamicClientRegistration() {
    // Handle preflight (already handled globally, but be safe)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        return;
    }

    // Dynamic Client Registration endpoint (RFC 7591)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST, OPTIONS');
        echo json_encode([
            'error' => 'invalid_request',
            'error_description' => 'Method not allowed. Use POST for client registration.'
        ]);
        return;
    }

    // Get and validate input
    $input_raw = file_get_contents('php://input');
    if (empty($input_raw)) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_request', 'error_description' => 'Empty request body']);
        return;
    }

    $input = json_decode($input_raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_request', 'error_description' => 'Invalid JSON in request body: ' . json_last_error_msg()]);
        return;
    }

    $client_name = $input['client_name'] ?? 'MCP Client';
    $redirect_uris = $input['redirect_uris'] ?? [];

    // Validate that redirect_uris is provided and is an array
    if (!is_array($redirect_uris) || empty($redirect_uris)) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_redirect_uri', 'error_description' => 'redirect_uris must be a non-empty array']);
        return;
    }

    // Validate each redirect URI
    foreach ($redirect_uris as $uri) {
        if (!is_string($uri) || !isValidRedirectUri($uri)) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_redirect_uri', 'error_description' => "Invalid redirect URI: $uri. Must be HTTPS for production or localhost for development."]);
            return;
        }
    }

    // Generate client credentials
    $client_id = 'mcp_' . generateRandomString(16);

    // Prepare the response according to RFC 7591
    $response = [
        'client_id' => $client_id,
        'client_name' => $client_name,
        'redirect_uris' => $redirect_uris,
        'grant_types' => ['authorization_code'],
        'response_types' => ['code'],
        'token_endpoint_auth_method' => 'none', // Public client
        'client_id_issued_at' => time(),
        'software_id' => 'formulize-mcp-server',
        'software_version' => '1.0'
    ];

    // Return registration response
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(201); // Created
    echo json_encode($response, JSON_PRETTY_PRINT);
}

function handleProtectedResource() {
    $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (!preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
        http_response_code(401);
        header('WWW-Authenticate: Bearer realm="Formulize MCP Server", resource_metadata="' . XOOPS_URL . '/.well-known/oauth-protected-resource"');
        echo json_encode(['error' => 'invalid_token', 'error_description' => 'Missing or invalid authorization header']);
        return;
    }

    $token = $matches[1];

    // Load access tokens from storage
    $access_tokens = getAccessTokens();

    if (!isset($access_tokens[$token])) {
        http_response_code(401);
        header('WWW-Authenticate: Bearer realm="Formulize MCP Server", resource_metadata="' . XOOPS_URL . '/.well-known/oauth-protected-resource"');
        echo json_encode(['error' => 'invalid_token', 'error_description' => 'Token not found']);
        return;
    }

    $token_data = $access_tokens[$token];

    if (time() > $token_data['expires']) {
        unset($access_tokens[$token]);
        saveAccessTokens($access_tokens);
        http_response_code(401);
        header('WWW-Authenticate: Bearer realm="Formulize MCP Server", resource_metadata="' . XOOPS_URL . '/.well-known/oauth-protected-resource"');
        echo json_encode(['error' => 'invalid_token', 'error_description' => 'Token expired']);
        return;
    }

    // Validate resource binding (RFC 8707)
    if (isset($token_data['resource'])) {
        $canonical_resource = XOOPS_URL . '/mcp';
        $canonical_resource_with_slash = XOOPS_URL . '/mcp/';

        // Only accept the canonical resource URLs (with or without trailing slash) or base URL
        if ($token_data['resource'] !== $canonical_resource &&
            $token_data['resource'] !== $canonical_resource_with_slash &&
            $token_data['resource'] !== $base_url) {
            http_response_code(403);
            echo json_encode(['error' => 'insufficient_scope', 'error_description' => 'Token not valid for this resource']);
            return;
        }
    }

    // Return protected resource data
    header('Content-Type: application/json');
    echo json_encode([
        'message' => 'Hello from Formulize MCP Server!',
        'scope' => $token_data['scope'],
        'client_id' => $token_data['client_id'],
        'resource' => $token_data['resource'] ?? null,
        'server_time' => date('c'),
        'data' => [
            'user_id' => 'mcp_user_001',
            'permissions' => explode(' ', $token_data['scope'])
        ]
    ]);
}

function handleStatusCheck() {
    // Debug endpoint to check stored data
    $auth_codes = getAuthorizationCodes();
    $access_tokens = getAccessTokens();
    $pkce_challenges = getPkceChallenges();

    // Check storage directory permissions
    global $storage_dir;
    $storage_info = [
        'directory' => $storage_dir,
        'exists' => is_dir($storage_dir),
        'writable' => is_writable($storage_dir),
        'readable' => is_readable($storage_dir)
    ];

    if (is_dir($storage_dir)) {
        $files = scandir($storage_dir);
        $storage_info['files'] = array_filter($files, function($file) {
            return $file !== '.' && $file !== '..';
        });
    }

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ok',
        'storage' => $storage_info,
        'counts' => [
            'authorization_codes' => count($auth_codes),
            'access_tokens' => count($access_tokens),
            'pkce_challenges' => count($pkce_challenges)
        ],
        'server_info' => [
            'base_url' => XOOPS_URL,
            'resource_metadata_url' => XOOPS_URL . '/.well-known/oauth-protected-resource',
            'authorization_server_metadata_url' => XOOPS_URL . '/.well-known/oauth-authorization-server'
        ],
        'oauth_compliance' => [
            'oauth_2_1' => true,
            'pkce_required' => true,
            'resource_indicators_rfc8707' => true,
            'protected_resource_metadata_rfc9728' => true,
            'authorization_server_metadata_rfc8414' => true
        ],
        'endpoints' => [
            'authorize' => XOOPS_URL . '/oauth/authorize',
            'token' => XOOPS_URL . '/oauth/token',
            'register' => XOOPS_URL . '/oauth/register',
            'resource' => XOOPS_URL . '/oauth/resource',
            'status' => XOOPS_URL . '/oauth/status'
        ],
        'session_info' => [
            'session_id' => session_id(),
            'session_status' => session_status(),
            'session_data' => $_SESSION ?? []
        ],
        'request_info' => [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'none',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ],
        'server_time' => date('c')
    ], JSON_PRETTY_PRINT);
}

function redirectWithError($redirect_uri, $error, $description, $state = '') {
    if (empty($redirect_uri) || !isValidRedirectUri($redirect_uri)) {
        http_response_code(400);
        echo json_encode(['error' => $error, 'error_description' => $description]);
        return;
    }

    $params = ['error' => $error, 'error_description' => $description];
    if (!empty($state)) {
        $params['state'] = $state;
    }

    $redirect_url = $redirect_uri . '?' . http_build_query($params);
    header('Location: ' . $redirect_url);
}

/*
OAUTH 2.1 COMPLIANCE WITH RFC 8707 RESOURCE INDICATORS

This updated implementation provides clean OAuth endpoints:

ENDPOINTS:
- /oauth/authorize - Authorization endpoint
- /oauth/token - Token exchange endpoint
- /oauth/register - Dynamic client registration
- /oauth/resource - Protected resource test
- /oauth/status - Debug/status endpoint
- /oauth/consent - Consent form submission

WELL-KNOWN ENDPOINTS:
- /.well-known/oauth-protected-resource - Resource metadata (RFC 9728)
- /.well-known/oauth-authorization-server - Authorization server metadata (RFC 8414)

MCP CLIENT OAUTH FLOW WITH CLEAN URLS:

1. Authorization Request:
   GET /oauth/authorize?client_id=my_mcp_client&redirect_uri=http://localhost:8080/callback&response_type=code&scope=read_data&state=xyz123&code_challenge=CODE_CHALLENGE&code_challenge_method=S256&resource=https://example.com/mcp

2. Token Exchange:
   POST /oauth/token
   Content-Type: application/x-www-form-urlencoded

   grant_type=authorization_code&code=AUTH_CODE&client_id=my_mcp_client&redirect_uri=http://localhost:8080/callback&code_verifier=CODE_VERIFIER&resource=https://example.com/mcp

3. Protected Resource Access:
   GET /oauth/resource
   Authorization: Bearer ACCESS_TOKEN

This solves the URL parameter conflict by providing dedicated endpoints without query parameters.
*/
