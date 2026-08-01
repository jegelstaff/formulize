<?php
/**
 * Dumps the real, fully-resolved MCP tool registry (name, description,
 * inputSchema) as JSON, for the formulize.org docs site to generate one
 * page per tool from.
 *
 * This exists because most tool schemas are assembled at runtime from PHP
 * variables (array_merge($formProperties, ...), etc.) and the create/update
 * element tools are built entirely at runtime by buildFormElementTools()
 * (element-type discovery). None of that is visible to a static text
 * scraper, so this runs the real registerTools() code instead.
 *
 * Runs registerTools() twice - once as a non-webmaster, once as a webmaster
 * (XOOPS_GROUP_ADMIN) - and tags each tool with the visibility that
 * comparison reveals, using the app's own permission check rather than a
 * hand-maintained list.
 *
 * Usage (from inside the formulize-web container):
 *   php /var/www/html/mcp/dump_tools_for_docs.php
 *
 * @package Formulize
 * @subpackage mcp
 */

// Command-line / CI only. Refuse to run over the web: this bypasses normal
// MCP authentication (see FormulizeMCP::__construct's $forDocsCli branch) to
// force a webmaster view of the tool registry, so it must never be reachable
// as an HTTP endpoint.
if (PHP_SAPI !== 'cli') {
	http_response_code(404);
	exit;
}

chdir(__DIR__);

// mainfile.php derives SITE_BASE_URL/XOOPS_URL from these $_SERVER keys, which
// don't exist under the CLI SAPI. None of that matters for registerTools()
// (it only needs a DB connection), but leaving them unset produces a stream
// of "Undefined array key" warnings whose destination (stdout vs stderr)
// depends on the environment's display_errors setting - on a config where
// that's stdout, it would corrupt the JSON output below. Filling in
// harmless defaults up front avoids the warnings entirely rather than
// relying on a particular display_errors value.
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__); // mainfile.php defines XOOPS_ROOT_PATH as realpath(dirname(mainfile.php)), i.e. this same directory
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

ob_start();
require_once '../mainfile.php';
require_once 'mcp.php';
ob_end_clean();

// Non-webmaster pass: whatever this returns is the "standard" tool set.
$standardMcp = new FormulizeMCP(null, true, []);
$standardNames = array_keys($standardMcp->tools);

// Webmaster pass: authoritative content for every tool, including the
// dynamically-built create/update element tools.
$fullMcp = new FormulizeMCP(null, true, [XOOPS_GROUP_ADMIN]);

// The self-referencing "read this first" instructions tool is keyed off
// mcpRequest['localServerName'], which dump mode fixes at 'formulize'. It's
// documented separately (or not at all) rather than as a regular tool page.
$selfReferencingToolName = 'formulize';

$tools = [];
foreach ($fullMcp->tools as $name => $tool) {
	if ($name === $selfReferencingToolName) {
		continue;
	}
	$tool['visibility'] = in_array($name, $standardNames, true) ? 'standard' : 'admin_only';
	$tools[] = $tool;
}

echo json_encode(array_values($tools), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
