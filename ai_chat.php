<?php
/**
 * Formulize AI Assistant Proof of Concept
 *
 * This page provides a chat interface that integrates with Claude or Gemini AI
 * and uses Formulize MCP tools to interact with the system.
 */

include_once "mainfile.php";
include "header.php";

// Ensure the user is logged in for the PoC to work with session auth
if (!$xoopsUser) {
    echo "<div class='errorMsg'>" . _MD_FORMULIZE_MUST_BE_LOGGED_IN . "</div>";
    include "footer.php";
    exit();
}
?>

<div id="ai-assistant-container" style="max-width: 1000px; margin: 20px auto; font-family: sans-serif; display: flex; flex-direction: column;">
    <div style="background: #007cba; color: white; padding: 15px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0; color: white;">Formulize AI Assistant (PoC)</h2>
        <div id="settings-toggle" title="Toggle settings" style="font-size: 0.8em; background: rgba(0,0,0,0.2); padding: 4px 10px; border-radius: 4px; cursor: pointer; user-select: none; display: flex; gap: 8px; align-items: center;">
            <span id="mcp-status">Initializing...</span>
            <span style="opacity: 0.75; font-size: 1.1em;">⚙</span>
        </div>
    </div>

    <div id="settings-panel" style="background: #f8f9fa; border: 1px solid #ddd; border-top: none; padding: 15px; display: none; gap: 10px; align-items: center; flex-wrap: wrap;">
        <div style="display: flex; gap: 10px; align-items: center;">
            <label for="provider-select" style="font-weight: bold; font-size: 0.9em;">Provider:</label>
            <select id="provider-select" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="claude">Claude (Anthropic)</option>
                <option value="gemini">Gemini (Google)</option>
                <option value="ollama">Ollama (Local)</option>
            </select>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex: 2; min-width: 180px;">
            <label for="model-name" style="font-weight: bold; font-size: 0.9em;">Model:</label>
            <select id="model-name" style="flex: 1; min-width: 0; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"></select>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex: 1; min-width: 140px;">
            <label for="ai-api-key" style="font-weight: bold; font-size: 0.9em;">API Key:</label>
            <input type="password" id="ai-api-key" placeholder="Enter your API Key" autocomplete="new-password" style="flex: 1; min-width: 0; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>
        <button id="save-settings" style="padding: 8px 15px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer;">Save Settings</button>
        <div id="tool-selection-panel" style="display: none; flex: 0 0 100%; border-top: 1px solid #ddd; padding-top: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <span style="font-weight: bold; font-size: 0.85em;">Active Tools: <span id="tool-selection-count"></span></span>
                <div style="display: flex; gap: 8px;">
                    <button id="tool-select-all" style="padding: 3px 8px; font-size: 0.8em; background: #007cba; color: white; border: none; border-radius: 3px; cursor: pointer;">All</button>
                    <button id="tool-select-none" style="padding: 3px 8px; font-size: 0.8em; background: #6c757d; color: white; border: none; border-radius: 3px; cursor: pointer;">None</button>
                </div>
            </div>
            <div id="tool-selection-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 2px 16px;"></div>
        </div>
    </div>

    <div id="chat-window" style="flex: 1; min-height: 0; overflow-y: auto; background: white; border: 1px solid #ddd; border-top: none; padding: 20px; display: flex; flex-direction: column; gap: 15px;">
        <div class="message system" style="background: #e9ecef; padding: 10px 15px; border-radius: 10px; align-self: flex-start; max-width: 80%;">
            Welcome to the Formulize AI Assistant! Select a provider, enter your API Key, and click Save Settings to start.
            Once connected, I can help you explore your Formulize system, list forms, create entries, and more.
        </div>
    </div>

    <div style="background: #f8f9fa; border: 1px solid #ddd; border-top: none; padding: 15px; border-radius: 0; display: flex; gap: 10px;">
        <textarea id="user-input" placeholder="Ask me anything about Formulize..." style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px; resize: none; height: 60px;"></textarea>
        <button id="send-btn" style="padding: 0 25px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Send</button>
    </div>

    <div id="activity-toggle-bar" title="Toggle activity context panel" style="background: #eef2f5; border: 1px solid #ddd; border-top: none; padding: 7px 15px; border-radius: 0 0 8px 8px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-size: 0.8em; color: #555; user-select: none;">
        <span>Activity Context &nbsp;<span id="activity-count" style="background: #6c757d; color: white; padding: 1px 7px; border-radius: 10px; font-size: 0.85em;">0</span></span>
        <span id="activity-arrow" style="font-size: 0.85em; opacity: 0.7;">▶ show what AI sees</span>
    </div>
    <div id="activity-panel" style="display: none; background: #fafafa; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px; max-height: 220px; overflow-y: auto;">
        <div id="activity-list" style="padding: 8px 15px; font-size: 0.78em; font-family: monospace; display: flex; flex-direction: column; gap: 3px;"></div>
        <div style="padding: 6px 15px; font-size: 0.72em; color: #888; border-top: 1px solid #eee;">Updates live from all open tabs · Last 30 min · Appended to every AI message</div>
    </div>
