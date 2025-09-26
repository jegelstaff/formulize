<?php
/**
 * OAuth Resource Documentation endpoint
 * Location: /.well-known/docs
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

// Get the base URL
$scheme = $_SERVER['REQUEST_SCHEME'] ?? (($_SERVER['HTTPS'] ?? 'off') === 'on' ? 'https' : 'http');
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $scheme . '://' . $host;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Formulize MCP Server - OAuth Documentation</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px; margin: 50px auto; padding: 20px;
            line-height: 1.6; color: #333;
        }
        .endpoint { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
        code { background: #e9ecef; padding: 2px 5px; border-radius: 3px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        h1, h2 { color: #007acc; }
        .note { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0; }
    </style>
</head>
<body>
    <h1>🔐 Formulize MCP Server - OAuth 2.1 Documentation</h1>

    <div class="note">
        <strong>📋 OAuth 2.1 Compliance:</strong> This server implements OAuth 2.1 with PKCE, Resource Indicators (RFC 8707),
        Protected Resource Metadata (RFC 9728), and Authorization Server Metadata (RFC 8414).
    </div>

    <h2>🔍 Discovery Endpoints</h2>
    <div class="endpoint">
        <strong>Protected Resource Metadata:</strong><br>
        <code>GET <?php echo $baseUrl; ?>/.well-known/oauth-protected-resource</code>
    </div>
    <div class="endpoint">
        <strong>Authorization Server Metadata:</strong><br>
        <code>GET <?php echo $baseUrl; ?>/.well-known/oauth-authorization-server</code>
    </div>

    <h2>🚀 OAuth Endpoints</h2>
    <div class="endpoint">
        <strong>Authorization Endpoint:</strong><br>
        <code>GET <?php echo $baseUrl; ?>/mcp?action=authorize</code><br>
        <small>Parameters: client_id, redirect_uri, response_type=code, scope, state, code_challenge, code_challenge_method=S256, resource</small>
    </div>
    <div class="endpoint">
        <strong>Token Endpoint:</strong><br>
        <code>POST <?php echo $baseUrl; ?>/mcp?action=token</code><br>
        <small>Parameters: grant_type=authorization_code, code, client_id, redirect_uri, code_verifier, resource</small>
    </div>
    <div class="endpoint">
        <strong>Client Registration:</strong><br>
        <code>POST <?php echo $baseUrl; ?>/mcp?action=register</code><br>
        <small>Dynamic client registration (RFC 7591)</small>
    </div>

    <h2>🛡️ Security Features</h2>
    <ul>
        <li><strong>PKCE Required:</strong> All authorization flows must use PKCE for security</li>
        <li><strong>Resource Indicators:</strong> Tokens are bound to specific resources (RFC 8707)</li>
        <li><strong>Public Clients:</strong> No client secrets required</li>
        <li><strong>Short-lived Tokens:</strong> Access tokens expire in 1 hour</li>
    </ul>

    <h2>🎯 Resource Binding</h2>
    <p>This server requires the <code>resource</code> parameter in authorization and token requests to bind tokens to specific resources:</p>
    <ul>
        <li><code><?php echo $baseUrl; ?>/mcp</code> - Main MCP endpoint</li>
        <li><code><?php echo $baseUrl; ?></code> - Base server resource</li>
    </ul>

    <h2>📝 Scopes</h2>
    <ul>
        <li><code>read</code> - Read access to data</li>
        <li><code>write</code> - Write access to data</li>
        <li><code>read_data</code> - Read form data and entries</li>
        <li><code>write_data</code> - Create and modify form data and entries</li>
				<li><code>claudeai</code> - Access to Claude AI integration</li>
    </ul>

    <h2>🔧 MCP Access</h2>
    <div class="endpoint">
        <strong>MCP Server:</strong><br>
        <code>POST <?php echo $baseUrl; ?>/mcp</code><br>
        <small>Include: <code>Authorization: Bearer {access_token}</code></small>
    </div>

    <h2>⚡ Example Flow</h2>
    <pre>
1. GET /.well-known/oauth-protected-resource
2. GET /.well-known/oauth-authorization-server
3. GET /mcp?action=authorize&client_id=...&resource=<?php echo $baseUrl; ?>/mcp&...
4. POST /mcp?action=token (with code_verifier and resource)
5. POST /mcp (with Bearer token)
    </pre>

    <hr>
    <p><small>🏠 <a href="<?php echo $baseUrl; ?>">Return to Formulize</a> | 🔍 <a href="<?php echo $baseUrl; ?>/mcp/health">Server Health</a></small></p>
</body>
</html>
