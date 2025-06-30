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
  McpError,
} from '@modelcontextprotocol/sdk/types.js';

interface FormulizeConfig {
  baseUrl: string;
  apiKey: string;
  timeout?: number;
  debug?: boolean;
}

class FormulizeServer {
  private server: Server;
  private config: FormulizeConfig;

  constructor() {
    this.config = this.loadConfig();
    this.server = new Server(
      {
        name: 'formulize-mcp',
        version: '1.0.0',
      },
      {
        capabilities: {
          tools: {},
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

  private setupHandlers(): void {
    this.server.setRequestHandler(ListToolsRequestSchema, async () => {
      if (this.config.debug) {
        console.error('[DEBUG] Fetching tools from remote server...');
      }

      try {
        // Get tools from remote Formulize server
        const response = await this.makeRequest('tools/list', {});

        if (response.result && response.result.tools) {
          return {
            tools: response.result.tools,
          };
        } else {
          throw new Error('Invalid tools response from remote server');
        }
      } catch (error) {
        if (this.config.debug) {
          console.error('[DEBUG] Error fetching tools:', error);
        }

        // Return fallback tools if remote server is unavailable
        return {
          tools: [
            {
              name: 'test_connection',
              description: 'Test connection to Formulize server',
              inputSchema: {
                type: 'object',
                properties: {},
              },
            },
            {
              name: 'proxy_status',
              description: 'Get status of the proxy connection',
              inputSchema: {
                type: 'object',
                properties: {},
              },
            },
          ],
        };
      }
    });

    this.server.setRequestHandler(CallToolRequestSchema, async (request) => {
      const { name, arguments: args } = request.params;

      if (this.config.debug) {
        console.error(`[DEBUG] Calling tool: ${name} with args:`, args);
      }

      try {
        // Handle special proxy tools locally
        if (name === 'proxy_status') {
          return await this.handleProxyStatus();
        }

        // Forward all other tool calls to remote server
        const response = await this.makeRequest('tools/call', {
          name,
          arguments: args || {},
        });

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
          console.error(`[DEBUG] Tool call error:`, error);
        }

        if (error instanceof McpError) {
          throw error;
        }

        throw new McpError(
          ErrorCode.InternalError,
          `Tool execution failed: ${error instanceof Error ? error.message : String(error)}`
        );
      }
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
              version: '1.0.0',
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
              version: '1.0.0',
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
      console.error(`[DEBUG] Starting Formulize MCP Server`);
      console.error(`[DEBUG] Remote URL: ${this.config.baseUrl}`);
      console.error(`[DEBUG] Timeout: ${this.config.timeout}ms`);
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