</div>

<style>
.ai-markdown { margin-top: 4px; }
.ai-markdown p { margin: 0 0 8px 0; }
.ai-markdown p:last-child { margin-bottom: 0; }
.ai-markdown ul, .ai-markdown ol { margin: 4px 0 8px 18px; padding: 0; }
.ai-markdown li { margin: 2px 0; }
.ai-markdown h1, .ai-markdown h2, .ai-markdown h3, .ai-markdown h4 { margin: 10px 0 4px 0; font-size: 1em; }
.ai-markdown code { background: rgba(0,0,0,0.07); padding: 1px 4px; border-radius: 3px; font-family: monospace; font-size: 0.9em; }
.ai-markdown pre { background: #2b2b2b; color: #f8f8f2; padding: 10px 12px; border-radius: 5px; margin: 8px 0; white-space: pre-wrap; word-break: break-word; }
.ai-markdown pre code { background: none; padding: 0; color: inherit; font-size: 0.88em; }
.ai-markdown blockquote { border-left: 3px solid #aaa; margin: 8px 0; padding: 2px 12px; color: #555; }
.ai-markdown table { border-collapse: collapse; margin: 8px 0; font-size: 0.9em; }
.ai-markdown th, .ai-markdown td { border: 1px solid #ccc; padding: 4px 8px; }
.ai-markdown th { background: #e9ecef; }
.ai-markdown a { color: #007cba; }
.ai-markdown hr { border: none; border-top: 1px solid #ddd; margin: 8px 0; }
</style>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script type="importmap">
  {
    "imports": {
      "@google/generative-ai": "https://esm.run/@google/generative-ai"
    }
  }
</script>

<script type="module">
    import { GoogleGenerativeAI } from "@google/generative-ai";

    const chatWindow = document.getElementById('chat-window');
    const userInput = document.getElementById('user-input');
    const sendBtn = document.getElementById('send-btn');
    const apiKeyInput = document.getElementById('ai-api-key');
    const modelNameInput = document.getElementById('model-name');
    const providerSelect = document.getElementById('provider-select');
    const saveSettingsBtn = document.getElementById('save-settings');
    const mcpStatus = document.getElementById('mcp-status');

    const SYSTEM_PROMPT = "You are the Formulize AI Assistant. You help users manage their data in Formulize. You have access to tools that can list forms, read entries, and modify the system. Always use the list_forms tool first to understand what is in the system. Be concise and helpful.";

    function getActivityLog() {
        try {
            const raw = localStorage.getItem('formulize_activity_log');
            return raw ? JSON.parse(raw) : [];
        } catch (e) { return []; }
    }

    function describeEvent(e) {
        const time = new Date(e.ts).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const ids = [];
        if (e.fid)    ids.push('form #' + e.fid);
        if (e.sid)    ids.push('screen #' + e.sid);
        if (e.ele_id) ids.push('element #' + e.ele_id);
        if (e.entry)  ids.push('entry #' + e.entry);
        const detail = ids.length ? ' (' + ids.join(', ') + ')' : '';

        if (e.type === 'formulize_event') {
            let extra = '';
            if (e.searches && typeof e.searches === 'object') {
                const terms = Object.entries(e.searches)
                    .filter(([, v]) => v !== '' && v !== null)
                    .map(([k, v]) => `${k}="${v}"`)
                    .join(', ');
                if (terms) extra += '; searching: ' + terms;
            }
            if (e.sort) extra += '; sort: ' + e.sort + (e.order ? ' ' + e.order : '');
            if (e.scope) extra += '; scope: ' + e.scope;
            return { time, text: e.event + detail + extra, kind: 'server' };
        }
        if (e.type === 'admin_save') {
            const status = e.success ? '' : ' [FAILED]';
            return { time, text: 'Admin saved: ' + (e.handler || '?') + status + detail, kind: e.success ? 'admin' : 'error' };
        }
        if (e.type === 'pageview') {
            const label = e.admin ? 'Admin page: ' + (e.title || e.url) : 'Viewed: ' + (e.title || e.url);
            return { time, text: label + detail, kind: e.admin ? 'admin' : 'view' };
        }
        if (e.type === 'form_submit') {
            return { time, text: 'Submitted: ' + (e.title || e.url) + detail, kind: 'save' };
        }
        return { time, text: e.type + detail, kind: 'other' };
    }

    function getActivityContext() {
        const log = getActivityLog();
        if (!log.length) return '';
        const lines = log.map(e => {
            const d = describeEvent(e);
            return `[${d.time}] ${d.text}`;
        });
        return `[Recent Formulize activity across all open tabs (last 30 min):\n${lines.join('\n')}\n]`;
    }

    // --- Activity panel ---

    const activityToggleBar = document.getElementById('activity-toggle-bar');
    const activityPanel     = document.getElementById('activity-panel');
    const activityList      = document.getElementById('activity-list');
    const activityCount     = document.getElementById('activity-count');
    const activityArrow     = document.getElementById('activity-arrow');

    const KIND_COLORS = {
        server: '#e8f4fd',
        admin:  '#fff3cd',
        view:   '#f0f0f0',
        save:   '#d4edda',
        error:  '#f8d7da',
        other:  '#f8f9fa'
    };

    function refreshActivityPanel() {
        const log = getActivityLog();
        activityCount.textContent = log.length;
        activityCount.style.background = log.length > 0 ? '#007cba' : '#6c757d';

        activityList.innerHTML = '';
        log.forEach(e => {
            const d = describeEvent(e);
            const row = document.createElement('div');
            row.style.cssText = `padding: 2px 6px; border-radius: 3px; background: ${KIND_COLORS[d.kind] || KIND_COLORS.other}; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;`;
            row.title = `[${d.time}] ${d.text}`;
            row.textContent = `[${d.time}] ${d.text}`;
            activityList.appendChild(row);
        });

        // Scroll to bottom (newest event)
        activityList.scrollTop = activityList.scrollHeight;
    }

    activityToggleBar.addEventListener('click', () => {
        const open = activityPanel.style.display !== 'none';
        activityPanel.style.display = open ? 'none' : 'block';
        activityToggleBar.style.borderRadius = open ? '0 0 8px 8px' : '0';
        activityArrow.textContent = open ? '▶ show what AI sees' : '▼ hide';
        if (!open) refreshActivityPanel();
    });

    // Live update from other tabs — the `storage` event fires in every tab
    // except the one that made the change, which is exactly what we want here:
    // the chat window refreshes when any other Formulize tab writes an event.
    window.addEventListener('storage', e => {
        if (e.key === 'formulize_activity_log') refreshActivityPanel();
    });

    refreshActivityPanel(); // initialize count on page load

    let availableTools = []; // Raw MCP tools (full list from server)
    let selectedToolNames = new Set(); // Which tools are currently active
    let geminiChat = null;   // Gemini stateful chat object (rebuilt on Save Settings / Refresh)
    let claudeHistory = [];  // Claude explicit conversation history
    let ollamaHistory = [];  // Ollama explicit conversation history

    // Load settings from localStorage
    const savedProvider = localStorage.getItem('ai_provider') || 'claude';
    providerSelect.value = savedProvider;

    function settingsForProvider(p) {
        return {
            key:   localStorage.getItem(`ai_api_key_${p}`) || '',
            model: localStorage.getItem(`ai_model_${p}`) || ''
        };
    }

    function saveSettingsForProvider(p, key, model) {
        if (key) localStorage.setItem(`ai_api_key_${p}`, key);
        if (model) localStorage.setItem(`ai_model_${p}`, model);
    }

    const MODEL_DEFAULTS = { claude: 'claude-sonnet-4-6', gemini: 'gemini-2.0-flash', ollama: 'llama3.2' };

    function populateModelSelect(models, preferredId) {
        modelNameInput.innerHTML = '';
        models.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = m.name;
            modelNameInput.appendChild(opt);
        });
        if (preferredId) modelNameInput.value = preferredId;
        if (!modelNameInput.value && models.length > 0) modelNameInput.value = models[0].id;
        updateToolCount();
    }

    async function discoverModels() {
        const provider = providerSelect.value;
        const apiKey = apiKeyInput.value.trim() || settingsForProvider(provider).key;
        const savedModel = settingsForProvider(provider).model || MODEL_DEFAULTS[provider] || '';

        // Seed the select immediately with saved/default so the rest of the page can read a value
        populateModelSelect([{ id: savedModel, name: savedModel }], savedModel);
        modelNameInput.disabled = true;

        try {
            let models = [];

            if (provider === 'claude' && apiKey) {
                const resp = await fetch('ai_proxy.php', { headers: { 'X-API-Key': apiKey } });
                if (resp.ok) {
                    const data = await resp.json();
                    models = (data.data || []).map(m => ({ id: m.id, name: m.display_name || m.id }));
                }
            } else if (provider === 'gemini' && apiKey) {
                const resp = await fetch(`https://generativelanguage.googleapis.com/v1beta/models?key=${apiKey}`);
                if (resp.ok) {
                    const data = await resp.json();
                    models = (data.models || [])
                        .filter(m => (m.supportedGenerationMethods || []).includes('generateContent'))
                        .map(m => ({ id: m.name.replace('models/', ''), name: m.displayName || m.name.replace('models/', '') }));
                }
            } else if (provider === 'ollama') {
                const resp = await fetch('http://localhost:11434/api/tags');
                if (resp.ok) {
                    const data = await resp.json();
                    models = (data.models || []).map(m => ({ id: m.name, name: m.name }));
                }
            }

            if (models.length > 0) populateModelSelect(models, savedModel);
        } catch (e) {
            // keep the seeded fallback
        }

        modelNameInput.disabled = false;
    }

    function updateProviderHints() {
        const p = providerSelect.value;
        const isOllama = p === 'ollama';
        apiKeyInput.placeholder = isOllama ? 'No key needed' : 'Enter your API Key';
        apiKeyInput.value = isOllama ? '' : (settingsForProvider(p).key || '');
        apiKeyInput.disabled = isOllama;
        apiKeyInput.style.opacity = isOllama ? '0.45' : '';
        discoverModels();
    }
    providerSelect.addEventListener('change', () => {
        localStorage.setItem('ai_provider', providerSelect.value);
        updateProviderHints();
    });
    apiKeyInput.addEventListener('blur', () => {
        if (apiKeyInput.value.trim()) discoverModels();
    });
    modelNameInput.addEventListener('change', updateToolCount);
    updateProviderHints(); // Apply on load — also triggers initial model discovery

    document.getElementById('settings-toggle').addEventListener('click', () => {
        const panel = document.getElementById('settings-panel');
        panel.style.display = panel.style.display === 'none' ? 'flex' : 'none';
    });

    const savedKey = settingsForProvider(savedProvider).key;
    if (savedKey || savedProvider === 'ollama') initializeMCP();

    saveSettingsBtn.addEventListener('click', () => {
        const key = apiKeyInput.value.trim();
        const modelName = modelNameInput.value.trim();
        const provider = providerSelect.value;
        if (modelName && (key || provider === 'ollama')) {
            saveSettingsForProvider(provider, key, modelName);
            localStorage.setItem('ai_provider', provider);
            geminiChat = null;
            claudeHistory = [];
            ollamaHistory = [];
            initializeMCP();
            addMessage('System', `Settings saved. Provider: ${provider}, Model: ${modelName}`, 'system');
        }
    });

    // --- Tool selection panel ---

    function getActiveTools() {
        return availableTools.filter(t => selectedToolNames.has(t.name));
    }

    function saveToolSelection() {
        localStorage.setItem('ai_selected_tools', JSON.stringify([...selectedToolNames]));
    }

    function updateToolCount() {
        const active = selectedToolNames.size;
        const total = availableTools.length;
        document.getElementById('tool-selection-count').innerText = `${active} / ${total}`;
        const model = modelNameInput.value.trim() || '—';
        mcpStatus.innerText = total > 0
            ? `Active tools: ${active}/${total}  ·  Model: ${model}`
            : `Model: ${model}`;
    }

    function renderToolPanel() {
        const panel = document.getElementById('tool-selection-panel');
        const list = document.getElementById('tool-selection-list');

        if (availableTools.length === 0) {
            panel.style.display = 'none';
            return;
        }

        // Restore saved selection, defaulting to all tools selected
        const saved = localStorage.getItem('ai_selected_tools');
        if (saved) {
            const savedNames = new Set(JSON.parse(saved));
            selectedToolNames = new Set(availableTools.map(t => t.name).filter(n => savedNames.has(n)));
        } else {
            selectedToolNames = new Set(availableTools.map(t => t.name));
        }

        // Build checklist
        list.innerHTML = '';
        availableTools.forEach(tool => {
            const label = document.createElement('label');
            label.title = tool.description || '';
            label.style.cssText = 'display: flex; align-items: baseline; gap: 6px; padding: 2px 0; cursor: pointer; font-size: 0.82em;';

            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.checked = selectedToolNames.has(tool.name);
            cb.addEventListener('change', () => {
                if (cb.checked) selectedToolNames.add(tool.name);
                else selectedToolNames.delete(tool.name);
                saveToolSelection();
                updateToolCount();
            });

            const name = document.createElement('span');
            name.innerText = tool.name;

            label.appendChild(cb);
            label.appendChild(name);
            list.appendChild(label);
        });

        document.getElementById('tool-select-all').onclick = () => {
            selectedToolNames = new Set(availableTools.map(t => t.name));
            list.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = true);
            saveToolSelection();
            updateToolCount();
        };
        document.getElementById('tool-select-none').onclick = () => {
            selectedToolNames = new Set();
            list.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = false);
            saveToolSelection();
            updateToolCount();
        };

        panel.style.display = 'block';
        updateToolCount();
    }

    async function initializeMCP() {
        const provider = providerSelect.value;
        try {
            mcpStatus.innerText = 'MCP: Fetching tools...';

            const response = await fetch('mcp/index.php/capabilities');
            const data = await response.json();

            const capabilities = data.result?.capabilities || data.capabilities;
            availableTools = capabilities?.tools || [];

            renderToolPanel();

            if (availableTools.length === 0) {
                mcpStatus.innerText = 'MCP: No tools found';
            }

            // Gemini needs a chat object initialized with the tool list
            if (provider === 'gemini') {
                const apiKey = apiKeyInput.value.trim();
                if (!apiKey) return;

                const genAI = new GoogleGenerativeAI(apiKey);
                const modelName = modelNameInput.value.trim() || 'gemini-2.0-flash';

                const functionDeclarations = getActiveTools().map(tool => ({
                    name: tool.name,
                    description: tool.description,
                    parameters: {
                        type: "object",
                        properties: tool.inputSchema?.properties || {},
                        required: tool.inputSchema?.required || []
                    }
                }));

                const modelConfig = { model: modelName, systemInstruction: SYSTEM_PROMPT };
                if (functionDeclarations.length > 0) {
                    modelConfig.tools = [{ functionDeclarations }];
                }

                geminiChat = genAI.getGenerativeModel(modelConfig).startChat();
            }
        } catch (error) {
            console.error('MCP init error:', error);
            mcpStatus.innerText = 'MCP: Error';
            addMessage('Error', 'Failed to initialize: ' + error.message, 'error');
        }
    }

    async function sendMessage() {
        const text = userInput.value.trim();
        if (!text) return;

        const provider = providerSelect.value;
        const apiKey = settingsForProvider(provider).key;
        if (provider !== 'ollama' && !apiKey) {
            addMessage('Error', 'Please save your settings first.', 'error');
            return;
        }

        addMessage('You', text, 'user');
        userInput.value = '';
        userInput.style.height = '60px';

        const loadingMsg = addMessage('AI', 'Thinking.', 'ai');
        // Animate the dots while waiting for the response
        const _thinkingEl = loadingMsg.querySelector('.ai-markdown') || loadingMsg.lastElementChild;
        let _dotCount = 1;
        const _dotsInterval = setInterval(() => {
            _dotCount = _dotCount >= 3 ? 1 : _dotCount + 1;
            _thinkingEl.textContent = 'Thinking' + '.'.repeat(_dotCount);
        }, 400);
        const _origRemove = loadingMsg.remove.bind(loadingMsg);
        loadingMsg.remove = () => { clearInterval(_dotsInterval); _origRemove(); };

        try {
            if (provider === 'gemini') {
                await sendGeminiMessage(text, loadingMsg);
            } else if (provider === 'ollama') {
                await sendOllamaMessage(text, loadingMsg);
            } else {
                await sendClaudeMessage(text, loadingMsg);
            }
            refreshActivityPanel(); // refresh count after send (catches same-tab events)
        } catch (error) {
            console.error('Chat error:', error);
            loadingMsg.remove();
            addMessage('Error', 'An error occurred: ' + error.message, 'error');
        }
    }

    // --- Gemini path ---

    async function sendGeminiMessage(text, loadingMsg) {
        if (!geminiChat) {
            loadingMsg.remove();
            addMessage('Error', 'Please save your settings first to initialize Gemini.', 'error');
            return;
        }

        const context = getActivityContext();
        let result = await geminiChat.sendMessage(context ? text + '\n\n' + context : text);
        let response = result.response;

        while (response.functionCalls && response.functionCalls()) {
            const calls = response.functionCalls();
            const toolResponses = [];
            for (const call of calls) {
                const toolBlock = addToolRequest(call.name, call.args);
                const result = await executeTool(call.name, call.args);
                addToolResponse(toolBlock, result.raw);
                toolResponses.push({
                    functionResponse: { name: call.name, response: { content: result.text } }
                });
            }
            result = await geminiChat.sendMessage(toolResponses);
            response = result.response;
        }

        loadingMsg.remove();
        const geminiMsg = addMessage('AI', 'Thinking...', 'ai');
        await typewriterEffect(geminiMsg.querySelector('.ai-markdown') || geminiMsg.lastElementChild, response.text());
    }

    // --- Claude path ---

    async function sendClaudeMessage(text, loadingMsg) {
        const context = getActivityContext();
        claudeHistory.push({ role: 'user', content: context ? text + '\n\n' + context : text });

        let response = await callClaude(claudeHistory);

        while (response.stop_reason === 'tool_use') {
            claudeHistory.push({ role: 'assistant', content: response.content });

            const toolResults = [];
            for (const block of response.content) {
                if (block.type === 'tool_use') {
                    const toolBlock = addToolRequest(block.name, block.input);
                    const result = await executeTool(block.name, block.input);
                    addToolResponse(toolBlock, result.raw);
                    toolResults.push({ type: 'tool_result', tool_use_id: block.id, content: result.text });
                }
            }

            claudeHistory.push({ role: 'user', content: toolResults });
            response = await callClaude(claudeHistory);
        }

        const textContent = response.content
            .filter(b => b.type === 'text')
            .map(b => b.text)
            .join('\n');

        claudeHistory.push({ role: 'assistant', content: response.content });

        loadingMsg.remove();
        const claudeMsg = addMessage('AI', 'Thinking...', 'ai');
        await typewriterEffect(claudeMsg.querySelector('.ai-markdown') || claudeMsg.lastElementChild, textContent);
    }

    async function callClaude(messages) {
        const apiKey = settingsForProvider('claude').key;
        const modelName = modelNameInput.value.trim() || 'claude-sonnet-4-6';

        const claudeTools = getActiveTools().map(tool => ({
            name: tool.name,
            description: tool.description,
            input_schema: tool.inputSchema || { type: 'object', properties: {} }
        }));

        const body = { model: modelName, max_tokens: 4096, system: SYSTEM_PROMPT, messages };
        if (claudeTools.length > 0) body.tools = claudeTools;

        const response = await fetch('ai_proxy.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-API-Key': apiKey },
            body: JSON.stringify(body)
        });

        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error?.message || `HTTP ${response.status}`);
        }
        return data;
    }

    // --- Ollama path ---

    async function sendOllamaMessage(text, loadingMsg) {
        const context = getActivityContext();
        ollamaHistory.push({ role: 'user', content: context ? text + '\n\n' + context : text });

        let response = await callOllama(ollamaHistory);
        let message = response.choices[0].message;

        while (response.choices[0].finish_reason === 'tool_calls' && message.tool_calls) {
            ollamaHistory.push({ role: 'assistant', content: message.content || '', tool_calls: message.tool_calls });

            for (const toolCall of message.tool_calls) {
                // Ollama returns arguments as a JSON string, not an object
                const args = JSON.parse(toolCall.function.arguments);
                const toolBlock = addToolRequest(toolCall.function.name, args);
                const result = await executeTool(toolCall.function.name, args);
                addToolResponse(toolBlock, result.raw);
                ollamaHistory.push({ role: 'tool', tool_call_id: toolCall.id, content: result.text });
            }

            response = await callOllama(ollamaHistory);
            message = response.choices[0].message;
        }

        ollamaHistory.push({ role: 'assistant', content: message.content || '' });

        loadingMsg.remove();
        const ollamaMsg = addMessage('AI', 'Thinking...', 'ai');
        await typewriterEffect(ollamaMsg.querySelector('.ai-markdown') || ollamaMsg.lastElementChild, message.content || '(no response)');
    }

    async function callOllama(messages) {
        const modelName = modelNameInput.value.trim() || 'llama3.2';

        const ollamaTools = getActiveTools().map(tool => ({
            type: 'function',
            function: {
                name: tool.name,
                description: tool.description,
                parameters: tool.inputSchema || { type: 'object', properties: {} }
            }
        }));

        // System prompt goes as the first message in OpenAI-compatible format
        const messagesWithSystem = [
            { role: 'system', content: SYSTEM_PROMPT },
            ...messages
        ];

        const body = { model: modelName, messages: messagesWithSystem, stream: false };
        if (ollamaTools.length > 0) body.tools = ollamaTools;

        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 300000); // 5 minutes

        try {
            const response = await fetch('http://localhost:11434/v1/chat/completions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
                signal: controller.signal
            });

            clearTimeout(timeout);

            if (!response.ok) {
                const err = await response.text();
                throw new Error(`Ollama error ${response.status}: ${err}`);
            }

            return response.json();
        } catch (error) {
            clearTimeout(timeout);
            if (error.name === 'AbortError') {
                throw new Error('Ollama request timed out — is Ollama running? If your page is served over HTTPS, browsers block requests to localhost (Private Network Access policy).');
            }
            throw error;
        }
    }

    // --- Shared MCP tool executor (same for all providers) ---

    async function executeTool(name, args) {
        try {
            const response = await fetch('mcp/index.php/mcp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    jsonrpc: '2.0',
                    method: 'tools/call',
                    params: { name, arguments: args },
                    id: Date.now()
                })
            });
            const raw = await response.json();
            const text = raw.result?.content?.[0]?.text
                || (raw.error ? `Error: ${raw.error.message}` : 'No output from tool');
            return { text, raw };
        } catch (error) {
            return { text: `Network error: ${error.message}`, raw: null };
        }
    }

    function addToolRequest(name, args) {
        const msgDiv = document.createElement('div');
        msgDiv.style.cssText = 'align-self: stretch; background: #f0f7ff; border: 1px solid #c8dff7; border-radius: 8px; font-size: 0.82em;';
        msgDiv.dataset.toolName = name;

        const header = document.createElement('div');
        header.style.cssText = 'padding: 6px 12px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: #e8f4fd; border-bottom: 1px solid #c8dff7; border-radius: 8px 8px 0 0;';

        const titleSpan = document.createElement('span');
        titleSpan.style.fontFamily = 'monospace';
        titleSpan.innerText = '⏳ Tool: ' + name;

        const toggleSpan = document.createElement('span');
        toggleSpan.style.cssText = 'font-size: 0.85em; color: #555; white-space: nowrap;';
        toggleSpan.innerText = '▶ expand';

        header.appendChild(titleSpan);
        header.appendChild(toggleSpan);

        const body = document.createElement('div');
        body.style.display = 'none';
        body.style.cssText = 'display: none; padding: 8px 12px; font-family: monospace;';

        const paramsLabel = document.createElement('div');
        paramsLabel.style.cssText = 'font-weight: bold; margin-bottom: 4px; color: #444;';
        paramsLabel.innerText = 'Parameters:';

        const paramsPre = document.createElement('pre');
        paramsPre.style.cssText = 'background: #f8f9fa; padding: 6px 8px; border-radius: 4px; white-space: pre-wrap; word-break: break-word; margin: 0 0 10px 0; font-size: 1em;';
        paramsPre.textContent = args && Object.keys(args).length > 0 ? JSON.stringify(args, null, 2) : '(no parameters)';

        const responseLabel = document.createElement('div');
        responseLabel.style.cssText = 'font-weight: bold; margin-bottom: 4px; color: #555;';
        responseLabel.innerText = 'Response:';

        const responsePre = document.createElement('pre');
        responsePre.style.cssText = 'background: #f8f9fa; padding: 6px 8px; border-radius: 4px; white-space: pre-wrap; word-break: break-word; margin: 0; font-size: 1em; color: #888;';
        responsePre.textContent = 'Waiting for response...';

        body.appendChild(paramsLabel);
        body.appendChild(paramsPre);
        body.appendChild(responseLabel);
        body.appendChild(responsePre);

        msgDiv._header = header;
        msgDiv._titleSpan = titleSpan;
        msgDiv._responseLabel = responseLabel;
        msgDiv._responsePre = responsePre;

        header.addEventListener('click', () => {
            const expanded = body.style.display !== 'none';
            body.style.display = expanded ? 'none' : 'block';
            toggleSpan.innerText = expanded ? '▶ expand' : '▼ collapse';
        });

        msgDiv.appendChild(header);
        msgDiv.appendChild(body);
        chatWindow.appendChild(msgDiv);
        chatWindow.scrollTop = chatWindow.scrollHeight;
        return msgDiv;
    }

    function addToolResponse(msgDiv, raw) {
        const hasError = raw && raw.error;
        const name = msgDiv.dataset.toolName;

        // Update header
        msgDiv._header.style.background = hasError ? '#fdf0f0' : '#e8f4fd';
        msgDiv._header.style.borderBottomColor = hasError ? '#f5c6cb' : '#c8dff7';
        msgDiv._titleSpan.innerText = (hasError ? '⚠ ' : '⚙ ') + 'Tool: ' + name;
        msgDiv.style.borderColor = hasError ? '#f5c6cb' : '#c8dff7';

        // Update response section
        msgDiv._responseLabel.style.color = hasError ? '#721c24' : '#155724';
        msgDiv._responseLabel.innerText = hasError ? 'Error:' : 'Response:';

        const responsePre = msgDiv._responsePre;
        responsePre.style.background = hasError ? '#f8d7da' : '#d4edda';
        responsePre.style.color = '';

        if (hasError) {
            responsePre.textContent = JSON.stringify(raw.error, null, 2);
        } else if (raw?.result?.content?.[0]?.text) {
            const text = raw.result.content[0].text;
            try { responsePre.textContent = JSON.stringify(JSON.parse(text), null, 2); }
            catch(e) { responsePre.textContent = text; }
        } else if (raw) {
            responsePre.textContent = JSON.stringify(raw, null, 2);
        } else {
            responsePre.textContent = 'No output';
        }

        chatWindow.scrollTop = chatWindow.scrollHeight;
    }

    async function typewriterEffect(container, text) {
        const chunks = text.match(/\S+\s*/g) || [text];
        let displayed = '';
        for (const chunk of chunks) {
            displayed += chunk;
            container.textContent = displayed;
            chatWindow.scrollTop = chatWindow.scrollHeight;
            await new Promise(r => setTimeout(r, 20));
        }
        // Final pass: full markdown render
        if (typeof marked !== 'undefined') {
            container.innerHTML = marked.parse(text, { breaks: false });
        } else {
            container.textContent = text;
        }
        chatWindow.scrollTop = chatWindow.scrollHeight;
    }

    function addMessage(sender, text, type) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `message ${type}`;

        const senderSpan = document.createElement('strong');
        senderSpan.innerText = sender + ': ';
        msgDiv.appendChild(senderSpan);

        if (type === 'ai' && typeof marked !== 'undefined') {
            const contentDiv = document.createElement('div');
            contentDiv.className = 'ai-markdown';
            contentDiv.innerHTML = marked.parse(text, { breaks: false });
            msgDiv.appendChild(contentDiv);
        } else {
            const textSpan = document.createElement('span');
            textSpan.innerText = text;
            msgDiv.appendChild(textSpan);
        }

        msgDiv.style.padding = '10px 15px';
        msgDiv.style.borderRadius = '10px';
        msgDiv.style.maxWidth = '80%';

        if (type === 'user') {
            msgDiv.style.alignSelf = 'flex-end';
            msgDiv.style.background = '#007cba';
            msgDiv.style.color = 'white';
        } else if (type === 'ai') {
            msgDiv.style.alignSelf = 'flex-start';
            msgDiv.style.background = '#f1f3f5';
            msgDiv.style.border = '1px solid #dee2e6';
        } else if (type === 'system') {
            msgDiv.style.alignSelf = 'center';
            msgDiv.style.background = '#fff3cd';
            msgDiv.style.color = '#856404';
            msgDiv.style.fontSize = '0.8em';
        } else if (type === 'error') {
            msgDiv.style.alignSelf = 'center';
            msgDiv.style.background = '#f8d7da';
            msgDiv.style.color = '#721c24';
        }

        chatWindow.appendChild(msgDiv);
        chatWindow.scrollTop = chatWindow.scrollHeight;
        return msgDiv;
    }

    sendBtn.addEventListener('click', sendMessage);
    userInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    userInput.addEventListener('input', () => {
        userInput.style.height = 'auto';
        userInput.style.height = (userInput.scrollHeight) + 'px';
        if (userInput.scrollHeight > 150) {
            userInput.style.height = '150px';
            userInput.style.overflowY = 'auto';
        } else {
            userInput.style.overflowY = 'hidden';
        }
    });

    function fitChatToViewport() {
        const container = document.getElementById('ai-assistant-container');
        const topOffset = container.getBoundingClientRect().top;
        container.style.height = (window.innerHeight - topOffset - 20) + 'px';
    }
    window.addEventListener('resize', fitChatToViewport);
    fitChatToViewport();
</script>

<?php
include "footer.php";
?>
