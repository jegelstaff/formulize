<?php
// AI Chat Assistant UI strings

// Auth
define('_MD_FORMULIZE_MUST_BE_LOGGED_IN',        'You must be logged in to use the AI Assistant.');

// Header / settings panel
define('_MD_FORMULIZE_AI_PAGE_TITLE',             'Gwynian - The Formulize AI Assistant');
define('_MD_FORMULIZE_AI_TOGGLE_SETTINGS_TITLE',  'Toggle settings');
define('_MD_FORMULIZE_AI_SETTINGS_CLOSE',         'close ✕');
define('_MD_FORMULIZE_AI_PROVIDER_LABEL',         'Provider:');
define('_MD_FORMULIZE_AI_PROVIDER_CLAUDE',        'Claude (Anthropic)');
define('_MD_FORMULIZE_AI_PROVIDER_GEMINI',        'Gemini (Google)');
define('_MD_FORMULIZE_AI_PROVIDER_OLLAMA',        'Ollama (Local)');
define('_MD_FORMULIZE_AI_MODEL_LABEL',            'Model:');
define('_MD_FORMULIZE_AI_API_KEY_LABEL',          'API Key:');
define('_MD_FORMULIZE_AI_API_KEY_PLACEHOLDER',    'Enter your API Key');
define('_MD_FORMULIZE_AI_API_KEY_OLLAMA',         'No key needed');
define('_MD_FORMULIZE_AI_SAVE_SETTINGS_BTN',      'Save Settings');
define('_MD_FORMULIZE_AI_ACTIVE_TOOLS_LABEL',     'Active Tools:');
define('_MD_FORMULIZE_AI_TOOLS_ALL_BTN',          'All');
define('_MD_FORMULIZE_AI_TOOLS_NONE_BTN',         'None');
define('_MD_FORMULIZE_AI_TOOLS_READ_DATA',        'Read data');
define('_MD_FORMULIZE_AI_TOOLS_WRITE_DATA',       'Read & Write data');
define('_MD_FORMULIZE_AI_TOOLS_MANAGE_FORMS',     'Manage forms');

// Chat input
define('_MD_FORMULIZE_AI_CHAT_PLACEHOLDER',       'Ask me anything about Formulize...');
define('_MD_FORMULIZE_AI_SEND_BTN',               'Send');

// Activity panel
define('_MD_FORMULIZE_AI_ACTIVITY_TOGGLE_TITLE',  'Toggle activity context panel');
define('_MD_FORMULIZE_AI_ACTIVITY_LABEL',         'Activity Context');
define('_MD_FORMULIZE_AI_ACTIVITY_SHOW',          '▶ show what the AI sees');
define('_MD_FORMULIZE_AI_ACTIVITY_HIDE',          '▼ hide');
define('_MD_FORMULIZE_AI_ACTIVITY_FOOTER',        'Updates live from all open tabs · Last 30 min · Appended to every AI message');

// Status / MCP
define('_MD_FORMULIZE_AI_INITIALIZING',           'Initializing...');
define('_MD_FORMULIZE_AI_FETCHING_TOOLS',         'MCP: Fetching tools...');
define('_MD_FORMULIZE_AI_NO_TOOLS_FOUND',         'MCP: No tools found');
define('_MD_FORMULIZE_AI_MCP_ERROR',              'MCP: Error');

// Status templates — use {placeholder} tokens replaced in JS
define('_MD_FORMULIZE_AI_SETTINGS_SAVED',         'Settings saved. Provider: {provider}, Model: {model}');
define('_MD_FORMULIZE_AI_ACTIVE_TOOLS_STATUS',    'Active tools: {active}/{total}  ·  Model: {model}');
define('_MD_FORMULIZE_AI_MODEL_STATUS',           'Model: {model}');

// Error / prompt messages
define('_MD_FORMULIZE_AI_SAVE_FIRST',             'Please save your settings first.');
define('_MD_FORMULIZE_AI_GEMINI_SAVE_FIRST',      'Please save your settings first to initialize Gemini.');
define('_MD_FORMULIZE_AI_FAILED_INIT',            'Failed to initialize: ');
define('_MD_FORMULIZE_AI_ERROR_OCCURRED',         'An error occurred: ');

