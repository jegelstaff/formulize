<?php

require_once '../mainfile.php';
include_once XOOPS_ROOT_PATH . '/mcp/mcp.php';
$server = new FormulizeMCP();

?>

<!DOCTYPE html>
<html>

<head>
	<title>Formulize MCP HTTP Server v<?php echo FORMULIZE_MCP_VERSION; ?></title>
	<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
	<style>
		body {
			font-family: Arial, sans-serif;
			max-width: 800px;
			margin: 0 auto;
			padding: 20px;
		}

		.endpoint {
			background: #f5f5f5;
			padding: 15px;
			margin: 10px 0;
			border-radius: 5px;
		}

		.method {
			color: #007cba;
			font-weight: bold;
		}

		pre {
			background: #eee;
			padding: 10px;
			border-radius: 3px;
			overflow-x: auto;
		}

		.success {
			color: #2e7d32;
		}

		.feature {
			background: #e3f2fd;
			padding: 10px;
			margin: 5px 0;
			border-radius: 3px;
		}
	</style>
</head>

<body>
	<h1>Formulize MCP HTTP Server v<?php echo FORMULIZE_MCP_VERSION; ?></h1>
	<p class="success">✅ Featuring Tools, Resources, and Prompts!</p>

	<h2>Endpoints:</h2>

	<div class="endpoint">
		<h3><span class="method">POST</span> /mcp</h3>
		<p>Main MCP endpoint - send JSON-RPC requests like this:</p>
		<pre>curl -X POST <?php echo $server->baseUrl; ?>/mcp \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_FORMULIZE_API_KEY" \
  -d '{"jsonrpc":"2.0","method":"tools/list","params":{},"id":1}'</pre>
	</div>

	<div class="endpoint">
		<h3><span class="method">GET</span> /capabilities</h3>
		<p>MCP server capabilities and authentication info</p>
		<p><a href="<?php echo $server->baseUrl; ?>/capabilities">View capabilities</a></p>
	</div>

	<div class="endpoint">
		<h3><span class="method">GET</span> /health</h3>
		<p>Health check endpoint</p>
		<p><a href="<?php echo $server->baseUrl; ?>/health">Check health</a></p>
	</div>

	<h2>Authentication:</h2>
	<div class="highlight">
		<p><strong>How to get your API key:</strong></p>
		<ol>
			<li>Login to your Formulize system</li>
			<li>Go to Admin → Manage API Keys</li>
			<li>Create or copy your API key</li>
			<li>Use it as: <code>Authorization: Bearer YOUR_API_KEY</code></li>
		</ol>
		<p><strong>Site behind HTTP Basic Auth?</strong> Then the Authorization header is needed for your Basic Auth credentials, so send the API key in its own header instead:</p>
		<pre>curl -X POST <?php echo $server->baseUrl; ?>/mcp \
  -u BASIC_AUTH_USER:BASIC_AUTH_PASSWORD \
  -H "Content-Type: application/json" \
  -H "X-Formulize-API-Key: YOUR_FORMULIZE_API_KEY" \
  -d '{"jsonrpc":"2.0","method":"tools/list","params":{},"id":1}'</pre>
		<p>When the MCP Server preference is turned on, Formulize checks that it can reach its own MCP server, and it forwards your Basic Auth credentials to get through. If your server does not pass those credentials to PHP (common with PHP-FPM unless <code>CGIPassAuth On</code> is set), the check is refused and the preference will not stay on. In that case, let the server reach itself without Basic Auth, or exempt the <code>/mcp/</code> folder from Basic Auth. For example, in Apache:</p>
		<pre>&lt;RequireAny&gt;
    Require ip 127.0.0.1 ::1
    Require valid-user
&lt;/RequireAny&gt;</pre>
	</div>

	<h2>Available Capabilities:</h2>

	<div class="feature">
		<h3>🔧 Tools (<?php echo count($server->tools); ?> available)</h3>
		<ul>
			<?php foreach ($server->tools as $tool): ?>
				<li><strong><?php echo htmlspecialchars($tool['name']); ?></strong> - <?php echo htmlspecialchars($tool['description']); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>

	<div class="feature">
		<h3>📄 Resources (<?php echo count($server->resources); ?> available)</h3>
		<ul>
			<?php foreach ($server->resources as $resource): ?>
				<li><strong><?php echo htmlspecialchars($resource['name']); ?></strong> - <?php echo htmlspecialchars($resource['description']); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>

	<div class="feature">
		<h3>💬 Prompts (<?php echo count($server->prompts); ?> available)</h3>
		<ul>
			<?php foreach ($server->prompts as $prompt): ?>
				<li><strong><?php echo htmlspecialchars($prompt['name']); ?></strong> - <?php echo htmlspecialchars($prompt['description']); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>

	<p><small>Formulize MCP HTTP Server v<?php echo FORMULIZE_MCP_VERSION; ?> | <?php echo date('Y-m-d H:i:s'); ?></small></p>
</body>

</html>
