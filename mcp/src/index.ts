#!/usr/bin/env node

/**
 * Formulize MCP Server
 *
 * Local TypeScript MCP server that proxies requests to remote Formulize HTTP server
 */

import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import {
  CallToolRequestSchema,
  ErrorCode,
  ListToolsRequestSchema,
  ListResourcesRequestSchema,
  ReadResourceRequestSchema,
  ListPromptsRequestSchema,
  GetPromptRequestSchema,
  McpError,
} from '@modelcontextprotocol/sdk/types.js';

interface FormulizeConfig {
  baseUrl: string;
  apiKey: string;
  timeout?: number;
  debug?: boolean;
}

type MCPType = 'tools' | 'resources' | 'prompts';
type MCPAction = 'list' | 'call' | 'read' | 'get';

class FormulizeServer {
  private server: Server;
  private config: FormulizeConfig;
  private readonly version = '1.1.0';
  private readonly name = 'formulize-mcp';

  constructor() {
    this.config = this.loadConfig();
    this.server = new Server(
      {
        name: this.name,
        version: this.version,
      },
      {
        capabilities: {
          tools: {},
          resources: {},
          prompts: {},
        },
      }
    );

    this.setupHandlers();
  }

  private loadConfig(): FormulizeConfig {
    const baseUrl = process.env.FORMULIZE_BASE_URL;
    if (!baseUrl) {
      throw new Error('FORMULIZE_BASE_URL environment variable is required');
    }
    const apiKey = process.env.FORMULIZE_API_KEY;
    if (!apiKey) {
      throw new Error('FORMULIZE_API_KEY environment variable is required');
    }

    return {
      baseUrl: baseUrl.replace(/\/$/, ''), // Remove trailing slash
      apiKey: process.env.FORMULIZE_API_KEY,
      timeout: parseInt(process.env.FORMULIZE_TIMEOUT || '30000'),
      debug: process.env.FORMULIZE_DEBUG === 'true',
    };
  }

  /**
   * Generic handler for listing MCP items (tools, resources, prompts)
   */
  private async handleListMCP(type: MCPType) {
    if (this.config.debug) {
      console.error(`[DEBUG] Fetching ${type} from remote server...`);
    }

    try {
      const response = await this.makeRequest(`${type}/list`, {});

      if (response.result && response.result[type]) {
        return {
          [type]: response.result[type],
        };
      } else {
        throw new Error(`Invalid ${type} response from remote server`);
      }
    } catch (error) {
      if (this.config.debug) {
        console.error(`[DEBUG] Error fetching ${type}:`, error);
      }

      // Return fallback for tools if remote server is unavailable
      if (type === 'tools') {
        return {
          tools: [
            {
              name: 'Test Connection',
              description: 'Test connection to Formulize server (works even if remote is down)',
              inputSchema: {
                type: 'object',
                properties: {},
              },
            },
            {
              name: 'Proxy Status',
              description: 'Get status of the proxy connection',
              inputSchema: {
                type: 'object',
                properties: {},
              },
            },
          ],
        };
      }

      // Return empty arrays for resources and prompts
      return {
        [type]: [],
      };
    }
  }

  /**
   * Generic handler for using MCP items (calling tools, reading resources, getting prompts)
   */
  private async handleUseMCP(
    type: MCPType,
    action: MCPAction,
    params: { name?: string; uri?: string; arguments?: any }
  ) {
    const identifier = params.name || params.uri || '';
    const args = params.arguments;

    if (this.config.debug) {
      console.error(`[DEBUG] ${action} ${type}: ${identifier}${args ? ' with args:' : ''}`, args);
    }

    try {
      // Handle special proxy tools locally
      if (type === 'tools' && params.name === 'Proxy Status') {
        return await this.handleProxyStatus();
      }

      if (type === 'tools' && params.name === 'Test Connection') {
        return await this.handleTestConnection();
      }

      // Forward all other requests to remote server
      const method = `${type}/${action}`;
      const requestParams: any = {};

      // Set the appropriate parameter based on the type
      if (type === 'resources') {
        requestParams.uri = identifier;
      } else {
        requestParams.name = identifier;
        if (args !== undefined) {
          requestParams.arguments = args;
        }
      }

      const response = await this.makeRequest(method, requestParams);

      if (response.result) {
        return response.result;
      } else if (response.error) {
        throw new McpError(
          ErrorCode.InternalError,
          `Remote server error: ${response.error.message}`
        );
      } else {
        throw new Error('Invalid response from remote server');
      }
    } catch (error) {
      if (this.config.debug) {
        console.error(`[DEBUG] ${type} ${action} error:`, error);
      }

      if (error instanceof McpError) {
        throw error;
      }

      throw new McpError(
        ErrorCode.InternalError,
        `${type} ${action} failed: ${error instanceof Error ? error.message : String(error)}`
      );
    }
  }

  private setupHandlers(): void {
    // Tools handlers
    this.server.setRequestHandler(ListToolsRequestSchema, async () => {
      return this.handleListMCP('tools');
    });

    this.server.setRequestHandler(CallToolRequestSchema, async (request) => {
      return this.handleUseMCP('tools', 'call', {
        name: request.params.name,
        arguments: request.params.arguments,
      });
    });

    // Resources handlers
    this.server.setRequestHandler(ListResourcesRequestSchema, async () => {
      return this.handleListMCP('resources');
    });

    this.server.setRequestHandler(ReadResourceRequestSchema, async (request) => {
      return this.handleUseMCP('resources', 'read', {
        uri: request.params.uri,
      });
    });

    // Prompts handlers
    this.server.setRequestHandler(ListPromptsRequestSchema, async () => {
      return this.handleListMCP('prompts');
    });

    this.server.setRequestHandler(GetPromptRequestSchema, async (request) => {
      return this.handleUseMCP('prompts', 'get', {
        name: request.params.name,
        arguments: request.params.arguments,
      });
    });
  }