// Welcome (shown only on first visit / no saved settings)
define('_MD_FORMULIZE_AI_WELCOME_ALERT',          "Welcome! I'm Gwynian, the Formulize AI Assistant!\n\nTo get started, select an AI provider, enter your API key in the settings panel, then click Save Settings.");
define('_MD_FORMULIZE_AI_WELCOME_MSG',            "I'm Gwynian, your Formulize AI Assistant! Select a provider, enter your API Key, and click Save Settings to start. Once connected, I can help you explore your Formulize system, make forms, create entries, and more.");

// Chat sender labels
define('_MD_FORMULIZE_AI_SENDER_YOU',             'You');
define('_MD_FORMULIZE_AI_SENDER_AI',              'AI');
define('_MD_FORMULIZE_AI_SENDER_SYSTEM',          'System');
define('_MD_FORMULIZE_AI_SENDER_ERROR',           'Error');

// Thinking animation
define('_MD_FORMULIZE_AI_THINKING',               'Thinking');

// Tool call UI
define('_MD_FORMULIZE_AI_TOOL_PENDING',           '⏳ Tool: ');
define('_MD_FORMULIZE_AI_TOOL_OK',                '⚙ Tool: ');
define('_MD_FORMULIZE_AI_TOOL_ERROR',             '⚠ Tool: ');
define('_MD_FORMULIZE_AI_TOOL_EXPAND',            '▶ expand');
define('_MD_FORMULIZE_AI_TOOL_COLLAPSE',          '▼ collapse');
define('_MD_FORMULIZE_AI_TOOL_PARAMS_LABEL',      'Parameters:');
define('_MD_FORMULIZE_AI_TOOL_NO_PARAMS',         '(no parameters)');
define('_MD_FORMULIZE_AI_TOOL_RESPONSE_LABEL',    'Response:');
define('_MD_FORMULIZE_AI_TOOL_WAITING',           'Waiting for response...');
define('_MD_FORMULIZE_AI_TOOL_NO_OUTPUT',         'No output');
define('_MD_FORMULIZE_AI_TOOL_RESPONSE_ERROR',    'Error:');
define('_MD_FORMULIZE_AI_TOOL_NET_ERROR',         'Network error: ');
define('_MD_FORMULIZE_AI_TOOL_CALL_ERROR',        'Error: ');

// Activity describeEvent strings
define('_MD_FORMULIZE_AI_EVENT_SAVED_NEW',        'Saved new entry');
define('_MD_FORMULIZE_AI_EVENT_SAVED',            'Saved entry');
define('_MD_FORMULIZE_AI_EVENT_DELETED',          'Deleted entry');
define('_MD_FORMULIZE_AI_EVENT_GATHERED',         'Gathered data');
define('_MD_FORMULIZE_AI_EVENT_SEARCHING',        '; searching: ');
define('_MD_FORMULIZE_AI_EVENT_SORT',             '; sort: ');
define('_MD_FORMULIZE_AI_EVENT_SCOPE',            '; scope: ');
define('_MD_FORMULIZE_AI_EVENT_ADMIN_SAVED',      'Admin saved: ');
define('_MD_FORMULIZE_AI_EVENT_ADMIN_FAILED',     ' [FAILED]');
define('_MD_FORMULIZE_AI_EVENT_VIEWED',           'Viewed: ');
define('_MD_FORMULIZE_AI_EVENT_ADMIN_PAGE',       'Admin page: ');
define('_MD_FORMULIZE_AI_EVENT_SUBMITTED',        'Submitted: ');
define('_MD_FORMULIZE_AI_CONTEXT_HEADER',         '[Recent Formulize activity across all open tabs (last 30 min):');

// System prompt sent to AI
define('_MD_FORMULIZE_AI_SYSTEM_PROMPT',          'You are the Formulize AI Assistant. You help users manage their data in Formulize. You have access to tools that can interact with the Formulize data and configuration. Be concise and helpful.');
