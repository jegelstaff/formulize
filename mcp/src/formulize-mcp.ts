#!/usr/bin/env node

/**
 * Formulize MCP Server
 *
 * Local TypeScript MCP server that proxies requests to remote Formulize HTTP server
 * Features intelligent caching for non-tool endpoints to improve performance
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

interface CacheEntry {
  data: any;
  timestamp: number;
  ttl: number; // Time to live in milliseconds
}

interface CacheStats {
  hits: number;
  misses: number;
  evictions: number;
  size: number;
}

class FormulizeServer {
  private server: Server;
  private config: FormulizeConfig;
  private readonly version = '1.2.0';
  private readonly name = 'formulize-mcp';

  // Caching system
  private cache: Map<string, CacheEntry> = new Map();
  private cacheStats: CacheStats = { hits: 0, misses: 0, evictions: 0, size: 0 };
  private readonly defaultTTL = 5 * 60 * 1000; // 5 minutes
  private readonly maxCacheSize = 1000; // Maximum number of cache entries

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

    // Start cache cleanup interval (every 2 minutes)
    setInterval(() => this.cleanupExpiredCache(), 2 * 60 * 1000);
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
   * Generate cache key for a request
   */
  private getCacheKey(method: string, params: any): string {
    // Sort params to ensure consistent keys
    const sortedParams = JSON.stringify(params, Object.keys(params).sort());
    return `${method}:${sortedParams}`;
  }

  /**
   * Check if a request should be cached
   */
  private shouldCache(method: string): boolean {
    // Don't cache in debug mode
    if (this.config.debug) {
      return false;
    }

    // Don't cache tool calls (they interact with live data)
    if (method === 'tools/call') {
      return false;
    }

    // Cache everything else (tools/list, resources/list, resources/read, prompts/list, prompts/get)
    return true;
  }

  /**
   * Get TTL for different request types
   */
  private getTTL(method: string): number {
    switch (method) {
      case 'tools/list':
        return 10 * 60 * 1000; // 10 minutes - tools don't change often
      case 'resources/list':
        return 2 * 60 * 1000; // 2 minutes - resources might be created
      case 'resources/read':
        return 5 * 60 * 1000; // 5 minutes - resource content is fairly stable
      case 'prompts/list':
        return 10 * 60 * 1000; // 10 minutes - prompts don't change often
      case 'prompts/get':
        return 5 * 60 * 1000; // 5 minutes - prompt content is stable
      default:
        return this.defaultTTL;
    }
  }

  /**
   * Get data from cache if available and not expired
   */
  private getFromCache(cacheKey: string): any | null {
    const entry = this.cache.get(cacheKey);

    if (!entry) {
      this.cacheStats.misses++;
      return null;
    }

    // Check if expired
    if (Date.now() > entry.timestamp + entry.ttl) {
      this.cache.delete(cacheKey);
      this.cacheStats.evictions++;
      this.cacheStats.misses++;
      this.updateCacheSize();
      return null;
    }

    this.cacheStats.hits++;

    if (this.config.debug) {
      console.error(`[DEBUG] Cache HIT for key: ${cacheKey}`);
    }

    return entry.data;
  }

  /**
   * Store data in cache
   */
  private setCache(cacheKey: string, data: any, method: string): void {
    // Enforce max cache size
    if (this.cache.size >= this.maxCacheSize) {
      // Remove oldest entry
      const oldestKey = this.cache.keys().next().value;
      if (oldestKey) {
        this.cache.delete(oldestKey);
        this.cacheStats.evictions++;
      }
    }

    const ttl = this.getTTL(method);
    const entry: CacheEntry = {
      data,
      timestamp: Date.now(),
      ttl
    };

    this.cache.set(cacheKey, entry);
    this.updateCacheSize();

    if (this.config.debug) {
      console.error(`[DEBUG] Cache SET for key: ${cacheKey} (TTL: ${ttl}ms)`);
    }
  }

  /**
   * Update cache size stat
   */
  private updateCacheSize(): void {
    this.cacheStats.size = this.cache.size;
  }

  /**
   * Clean up expired cache entries
   */
  private cleanupExpiredCache(): void {
    const now = Date.now();
    let cleaned = 0;

    for (const [key, entry] of this.cache.entries()) {
      if (now > entry.timestamp + entry.ttl) {
        this.cache.delete(key);
        cleaned++;
      }
    }

    if (cleaned > 0) {
      this.cacheStats.evictions += cleaned;
      this.updateCacheSize();

      if (this.config.debug) {
        console.error(`[DEBUG] Cleaned up ${cleaned} expired cache entries`);
      }
    }
  }

  /**
   * Clear all cache entries
   */
  private clearCache(): void {
    const size = this.cache.size;
    this.cache.clear();
    this.cacheStats.evictions += size;
    this.updateCacheSize();

    if (this.config.debug) {
      console.error(`[DEBUG] Cleared entire cache (${size} entries)`);
    }
  }

  /**
   * Get cache statistics
   */
  private getCacheStats(): any {
    const totalRequests = this.cacheStats.hits + this.cacheStats.misses;
    const hitRate = totalRequests > 0 ? (this.cacheStats.hits / totalRequests * 100).toFixed(2) : '0.00';

    return {
      ...this.cacheStats,
      hit_rate_percent: hitRate,
      total_requests: totalRequests,
      cache_enabled: !this.config.debug,
      max_size: this.maxCacheSize,
      default_ttl_ms: this.defaultTTL,
    };
  }

  /**
   * Get local proxy tools that should always be available
   */
  private getLocalProxyTools(): any[] {
    return [
      {
        name: 'test_connection',
        description: 'Test proxy and remote Formulize server connectivity with configurable detail levels',
        inputSchema: {
          type: 'object',
          properties: {
            verbose: {
              type: 'boolean',
              description: 'Include detailed response data in output',
              default: false
            },
            quick: {
              type: 'boolean',
              description: 'Quick status check only (lightweight alternative to full diagnostics)',
              default: false
            },
            skip_remote: {
              type: 'boolean',
              description: 'Only test proxy server, skip remote server tests',
              default: false
            }
          } as Record<string, any>,
        },
      },
      {
        name: 'cache_refresh',
        description: 'Clear the local cache and force fresh data from remote server',
        inputSchema: {
          type: 'object',
          properties: {
            clear_all: {
              type: 'boolean',
              description: 'Clear entire cache (default: true)',
              default: true
            }
          } as Record<string, any>,
        },
      },
      {
        name: 'cache_stats',
        description: 'Show cache performance statistics and configuration',
        inputSchema: {
          type: 'object',
          properties: {} as Record<string, any>,
        },
      }
    ];
  }

  /**
   * Generic handler for listing MCP items (tools, resources, prompts) with caching
   */
  private async handleListMCP(type: MCPType) {
    const method = `${type}/list`;
    const cacheKey = this.getCacheKey(method, {});

    // For non-tools, handle normally
    if (type !== 'tools') {
			// Try cache first
			if (this.shouldCache(method)) {
				const cachedData = this.getFromCache(cacheKey);
				if (cachedData) {
					return cachedData;
				}
			}

			if (this.config.debug) {
				console.error(`[DEBUG] Fetching ${type} from remote server...`);
			}

			try {
				const response = await this.makeRequest(method, {});

				if (response.result && response.result[type]) {
					const result = {
						[type]: response.result[type],
					};

					// Cache the result
					if (this.shouldCache(method)) {
						this.setCache(cacheKey, result, method);
					}

					if (this.config.debug) {
						console.error(`[DEBUG] Successfully fetched ${response.result[type].length} ${type}`);
					}

					return result;
				} else {
					throw new Error(`Invalid ${type} response from remote server`);
				}
			} catch (error) {
				if (this.config.debug) {
					console.error(`[DEBUG] Error fetching ${type}:`, error);
				}
        // FOR RESOURCES AND PROMPTS: Throw the error instead of returning empty arrays
        throw new McpError(
          ErrorCode.InternalError,
          `Failed to fetch ${type} from remote server: ${error instanceof Error ? error.message : String(error)}`
        );
      }
    }

    // SPECIAL HANDLING FOR TOOLS: Always include local proxy tools
    if (this.config.debug) {
      console.error(`[DEBUG] Fetching tools from remote server...`);
    }

    let remoteTools: any[] = [];
    let remoteError = null;

    // Try to get remote tools first
    try {
      // Check cache first
      if (this.shouldCache(method)) {
        const cachedData = this.getFromCache(cacheKey);
        if (cachedData && cachedData.tools) {
          // Extract only remote tools from cache (exclude local proxy tools)
          remoteTools = cachedData.tools.filter((tool: any) =>
            !['cache_refresh', 'cache_stats', 'test_connection'].includes(tool.name)
          );

          if (this.config.debug) {
            console.error(`[DEBUG] Found ${remoteTools.length} remote tools in cache`);
          }
        }
      }

      // If not in cache, fetch from remote server
      if (remoteTools.length === 0) {
        const response = await this.makeRequest(method, {});

        if (response.result && response.result.tools) {
          // Filter out remote test_connection tool since our local version is more comprehensive
          remoteTools = response.result.tools.filter((tool: any) => tool.name !== 'test_connection');

          if (this.config.debug) {
            console.error(`[DEBUG] Successfully fetched ${response.result.tools.length} tools from remote server (${remoteTools.length} after filtering)`);
          }
        } else {
          throw new Error(`Invalid tools response from remote server`);
        }
      }
    } catch (error) {
      remoteError = error;
      if (this.config.debug) {
        console.error(`[DEBUG] Error fetching tools from remote server:`, error);
      }
    }

    // Always include local proxy tools
    const localTools = this.getLocalProxyTools();

    // Note: We always include our local test_connection tool because it provides
    // proxy-specific diagnostics that the remote tool cannot provide

    // If remote server failed, we already have test_connection in localTools,
    // but we can add a note about the failure context
    if (remoteError) {
      console.error(`[WARNING] Remote server unavailable for tools, providing local tools with diagnostics`);
		}

    // Combine remote tools with local proxy tools
    const allTools = [...remoteTools, ...localTools];

    const result = {
      tools: allTools
    };

    // Only cache if we successfully got remote tools (don't cache error states)
    if (this.shouldCache(method) && !remoteError && remoteTools.length > 0) {
      this.setCache(cacheKey, result, method);
    }

    if (this.config.debug) {
      console.error(`[DEBUG] Returning ${allTools.length} total tools (${remoteTools.length} remote + ${localTools.length} local)`);
    }

    return result;
  }

  /**
   * Generic handler for using MCP items (calling tools, reading resources, getting prompts) with caching
   */
  private async handleUseMCP(
    type: MCPType,
    action: MCPAction,
    params: { name?: string; uri?: string; arguments?: any }
  ) {
    const identifier = params.name || params.uri || '';
    const args = params.arguments;
    const method = `${type}/${action}`;

    if (this.config.debug) {
      console.error(`[DEBUG] ${action} ${type}: ${identifier}${args ? ' with args:' : ''}`, args);
    }

      // Handle special proxy tools locally
      if (type === 'tools' && params.name === 'test_connection') {
      return await this.handleTestConnection(args);
    }

    if (type === 'tools' && params.name === 'cache_refresh') {
      return await this.handleCacheRefresh(args);
    }

    if (type === 'tools' && params.name === 'cache_stats') {
      return await this.handleCacheStats();
      }

    // Check cache for non-tool requests
    const cacheKey = this.getCacheKey(method, params);
    if (this.shouldCache(method)) {
      const cachedData = this.getFromCache(cacheKey);
      if (cachedData) {
        return cachedData;
      }
    }

    try {
      // Forward request to remote server
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
        // Cache the result if appropriate
        if (this.shouldCache(method)) {
          this.setCache(cacheKey, response.result, method);
        }

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
      'Pragma': 'no-cache',
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

  private async handleCacheRefresh(args: any = {}): Promise<any> {
    const clearAll = args?.clear_all !== false; // Default to true
    const statsBeforeClear = { ...this.cacheStats };

    if (clearAll) {
      this.clearCache();
    }

    const results = {
      action: 'cache_refresh',
      timestamp: new Date().toISOString(),
      cache_cleared: clearAll,
      stats_before_clear: statsBeforeClear,
      stats_after_clear: this.getCacheStats(),
      cache_enabled: !this.config.debug,
      message: clearAll
        ? 'Cache completely cleared. Next requests will fetch fresh data from remote server.'
        : 'Cache refresh requested but clear_all was false.',
    };

    if (this.config.debug) {
      results.message += ' Note: Caching is disabled in debug mode.';
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

  private async handleCacheStats(): Promise<any> {
    const stats = this.getCacheStats();

    // Get cache entry details
    const entryDetails: any[] = [];
    for (const [key, entry] of this.cache.entries()) {
      const ageMs = Date.now() - entry.timestamp;
      const remainingMs = Math.max(0, entry.ttl - ageMs);

      entryDetails.push({
        key: key.length > 50 ? key.substring(0, 50) + '...' : key,
        age_ms: ageMs,
        remaining_ttl_ms: remainingMs,
        expired: remainingMs === 0,
        size_estimate: JSON.stringify(entry.data).length
      });
    }

    // Sort by age (newest first)
    entryDetails.sort((a, b) => a.age_ms - b.age_ms);

    const results = {
      cache_statistics: stats,
      cache_entries: entryDetails.slice(0, 10), // Show up to 10 most recent entries
      total_entries_shown: Math.min(10, entryDetails.length),
      total_entries: entryDetails.length,
      memory_usage_estimate_kb: Math.round(
        Array.from(this.cache.values())
          .reduce((total, entry) => total + JSON.stringify(entry.data).length, 0) / 1024
      ),
      configuration: {
        max_cache_size: this.maxCacheSize,
        default_ttl_ms: this.defaultTTL,
        debug_mode: this.config.debug,
        cache_enabled: !this.config.debug,
      },
      timestamp: new Date().toISOString(),
    };

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify(results, null, 2),
        },
      ],
    };
  }

  private async handleTestConnection(args: any = {}): Promise<any> {
    const verbose = args?.verbose || false;
    const quick = args?.quick || false;
    const skipRemote = args?.skip_remote || false;

    // If quick mode is requested, return lightweight status
    if (quick) {
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
                mode: 'quick_status',
                status: 'connected',
                version: this.version,
                remote_url: this.config.baseUrl,
                response_time_ms: responseTime,
                config: {
                  timeout: this.config.timeout,
                  debug: this.config.debug,
                  api_key_configured: !!this.config.apiKey,
                },
                cache: this.getCacheStats(),
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
                mode: 'quick_status',
                status: 'disconnected',
                version: this.version,
                remote_url: this.config.baseUrl,
                error: error instanceof Error ? error.message : String(error),
                config: {
                  timeout: this.config.timeout,
                  debug: this.config.debug,
                  api_key_configured: !!this.config.apiKey,
                },
                cache: this.getCacheStats(),
                timestamp: new Date().toISOString(),
              }, null, 2),
            },
          ],
        };
      }
    }

    // Full comprehensive diagnostics (existing implementation)
    const results: any = {
      timestamp: new Date().toISOString(),
      proxy_server: {
        status: 'operational',
        version: this.version,
        environment: {
          node_version: process.version,
          platform: process.platform,
          api_key_configured: !!this.config.apiKey,
          api_key_length: this.config.apiKey ? this.config.apiKey.length : 0,
          base_url: this.config.baseUrl,
          timeout: this.config.timeout,
          debug_mode: this.config.debug,
        },
        cache: this.getCacheStats(),
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
      results.recommendations.push('Only tools working - this suggests AI assistant cached empty resources/prompts during startup. Try using cache_refresh tool or restart AI assistant.');
      }

    if (results.remote_server.tests.health_check?.response_time_ms > 2000 ||
        results.remote_server.tests.capabilities_endpoint?.response_time_ms > 2000) {
      results.recommendations.push('Slow response times detected - consider increasing FORMULIZE_TIMEOUT or checking network');
    }

    // Add cache-specific recommendations
    if (results.proxy_server.cache.hit_rate_percent < 50 && results.proxy_server.cache.total_requests > 10) {
      results.recommendations.push('Low cache hit rate - this is normal for first use but may indicate frequently changing data');
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

  async run(): Promise<void> {
    const transport = new StdioServerTransport();

    if (this.config.debug) {
      console.error(`[DEBUG] Starting Formulize MCP Server v${this.version}`);
      console.error(`[DEBUG] Remote URL: ${this.config.baseUrl}`);
      console.error(`[DEBUG] Timeout: ${this.config.timeout}ms`);
      console.error(`[DEBUG] Capabilities: tools, resources, prompts`);
      console.error(`[DEBUG] Caching: DISABLED (debug mode)`);
    } else {
      console.error(`[INFO] Starting Formulize MCP Server v${this.version} with caching enabled`);
      console.error(`[INFO] Cache settings: max size=${this.maxCacheSize}, default TTL=${this.defaultTTL}ms`);
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
