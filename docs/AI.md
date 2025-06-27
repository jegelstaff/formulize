---
layout: default
permalink: AI/
title: AI and Formulize
---

# We have a Formulize MCP Server

The server can retrieve basic information about the configuration of a Formulize site where the server is installed.

This has been proven to work inside VSCode, where two settings files need to be updated:

## mcp.json

An mcp.json file needs to be created inside your project's .vscode folder, and the server started with the Play icon that VSCode gives you in the editor interface.

```json
{
  "servers": {
    "Formulize": {
      "type": "http",
      "url": "https://julian.formulize.net/formulize_mcp_http_direct.php/mcp"
    }
  }
}
```

## The user's settings.json file:

```json
"chat.mcp.discovery.enabled": true,
"chat.mcp.discovery.server": "https://julian.formulize.net/formulize_mcp_http_direct.php/mcp",
"chat.mcp.discovery.serverName": "Formulize",
"chat.mcp.discovery.serverType": "http",
"chat.mcp.discovery.serverUrl": "https://julian.formulize.net/formulize_mcp_http_direct.php/mcp",
"chat.mcp.discovery.serverVersion": "1.0.0",
"chat.mcp.discovery.serverDescription": "Formulize MCP Server",
"chat.mcp.discovery.serverCapabilities": {
	"chat": true,
	"code": true,
	"file": true,
	"image": true,
	"video": true,
	"audio": true,
	"text": true,
	"command": true,
	"tool": true,
	"search": true,
	"history": true,
	"settings": true,
	"notification": true,
	"user": true,
	"group": true,
	"admin": true,
	"plugin": true,
	"extension": true,
}
```
