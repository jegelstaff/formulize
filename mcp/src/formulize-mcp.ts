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
  apiKey: string | undefined;
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
    if (!process.env.FORMULIZE_BASE_URL) {
      throw new Error('FORMULIZE_BASE_URL environment variable is required');
    }
    if (!process.env.FORMULIZE_API_KEY) {
      throw new Error('FORMULIZE_API_KEY environment variable is required');
    }

		const apiKey = process.env.FORMULIZE_API_KEY;
		let baseUrl = process.env.FORMULIZE_BASE_URL;
		if (baseUrl.endsWith('/')) {
			baseUrl += 'index.php';
		}

		return {
			baseUrl: baseUrl,
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
        if (this.config.debug) {
          console.error(`[DEBUG] Successfully fetched ${response.result[type].length} ${type}`);
        }
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

      // FOR TOOLS: Provide meaningful fallback tools that can diagnose the issue
      if (type === 'tools') {
        console.error(`[WARNING] Remote server unavailable, providing fallback tools`);
        return {
          tools: [
            {
              name: 'test_connection',
                description: 'Comprehensive test of proxy and remote Formulize server connectivity, including all MCP capabilities',
                inputSchema: {
                    type: 'object',
                    properties: {
                    verbose: {
                        type: 'boolean',
                        description: 'Include detailed response data in output',
                        default: false
                    },
                    skip_remote: {
                        type: 'boolean',
                        description: 'Only test proxy server, skip remote server tests',
                        default: false
                    }
                    },
                },
                },
                {
                name: 'proxy_status',
                description: 'Quick status check of the proxy server configuration',
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
            }
          ]
        };
      } else {
        // FOR RESOURCES AND PROMPTS: Throw the error instead of returning empty arrays
        // This forces AI assistant to retry or show a proper error message
        throw new McpError(
            ErrorCode.InternalError,
            `Failed to fetch ${type} from remote server: ${error instanceof Error ? error.message : String(error)}`
        );
      }
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
      if (type === 'tools' && params.name === 'proxy_status') {
        return await this.handleProxyStatus();
      }

      if (type === 'tools' && params.name === 'test_connection') {
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

      // Provide helpful error context
      if (error instanceof McpError) {
        throw error;
      }

      // Enhanced error message with troubleshooting hints
      let errorMessage = `${type} ${action} failed: ${error instanceof Error ? error.message : String(error)}`;

      if (error instanceof Error && error.message.includes('timeout')) {
        errorMessage += '\n\nTroubleshooting: Try increasing FORMULIZE_TIMEOUT environment variable or check network connectivity.';
      } else if (error instanceof Error && error.message.includes('HTTP')) {
        errorMessage += '\n\nTroubleshooting: Check if the Formulize server is running and the API key is valid.';
      }

      throw new McpError(ErrorCode.InternalError, errorMessage);
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

  private async makeRequest(method: string, params: any, retries: number = 3): Promise<any> {
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
      'Pragma': 'no-cache',
    };

    if (this.config.apiKey) {
      headers['Authorization'] = `Bearer ${this.config.apiKey}`;
    }

    for (let attempt = 1; attempt <= retries + 1; attempt++) {
    if (this.config.debug) {
        console.error(`[DEBUG] Making request to ${url} (attempt ${attempt}/${retries + 1}):`, requestBody);
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

        if (attempt === retries + 1) {
          // Last attempt failed
      if (error instanceof Error && error.name === 'AbortError') {
        throw new Error(`Request timeout after ${this.config.timeout}ms`);
      }
          throw error;
        }

        // Wait before retry (exponential backoff)
        const waitTime = Math.min(1000 * Math.pow(2, attempt - 1), 5000);
        if (this.config.debug) {
          console.error(`[DEBUG] Attempt ${attempt} failed, retrying in ${waitTime}ms:`, error);
        }
        await new Promise(resolve => setTimeout(resolve, waitTime));
      }
    }
  }

  private async handleTestConnection(args: any = {}): Promise<any> {
    const verbose = args.verbose || false;
    const skipRemote = args.skip_remote || false;

    const results: any = {
      timestamp: new Date().toISOString(),
      proxy_server: {
        status: 'operational',
        version: this.version,
        environment: {
          node_version: process.version,
          platform: process.platform,
          api_key_configured: this.config.apiKey,
          api_key_length: this.config.apiKey ? this.config.apiKey.length : 0,
          base_url: this.config.baseUrl,
          timeout: this.config.timeout,
          debug_mode: this.config.debug,
        },
      },
      remote_server: {
        url: this.config.baseUrl,
        status: 'unknown',
        tests: {},
        capabilities: {},
        error: null,
      },
      recommendations: [],
      summary: {
        overall_status: 'unknown',
        issues_found: 0,
        capabilities_working: 0,
        total_capabilities: 3,
      }
    };

    if (skipRemote) {
      results.remote_server.status = 'skipped';
      results.summary.overall_status = 'proxy_only';
      return {
        content: [{ type: 'text', text: JSON.stringify(results, null, 2) }],
      };
    }

    // Test 1: Basic HTTP Health Check
    try {
      const healthUrl = `${this.config.baseUrl}/health`;
      const startTime = Date.now();
      const response = await fetch(healthUrl, {
        method: 'GET',
        headers: { 'Authorization': `Bearer ${this.config.apiKey}` },
        signal: AbortSignal.timeout(5000),
      });
      const responseTime = Date.now() - startTime;

      results.remote_server.tests.health_check = {
        status: response.ok ? 'pass' : 'fail',
        http_status: response.status,
        response_time_ms: responseTime,
      };

      if (verbose && response.ok) {
        try {
          const healthData = await response.json();
          results.remote_server.tests.health_check.details = healthData;
        } catch (e) {
          results.remote_server.tests.health_check.raw_response = await response.text();
        }
      }

      if (!response.ok) {
        results.summary.issues_found++;
        results.recommendations.push(`Health check failed with HTTP ${response.status} - check server status and API key`);
      }
    } catch (error) {
      results.remote_server.tests.health_check = {
        status: 'error',
        error: error instanceof Error ? error.message : String(error),
    };
      results.summary.issues_found++;
      results.recommendations.push('Health endpoint unreachable - check if Formulize server is running and URL is correct');
    }

    // Test 2: MCP Capabilities Endpoint
    try {
      const capUrl = `${this.config.baseUrl}/capabilities`;
      const startTime = Date.now();
      const response = await fetch(capUrl, {
        method: 'GET',
        headers: { 'Authorization': `Bearer ${this.config.apiKey}` },
        signal: AbortSignal.timeout(5000),
      });
      const responseTime = Date.now() - startTime;

      results.remote_server.tests.capabilities_endpoint = {
        status: response.ok ? 'pass' : 'fail',
        http_status: response.status,
        response_time_ms: responseTime,
      };

      if (response.ok) {
        try {
          const capData = await response.json();
          results.remote_server.tests.capabilities_endpoint.tools_count = capData.capabilities?.tools?.length || 0;
          results.remote_server.tests.capabilities_endpoint.resources_count = capData.capabilities?.resources?.length || 0;
          results.remote_server.tests.capabilities_endpoint.prompts_count = capData.capabilities?.prompts?.length || 0;

          if (verbose) {
            results.remote_server.tests.capabilities_endpoint.full_response = capData;
          }
        } catch (e) {
          results.remote_server.tests.capabilities_endpoint.parse_error = 'Failed to parse JSON response';
        }
      } else {
        results.summary.issues_found++;
        results.recommendations.push('Capabilities endpoint failed - MCP features may not work');
      }
    } catch (error) {
      results.remote_server.tests.capabilities_endpoint = {
        status: 'error',
        error: error instanceof Error ? error.message : String(error),
      };
      results.summary.issues_found++;
    }

    // Test 3: Individual MCP Capabilities
    const capabilities = [
      { name: 'tools', method: 'tools/list' },
      { name: 'resources', method: 'resources/list' },
      { name: 'prompts', method: 'prompts/list' }
    ];

    for (const capability of capabilities) {
      try {
        const startTime = Date.now();
        const response = await this.makeRequest(capability.method, {});
        const responseTime = Date.now() - startTime;

        results.remote_server.capabilities[capability.name] = {
          status: 'pass',
          response_time_ms: responseTime,
          count: response.result?.[capability.name]?.length || 0,
        };

        if (verbose && response.result?.[capability.name] && Array.isArray(response.result[capability.name]) && response.result[capability.name].length > 0) {
          results.remote_server.capabilities[capability.name].sample_items =
            response.result[capability.name].slice(0, 3).map((item: any) => ({
              name: item.name || item.uri || 'unknown',
              description: (item.description || '').substring(0, 100)
            }));
        }

        results.summary.capabilities_working++;
      } catch (error) {
        results.remote_server.capabilities[capability.name] = {
          status: 'error',
          error: error instanceof Error ? error.message : String(error),
        };
        results.summary.issues_found++;
        results.recommendations.push(`${capability.name} capability failed - may need to restart AI assistant to refresh cache`);
      }
    }

    // Test 4: Try the remote test_connection tool for detailed server info
    try {
      const response = await this.makeRequest('tools/call', {
        name: 'test_connection',
        arguments: {},
        });

      if (response.result?.content?.[0]?.text) {
        try {
          const remoteTestData = JSON.parse(response.result.content[0].text);
          results.remote_server.tests.formulize_connection = {
            status: 'pass',
            details: remoteTestData,
          };
        } catch (e) {
          results.remote_server.tests.formulize_connection = {
            status: 'pass',
            raw_response: response.result.content[0].text,
          };
        }
      }
    } catch (error) {
      results.remote_server.tests.formulize_connection = {
        status: 'error',
        error: error instanceof Error ? error.message : String(error),
      };
      results.summary.issues_found++;
    }

    // Determine overall status
    if (results.summary.issues_found === 0 && results.summary.capabilities_working === 3) {
      results.summary.overall_status = 'healthy';
      results.remote_server.status = 'connected';
    } else if (results.summary.capabilities_working > 0) {
      results.summary.overall_status = 'partial';
      results.remote_server.status = 'partial';
      results.recommendations.push('Some capabilities are working but issues detected - see individual test results');
        } else {
      results.summary.overall_status = 'failed';
      results.remote_server.status = 'error';
      results.recommendations.push('All capabilities failed - check server connectivity and authentication');
        }

    // Add specific recommendations based on patterns
    if (results.summary.capabilities_working === 1 && results.remote_server.capabilities.tools?.status === 'pass') {
      results.recommendations.push('Only tools working - this suggests AI assistant cached empty resources/prompts during startup. Clear the cache of the AI assistant (restart?).');
      }

    if (results.remote_server.tests.health_check?.response_time_ms > 2000 ||
        results.remote_server.tests.capabilities_endpoint?.response_time_ms > 2000) {
      results.recommendations.push('Slow response times detected - consider increasing FORMULIZE_TIMEOUT or checking network');
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
