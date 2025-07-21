<?php

require_once '../mainfile.php';
include_once XOOPS_ROOT_PATH . '/mcp/mcp.php';

// Handle the HTTP request with proper Formulize authentication
try {
	$server = new FormulizeMCP();
	if ($server->enabled) {
		$server->handleHTTPRequest();
	} elseif ($server->canBeEnabled) {
		// if the MCP server passed the canBeEnabled check, but is not enabled, return a 200 OK response with a JSON payload indicating that the server is not enabled
		$content = [
			'status' => 'canBeEnabled',
			'message' => 'MCP Server can be enabled',
			'code' => 200,
			'timestamp' => date('Y-m-d H:i:s')
		];
		$server->sendResponse($content);
	} else {
		// If the MCP server is disabled, return a 503 Service Unavailable response
		throw new FormulizeMCPException('MCP Server is disabled', 'server_disabled');
	}
} catch (FormulizeMCPException $e) {
	$server->sendResponse([
		'jsonrpc' => '2.0',
		'error' => [
			'message' => $e->getMessage(),
			'type' => $e->getType(),
			'timestamp' => $e->getTimestamp()
		]
	], $e->toHTTPStatusCode());
}
