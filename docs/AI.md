---
layout: default
permalink: ai/
title: AI and Formulize
---

# We have a Formulize MCP Server

The server can retrieve basic information about the configuration of a Formulize site where the server is installed.

This has been proven to work inside VSCode, where two settings files need to be updated:

Need enabling/installation instructions

Need Node/setup local MCP instructions

Need config instructions for .json files
sub of this, we need VS Code notes because of extra settings.json in the chat

## .htaccess

```apacheconf
# Necessary for HTTP Authorization header to be passed through to the MCP server
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

## mcp.json

An mcp.json file needs to be created inside your project's .vscode folder, and the server started with the Play icon that VSCode gives you in the editor interface.

```json
{
  "servers": {
    "Formulize": {
      "command": "node",
      "args": ["C:\\formulize-proxy-mcp\\dist\\index.js"],
      "env": {
        "FORMULIZE_BASE_URL": "https://<your server url>/MCP.php",
        "FORMULIZE_DEBUG": "false",
        "FORMULIZE_TIMEOUT": "30000",
        "FORMULIZE_API_KEY": "YOUR KEY GOES HERE"
      }
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

## claude_desktop_config.json

```json
{
  "mcpServers": {
    "formulize": {
      "command": "node",
      "args": ["C:\\formulize-proxy-mcp\\dist\\index.js"],
      "env": {
        "FORMULIZE_BASE_URL": "https://julian.formulize.net/MCP.php",
        "FORMULIZE_DEBUG": "false",
        "FORMULIZE_TIMEOUT": "30000",
        "FORMULIZE_API_KEY": "YOUR KEY GOES HERE"
      }
    }
  }
}
```

# Formulize Proxy MCP Server Setup (Windows 11)

This TypeScript MCP server acts as a local proxy to your remote Formulize HTTP server, enabling Claude Desktop integration.

## Installation

1. **Create project directory:**
```cmd
mkdir formulize-proxy-mcp
cd formulize-proxy-mcp
```

2. **Create the directory structure:**
```cmd
mkdir src
```

3. **Create the files:**
   - Save the TypeScript code as `src\index.ts`
   - Save the package.json in the root directory
   - Save the tsconfig.json in the root directory

4. **Install the MCP SDK first:**
```cmd
npm install @modelcontextprotocol/sdk
```

5. **Install remaining dependencies:**
```cmd
npm install
```

6. **If you get import errors, try installing the latest MCP SDK:**
```cmd
npm install @modelcontextprotocol/sdk@latest
```

7. **Build the project:**
```cmd
npm run build
```

**If you still get SDK import errors, try this alternative installation:**
```cmd
rem Clean install
rmdir /s node_modules
del package-lock.json
npm install @modelcontextprotocol/sdk@latest
npm install
npm run build
```

## Claude Desktop Configuration

Add this to your Claude Desktop config file:

**Windows 11:** `%APPDATA%\Claude\claude_desktop_config.json`

**Full path example:** `C:\Users\YourUsername\AppData\Roaming\Claude\claude_desktop_config.json`

```json
{
  "mcpServers": {
    "formulize": {
      "command": "node",
      "args": ["C:\\full\\path\\to\\formulize-proxy-mcp\\dist\\index.js"],
      "env": {
        "FORMULIZE_BASE_URL": "https://julian.formulize.net/formulize_mcp_http_direct.php",
        "FORMULIZE_DEBUG": "false",
        "FORMULIZE_TIMEOUT": "30000"
      }
    }
  }
}
```

**Important Windows Notes:**
- Use **double backslashes** (`\\`) in the path
- Use **full absolute paths** (e.g., `C:\\Users\\YourName\\...`)
- You can find your exact path by running `echo %APPDATA%` in Command Prompt

## Environment Variables

- **`FORMULIZE_BASE_URL`** (required): Base URL of your Formulize HTTP MCP server
- **`FORMULIZE_API_KEY`** (optional): API key for authentication
- **`FORMULIZE_TIMEOUT`** (optional): Request timeout in milliseconds (default: 30000)
- **`FORMULIZE_DEBUG`** (optional): Enable debug logging (default: false)

## Testing

### Using Command Prompt:

1. **Test the proxy locally:**
```cmd
rem Set environment variables
set FORMULIZE_BASE_URL=https://julian.formulize.net/formulize_mcp_http_direct.php
set FORMULIZE_DEBUG=true

rem Test with stdio
echo {"jsonrpc":"2.0","method":"tools/list","params":{},"id":1} | npm start
```

2. **Test specific tools:**
```cmd
rem Test connection
echo {"jsonrpc":"2.0","method":"tools/call","params":{"name":"proxy_status","arguments":{}},"id":2} | npm start

rem Test Formulize connection
echo {"jsonrpc":"2.0","method":"tools/call","params":{"name":"test_connection","arguments":{}},"id":3} | npm start
```

### Using PowerShell:

1. **Test the proxy locally:**
```powershell
# Set environment variables
$env:FORMULIZE_BASE_URL = "https://julian.formulize.net/formulize_mcp_http_direct.php"
$env:FORMULIZE_DEBUG = "true"

# Test with stdio
'{"jsonrpc":"2.0","method":"tools/list","params":{},"id":1}' | npm start
```

2. **Test specific tools:**
```powershell
# Test connection
'{"jsonrpc":"2.0","method":"tools/call","params":{"name":"proxy_status","arguments":{}},"id":2}' | npm start

# Test Formulize connection
'{"jsonrpc":"2.0","method":"tools/call","params":{"name":"test_connection","arguments":{}},"id":3}' | npm start
```

## Architecture Benefits

✅ **Local stdio interface** - Claude Desktop compatible
✅ **Remote HTTP calls** - Uses your existing Formulize server
✅ **Clean separation** - MCP protocol vs Formulize logic
✅ **Configurable** - Environment-based configuration
✅ **Error handling** - Graceful fallbacks and debugging
✅ **Caching prevention** - Fresh data on every request

## Troubleshooting

1. **Check the logs:**
   Enable debug mode: `"FORMULIZE_DEBUG": "true"`

2. **Test remote server directly (using PowerShell):**
   ```powershell
   Invoke-RestMethod -Uri "https://julian.formulize.net/formulize_mcp_http_direct.php/mcp" `
     -Method Post `
     -ContentType "application/json" `
     -Body '{"jsonrpc":"2.0","method":"tools/list","params":{},"id":1}'
   ```

   **Or using curl (if installed):**
   ```cmd
   curl -X POST https://julian.formulize.net/formulize_mcp_http_direct.php/mcp ^
     -H "Content-Type: application/json" ^
     -d "{\"jsonrpc\":\"2.0\",\"method\":\"tools/list\",\"params\":{},\"id\":1}"
   ```

3. **Find your exact config path:**
   ```cmd
   echo %APPDATA%\Claude\claude_desktop_config.json
   ```

4. **Verify Claude Desktop config:**
   - Check file path uses **double backslashes** (`\\`)
   - Ensure environment variables are set correctly in the JSON
   - Use **absolute paths** (e.g., `C:\\Users\\YourName\\...`)
   - Restart Claude Desktop after config changes

5. **Common Windows Issues:**
   - **Path separators**: Use `\\` instead of `/` in Windows paths
   - **Permissions**: Ensure the user can read the script files
   - **Node.js**: Verify Node.js is installed and in PATH (`node --version`)

## Development

- **Development mode:** `npm run dev` (auto-recompile)
- **Build:** `npm run build`
- **Production:** `npm start`

## Security Notes

- The proxy server only forwards requests to your configured Formulize server
- No local data storage or caching
- Environment variables keep credentials secure
- HTTPS recommended for remote connections

## Windows-Specific Notes

- **File paths**: Always use absolute paths with double backslashes
- **Testing**: PowerShell is recommended over Command Prompt for JSON testing
- **Environment variables**: Set in the Claude Desktop config, not system environment
- **Node.js**: Download from [nodejs.org](https://nodejs.org) if not installed