  private async makeRequest(method: string, params: any): Promise<any> {
    const url = `${this.config.baseUrl}/mcp`;

    const requestBody = {
      jsonrpc: '2.0',
      method,
      params,
      id: Date.now(),
    };

    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      'Cache-Control': 'no-cache',
    };

    if (this.config.apiKey) {
      headers['Authorization'] = `Bearer ${this.config.apiKey}`;
    }

    if (this.config.debug) {
      console.error(`[DEBUG] Making request to ${url}:`, requestBody);
    }

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), this.config.timeout);

    try {
      const response = await fetch(url, {
        method: 'POST',
        headers,
        body: JSON.stringify(requestBody),
        signal: controller.signal,
      });

      clearTimeout(timeoutId);

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();

      if (this.config.debug) {
        console.error('[DEBUG] Response from server:', data);
      }

      return data;
    } catch (error) {
      clearTimeout(timeoutId);

      if (error instanceof Error && error.name === 'AbortError') {
        throw new Error(`Request timeout after ${this.config.timeout}ms`);
      }

      throw error;
    }
  }

  private async handleTestConnection(): Promise<any> {
    const results: any = {
      proxy_server: {
        status: 'operational',
        version: this.version,
        environment: {
          node_version: process.version,
          platform: process.platform,
          api_key_configured: this.config.apiKey,
          api_key_length: this.config.apiKey ? this.config.apiKey.length : 0,
        },
      },
      remote_server: {
        url: this.config.baseUrl,
        status: 'unknown',
        response_time_ms: null,
        error: null,
      },
      timestamp: new Date().toISOString(),
    };

    try {
      // Try to call the remote Test Connection tool
      const startTime = Date.now();
      const response = await this.makeRequest('tools/call', {
        name: 'Test Connection',
        arguments: {},
      });
      const responseTime = Date.now() - startTime;

      results.remote_server.status = 'connected';
      results.remote_server.response_time_ms = responseTime;

      if (response.result && response.result.content && response.result.content[0]) {
        // Parse the remote response
        try {
          const remoteData = JSON.parse(response.result.content[0].text);
          results.remote_server.details = remoteData;
        } catch (e) {
          results.remote_server.raw_response = response.result.content[0].text;
        }
      }
    } catch (error) {
      results.remote_server.status = 'error';
      results.remote_server.error = error instanceof Error ? error.message : String(error);

      // Try a basic HTTP GET to check if server is reachable
      let timeoutId: NodeJS.Timeout | undefined;
      try {
        const healthUrl = `${this.config.baseUrl}/health`;
        const controller = new AbortController();
        timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout

        const healthResponse = await fetch(healthUrl, {
          method: 'GET',
          headers: {
            'Authorization': `Bearer ${this.config.apiKey}`,
          },
          signal: controller.signal,
        });

        clearTimeout(timeoutId);

        if (healthResponse.ok) {
          results.remote_server.health_check = 'passed';
          results.remote_server.http_status = healthResponse.status;
        } else {
          results.remote_server.health_check = 'failed';
          results.remote_server.http_status = healthResponse.status;
        }
      } catch (healthError) {
        if (timeoutId) clearTimeout(timeoutId);
        results.remote_server.health_check = 'unreachable';
        results.remote_server.health_error = healthError instanceof Error ? healthError.message : String(healthError);
      }
    }

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify(results, null, 2),
        },
      ],
    };
  }

  private async handleProxyStatus(): Promise<any> {
    try {
      // Test connection to remote server
      const startTime = Date.now();
      await this.makeRequest('initialize', {});
      const responseTime = Date.now() - startTime;

      return {
        content: [
          {
            type: 'text',
            text: JSON.stringify({
              status: 'connected',
              version: this.version,
              remote_url: this.config.baseUrl,
              response_time_ms: responseTime,
              config: {
                timeout: this.config.timeout,
                debug: this.config.debug,
                api_key_configured: this.config.apiKey,
              },
              timestamp: new Date().toISOString(),
            }, null, 2),
          },
        ],
      };
    } catch (error) {
      return {
        content: [
          {
            type: 'text',
            text: JSON.stringify({
              status: 'disconnected',
              version: this.version,
              remote_url: this.config.baseUrl,
              error: error instanceof Error ? error.message : String(error),
              config: {
                timeout: this.config.timeout,
                debug: this.config.debug,
                api_key_configured: this.config.apiKey,
              },
              timestamp: new Date().toISOString(),
            }, null, 2),
          },
        ],
      };
    }
  }

  async run(): Promise<void> {
    const transport = new StdioServerTransport();

    if (this.config.debug) {
      console.error(`[DEBUG] Starting Formulize MCP Server v${this.version}`);
      console.error(`[DEBUG] Remote URL: ${this.config.baseUrl}`);
      console.error(`[DEBUG] Timeout: ${this.config.timeout}ms`);
      console.error(`[DEBUG] Capabilities: tools, resources, prompts`);
    }

    await this.server.connect(transport);
  }
}

// Handle uncaught errors
process.on('uncaughtException', (error) => {
  console.error('Uncaught exception:', error);
  process.exit(1);
});

process.on('unhandledRejection', (reason, promise) => {
  console.error('Unhandled rejection at:', promise, 'reason:', reason);
  process.exit(1);
});

// Start the server
const server = new FormulizeServer();
server.run().catch((error) => {
  console.error('Failed to start server:', error);
  process.exit(1);
});