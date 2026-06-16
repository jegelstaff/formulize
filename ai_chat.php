<?php
/**
 * Formulize AI Assistant
 *
 * This page provides a chat interface that integrates with Claude or Gemini AI or Ollama
 * and uses Formulize MCP tools to interact with the system.
 */

include_once "mainfile.php";
include "header.php";

$_aiChatLang = isset($icmsConfig['language']) ? $icmsConfig['language'] : 'english';
$_aiChatLangFile = XOOPS_ROOT_PATH . '/modules/formulize/language/' . $_aiChatLang . '/ai_chat.php';
include_once (file_exists($_aiChatLangFile) ? $_aiChatLangFile : XOOPS_ROOT_PATH . '/modules/formulize/language/english/ai_chat.php');

// Ensure the user is logged in for the PoC to work with session auth
if (!$xoopsUser) {
    echo "<div class='errorMsg'>" . _MD_FORMULIZE_MUST_BE_LOGGED_IN . "</div>";
    include "footer.php";
    exit();
}
?>

<div id="ai-assistant-container" style="max-width: 1000px; margin: 20px auto; font-family: sans-serif; display: flex; flex-direction: column;">
    <div style="background: #007cba; color: white; padding: 15px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0; color: white;"><?php echo _MD_FORMULIZE_AI_PAGE_TITLE; ?></h2>
        <div id="settings-toggle" title="<?php echo _MD_FORMULIZE_AI_TOGGLE_SETTINGS_TITLE; ?>" style="font-size: 0.8em; background: rgba(0,0,0,0.2); padding: 4px 10px; border-radius: 4px; cursor: pointer; user-select: none; display: flex; gap: 8px; align-items: center;">
            <span id="mcp-status"><?php echo _MD_FORMULIZE_AI_INITIALIZING; ?></span>
            <span style="opacity: 0.75; font-size: 1.1em;">⚙</span>
            <span id="settings-close-label" style="display:none; opacity: 0.85;"><?php echo _MD_FORMULIZE_AI_SETTINGS_CLOSE; ?></span>
        </div>
    </div>

    <div id="settings-panel" style="background: #f8f9fa; border: 1px solid #ddd; border-top: none; padding: 15px; display: none; gap: 10px; align-items: center; flex-wrap: wrap;">
        <div style="display: flex; gap: 10px; align-items: center;">
            <label for="provider-select" style="font-weight: bold; font-size: 0.9em;"><?php echo _MD_FORMULIZE_AI_PROVIDER_LABEL; ?></label>
            <select id="provider-select" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="claude"><?php echo _MD_FORMULIZE_AI_PROVIDER_CLAUDE; ?></option>
                <option value="gemini"><?php echo _MD_FORMULIZE_AI_PROVIDER_GEMINI; ?></option>
                <option value="ollama"><?php echo _MD_FORMULIZE_AI_PROVIDER_OLLAMA; ?></option>
            </select>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex: 2; min-width: 180px;">
            <label for="model-name" style="font-weight: bold; font-size: 0.9em;"><?php echo _MD_FORMULIZE_AI_MODEL_LABEL; ?></label>
            <select id="model-name" style="flex: 1; min-width: 0; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"></select>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex: 1; min-width: 140px;">
            <label for="ai-api-key" style="font-weight: bold; font-size: 0.9em;"><?php echo _MD_FORMULIZE_AI_API_KEY_LABEL; ?></label>
            <input type="password" id="ai-api-key" placeholder="<?php echo _MD_FORMULIZE_AI_API_KEY_PLACEHOLDER; ?>" autocomplete="new-password" style="flex: 1; min-width: 0; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>
        <div style="display: flex; gap: 6px; align-items: center;">
            <label for="context-limit" style="font-weight: bold; font-size: 0.9em; white-space: nowrap;">History limit:</label>
            <input type="number" id="context-limit" min="4000" step="1000"
                   title="Maximum characters of conversation history sent per request. Reduce for local models with limited RAM."
                   style="width: 90px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; opacity: 0.55;" disabled>
            <span style="font-size: 0.82em; color: #555; white-space: nowrap;">chars</span>
            <label title="Enable to override the default history limit for this provider" style="display: flex; align-items: center; gap: 4px; font-size: 0.82em; cursor: pointer; white-space: nowrap; color: #555;">
                <input type="checkbox" id="context-limit-custom"> Custom
            </label>
        </div>
        <button id="save-settings" style="padding: 8px 15px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer;"><?php echo _MD_FORMULIZE_AI_SAVE_SETTINGS_BTN; ?></button>
        <div id="tool-selection-panel" style="display: none; flex: 0 0 100%; border-top: 1px solid #ddd; padding-top: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; flex-wrap: wrap; gap: 6px;">
                <span style="font-weight: bold; font-size: 0.85em;"><?php echo _MD_FORMULIZE_AI_ACTIVE_TOOLS_LABEL; ?> <span id="tool-selection-count"></span></span>
                <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                    <button id="tool-group-read" style="padding: 3px 8px; font-size: 0.8em; background: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer;"><?php echo _MD_FORMULIZE_AI_TOOLS_READ_DATA; ?></button>
                    <button id="tool-group-write" style="padding: 3px 8px; font-size: 0.8em; background: #fd7e14; color: white; border: none; border-radius: 3px; cursor: pointer;"><?php echo _MD_FORMULIZE_AI_TOOLS_WRITE_DATA; ?></button>
                    <button id="tool-group-manage" style="padding: 3px 8px; font-size: 0.8em; background: #6f42c1; color: white; border: none; border-radius: 3px; cursor: pointer;"><?php echo _MD_FORMULIZE_AI_TOOLS_MANAGE_FORMS; ?></button>
                    <span style="opacity: 0.3; font-size: 0.9em;">|</span>
                    <button id="tool-select-all" style="padding: 3px 8px; font-size: 0.8em; background: #007cba; color: white; border: none; border-radius: 3px; cursor: pointer;"><?php echo _MD_FORMULIZE_AI_TOOLS_ALL_BTN; ?></button>
                    <button id="tool-select-none" style="padding: 3px 8px; font-size: 0.8em; background: #6c757d; color: white; border: none; border-radius: 3px; cursor: pointer;"><?php echo _MD_FORMULIZE_AI_TOOLS_NONE_BTN; ?></button>
                </div>
            </div>
            <div id="tool-selection-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(185px, 1fr)); gap: 2px 16px;"></div>
        </div>
    </div>

    <div id="chat-window" style="flex: 1; min-height: 0; overflow-y: auto; background: white; border: 1px solid #ddd; border-top: none; padding: 20px; display: flex; flex-direction: column; gap: 15px;"></div>

    <div style="background: #f8f9fa; border: 1px solid #ddd; border-top: none; padding: 15px; border-radius: 0; display: flex; gap: 10px;">
        <textarea id="user-input" placeholder="<?php echo _MD_FORMULIZE_AI_CHAT_PLACEHOLDER; ?>" style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px; resize: none; height: 60px;"></textarea>
        <button id="send-btn" style="padding: 0 25px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;"><?php echo _MD_FORMULIZE_AI_SEND_BTN; ?></button>
    </div>

    <div id="activity-toggle-bar" title="<?php echo _MD_FORMULIZE_AI_ACTIVITY_TOGGLE_TITLE; ?>" style="background: #eef2f5; border: 1px solid #ddd; border-top: none; padding: 7px 15px; border-radius: 0 0 8px 8px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-size: 0.8em; color: #555; user-select: none;">
        <span><?php echo _MD_FORMULIZE_AI_ACTIVITY_LABEL; ?> &nbsp;<span id="activity-count" style="background: #6c757d; color: white; padding: 1px 7px; border-radius: 10px; font-size: 0.85em;">0</span></span>
        <span id="activity-arrow" style="font-size: 0.85em; opacity: 0.7;"><?php echo _MD_FORMULIZE_AI_ACTIVITY_SHOW; ?></span>
    </div>
    <div id="activity-panel" style="display: none; background: #fafafa; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px; max-height: 220px; overflow-y: auto;">
        <div id="activity-list" style="padding: 8px 15px; font-size: 0.78em; font-family: monospace; display: flex; flex-direction: column; gap: 3px;"></div>
        <div style="padding: 6px 15px; font-size: 0.72em; color: #888; border-top: 1px solid #eee;"><?php echo _MD_FORMULIZE_AI_ACTIVITY_FOOTER; ?></div>
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
<script type="text/javascript">
window.formulizeAI = window.formulizeAI || {};
window.formulizeAI.strings = {
    activityShow:      <?php echo json_encode(_MD_FORMULIZE_AI_ACTIVITY_SHOW); ?>,
    activityHide:      <?php echo json_encode(_MD_FORMULIZE_AI_ACTIVITY_HIDE); ?>,
    fetchingTools:     <?php echo json_encode(_MD_FORMULIZE_AI_FETCHING_TOOLS); ?>,
    noToolsFound:      <?php echo json_encode(_MD_FORMULIZE_AI_NO_TOOLS_FOUND); ?>,
    mcpError:          <?php echo json_encode(_MD_FORMULIZE_AI_MCP_ERROR); ?>,
    settingsSaved:     <?php echo json_encode(_MD_FORMULIZE_AI_SETTINGS_SAVED); ?>,
    activeToolsStatus: <?php echo json_encode(_MD_FORMULIZE_AI_ACTIVE_TOOLS_STATUS); ?>,
    modelStatus:       <?php echo json_encode(_MD_FORMULIZE_AI_MODEL_STATUS); ?>,
    saveFirst:         <?php echo json_encode(_MD_FORMULIZE_AI_SAVE_FIRST); ?>,
    geminiSaveFirst:   <?php echo json_encode(_MD_FORMULIZE_AI_GEMINI_SAVE_FIRST); ?>,
    failedInit:        <?php echo json_encode(_MD_FORMULIZE_AI_FAILED_INIT); ?>,
    errorOccurred:     <?php echo json_encode(_MD_FORMULIZE_AI_ERROR_OCCURRED); ?>,
    welcomeAlert:      <?php echo json_encode(_MD_FORMULIZE_AI_WELCOME_ALERT); ?>,
    welcomeMsg:        <?php echo json_encode(_MD_FORMULIZE_AI_WELCOME_MSG); ?>,
    senderYou:         <?php echo json_encode(_MD_FORMULIZE_AI_SENDER_YOU); ?>,
    senderAI:          <?php echo json_encode(_MD_FORMULIZE_AI_SENDER_AI); ?>,
    senderSystem:      <?php echo json_encode(_MD_FORMULIZE_AI_SENDER_SYSTEM); ?>,
    senderError:       <?php echo json_encode(_MD_FORMULIZE_AI_SENDER_ERROR); ?>,
    thinking:          <?php echo json_encode(_MD_FORMULIZE_AI_THINKING); ?>,
    toolPending:       <?php echo json_encode(_MD_FORMULIZE_AI_TOOL_PENDING); ?>,
    toolOk:            <?php echo json_encode(_MD_FORMULIZE_AI_TOOL_OK); ?>,
    toolError:         <?php echo json_encode(_MD_FORMULIZE_AI_TOOL_ERROR); ?>,
    toolExpand:        <?php echo json_encode(_MD_FORMULIZE_AI_TOOL_EXPAND); ?>,
    toolCollapse:      <?php echo json_encode(_MD_FORMULIZE_AI_TOOL_COLLAPSE); ?>,
    toolParamsLabel:   <?php echo json_encode(_MD_FORMULIZE_AI_TOOL_PARAMS_LABEL); ?>,
    toolNoParams:      <?php echo json_encode(_MD_FORMULIZE_AI_TOOL_NO_PARAMS); ?>,
    toolResponseLabel: <?php echo json_encode(_MD_FORMULIZE_AI_TOOL_RESPONSE_LABEL); ?>,
    toolWaiting:       <?php echo json_encode(_MD_FORMULIZE_AI_TOOL_WAITING); ?>,
    toolNoOutput:      <?php echo json_encode(_MD_FORMULIZE_AI_TOOL_NO_OUTPUT); ?>,
    toolResponseError: <?php echo json_encode(_MD_FORMULIZE_AI_TOOL_RESPONSE_ERROR); ?>,
    toolNetError:      <?php echo json_encode(_MD_FORMULIZE_AI_TOOL_NET_ERROR); ?>,
    evtSavedNew:       <?php echo json_encode(_MD_FORMULIZE_AI_EVENT_SAVED_NEW); ?>,
    evtSaved:          <?php echo json_encode(_MD_FORMULIZE_AI_EVENT_SAVED); ?>,
    evtDeleted:        <?php echo json_encode(_MD_FORMULIZE_AI_EVENT_DELETED); ?>,
    evtGathered:       <?php echo json_encode(_MD_FORMULIZE_AI_EVENT_GATHERED); ?>,
    evtSearching:      <?php echo json_encode(_MD_FORMULIZE_AI_EVENT_SEARCHING); ?>,
    evtSort:           <?php echo json_encode(_MD_FORMULIZE_AI_EVENT_SORT); ?>,
    evtScope:          <?php echo json_encode(_MD_FORMULIZE_AI_EVENT_SCOPE); ?>,
    evtAdminSaved:     <?php echo json_encode(_MD_FORMULIZE_AI_EVENT_ADMIN_SAVED); ?>,
    evtAdminFailed:    <?php echo json_encode(_MD_FORMULIZE_AI_EVENT_ADMIN_FAILED); ?>,
    evtViewed:         <?php echo json_encode(_MD_FORMULIZE_AI_EVENT_VIEWED); ?>,
    evtAdminPage:      <?php echo json_encode(_MD_FORMULIZE_AI_EVENT_ADMIN_PAGE); ?>,
    evtSubmitted:      <?php echo json_encode(_MD_FORMULIZE_AI_EVENT_SUBMITTED); ?>,
    contextHeader:     <?php echo json_encode(_MD_FORMULIZE_AI_CONTEXT_HEADER); ?>,
    apiKeyPlaceholder: <?php echo json_encode(_MD_FORMULIZE_AI_API_KEY_PLACEHOLDER); ?>,
    apiKeyOllama:      <?php echo json_encode(_MD_FORMULIZE_AI_API_KEY_OLLAMA); ?>,
    toolsReadData:     <?php echo json_encode(_MD_FORMULIZE_AI_TOOLS_READ_DATA); ?>,
    toolsWriteData:    <?php echo json_encode(_MD_FORMULIZE_AI_TOOLS_WRITE_DATA); ?>,
    toolsManageForms:  <?php echo json_encode(_MD_FORMULIZE_AI_TOOLS_MANAGE_FORMS); ?>,
    systemPrompt:      <?php echo json_encode(_MD_FORMULIZE_AI_SYSTEM_PROMPT); ?>
};
</script>
<script type="importmap">
  {
    "imports": {
      "@google/generative-ai": "https://esm.run/@google/generative-ai"
    }
  }
</script>

<script type="module">
    import { GoogleGenerativeAI } from "@google/generative-ai";

    const S = window.formulizeAI.strings;

    // Replace {token} placeholders in a string with values from a vars object
    function t(str, vars) {
        return str.replace(/\{(\w+)\}/g, (_, k) => vars[k] !== undefined ? String(vars[k]) : '{' + k + '}');
    }

    const chatWindow = document.getElementById('chat-window');
    const userInput = document.getElementById('user-input');
    const sendBtn = document.getElementById('send-btn');
    const apiKeyInput = document.getElementById('ai-api-key');
    const modelNameInput = document.getElementById('model-name');
    const providerSelect = document.getElementById('provider-select');
    const saveSettingsBtn = document.getElementById('save-settings');
    const mcpStatus = document.getElementById('mcp-status');

    const SYSTEM_PROMPT = S.systemPrompt;

    // Tool names that should never appear in the UI or be sent to the AI
    const EASTER_EGGS = new Set([
        'locate_captain_picard',
        'open_the_pod_bay_doors_hal',
        'lets_play_global_thermonuclear_war'
    ]);

    // Tools whose output is embedded in the system prompt; removed from the tool list.
    // The PHP MCP server registers this tool using the local server name, which defaults to 'formulize'.
    const INIT_TOOLS = ['formulize'];

    // Tools that create/update form structure (Manage forms group)
    const FORM_MGMT_TOOLS = new Set([
        'create_derived_value_element', 'create_form', 'create_linked_list_element',
        'create_list_element', 'create_selector_element', 'create_subform_interface',
        'create_table_of_elements', 'create_text_box_element', 'create_user_list_element',
        'update_derived_value_element', 'update_linked_list_element', 'update_list_element',
        'update_selector_element', 'update_subform_interface', 'update_table_of_elements',
        'update_text_box_element', 'update_user_list_element'
    ]);

    // Tools that write entry data (add to Read data to get Write data)
    const ENTRY_WRITE_TOOLS = new Set(['create_entries', 'update_entries']);

    // Default history character limits per provider (conversation history only, not system prompt/tools)
    const CONTEXT_WINDOW_DEFAULTS = { claude: 100000, gemini: 200000, ollama: 16000 };

    function getContextLimit() {
        const provider = providerSelect.value;
        const saved = localStorage.getItem(`ai_context_limit_${provider}`);
        return saved ? parseInt(saved, 10) : CONTEXT_WINDOW_DEFAULTS[provider];
    }

    // Update the visual context-window cutoff marker in the chat DOM.
    // messagesForApi is the trimmed array actually sent to the API.
    // firstOriginalContent is claudeHistory[0] or ollamaHistory[0].content (the very first message ever sent).
    function updateContextCutoffMarker(messagesForApi, firstOriginalContent) {
        // Reset all message opacity
        for (const el of chatWindow.children) {
            if (el.id !== 'context-cutoff-marker') el.style.opacity = '';
        }
        const existing = document.getElementById('context-cutoff-marker');
        if (existing) existing.remove();

        // No trimming if the first in-context message is still the original first message
        const firstContent = messagesForApi[0]?.content;
        if (!firstContent || typeof firstContent !== 'string') return;
        if (firstContent === firstOriginalContent) return;

        // Find the DOM element for the first in-context user message via text search
        const searchText = firstContent.slice(0, 100);
        let cutoffEl = null;
        for (const el of chatWindow.children) {
            if (el.classList.contains('user') && (el.textContent || '').includes(searchText)) {
                cutoffEl = el;
                break;
            }
        }
        if (!cutoffEl) return;

        // Dim everything before the cutoff
        for (const el of chatWindow.children) {
            if (el === cutoffEl) break;
            el.style.opacity = '0.35';
        }

        // Insert the cutoff notice
        const marker = document.createElement('div');
        marker.id = 'context-cutoff-marker';
        marker.style.cssText = 'align-self: stretch; text-align: center; font-size: 0.75em; color: #856404; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 4px 10px; margin: 4px 0;';
        marker.textContent = '↑ Messages above this point are outside the AI\'s active context window';
        chatWindow.insertBefore(marker, cutoffEl);
    }

    // Trim message history to fit within maxChars. Always keeps at least the last message.
    // Drops from the front in whole messages, ensuring history still starts with a user turn.
    function trimHistoryToLimit(messages, maxChars) {
        let total = messages.reduce((sum, msg) => {
            const c = typeof msg.content === 'string' ? msg.content : JSON.stringify(msg.content);
            return sum + c.length;
        }, 0);
        if (total <= maxChars) return messages;
        const result = [...messages];
        while (result.length > 1 && total > maxChars) {
            const removed = result.shift();
            total -= (typeof removed.content === 'string' ? removed.content : JSON.stringify(removed.content)).length;
            // Keep history starting on a user message (required by Claude; good practice for Ollama)
            while (result.length > 1 && result[0].role !== 'user') {
                const r = result.shift();
                total -= (typeof r.content === 'string' ? r.content : JSON.stringify(r.content)).length;
            }
        }
        return result;
    }

    // Returns the names of tools in the named preset group
    function getToolGroupNames(group) {
        const all = availableTools.map(t => t.name);
        if (group === 'readData')    return all.filter(n => !FORM_MGMT_TOOLS.has(n) && !ENTRY_WRITE_TOOLS.has(n));
        if (group === 'writeData')   return all.filter(n => !FORM_MGMT_TOOLS.has(n));
        if (group === 'manageForms') return all.filter(n => FORM_MGMT_TOOLS.has(n));
        return [];
    }

    // System prompt extended with initialization context after MCP connects
    let dynamicSystemPrompt = SYSTEM_PROMPT;

    function getActivityLog() {
        try {
            const raw = localStorage.getItem('formulize_activity_log');
            const log = raw ? JSON.parse(raw) : [];
            return log.filter(function(e) {
                if (e.type === 'formulize_event') {
                    // Noise: session housekeeping
                    if (e.event === 'session-loaded-for-user') return false;
                    // Implied by the page-title in the pageview event
                    if (e.event === 'rendering-form' || e.event === 'rendering-form-screen-page') return false;
                    // Plain list view with no searches/sort/scope is implied by the "Viewed:" pageview
                    if (e.event === 'gathering-data-for-list-of-entries' && !e.searches && !e.sort && !e.scope) return false;
                }
                // Front-end form submits are covered by server-side saving-data events
                if (e.type === 'form_submit' && !e.admin) return false;
                // Admin page loads aren't useful context — only admin saves matter
                if (e.type === 'pageview' && e.admin) return false;
                return true;
            });
        } catch (e) { return []; }
    }

    function eventFingerprint(e) {
        if (e.type === 'formulize_event') {
            const searchKey = e.searches ? JSON.stringify(e.searches) : '';
            // Include new_entry so "saved new entry" and "saved entry" for the same
            // fid+entry are kept as distinct events (the re-save after new is non-new).
            return [e.type, e.event, e.fid || '', e.sid || '', e.entry || '', searchKey, e.sort || '', e.scope || '', e.new_entry ? 'new' : ''].join('|');
        }
        if (e.type === 'pageview') {
            // Admin pageviews are client-recorded; URL is the right differentiator.
            // Front-end pageviews are server-recorded with authoritative sid/fid/entry.
            // Use sid || fid so events recorded with only one of the two still dedup correctly.
            if (e.admin) return ['pageview', e.url || ''].join('|');
            return ['pageview', e.sid || e.fid || '', e.entry || ''].join('|');
        }
        if (e.type === 'admin_save') return [e.type, e.fid || '', e.sid || '', e.ele_id || '', e.aid || ''].join('|');
        if (e.type === 'form_submit') return [e.type, e.url, e.fid || ''].join('|');
        return e.type + '|' + e.ts;
    }

    // Deduplicate the filtered log, keeping the most recent occurrence of each fingerprint.
    // Returns events in chronological order (oldest first).
    function getDeduplicatedLog() {
        const log = getActivityLog();
        const seen = new Set();
        const result = [];
        for (var i = log.length - 1; i >= 0; i--) {
            var fp = eventFingerprint(log[i]);
            if (!seen.has(fp)) { seen.add(fp); result.unshift(log[i]); }
        }
        return result;
    }

    function describeEvent(e) {
        const time = new Date(e.ts).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
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
                if (terms) extra += S.evtSearching + terms;
            }
            if (e.sort) extra += S.evtSort + e.sort + (e.order ? ' ' + e.order : '');
            if (e.scope) extra += S.evtScope + e.scope;
            if (e.event === 'saving-data') {
                const label = e.new_entry ? S.evtSavedNew : S.evtSaved;
                return { time, text: label + detail + extra, kind: 'save' };
            }
            if (e.event === 'deleting-entry') {
                return { time, text: S.evtDeleted + detail, kind: 'delete' };
            }
            if (e.event === 'gathering-data-for-list-of-entries') {
                return { time, text: S.evtGathered + detail + extra, kind: 'server' };
            }
            return { time, text: e.event + detail + extra, kind: 'server' };
        }
        if (e.type === 'admin_save') {
            const parts = [];
            if (e.fid)    parts.push('form #' + e.fid + (e.form_handle ? ' (' + e.form_handle + ')' : ''));
            if (e.sid)    parts.push('screen #' + e.sid + (e.screen_name ? ' (' + e.screen_name + ')' : ''));
            if (e.ele_id) parts.push('element #' + e.ele_id + (e.ele_handle ? ' (' + e.ele_handle + ')' : ''));
            // Only show application when it's the primary entity (no form/screen/element context)
            if (e.aid && !e.fid && !e.sid && !e.ele_id) parts.push('application #' + e.aid + (e.app_name ? ' (' + e.app_name + ')' : ''));
            const idStr = parts.length ? ': ' + parts.join(', ') : '';
            const status = e.success ? '' : S.evtAdminFailed;
            return { time, text: S.evtAdminSaved + idStr + status, kind: e.success ? 'admin' : 'error' };
        }
        if (e.type === 'pageview') {
            const label = e.admin ? S.evtAdminPage + (e.title || e.url) : S.evtViewed + (e.title || e.url);
            return { time, text: label + detail, kind: e.admin ? 'admin' : 'view' };
        }
        if (e.type === 'form_submit') {
            return { time, text: S.evtSubmitted + (e.title || e.url) + detail, kind: 'save' };
        }
        return { time, text: e.type + detail, kind: 'other' };
    }

    // For AI: deduplicated, chronological
    function getActivityContext() {
        const log = getDeduplicatedLog();
        if (!log.length) return '';
        const lines = log.map(e => {
            const d = describeEvent(e);
            return `[${d.time}] ${d.text}`;
        });
        return `${S.contextHeader}\n${lines.join('\n')}\n]`;
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
        delete: '#fde8e8',
        error:  '#f8d7da',
        other:  '#f8f9fa'
    };

    function refreshActivityPanel() {
        const log = getDeduplicatedLog().slice().reverse(); // newest first
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
    }

    activityToggleBar.addEventListener('click', () => {
        const open = activityPanel.style.display !== 'none';
        activityPanel.style.display = open ? 'none' : 'block';
        activityToggleBar.style.borderRadius = open ? '0 0 8px 8px' : '0';
        activityArrow.textContent = open ? S.activityShow : S.activityHide;
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
    let geminiHistory = [];  // Gemini clean conversation history (bare text, no activity context)
    let lastGeminiActivityCount = 0; // How many deduplicated activity events Gemini has already seen
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

    function updateContextLimitDisplay() {
        const provider = providerSelect.value;
        const saved = localStorage.getItem(`ai_context_limit_${provider}`);
        const customCb = document.getElementById('context-limit-custom');
        const limitInput = document.getElementById('context-limit');
        if (saved) {
            customCb.checked = true;
            limitInput.disabled = false;
            limitInput.style.opacity = '';
            limitInput.value = saved;
        } else {
            customCb.checked = false;
            limitInput.disabled = true;
            limitInput.style.opacity = '0.55';
            limitInput.value = CONTEXT_WINDOW_DEFAULTS[provider];
        }
    }

    function updateProviderHints() {
        const p = providerSelect.value;
        const isOllama = p === 'ollama';
        apiKeyInput.placeholder = isOllama ? S.apiKeyOllama : S.apiKeyPlaceholder;
        apiKeyInput.value = isOllama ? '' : (settingsForProvider(p).key || '');
        apiKeyInput.disabled = isOllama;
        apiKeyInput.style.opacity = isOllama ? '0.45' : '';
        updateContextLimitDisplay();
        discoverModels();
    }
    providerSelect.addEventListener('change', () => {
        localStorage.setItem('ai_provider', providerSelect.value);
        updateProviderHints();
    });
    apiKeyInput.addEventListener('blur', () => {
        if (apiKeyInput.value.trim()) discoverModels();
    });

    document.getElementById('context-limit-custom').addEventListener('change', function() {
        const limitInput = document.getElementById('context-limit');
        if (this.checked && !confirm('Changing the history limit affects how much conversation context is sent to the AI each turn.\n\nSet too low and the AI loses earlier context; set too high and requests may fail on models with smaller context windows.\n\nContinue?')) {
            this.checked = false;
            return;
        }
        limitInput.disabled = !this.checked;
        limitInput.style.opacity = this.checked ? '' : '0.55';
        if (!this.checked) {
            limitInput.value = CONTEXT_WINDOW_DEFAULTS[providerSelect.value];
            localStorage.removeItem(`ai_context_limit_${providerSelect.value}`);
        } else {
            limitInput.focus();
        }
    });
    modelNameInput.addEventListener('change', updateToolCount);
    updateProviderHints(); // Apply on load — also triggers initial model discovery

    document.getElementById('settings-toggle').addEventListener('click', () => {
        const panel = document.getElementById('settings-panel');
        const open = panel.style.display === 'none';
        panel.style.display = open ? 'flex' : 'none';
        document.getElementById('settings-close-label').style.display = open ? '' : 'none';
    });

    const savedKey = settingsForProvider(savedProvider).key;
    if (savedKey || savedProvider === 'ollama') {
        initializeMCP();
    } else {
        // First visit: open settings panel and show welcome
        const panel = document.getElementById('settings-panel');
        panel.style.display = 'flex';
        document.getElementById('settings-close-label').style.display = '';
        addMessage(S.senderSystem, S.welcomeMsg, 'system');
        setTimeout(() => alert(S.welcomeAlert), 150);
    }

    saveSettingsBtn.addEventListener('click', () => {
        const key = apiKeyInput.value.trim();
        const modelName = modelNameInput.value.trim();
        const provider = providerSelect.value;
        if (modelName && (key || provider === 'ollama')) {
            saveSettingsForProvider(provider, key, modelName);
            localStorage.setItem('ai_provider', provider);
            const customCb = document.getElementById('context-limit-custom');
            const limitVal = document.getElementById('context-limit').value;
            if (customCb.checked && limitVal) {
                localStorage.setItem(`ai_context_limit_${provider}`, limitVal);
            } else {
                localStorage.removeItem(`ai_context_limit_${provider}`);
            }
            geminiChat = null;
            geminiHistory = [];
            lastGeminiActivityCount = 0;
            claudeHistory = [];
            ollamaHistory = [];
            initializeMCP();
            addMessage(S.senderSystem, t(S.settingsSaved, { provider, model: modelName }), 'system');
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
            ? t(S.activeToolsStatus, { active, total, model })
            : t(S.modelStatus, { model });
    }

    function renderToolPanel() {
        const panel = document.getElementById('tool-selection-panel');
        const list = document.getElementById('tool-selection-list');

        if (availableTools.length === 0) {
            panel.style.display = 'none';
            return;
        }

        // Restore saved selection, defaulting to no tools (user must opt in)
        const saved = localStorage.getItem('ai_selected_tools');
        if (saved) {
            const savedNames = new Set(JSON.parse(saved));
            selectedToolNames = new Set(availableTools.map(t => t.name).filter(n => savedNames.has(n)));
        } else {
            selectedToolNames = new Set();
        }

        // Build checklist
        list.innerHTML = '';
        availableTools.forEach(tool => {
            const label = document.createElement('label');
            label.title = tool.description || '';
            label.style.cssText = 'display: flex; align-items: baseline; gap: 6px; padding: 2px 0; cursor: pointer; font-size: 0.82em;';

            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.dataset.toolName = tool.name;
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

        // Group preset buttons
        ['readData', 'writeData', 'manageForms'].forEach(group => {
            const btnId = { readData: 'tool-group-read', writeData: 'tool-group-write', manageForms: 'tool-group-manage' }[group];
            const btn = document.getElementById(btnId);
            if (!btn) return;
            btn.onclick = () => {
                const groupSet = new Set(getToolGroupNames(group));
                selectedToolNames = groupSet;
                list.querySelectorAll('input[type=checkbox]').forEach(cb => {
                    cb.checked = groupSet.has(cb.dataset.toolName);
                });
                saveToolSelection();
                updateToolCount();
            };
        });

        panel.style.display = 'block';
        updateToolCount();
    }

    async function initializeMCP() {
        const provider = providerSelect.value;
        try {
            mcpStatus.innerText = S.fetchingTools;

            const response = await fetch('mcp/index.php/capabilities');
            const data = await response.json();

            const capabilities = data.result?.capabilities || data.capabilities;
            const rawTools = capabilities?.tools || [];

            // Strip easter eggs and find initialization tool
            availableTools = rawTools.filter(t => !EASTER_EGGS.has(t.name));

            // Call the first init tool found, embed its instructions in the system prompt, then remove it.
            // The tool returns JSON: { instructions: "...", authenticated_user: {...} }
            for (const initName of INIT_TOOLS) {
                const initTool = availableTools.find(t => t.name === initName);
                if (initTool) {
                    const initResult = await executeTool(initName, {});
                    const rawText = initResult.raw?.result?.content?.[0]?.text;
                    if (rawText) {
                        try {
                            const parsed = JSON.parse(rawText);
                            dynamicSystemPrompt = SYSTEM_PROMPT + '\n\n' + (parsed.instructions || rawText);
                        } catch (e) {
                            dynamicSystemPrompt = SYSTEM_PROMPT + '\n\n' + rawText;
                        }
                    }
                    availableTools = availableTools.filter(t => t.name !== initName);
                    break;
                }
            }

            renderToolPanel();

            if (availableTools.length === 0) {
                mcpStatus.innerText = S.noToolsFound;
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

                const modelConfig = { model: modelName, systemInstruction: dynamicSystemPrompt };
                if (functionDeclarations.length > 0) {
                    modelConfig.tools = [{ functionDeclarations }];
                }

                geminiChat = genAI.getGenerativeModel(modelConfig).startChat();
                lastGeminiActivityCount = 0;
            }
        } catch (error) {
            console.error('MCP init error:', error);
            mcpStatus.innerText = S.mcpError;
            addMessage(S.senderError, S.failedInit + error.message, 'error');
        }
    }

    async function sendMessage() {
        const text = userInput.value.trim();
        if (!text) return;

        const provider = providerSelect.value;
        const apiKey = settingsForProvider(provider).key;
        if (provider !== 'ollama' && !apiKey) {
            addMessage(S.senderError, S.saveFirst, 'error');
            return;
        }

        addMessage(S.senderYou, text, 'user');
        userInput.value = '';
        userInput.style.height = '60px';

        const loadingMsg = addMessage(S.senderAI, S.thinking + '.', 'ai');
        // Animate the dots while waiting for the response
        const _thinkingEl = loadingMsg.querySelector('.ai-markdown') || loadingMsg.lastElementChild;
        _thinkingEl.style.minWidth = '6rem'; // prevent bubble resizing as dots animate
        let _dotCount = 1;
        const _dotsInterval = setInterval(() => {
            _dotCount = _dotCount >= 3 ? 1 : _dotCount + 1;
            _thinkingEl.textContent = S.thinking + '.'.repeat(_dotCount);
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
            addMessage(S.senderError, S.errorOccurred + error.message, 'error');
        }
    }

    // --- Gemini path ---

    async function sendGeminiMessage(text, loadingMsg) {
        if (!geminiChat) {
            loadingMsg.remove();
            addMessage(S.senderError, S.geminiSaveFirst, 'error');
            return;
        }

        geminiHistory.push({ role: 'user', content: text });

        const allEvents = getDeduplicatedLog();
        const newEvents = allEvents.slice(lastGeminiActivityCount);
        lastGeminiActivityCount = allEvents.length;
        let activityContext = '';
        if (newEvents.length > 0) {
            const lines = newEvents.map(e => { const d = describeEvent(e); return `[${d.time}] ${d.text}`; });
            activityContext = `${S.contextHeader}\n${lines.join('\n')}\n]`;
        }

        let result = await geminiChat.sendMessage(activityContext ? text + '\n\n' + activityContext : text);
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

        const finalText = response.text();
        geminiHistory.push({ role: 'assistant', content: finalText });

        loadingMsg.remove();
        const geminiMsg = addMessage(S.senderAI, S.thinking + '...', 'ai');
        await typewriterEffect(geminiMsg.querySelector('.ai-markdown') || geminiMsg.lastElementChild, finalText);
    }

    // --- Claude path ---

    async function sendClaudeMessage(text, loadingMsg) {
        claudeHistory.push({ role: 'user', content: text });

        const context = getActivityContext();
        const messagesForApi = trimHistoryToLimit(
            context
                ? claudeHistory.map((msg, i) => i === claudeHistory.length - 1
                    ? { ...msg, content: msg.content + '\n\n' + context }
                    : msg)
                : claudeHistory,
            getContextLimit()
        );

        let response = await callClaude(messagesForApi);

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
        const claudeMsg = addMessage(S.senderAI, S.thinking + '...', 'ai');
        await typewriterEffect(claudeMsg.querySelector('.ai-markdown') || claudeMsg.lastElementChild, textContent);
        updateContextCutoffMarker(messagesForApi, claudeHistory[0]?.content);
    }

    async function callClaude(messages) {
        const apiKey = settingsForProvider('claude').key;
        const modelName = modelNameInput.value.trim() || 'claude-sonnet-4-6';

        const claudeTools = getActiveTools().map(tool => ({
            name: tool.name,
            description: tool.description,
            input_schema: tool.inputSchema || { type: 'object', properties: {} }
        }));

        const body = { model: modelName, max_tokens: 4096, system: dynamicSystemPrompt, messages };
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
        ollamaHistory.push({ role: 'user', content: text });

        const context = getActivityContext();
        const messagesForApi = trimHistoryToLimit(
            context
                ? ollamaHistory.map((msg, i) => i === ollamaHistory.length - 1
                    ? { ...msg, content: msg.content + '\n\n' + context }
                    : msg)
                : ollamaHistory,
            getContextLimit()
        );

        let response = await callOllama(messagesForApi);
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
        const ollamaMsg = addMessage(S.senderAI, S.thinking + '...', 'ai');
        await typewriterEffect(ollamaMsg.querySelector('.ai-markdown') || ollamaMsg.lastElementChild, message.content || '(no response)');
        updateContextCutoffMarker(messagesForApi, ollamaHistory[0]?.content);
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
            { role: 'system', content: dynamicSystemPrompt },
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
                || (raw.error ? S.toolCallError + raw.error.message : S.toolNoOutput);
            return { text, raw };
        } catch (error) {
            return { text: S.toolNetError + error.message, raw: null };
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
        titleSpan.innerText = S.toolPending + name;

        const toggleSpan = document.createElement('span');
        toggleSpan.style.cssText = 'font-size: 0.85em; color: #555; white-space: nowrap;';
        toggleSpan.innerText = S.toolExpand;

        header.appendChild(titleSpan);
        header.appendChild(toggleSpan);

        const body = document.createElement('div');
        body.style.display = 'none';
        body.style.cssText = 'display: none; padding: 8px 12px; font-family: monospace;';

        const paramsLabel = document.createElement('div');
        paramsLabel.style.cssText = 'font-weight: bold; margin-bottom: 4px; color: #444;';
        paramsLabel.innerText = S.toolParamsLabel;

        const paramsPre = document.createElement('pre');
        paramsPre.style.cssText = 'background: #f8f9fa; padding: 6px 8px; border-radius: 4px; white-space: pre-wrap; word-break: break-word; margin: 0 0 10px 0; font-size: 1em;';
        paramsPre.textContent = args && Object.keys(args).length > 0 ? JSON.stringify(args, null, 2) : S.toolNoParams;

        const responseLabel = document.createElement('div');
        responseLabel.style.cssText = 'font-weight: bold; margin-bottom: 4px; color: #555;';
        responseLabel.innerText = S.toolResponseLabel;

        const responsePre = document.createElement('pre');
        responsePre.style.cssText = 'background: #f8f9fa; padding: 6px 8px; border-radius: 4px; white-space: pre-wrap; word-break: break-word; margin: 0; font-size: 1em; color: #888;';
        responsePre.textContent = S.toolWaiting;

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
            toggleSpan.innerText = expanded ? S.toolExpand : S.toolCollapse;
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
        msgDiv._titleSpan.innerText = (hasError ? S.toolError : S.toolOk) + name;
        msgDiv.style.borderColor = hasError ? '#f5c6cb' : '#c8dff7';

        // Update response section
        msgDiv._responseLabel.style.color = hasError ? '#721c24' : '#155724';
        msgDiv._responseLabel.innerText = hasError ? S.toolResponseError : S.toolResponseLabel;

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
            responsePre.textContent = S.toolNoOutput;
        }

        chatWindow.scrollTop = chatWindow.scrollHeight;
    }

    async function typewriterEffect(container, text) {
        // Render the complete markdown upfront so formatting is correct from the first word
        if (typeof marked !== 'undefined') {
            container.innerHTML = marked.parse(text, { breaks: false });
        } else {
            container.textContent = text;
        }

        // Walk every text node in the rendered HTML and wrap each word in an opacity-0 span
        const wordSpans = [];
        const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT, null, false);
        const textNodes = [];
        let node;
        while ((node = walker.nextNode())) textNodes.push(node);

        for (const textNode of textNodes) {
            const nodeText = textNode.nodeValue;
            if (!nodeText.trim()) continue;
            const fragment = document.createDocumentFragment();
            for (const part of nodeText.split(/(\s+)/)) {
                if (/^\s+$/.test(part)) {
                    fragment.appendChild(document.createTextNode(part));
                } else if (part) {
                    const span = document.createElement('span');
                    span.style.opacity = '0';
                    span.textContent = part;
                    fragment.appendChild(span);
                    wordSpans.push(span);
                }
            }
            textNode.parentNode.replaceChild(fragment, textNode);
        }

        // Reveal words one at a time — fully-rendered markup, just gradually visible
        for (const span of wordSpans) {
            span.style.opacity = '1';
            chatWindow.scrollTop = chatWindow.scrollHeight;
            await new Promise(r => setTimeout(r, 65));
        }
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
    jQuery(document).ready(function() {
        jQuery(window).load(function() {
            fitChatToViewport();
        });
    });
</script>

<?php
include "footer.php";
?>
