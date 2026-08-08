<?php
/**
 * Formulize AI Assistant
 *
 * This page provides a chat interface that integrates with Claude or Gemini AI or Ollama
 * and uses Formulize MCP tools to interact with the system.
 */

include_once "../mainfile.php";
include "../header.php";

global $xoTheme;
if ($xoTheme) {
    $_aiCssFull = XOOPS_ROOT_PATH . '/modules/formulize/templates/css/formulize.css';
    $_aiCssVer  = file_exists($_aiCssFull) ? filemtime($_aiCssFull) : 0;
    $xoTheme->addStylesheet('/modules/formulize/templates/css/formulize.css?v=' . $_aiCssVer);
}

$_aiChatLang = isset($icmsConfig['language']) ? $icmsConfig['language'] : 'english';
$_aiChatLangFile = XOOPS_ROOT_PATH . '/modules/formulize/language/' . $_aiChatLang . '/ai_chat.php';
include_once (file_exists($_aiChatLangFile) ? $_aiChatLangFile : XOOPS_ROOT_PATH . '/modules/formulize/language/english/ai_chat.php');

// Ensure the user is logged in for the PoC to work with session auth
if (!$xoopsUser) {
    echo "<div class='errorMsg'>" . _MD_FORMULIZE_MUST_BE_LOGGED_IN . "</div>";
    include "../footer.php";
    exit();
}

// The embedded AI Assistant must be enabled in Formulize preferences. The MCP endpoint
// enforces this too (session callers require the AI Assistant pref), but guard the page
// itself so a disabled assistant doesn't present a chat UI whose tool calls would fail.
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/aiadminconfig.php";
if (!isAIAssistantEnabled()) {
    echo "<div class='errorMsg'>" . _MD_FORMULIZE_AI_NOT_ENABLED . "</div>";
    include "../footer.php";
    exit();
}

// API keys are deliberately NOT loaded here. This page used to decrypt every key the
// user had and print them into the HTML, where they were readable in view-source, in the
// browser's network log, and by any third-party script on the page. Every provider is
// now reached through ai_proxy.php, which loads the key server-side, so the browser only
// ever needs to know whether a key exists - see formulizeAI_adminConfigForClient().
?>

<style>
@keyframes fz-roll-down {
    from { opacity: 0; transform: translateY(-10px) scaleY(0.88); transform-origin: top; }
    to   { opacity: 1; transform: translateY(0)     scaleY(1);    transform-origin: top; }
}
.fz-roll-down { animation: fz-roll-down 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) both; }
</style>

<div id="ai-assistant-container" style="max-width: 1000px; margin: 20px auto; font-family: sans-serif; display: flex; flex-direction: column;">
    <div style="background: #007cba; color: white; padding: 15px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0; color: white;"><?php echo _MD_FORMULIZE_AI_PAGE_TITLE; ?></h2>
        <div style="display: flex; gap: 8px; align-items: center;">
            <div id="new-conversation-btn" style="font-size: 0.8em; background: rgba(0,0,0,0.2); padding: 4px 10px; border-radius: 4px; cursor: pointer; user-select: none;"><?php echo _MD_FORMULIZE_AI_NEW_CONVERSATION_BTN; ?></div>
            <div id="settings-toggle" title="<?php echo _MD_FORMULIZE_AI_TOGGLE_SETTINGS_TITLE; ?>" style="font-size: 0.8em; background: rgba(0,0,0,0.2); padding: 4px 10px; border-radius: 4px; cursor: pointer; user-select: none; display: flex; gap: 8px; align-items: center;">
                <span id="mcp-status"><?php echo _MD_FORMULIZE_AI_INITIALIZING; ?></span>
                <span style="opacity: 0.75; font-size: 1.1em;">⚙</span>
            </div>
        </div>
    </div>

    <div id="settings-panel" style="background: #f8f9fa; border: 1px solid #ddd; border-top: none; padding: 15px; display: none; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
        <!-- Shown instead of the editable fields when an administrator has configured the
             assistant. Filled in by applyAdminConfigToUI(). -->
        <div id="admin-readonly" style="display: none; flex: 0 0 100%; flex-direction: column; gap: 6px;">
            <div style="font-size: 0.85em; color: #555;"><?php echo _MD_FORMULIZE_AI_SET_BY_ADMIN; ?></div>
            <div id="admin-readonly-rows" style="display: flex; flex-wrap: wrap; gap: 8px 24px; font-size: 0.9em;"></div>
        </div>
        <div id="provider-field" style="display: flex; flex-direction: column; gap: 3px;">
            <label for="provider-select" style="font-weight: bold; font-size: 0.85em;"><?php echo _MD_FORMULIZE_AI_PROVIDER_LABEL; ?></label>
            <select id="provider-select" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="claude"><?php echo _MD_FORMULIZE_AI_PROVIDER_CLAUDE; ?></option>
                <option value="gemini"><?php echo _MD_FORMULIZE_AI_PROVIDER_GEMINI; ?></option>
                <option value="openai"><?php echo _MD_FORMULIZE_AI_PROVIDER_OPENAI; ?></option>
                <option value="ollama"><?php echo _MD_FORMULIZE_AI_PROVIDER_OLLAMA; ?></option>
            </select>
        </div>
        <div id="model-field" style="display: flex; flex-direction: column; gap: 3px; flex: 2; min-width: 180px;">
            <label for="model-name" style="font-weight: bold; font-size: 0.85em;"><?php echo _MD_FORMULIZE_AI_MODEL_LABEL; ?></label>
            <select id="model-name" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"></select>
        </div>
        <div id="apikey-field" style="display: flex; flex-direction: column; gap: 3px; flex: 1; min-width: 140px;">
            <label for="ai-api-key" style="font-weight: bold; font-size: 0.85em;"><?php echo _MD_FORMULIZE_AI_API_KEY_LABEL; ?></label>
            <input type="password" id="ai-api-key" placeholder="<?php echo _MD_FORMULIZE_AI_API_KEY_PLACEHOLDER; ?>" autocomplete="new-password" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
        </div>
        <div id="context-limit-field" style="display: flex; flex-direction: column; gap: 3px;">
            <label for="context-limit" style="font-weight: bold; font-size: 0.85em;"><?php echo _MD_FORMULIZE_AI_HISTORY_LIMIT_LABEL; ?></label>
            <input type="number" id="context-limit" min="4000" step="1000"
                   title="<?php echo htmlspecialchars(_MD_FORMULIZE_AI_HISTORY_LIMIT_TITLE, ENT_QUOTES); ?>"
                   style="width: 120px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>
        <div id="tool-selection-panel" style="display: none; flex: 0 0 100%; border-top: 1px solid #ddd; padding-top: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; flex-wrap: wrap; gap: 6px;">
                <span style="font-weight: bold; font-size: 0.85em;"><?php echo _MD_FORMULIZE_AI_ACTIVE_TOOLS_LABEL; ?> <span id="tool-selection-count"></span></span>
                <div id="tool-buttons" style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                    <button id="tool-group-read" class="formulize-ai-tool-group-btn" style="padding: 3px 8px; font-size: 0.8em; background: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer;"><?php echo _MD_FORMULIZE_AI_TOOLS_READ_DATA; ?></button>
                    <button id="tool-group-write" class="formulize-ai-tool-group-btn" style="padding: 3px 8px; font-size: 0.8em; background: #fd7e14; color: white; border: none; border-radius: 3px; cursor: pointer;"><?php echo _MD_FORMULIZE_AI_TOOLS_WRITE_DATA; ?></button>
                    <button id="tool-group-manage" class="formulize-ai-tool-group-btn" style="padding: 3px 8px; font-size: 0.8em; background: #6f42c1; color: white; border: none; border-radius: 3px; cursor: pointer;"><?php echo _MD_FORMULIZE_AI_TOOLS_MANAGE_FORMS; ?></button>
                    <span style="opacity: 0.3; font-size: 0.9em;">|</span>
                    <button id="tool-select-all" class="formulize-ai-tool-util-btn" style="padding: 3px 8px; font-size: 0.8em; background: #007cba; color: white; border: none; border-radius: 3px; cursor: pointer;"><?php echo _MD_FORMULIZE_AI_TOOLS_ALL_BTN; ?></button>
                    <button id="tool-select-none" class="formulize-ai-tool-util-btn" style="padding: 3px 8px; font-size: 0.8em; background: #6c757d; color: white; border: none; border-radius: 3px; cursor: pointer;"><?php echo _MD_FORMULIZE_AI_TOOLS_NONE_BTN; ?></button>
                </div>
            </div>
            <div id="tool-selection-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(185px, 1fr)); gap: 2px 16px;"></div>
        </div>
        <div id="settings-buttons" style="flex: 0 0 100%; border-top: 1px solid #ddd; padding-top: 10px; display: flex; gap: 8px; align-items: center;">
            <button id="save-settings" style="padding: 8px 15px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;"><?php echo _MD_FORMULIZE_AI_SAVE_SETTINGS_BTN; ?></button>
            <button id="close-settings" style="padding: 8px 15px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;"><?php echo _MD_FORMULIZE_AI_SETTINGS_CLOSE; ?></button>
        </div>
    </div>

    <div id="chat-window" style="flex: 1; min-height: 0; overflow-y: auto; background: white; border: 1px solid #ddd; border-top: none; padding: 20px; display: flex; flex-direction: column; gap: 15px;"></div>

    <div style="background: #f8f9fa; border: 1px solid #ddd; border-top: none; padding: 10px 15px 12px; border-radius: 0; display: flex; flex-direction: column; gap: 6px;">
        <div style="display: flex; gap: 10px; align-items: stretch;">
            <button id="attach-btn" type="button" title="Upload files" style="min-width: 42px; width: 42px; height: 42px; padding: 0; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1.3em; line-height: 1; flex-shrink: 0; align-self: center;">+</button>
            <textarea id="user-input" placeholder="<?php echo _MD_FORMULIZE_AI_CHAT_PLACEHOLDER; ?>" style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px; resize: none; height: 60px;"></textarea>
            <button id="send-btn" style="padding: 0 25px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;"><?php echo _MD_FORMULIZE_AI_SEND_BTN; ?></button>
            <button id="stop-btn" style="display:none; padding: 0 25px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;"><?php echo _MD_FORMULIZE_AI_STOP_BTN; ?></button>
        </div>
        <div id="attachment-strip" style="display:none; flex-wrap: wrap; gap: 6px; justify-content: flex-start;"></div>
    </div>
    <input type="file" id="file-attach-input" style="display:none" accept=".pdf,image/*" multiple>

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
#chat-window { overflow-anchor: none; }
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
<script src="https://cdn.jsdelivr.net/npm/dompurify/dist/purify.min.js"></script>
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
    lockedToolsStatus: <?php echo json_encode(_MD_FORMULIZE_AI_LOCKED_TOOLS_STATUS); ?>,
    setByAdmin:        <?php echo json_encode(_MD_FORMULIZE_AI_SET_BY_ADMIN); ?>,
    setByAdminTitle:   <?php echo json_encode(_MD_FORMULIZE_AI_SET_BY_ADMIN_TITLE); ?>,
    roProvider:        <?php echo json_encode(_MD_FORMULIZE_AI_READONLY_PROVIDER); ?>,
    roModel:           <?php echo json_encode(_MD_FORMULIZE_AI_READONLY_MODEL); ?>,
    roHistory:         <?php echo json_encode(_MD_FORMULIZE_AI_READONLY_HISTORY); ?>,
    roHistoryUnit:     <?php echo json_encode(_MD_FORMULIZE_AI_READONLY_HISTORY_UNIT); ?>,
    roTools:           <?php echo json_encode(_MD_FORMULIZE_AI_READONLY_TOOLS); ?>,
    noAdminKey:        <?php echo json_encode(_MD_FORMULIZE_AI_NO_ADMIN_KEY); ?>,
    saveFirst:         <?php echo json_encode(_MD_FORMULIZE_AI_SAVE_FIRST); ?>,
    geminiSaveFirst:   <?php echo json_encode(_MD_FORMULIZE_AI_GEMINI_SAVE_FIRST); ?>,
    failedInit:        <?php echo json_encode(_MD_FORMULIZE_AI_FAILED_INIT); ?>,
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
    copyBtnTitle:      <?php echo json_encode(_MD_FORMULIZE_AI_COPY_BTN_TITLE); ?>,
    copyLabel:         <?php echo json_encode(_MD_FORMULIZE_AI_COPY_BTN_LABEL); ?>,
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
    historyLimitTitle: <?php echo json_encode(_MD_FORMULIZE_AI_HISTORY_LIMIT_TITLE); ?>,
    historyLimitTitleOllama: <?php echo json_encode(_MD_FORMULIZE_AI_HISTORY_LIMIT_TITLE_OLLAMA); ?>,
    contextCutoffMsg:  <?php echo json_encode(_MD_FORMULIZE_AI_CONTEXT_CUTOFF_MSG); ?>,
    historyLimitConfirm: <?php echo json_encode(_MD_FORMULIZE_AI_HISTORY_LIMIT_CONFIRM); ?>,
    apiKeyPlaceholder: <?php echo json_encode(_MD_FORMULIZE_AI_API_KEY_PLACEHOLDER); ?>,
    apiKeySaved:       <?php echo json_encode(_MD_FORMULIZE_AI_API_KEY_SAVED); ?>,
    apiKeyOllama:      <?php echo json_encode(_MD_FORMULIZE_AI_API_KEY_OLLAMA); ?>,
    toolsReadData:     <?php echo json_encode(_MD_FORMULIZE_AI_TOOLS_READ_DATA); ?>,
    toolsWriteData:    <?php echo json_encode(_MD_FORMULIZE_AI_TOOLS_WRITE_DATA); ?>,
    toolsManageForms:  <?php echo json_encode(_MD_FORMULIZE_AI_TOOLS_MANAGE_FORMS); ?>,
    ollamaTimeout:     <?php echo json_encode(_MD_FORMULIZE_AI_OLLAMA_TIMEOUT); ?>,
    openaiTimeout:     <?php echo json_encode(_MD_FORMULIZE_AI_OPENAI_TIMEOUT); ?>,
    newConversationBtn: <?php echo json_encode(_MD_FORMULIZE_AI_NEW_CONVERSATION_BTN); ?>,
    newConversationMsg: <?php echo json_encode(_MD_FORMULIZE_AI_NEW_CONVERSATION_MSG); ?>,
    stopBtn:           <?php echo json_encode(_MD_FORMULIZE_AI_STOP_BTN); ?>,
    stoppedMsg:        <?php echo json_encode(_MD_FORMULIZE_AI_STOPPED_MSG); ?>,
    systemPrompt:      <?php echo json_encode(_MD_FORMULIZE_AI_SYSTEM_PROMPT); ?>
};
// Administrator configuration plus the tool category sets and per-provider defaults.
// Resolved in PHP because the MCP server enforces the tool list server-side; this is
// the same data, handed to the browser so the UI agrees with what is actually allowed.
window.formulizeAI.adminConfig = <?php echo json_encode(formulizeAI_adminConfigForClient((int)$xoopsUser->getVar('uid'))); ?>;
</script>

<script type="module">
    const S = window.formulizeAI.strings;

    // Administrator configuration, resolved server-side. Also carries the tool category
    // sets and per-provider defaults, which are defined in PHP because the MCP server has
    // to enforce an admin-specified tool list — hiding a tool here would not deny it.
    // See modules/formulize/include/aiadminconfig.php.
    const adminConfig = window.formulizeAI.adminConfig || {};
    const TOOL_SETS = adminConfig.toolSets || {};

    // Whether a key is stored for each provider. Not the key itself — no API key ever
    // reaches this page. Updated in place when one is saved, so the UI reflects it
    // without a reload.
    const keyPresent = Object.assign({}, adminConfig.keyPresent || {});
    function hasStoredKey(provider) {
        return provider === 'ollama' || !!keyPresent[provider];
    }

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
    const stopBtn = document.getElementById('stop-btn');
    const attachBtn = document.getElementById('attach-btn');
    const fileAttachInput = document.getElementById('file-attach-input');
    const attachmentStrip = document.getElementById('attachment-strip');
    let pendingAttachments = [];

    let isSending = false;
    let currentAbortController = null;
    let userStopped = false;

    const SYSTEM_PROMPT = S.systemPrompt;

    // Tool names that should never appear in the UI or be sent to the AI
    const EASTER_EGGS = new Set(TOOL_SETS.easterEggs || []);

    // Tools whose output is embedded in the system prompt; removed from the tool list.
    // The PHP MCP server registers this tool using the local server name, which defaults to 'formulize'.
    const INIT_TOOLS = TOOL_SETS.initTools || [];

    // Tools that create/update/administer form, screen, element, permission, user, group,
    // menu/application and custom-code structure (Manage forms group)
    const FORM_MGMT_TOOLS = new Set(TOOL_SETS.formMgmt || []);

    // Tools included in every preset group — the AI needs to know what forms/applications exist and their
    // field/element/menu structure regardless of whether it's reading data, writing data, or managing forms
    const FORM_INSPECT_TOOLS = new Set(TOOL_SETS.formInspect || []);

    // Tools that write entry data (add to Read data to get Write data)
    const ENTRY_WRITE_TOOLS = new Set(TOOL_SETS.entryWrite || []);

    // Default history character limits per provider (conversation history only, not system prompt/tools).
    // Set near each model's actual context window, leaving headroom for system prompt + tool definitions.
    // Claude 200K tokens → 600K chars; gpt-4o 128K tokens → 400K chars; Ollama varies by model/RAM.
    // Used as the fallback when a model's own context window isn't known (see modelContextWindows below -
    // Gemini's models endpoint reports a real one per model; the other providers' do not).
    const CONTEXT_WINDOW_DEFAULTS = adminConfig.contextWindowDefaults || {};

    // id -> context window (characters), for whichever models the last discovery reported one for.
    // Rebuilt on every discoverModels() call (see populateModelSelect), so it always reflects the
    // currently selected provider's models, never a stale provider's.
    let modelContextWindows = {};

    function defaultContextLimitFor(provider, model) {
        return modelContextWindows[model] || CONTEXT_WINDOW_DEFAULTS[provider];
    }

    function getContextLimit() {
        const provider = providerSelect.value;
        // An administrator's limit wins outright, and the personal one is not even read,
        // so it survives untouched if the lock is later removed.
        if (adminConfig.providerLocked && adminConfig.contextLimit) {
            return adminConfig.contextLimit;
        }
        const saved = localStorage.getItem(`ai_context_limit_${provider}`);
        return saved ? parseInt(saved, 10) : defaultContextLimitFor(provider, modelNameInput.value.trim());
    }

    function slideDown(el) {
        el.classList.remove('fz-roll-down');
        void el.offsetWidth; // force reflow so the animation restarts each time
        el.classList.add('fz-roll-down');
    }

    function updateInputState() {
        if (isSending) return;
        const provider = providerSelect.value;
        const model = modelNameInput.value.trim();
        // When an administrator configured the assistant, the only question is whether
        // they finished the job by saving a key. Otherwise: for Ollama (keyless), gate on
        // ai_settings_saved to confirm setup was done; for the rest, a stored key or one
        // just typed is enough.
        let credentialsReady;
        if (adminConfig.providerLocked) {
            credentialsReady = hasStoredKey(provider);
        } else if (provider === 'ollama') {
            credentialsReady = !!localStorage.getItem('ai_settings_saved');
        } else {
            credentialsReady = hasStoredKey(provider) || !!apiKeyInput.value.trim();
        }
        const ready = credentialsReady && !!model;
        userInput.disabled = !ready;
        sendBtn.disabled = !ready;
        sendBtn.style.opacity = ready ? '' : '0.5';
    }

    // Reshape the settings panel to match what an administrator has locked down. Anything
    // they chose stops being editable and becomes a plain statement of what is in force —
    // which is also how somebody finds out exactly which tools they have.
    function applyAdminConfigToUI() {
        const locked = adminConfig.providerLocked;
        const toolsLocked = adminConfig.toolsLocked;
        if (!locked && !toolsLocked) return;

        const rows = [];
        if (locked) {
            ['provider-field', 'model-field', 'apikey-field', 'context-limit-field'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });
            rows.push([S.roProvider, adminConfig.provider]);
            rows.push([S.roModel, adminConfig.model || '—']);
            if (adminConfig.contextLimit) {
                rows.push([S.roHistory, t(S.roHistoryUnit, { limit: adminConfig.contextLimit.toLocaleString() })]);
            }
        }
        if (toolsLocked) {
            const buttons = document.getElementById('tool-buttons');
            if (buttons) buttons.style.display = 'none';
        }

        const readonly = document.getElementById('admin-readonly');
        const rowsEl = document.getElementById('admin-readonly-rows');
        if (rows.length > 0) {
            rowsEl.innerHTML = '';
            rows.forEach(([label, value]) => {
                const row = document.createElement('div');
                const strong = document.createElement('strong');
                strong.innerText = label + ': ';
                row.appendChild(strong);
                row.appendChild(document.createTextNode(value));
                rowsEl.appendChild(row);
            });
            readonly.style.display = 'flex';
        }

        // With nothing left to change, a Save button would be a lie.
        if (locked && toolsLocked) {
            const save = document.getElementById('save-settings');
            if (save) save.style.display = 'none';
        }

        // An administrator can pick a provider and forget the key. Without this the page
        // is simply dead, with no indication why.
        if (locked && !hasStoredKey(adminConfig.provider)) {
            addMessage(S.senderSystem, S.noAdminKey, 'system');
        }
    }

    function refreshSettingsUI() {
        const provider = providerSelect.value;
        const returning = adminConfig.providerLocked
            || !!(keyPresent[provider] || (provider === 'ollama' && localStorage.getItem('ai_settings_saved')));

        const saveBtn = document.getElementById('save-settings');
        const closeBtn = document.getElementById('close-settings');

        // Save is always enabled — the handler validates before acting
        saveBtn.disabled = false;
        saveBtn.style.opacity = '';
        // Close is only enabled once credentials have been confirmed saved
        closeBtn.disabled = !returning;
        closeBtn.style.opacity = returning ? '' : '0.5';
    }

    // Update the visual context-window cutoff marker in the chat DOM.
    // messagesForApi is the trimmed array actually sent to the API.
    // firstOriginalTurn is the very first turn ever sent, in that provider's own shape.
    function updateContextCutoffMarker(messagesForApi, firstOriginalTurn) {
        // Reset all message opacity
        for (const el of chatWindow.children) {
            if (el.id !== 'context-cutoff-marker') el.style.opacity = '';
        }
        const existing = document.getElementById('context-cutoff-marker');
        if (existing) existing.remove();

        // The plain text of a turn, whichever provider's shape it is in
        const turnText = (turn) => {
            if (!turn) return null;
            if (typeof turn.content === 'string') return turn.content;
            if (Array.isArray(turn.parts)) {
                const texts = turn.parts.filter(p => p.text).map(p => p.text);
                return texts.length ? texts.join('\n') : null;
            }
            return null;
        };

        // No trimming if the first in-context message is still the original first message
        const firstContent = turnText(messagesForApi[0]);
        if (!firstContent) return;
        if (firstContent === turnText(firstOriginalTurn)) return;

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

        // If nothing actually precedes the cutoff, the mismatch was from appended activity context
        // rather than real trimming — skip to avoid a spurious marker.
        let hasItemsBefore = false;
        for (const el of chatWindow.children) {
            if (el === cutoffEl) break;
            if (el.id !== 'context-cutoff-marker') { hasItemsBefore = true; break; }
        }
        if (!hasItemsBefore) return;

        // Dim everything before the cutoff
        for (const el of chatWindow.children) {
            if (el === cutoffEl) break;
            el.style.opacity = '0.35';
        }

        // Insert the cutoff notice
        const marker = document.createElement('div');
        marker.id = 'context-cutoff-marker';
        marker.style.cssText = 'align-self: stretch; text-align: center; font-size: 0.75em; color: #856404; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 4px 10px; margin: 4px 0;';
        marker.textContent = S.contextCutoffMsg;
        chatWindow.insertBefore(marker, cutoffEl);
    }

    // The size of a message, for providers that carry their text in .content
    // (Claude, OpenAI, Ollama). Gemini passes its own, since its turns use .parts.
    function defaultTurnSize(msg) {
        return (typeof msg.content === 'string' ? msg.content : JSON.stringify(msg.content)).length;
    }

    // A turn that answers a tool call. Gemini sends these with role 'user', so the
    // "start on a user turn" rule alone would happily leave one stranded at the front
    // with nothing to answer — which the API rejects.
    function isToolResponseTurn(msg) {
        return Array.isArray(msg.parts) && msg.parts.some(p => p.functionResponse);
    }

    // Trim message history to fit within maxChars. Always keeps at least the last message.
    // Drops from the front in whole messages, ensuring history still starts with a user turn.
    function trimHistoryToLimit(messages, maxChars, sizeOf = defaultTurnSize) {
        let total = messages.reduce((sum, msg) => sum + sizeOf(msg), 0);
        if (total <= maxChars) return messages;
        const result = [...messages];
        while (result.length > 1 && total > maxChars) {
            total -= sizeOf(result.shift());
            // Keep history starting on a real user message: role 'user' (required by
            // Claude, good practice for Ollama) and not a dangling tool response.
            while (result.length > 1 && (result[0].role !== 'user' || isToolResponseTurn(result[0]))) {
                total -= sizeOf(result.shift());
            }
        }
        return result;
    }

    // Returns the names of tools in the named preset group.
    // Mirrors formulizeAI_toolCategoryNames() in modules/formulize/include/aiadminconfig.php,
    // which is the copy the MCP server enforces with. Keep the two in step.
    function getToolGroupNames(group) {
        const all = availableTools.map(t => t.name);
        if (group === 'readData')    return all.filter(n => FORM_INSPECT_TOOLS.has(n));
        if (group === 'writeData')   return all.filter(n => FORM_INSPECT_TOOLS.has(n) || ENTRY_WRITE_TOOLS.has(n));
        if (group === 'manageForms') return all.filter(n => FORM_MGMT_TOOLS.has(n) || n === 'test_connection');
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
    // Gemini history in its own wire shape: {role:'user'|'model', parts:[...]}.
    // It used to be held inside a stateful SDK chat object, which meant the history could
    // not be trimmed and had to be rebuilt from scratch in three places whenever anything
    // changed. Keeping it here makes Gemini behave like the other providers.
    let geminiHistory = [];
    let lastGeminiActivityCount = 0; // How many deduplicated activity events Gemini has already seen
    let claudeHistory = [];  // Claude explicit conversation history
    let openaiHistory = [];  // OpenAI explicit conversation history
    let ollamaHistory = [];  // Ollama explicit conversation history

    // Load settings from localStorage — unless an administrator has chosen for everyone,
    // in which case their choice wins and the stored preference is left untouched, so it
    // comes back if the lock is ever removed.
    const savedProvider = adminConfig.providerLocked
        ? adminConfig.provider
        : (localStorage.getItem('ai_provider') || 'claude');
    providerSelect.value = savedProvider;

    function settingsForProvider(p) {
        if (adminConfig.providerLocked) {
            return { model: adminConfig.model || '' };
        }
        return { model: localStorage.getItem(`ai_model_${p}`) || '' };
    }

    function saveSettingsForProvider(p, key, model) {
        // Nothing here applies when an administrator has configured the assistant: their
        // key is the one in use, and the panel offers no way to change any of this.
        if (adminConfig.providerLocked) return;
        if (key && p !== 'ollama') {
            keyPresent[p] = true; // reflect it immediately; the key itself stays server-side
            fetch('ai_keys.php', {  // persist to server in background — no need to await
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ provider: p, key })
            });
        }
        if (model) localStorage.setItem(`ai_model_${p}`, model);
    }

    const MODEL_DEFAULTS = adminConfig.modelDefaults || {};

    function populateModelSelect(models, preferredId) {
        modelContextWindows = {};
        models.forEach(m => {
            if (m.contextWindow) modelContextWindows[m.id] = m.contextWindow;
        });

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
        // Refresh with the now-current model's real context window (if the provider
        // reported one) or the provider default - a no-op when the person has an
        // explicit saved override, which updateContextLimitDisplay() still honours.
        updateContextLimitDisplay();
    }

    // Every provider's model list comes back through the proxy in one shape,
    // {models:[{id,name}]}, so there is nothing provider-specific left to do here.
    // Talking to providers directly is what used to require the key in the browser.
    async function discoverModels() {
        const provider = providerSelect.value;
        const savedModel = settingsForProvider(provider).model || MODEL_DEFAULTS[provider] || '';

        // Seed the select immediately with saved/default so the rest of the page can read a value
        populateModelSelect([{ id: savedModel, name: savedModel }], savedModel);
        modelNameInput.disabled = true;

        try {
            // The one case where a key is sent from the browser: the very first setup,
            // before any key has been stored for the proxy to find. The proxy accepts it
            // for listing models and nothing else.
            const discoverHeaders = {};
            const typedKey = apiKeyInput.value.trim();
            if (typedKey && !hasStoredKey(provider) && !adminConfig.providerLocked) {
                discoverHeaders['X-API-Key'] = typedKey;
            }

            const resp = await fetch(`ai_proxy.php?provider=${encodeURIComponent(provider)}&op=models`, {
                headers: discoverHeaders
            });
            if (resp.ok) {
                const data = await resp.json();
                const models = data.models || [];
                if (models.length > 0) populateModelSelect(models, savedModel);
            }
        } catch (e) {
            console.error('Model discovery error:', e);
        }

        modelNameInput.disabled = false;
        updateInputState();
    }

    function updateContextLimitDisplay() {
        const provider = providerSelect.value;
        const saved = localStorage.getItem(`ai_context_limit_${provider}`);
        const el = document.getElementById('context-limit');
        el.value = saved ? parseInt(saved, 10) : defaultContextLimitFor(provider, modelNameInput.value.trim());
        el.title = provider === 'ollama' ? S.historyLimitTitleOllama : S.historyLimitTitle;
    }

    function updateProviderHints() {
        const p = providerSelect.value;
        const isOllama = p === 'ollama';
        const hasSavedKey = !!keyPresent[p];
        apiKeyInput.placeholder = isOllama ? S.apiKeyOllama : (hasSavedKey ? S.apiKeySaved : S.apiKeyPlaceholder);
        apiKeyInput.value = ''; // actual key lives server-side only, never in the field
        apiKeyInput.disabled = isOllama;
        apiKeyInput.style.opacity = isOllama ? '0.45' : '';
        updateContextLimitDisplay();
        discoverModels();
    }
    providerSelect.addEventListener('change', () => {
        localStorage.setItem('ai_provider', providerSelect.value);
        updateProviderHints();
        refreshSettingsUI();
    });
    apiKeyInput.addEventListener('blur', () => {
        if (apiKeyInput.value.trim()) discoverModels();
        refreshSettingsUI();
    });

    let historyLimitWarningShown = false;
    document.getElementById('context-limit').addEventListener('focus', function() {
        if (!historyLimitWarningShown) {
            historyLimitWarningShown = true;
            setTimeout(() => alert(S.historyLimitConfirm), 0);
        }
    });
    modelNameInput.addEventListener('change', () => {
        updateToolCount();
        updateContextLimitDisplay();
    });
    applyAdminConfigToUI(); // before the first discovery, so hidden fields never flash
    updateProviderHints(); // Apply on load — also triggers initial model discovery

    document.getElementById('settings-toggle').addEventListener('click', () => {
        const panel = document.getElementById('settings-panel');
        if (panel.style.display === 'none') {
            panel.style.display = 'flex';
            slideDown(panel);
        } else {
            panel.style.display = 'none';
        }
    });

    // When an administrator has set the assistant up there is nothing for the person to
    // configure, so there is no first-visit setup step either — go straight to ready.
    const ollamaReady = savedProvider === 'ollama' && !!localStorage.getItem('ai_settings_saved');
    if (adminConfig.providerLocked || hasStoredKey(savedProvider) || ollamaReady) {
        initializeMCP();
    } else {
        // First visit: open settings panel and show welcome, but still pre-fetch tools in background
        const _settingsPanel = document.getElementById('settings-panel');
        _settingsPanel.style.display = 'flex';
        slideDown(_settingsPanel);
        addMessage(S.senderSystem, S.welcomeMsg, 'system');
        refreshSettingsUI();
        initializeMCP(false); // silently pre-fetch; panel stays hidden until Save is clicked
    }

    saveSettingsBtn.addEventListener('click', () => {
        // Nothing in this panel is the person's to change once an administrator has set
        // both. The button is hidden in that case; this is the belt to that braces.
        if (adminConfig.providerLocked && adminConfig.toolsLocked) return;
        const key = apiKeyInput.value.trim();
        const modelName = modelNameInput.value.trim();
        const provider = providerSelect.value;
        // Allow saving when: a new key is typed, or a key already exists server-side, or Ollama (no key needed)
        const hasKey = key || hasStoredKey(provider);
        if (modelName && hasKey) {
            saveSettingsForProvider(provider, key, modelName);  // fire-and-forget server save; in-memory update is synchronous
            localStorage.setItem('ai_provider', provider);
            localStorage.setItem('ai_settings_saved', '1');
            const limitVal = parseInt(document.getElementById('context-limit').value, 10);
            if (limitVal && limitVal !== defaultContextLimitFor(provider, modelName)) {
                localStorage.setItem(`ai_context_limit_${provider}`, limitVal);
            } else {
                localStorage.removeItem(`ai_context_limit_${provider}`);
            }
            // Tools were already fetched at page load — no need to re-fetch, and no
            // per-provider chat object to rebuild: every provider now reads the current
            // tool selection at the moment it sends. Saving settings does not touch the
            // conversation - each provider keeps its own history array already, so there
            // is nothing to reconcile, and silently discarding it here (while the visible
            // transcript stayed on screen) used to leave the model with no memory of a
            // conversation the person could still see. "New Conversation" is the
            // deliberate way to clear history - see startNewConversation().
            renderToolPanel(); // data was pre-fetched; panel appears instantly
            updateInputState();
            updateProviderHints(); // refresh placeholder to reflect newly stored key
            refreshSettingsUI();
            addMessage(S.senderSystem, t(S.settingsSaved, { provider, model: modelName }), 'system');
        }
    });

    // --- Tool selection panel ---

    function getActiveTools() {
        return availableTools.filter(t => selectedToolNames.has(t.name));
    }

    function saveToolSelection() {
        // Never overwrite the personal selection while an administrator's choice is in
        // force — it is what comes back if the lock is lifted.
        if (adminConfig.toolsLocked) return;
        localStorage.setItem('ai_selected_tools', JSON.stringify([...selectedToolNames]));
    }

    function updateToolCount() {
        const active = selectedToolNames.size;
        const total = availableTools.length;
        const model = modelNameInput.value.trim() || '—';
        const countEl = document.getElementById('tool-selection-count');

        // With the tools chosen for everyone, the list is already exactly what is allowed
        // (the server filtered it), so a "12 of 12" reads as noise.
        if (adminConfig.toolsLocked) {
            countEl.innerText = `(${total})`;
            mcpStatus.innerText = t(S.lockedToolsStatus, { total, model });
            document.getElementById('settings-toggle').title = S.setByAdminTitle;
            return;
        }

        countEl.innerText = `${active} / ${total}`;
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

        // When an administrator has chosen the tools, availableTools IS the allowed set —
        // the MCP server filtered it before we ever saw it — so everything in the list is
        // in use and the panel becomes a readout rather than a picker. The saved personal
        // selection is left in localStorage untouched, so it returns if the lock is lifted.
        if (adminConfig.toolsLocked) {
            selectedToolNames = new Set(availableTools.map(t => t.name));
            list.innerHTML = '';
            availableTools.forEach(tool => {
                const item = document.createElement('div');
                item.innerText = tool.name;
                item.title = tool.description || tool.name;
                item.style.cssText = 'padding: 2px 0; font-size: 0.82em; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;';
                list.appendChild(item);
            });
            panel.style.display = 'block';
            slideDown(panel);
            updateToolCount();
            return;
        }

        // Restore saved selection; default to read-only tools on first use
        const saved = localStorage.getItem('ai_selected_tools');
        if (saved) {
            const savedNames = new Set(JSON.parse(saved));
            selectedToolNames = new Set(availableTools.map(t => t.name).filter(n => savedNames.has(n)));
        } else {
            selectedToolNames = new Set(getToolGroupNames('readData'));
        }

        // Build checklist
        list.innerHTML = '';
        availableTools.forEach(tool => {
            const label = document.createElement('label');
            label.title = tool.description || '';
            label.style.cssText = 'display: flex; align-items: baseline; gap: 6px; padding: 2px 0; cursor: pointer; font-size: 0.82em; overflow: hidden;';

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
            name.style.cssText = 'overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0;';
            name.title = tool.description || tool.name;

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
        slideDown(panel);
        updateToolCount();
    }

    // renderPanel = false: silently pre-fetch capabilities into availableTools without showing the panel.
    // renderPanel = true (default): fetch and immediately render/show the tool panel.
    async function initializeMCP(renderPanel = true) {
        const provider = providerSelect.value;
        try {
            if (renderPanel) mcpStatus.innerText = S.fetchingTools;

            const response = await fetch('../mcp/index.php/capabilities');
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

            if (renderPanel) {
                renderToolPanel();
                if (availableTools.length === 0) mcpStatus.innerText = S.noToolsFound;
            }

            if (renderPanel) refreshSettingsUI();
        } catch (error) {
            console.error('MCP init error:', error);
            if (renderPanel) mcpStatus.innerText = S.mcpError;
            addMessage(S.senderError, S.failedInit + error.message, 'error');
        }
    }

    function startNewConversation() {
        claudeHistory = [];
        openaiHistory = [];
        ollamaHistory = [];
        geminiHistory = [];
        lastGeminiActivityCount = 0;

        chatWindow.innerHTML = '';
        addMessage(S.senderSystem, S.newConversationMsg, 'system');
    }

    document.getElementById('new-conversation-btn').addEventListener('click', startNewConversation);

    document.getElementById('close-settings').addEventListener('click', () => {
        document.getElementById('settings-panel').style.display = 'none';
    });

    stopBtn.addEventListener('click', () => {
        userStopped = true;
        if (currentAbortController) currentAbortController.abort();
    });

    attachBtn.addEventListener('click', () => fileAttachInput.click());

    fileAttachInput.addEventListener('change', () => {
        Array.from(fileAttachInput.files).forEach(file => {
            if (file.size > 10 * 1024 * 1024) {
                addMessage(S.senderSystem, '"' + file.name + '" exceeds the 10 MB limit and was not attached.', 'system');
                return;
            }
            const reader = new FileReader();
            reader.onload = (e) => {
                const dataUrl = e.target.result;
                const data = dataUrl.slice(dataUrl.indexOf(',') + 1);
                pendingAttachments.push({ name: file.name, type: file.type || 'application/octet-stream', size: file.size, data });
                renderAttachmentStrip();
            };
            reader.readAsDataURL(file);
        });
        fileAttachInput.value = '';
    });

    function renderAttachmentStrip() {
        attachmentStrip.innerHTML = '';
        if (pendingAttachments.length === 0) {
            attachmentStrip.style.display = 'none';
            return;
        }
        attachmentStrip.style.display = 'flex';
        pendingAttachments.forEach((att, i) => {
            const chip = document.createElement('span');
            chip.style.cssText = 'display:inline-flex;align-items:center;gap:4px;max-width:200px;overflow:hidden;background:#e8f4fd;border:1px solid #b8d9f0;border-radius:12px;padding:4px 6px 4px 10px;font-size:0.82em;';
            const icon = att.type === 'application/pdf' ? '📄' : att.type.startsWith('image/') ? '🖼️' : '📎';
            const label = document.createElement('span');
            label.style.cssText = 'flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
            label.textContent = icon + ' ' + att.name;
            const rm = document.createElement('button');
            rm.type = 'button';
            rm.textContent = '×';
            rm.style.cssText = 'background:none;border:none;cursor:pointer;color:#888;font-size:1em;padding:0 2px;line-height:1;flex-shrink:0;min-width:auto';
            rm.addEventListener('click', () => { pendingAttachments.splice(i, 1); renderAttachmentStrip(); });
            chip.append(label, rm);
            attachmentStrip.appendChild(chip);
        });
    }

    async function sendMessage() {
        const text = userInput.value.trim();
        if (!text) return;

        const provider = providerSelect.value;
        // The key lives server-side; all this needs to know is whether one exists.
        if (!hasStoredKey(provider)) {
            addMessage(S.senderError, adminConfig.providerLocked ? S.noAdminKey : S.saveFirst, 'error');
            return;
        }

        const attachments = [...pendingAttachments];
        pendingAttachments = [];
        renderAttachmentStrip();

        isSending = true;
        userStopped = false;
        currentAbortController = new AbortController();
        sendBtn.style.display = 'none';
        stopBtn.style.display = '';
        userInput.disabled = true;

        const userBubble = addMessage(S.senderYou, text, 'user');
        if (attachments.length > 0) {
            const attNote = document.createElement('div');
            attNote.style.cssText = 'margin-top: 4px; font-size: 0.8em; opacity: 0.8;';
            attNote.textContent = attachments.map(a => (a.type === 'application/pdf' ? '📄' : a.type.startsWith('image/') ? '🖼️' : '📎') + ' ' + a.name).join('  ');
            userBubble.appendChild(attNote);
        }
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
                await sendGeminiMessage(text, loadingMsg, attachments);
            } else if (provider === 'openai') {
                await sendOpenAIMessage(text, loadingMsg, attachments);
            } else if (provider === 'ollama') {
                await sendOllamaMessage(text, loadingMsg, attachments);
            } else {
                await sendClaudeMessage(text, loadingMsg, attachments);
            }
            refreshActivityPanel(); // refresh count after send (catches same-tab events)
        } catch (error) {
            console.error('Chat error:', error);
            loadingMsg.remove();
            if (userStopped) {
                addMessage(S.senderSystem, S.stoppedMsg, 'system');
            } else {
                addMessage(S.senderError, error.message, 'error');
            }
        } finally {
            isSending = false;
            currentAbortController = null;
            sendBtn.style.display = '';
            stopBtn.style.display = 'none';
            updateInputState();
            if (!userInput.disabled) userInput.focus();
        }
    }

    // --- Gemini path ---

    // Gemini's tool declarations. Its schema is close to, but not the same as, the one
    // the other providers take, so it is built from the MCP schema rather than passed on.
    function buildGeminiTools() {
        const functionDeclarations = getActiveTools().map(tool => ({
            name: tool.name,
            description: tool.description,
            parameters: {
                type: 'object',
                properties: tool.inputSchema?.properties || {},
                required: tool.inputSchema?.required || []
            }
        }));
        return functionDeclarations.length > 0 ? [{ functionDeclarations }] : null;
    }

    // The text of a Gemini turn, for measuring it against the history limit.
    function geminiTurnSize(turn) {
        return JSON.stringify(turn.parts || '').length;
    }

    async function callGemini(contents) {
        const body = {
            contents,
            systemInstruction: { parts: [{ text: dynamicSystemPrompt }] }
        };
        const tools = buildGeminiTools();
        if (tools) body.tools = tools;
        // The model rides in the query string; the proxy puts it in the URL path, which
        // is where Gemini wants it.
        body.model = modelNameInput.value.trim() || 'gemini-2.0-flash';

        const response = await fetch('ai_proxy.php?provider=gemini&op=chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
            signal: currentAbortController?.signal
        });
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error?.message || `HTTP ${response.status}`);
        }
        return data;
    }

    async function sendGeminiMessage(text, loadingMsg, attachments = []) {
        const allEvents = getDeduplicatedLog();
        const newEvents = allEvents.slice(lastGeminiActivityCount);
        const prevActivityCount = lastGeminiActivityCount;
        lastGeminiActivityCount = allEvents.length;
        let activityContext = '';
        if (newEvents.length > 0) {
            const lines = newEvents.map(e => { const d = describeEvent(e); return `[${d.time}] ${d.text}`; });
            activityContext = `${S.contextHeader}\n${lines.join('\n')}\n]`;
        }

        const geminiText = activityContext ? text + '\n\n' + activityContext : text;
        const userParts = attachments
            .filter(a => a.type.startsWith('image/') || a.type === 'application/pdf')
            .map(a => ({ inlineData: { data: a.data, mimeType: a.type } }));
        userParts.push({ text: geminiText });
        geminiHistory.push({ role: 'user', parts: userParts });

        const historyLength = geminiHistory.length;
        try {
            let contents = trimHistoryToLimit(geminiHistory, getContextLimit(), geminiTurnSize);
            let data = await callGemini(contents);
            if (userStopped) throw new DOMException('User stopped', 'AbortError');

            let parts = data.candidates?.[0]?.content?.parts || [];
            let calls = parts.filter(p => p.functionCall);

            while (calls.length > 0) {
                // Keep the model's turn verbatim: a functionResponse is only valid
                // immediately after the functionCall it answers.
                geminiHistory.push({ role: 'model', parts });

                const responseParts = [];
                for (const part of calls) {
                    const call = part.functionCall;
                    const toolBlock = addToolRequest(call.name, call.args || {});
                    const toolResult = await executeTool(call.name, call.args || {});
                    addToolResponse(toolBlock, toolResult.raw);
                    responseParts.push({
                        functionResponse: { name: call.name, response: { content: toolResult.text } }
                    });
                }
                geminiHistory.push({ role: 'user', parts: responseParts });

                contents = trimHistoryToLimit(geminiHistory, getContextLimit(), geminiTurnSize);
                data = await callGemini(contents);
                if (userStopped) throw new DOMException('User stopped', 'AbortError');
                parts = data.candidates?.[0]?.content?.parts || [];
                calls = parts.filter(p => p.functionCall);
            }

            const finalText = parts.filter(p => p.text).map(p => p.text).join('\n');
            geminiHistory.push({ role: 'model', parts });

            loadingMsg.remove();
            const scrollBeforeGemini = chatWindow.scrollTop;
            const heightBeforeGemini = chatWindow.scrollHeight;
            const geminiMsg = addMessage(S.senderAI, S.thinking + '...', 'ai');
            chatWindow.scrollTop = scrollBeforeGemini + (chatWindow.scrollHeight - heightBeforeGemini);
            await typewriterEffect(geminiMsg.querySelector('.ai-markdown') || geminiMsg.lastElementChild, finalText);
            updateContextCutoffMarker(contents, geminiHistory[0]);
        } catch (error) {
            // Roll back every turn this message added, not just the first one — the tool
            // loop may have pushed several before failing.
            geminiHistory.length = historyLength - 1;
            lastGeminiActivityCount = prevActivityCount;
            throw error;
        }
    }

    // --- Claude path ---

    async function sendClaudeMessage(text, loadingMsg, attachments = []) {
        claudeHistory.push({ role: 'user', content: text });

        const context = getActivityContext();
        let messagesForApi = trimHistoryToLimit(
            context
                ? claudeHistory.map((msg, i) => i === claudeHistory.length - 1
                    ? { ...msg, content: msg.content + '\n\n' + context }
                    : msg)
                : claudeHistory,
            getContextLimit()
        );

        if (attachments.length > 0) {
            const lastIdx = messagesForApi.length - 1;
            if (messagesForApi[lastIdx]?.role === 'user') {
                const baseText = typeof messagesForApi[lastIdx].content === 'string'
                    ? messagesForApi[lastIdx].content : text;
                const parts = attachments.reduce((acc, att) => {
                    if (att.type === 'application/pdf') {
                        acc.push({ type: 'document', source: { type: 'base64', media_type: 'application/pdf', data: att.data } });
                    } else if (att.type.startsWith('image/')) {
                        acc.push({ type: 'image', source: { type: 'base64', media_type: att.type, data: att.data } });
                    }
                    return acc;
                }, []);
                parts.push({ type: 'text', text: baseText });
                messagesForApi = messagesForApi.map((msg, i) =>
                    i === lastIdx ? { ...msg, content: parts } : msg
                );
            }
        }

        try {
            let response = await callClaude(messagesForApi);
            const newMessages = [];

            while (response.stop_reason === 'tool_use') {
                newMessages.push({ role: 'assistant', content: response.content });

                const toolResults = [];
                for (const block of response.content) {
                    if (block.type === 'tool_use') {
                        const toolBlock = addToolRequest(block.name, block.input);
                        const result = await executeTool(block.name, block.input);
                        addToolResponse(toolBlock, result.raw);
                        toolResults.push({ type: 'tool_result', tool_use_id: block.id, content: result.text });
                    }
                }

                newMessages.push({ role: 'user', content: toolResults });
                response = await callClaude([...claudeHistory, ...newMessages]);
            }

            const textContent = response.content
                .filter(b => b.type === 'text')
                .map(b => b.text)
                .join('\n');

            newMessages.push({ role: 'assistant', content: response.content });
            for (const m of newMessages) claudeHistory.push(m);

            loadingMsg.remove();
            const scrollBeforeClaude = chatWindow.scrollTop;
            const heightBeforeClaude = chatWindow.scrollHeight;
            const claudeMsg = addMessage(S.senderAI, S.thinking + '...', 'ai');
            chatWindow.scrollTop = scrollBeforeClaude + (chatWindow.scrollHeight - heightBeforeClaude);
            await typewriterEffect(claudeMsg.querySelector('.ai-markdown') || claudeMsg.lastElementChild, textContent);
            updateContextCutoffMarker(messagesForApi, claudeHistory[0]);
        } catch (error) {
            claudeHistory.pop();
            throw error;
        }
    }

    async function callClaude(messages) {
        const modelName = modelNameInput.value.trim() || 'claude-sonnet-4-6';

        const claudeTools = getActiveTools().map(tool => ({
            name: tool.name,
            description: tool.description,
            input_schema: tool.inputSchema || { type: 'object', properties: {} }
        }));

        const bodyBase = { model: modelName, max_tokens: 4096, system: dynamicSystemPrompt };
        if (claudeTools.length > 0) bodyBase.tools = claudeTools;

        // If any message block carries inline base64 file data, send the files as binary
        // multipart parts instead of embedding them in the JSON body. Binary multipart is
        // ~33% smaller than base64-in-JSON, keeping the browser→server request under
        // typical server limits (e.g. Apache LimitRequestBody 8 MB). The proxy re-encodes
        // each uploaded file to base64 before forwarding to Anthropic.
        const hasInlineFiles = messages.some(m =>
            Array.isArray(m.content) && m.content.some(b =>
                (b.type === 'document' || b.type === 'image') && b.source?.type === 'base64'
            )
        );

        let fetchInit;

        if (hasInlineFiles) {
            const fd = new FormData();
            let fileIdx = 0;

            const processedMessages = await Promise.all(messages.map(async msg => {
                if (!Array.isArray(msg.content)) return msg;
                const processedContent = await Promise.all(msg.content.map(async block => {
                    if ((block.type === 'document' || block.type === 'image') && block.source?.type === 'base64') {
                        const ref = 'file_' + fileIdx++;
                        // Decode base64 → Blob via data URL fetch (efficient, no manual char loop)
                        const blob = await fetch('data:' + block.source.media_type + ';base64,' + block.source.data).then(r => r.blob());
                        fd.append(ref, blob, ref);
                        // file_ref (not ref) - formulizeAI_expandFileRefs() in ai_providers.php
                        // matches placeholders by that exact property name.
                        return { type: block.type, source: { type: 'file_ref', file_ref: ref, media_type: block.source.media_type } };
                    }
                    return block;
                }));
                return { ...msg, content: processedContent };
            }));

            fd.append('payload', JSON.stringify({ ...bodyBase, messages: processedMessages }));
            // No explicit Content-Type header — browser sets multipart/form-data with the correct boundary
            fetchInit = { method: 'POST', body: fd, signal: currentAbortController?.signal };
        } else {
            fetchInit = {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ...bodyBase, messages }),
                signal: currentAbortController?.signal
            };
        }

        // No key header — the proxy loads the key server-side from the DB.
        const response = await fetch('ai_proxy.php?provider=claude&op=chat', fetchInit);

        let data;
        try {
            data = await response.json();
        } catch (_) {
            throw new Error(`HTTP ${response.status} — server returned a non-JSON response. If you attached a large file, try adjusting upload_max_filesize / post_max_size in PHP config, or LimitRequestBody in Apache config.`);
        }
        if (!response.ok) {
            throw new Error(data.error?.message || `HTTP ${response.status}`);
        }
        return data;
    }

    // --- OpenAI-compatible providers (Ollama, OpenAI) ---

    // Shared HTTP call for any OpenAI-compatible /v1/chat/completions endpoint.
    // Both go through the proxy, so no key is involved here: OpenAI's is loaded
    // server-side, and Ollama's address is the server's, not the browser's.
    async function callOpenAICompat(messages, { provider, defaultModel, timeoutMs, timeoutMsg }) {
        const modelName = modelNameInput.value.trim() || defaultModel;

        const tools = getActiveTools().map(tool => ({
            type: 'function',
            function: {
                name: tool.name,
                description: tool.description,
                parameters: tool.inputSchema || { type: 'object', properties: {} }
            }
        }));

        const headers = { 'Content-Type': 'application/json' };
        const url = `ai_proxy.php?provider=${encodeURIComponent(provider)}&op=chat`;

        const messagesWithSystem = [{ role: 'system', content: dynamicSystemPrompt }, ...messages];
        const body = { model: modelName, messages: messagesWithSystem, stream: false };
        if (tools.length > 0) body.tools = tools;

        const timeoutController = new AbortController();
        const timer = setTimeout(() => timeoutController.abort(), timeoutMs);

        // Combine timeout with the global stop signal if available
        const signals = [timeoutController.signal];
        if (currentAbortController?.signal) signals.push(currentAbortController.signal);
        const fetchSignal = (signals.length > 1 && typeof AbortSignal.any === 'function')
            ? AbortSignal.any(signals)
            : timeoutController.signal;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers,
                body: JSON.stringify(body),
                signal: fetchSignal
            });
            clearTimeout(timer);
            if (!response.ok) {
                const err = await response.text();
                throw new Error(`HTTP ${response.status}: ${err}`);
            }
            return response.json();
        } catch (error) {
            clearTimeout(timer);
            if (error.name === 'AbortError') {
                if (userStopped) throw error; // propagate so sendMessage shows "stopped"
                throw new Error(timeoutMsg);
            }
            throw error;
        }
    }

    // Shared send/tool-loop logic for any OpenAI-compatible provider.
    // history is the provider's history array (mutated in place on success, rolled back on error).
    // callFn is the bound call function for that provider.
    async function sendOpenAICompatMessage(text, loadingMsg, history, callFn, attachments = []) {
        history.push({ role: 'user', content: text });

        const context = getActivityContext();
        let messagesForApi = trimHistoryToLimit(
            context
                ? history.map((msg, i) => i === history.length - 1
                    ? { ...msg, content: msg.content + '\n\n' + context }
                    : msg)
                : history,
            getContextLimit()
        );

        if (attachments.length > 0) {
            const lastIdx = messagesForApi.length - 1;
            if (messagesForApi[lastIdx]?.role === 'user') {
                const baseText = typeof messagesForApi[lastIdx].content === 'string'
                    ? messagesForApi[lastIdx].content : text;
                const contentParts = attachments.reduce((acc, a) => {
                    if (a.fileId) {
                        // PDF uploaded via Files API — reference by file_id
                        acc.push({ type: 'file', file: { file_id: a.fileId } });
                    } else if (a.type.startsWith('image/')) {
                        acc.push({ type: 'image_url', image_url: { url: 'data:' + a.type + ';base64,' + a.data } });
                    }
                    return acc;
                }, []);
                if (contentParts.length > 0) {
                    messagesForApi = messagesForApi.map((msg, i) =>
                        i === lastIdx
                            ? { ...msg, content: [...contentParts, { type: 'text', text: baseText }] }
                            : msg
                    );
                }
            }
        }

        try {
            let response = await callFn(messagesForApi);
            let message = response.choices[0].message;
            const newMessages = [];

            while (response.choices[0].finish_reason === 'tool_calls' && message.tool_calls) {
                newMessages.push({ role: 'assistant', content: message.content || '', tool_calls: message.tool_calls });

                for (const toolCall of message.tool_calls) {
                    // OpenAI-compatible APIs return tool arguments as a JSON string
                    const args = JSON.parse(toolCall.function.arguments);
                    const toolBlock = addToolRequest(toolCall.function.name, args);
                    const result = await executeTool(toolCall.function.name, args);
                    addToolResponse(toolBlock, result.raw);
                    newMessages.push({ role: 'tool', tool_call_id: toolCall.id, content: result.text });
                }

                response = await callFn([...history, ...newMessages]);
                message = response.choices[0].message;
            }

            newMessages.push({ role: 'assistant', content: message.content || '' });
            for (const m of newMessages) history.push(m);

            loadingMsg.remove();
            const scrollBeforeOAI = chatWindow.scrollTop;
            const heightBeforeOAI = chatWindow.scrollHeight;
            const aiMsg = addMessage(S.senderAI, S.thinking + '...', 'ai');
            chatWindow.scrollTop = scrollBeforeOAI + (chatWindow.scrollHeight - heightBeforeOAI);
            await typewriterEffect(aiMsg.querySelector('.ai-markdown') || aiMsg.lastElementChild, message.content || '(no response)');
            updateContextCutoffMarker(messagesForApi, history[0]);
        } catch (error) {
            history.pop();
            throw error;
        }
    }

    // Ollama

    function callOllama(messages) {
        return callOpenAICompat(messages, {
            provider: 'ollama',
            defaultModel: 'llama3.2',
            timeoutMs: 300000,
            timeoutMsg: S.ollamaTimeout
        });
    }

    function sendOllamaMessage(text, loadingMsg, attachments = []) {
        const pdfs = attachments.filter(a => a.type === 'application/pdf');
        if (pdfs.length > 0) {
            pdfs.forEach(a => addMessage(S.senderSystem, '"' + a.name + '" was not sent — Ollama has no file upload API. Use Claude for PDF support.', 'system'));
            attachments = attachments.filter(a => a.type !== 'application/pdf');
        }
        return sendOpenAICompatMessage(text, loadingMsg, ollamaHistory, callOllama, attachments);
    }

    // OpenAI

    function callOpenAI(messages) {
        return callOpenAICompat(messages, {
            provider: 'openai',
            defaultModel: 'gpt-4o',
            timeoutMs: 60000,
            timeoutMsg: S.openaiTimeout
        });
    }

    async function uploadToOpenAI(att) {
        try {
            const res = await fetch('ai_upload.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ file_data: att.data, file_name: att.name, file_type: att.type })
            });
            const data = await res.json();
            if (!res.ok || data.error) {
                addMessage(S.senderSystem, 'Upload failed for "' + att.name + '": ' + (data.error || 'Unknown error'), 'system');
                return null;
            }
            return data.file_id;
        } catch (err) {
            addMessage(S.senderSystem, 'Upload failed for "' + att.name + '": ' + err.message, 'system');
            return null;
        }
    }

    async function sendOpenAIMessage(text, loadingMsg, attachments = []) {
        // Upload PDFs to the OpenAI Files API before sending the chat message
        if (attachments.some(a => a.type === 'application/pdf')) {
            const processed = await Promise.all(attachments.map(async att => {
                if (att.type === 'application/pdf') {
                    const fileId = await uploadToOpenAI(att);
                    return fileId ? { ...att, fileId } : null; // null = upload failed, skip
                }
                return att;
            }));
            attachments = processed.filter(Boolean);
        }
        return sendOpenAICompatMessage(text, loadingMsg, openaiHistory, callOpenAI, attachments);
    }

    // --- Shared MCP tool executor (same for all providers) ---

    async function executeTool(name, args) {
        try {
            const response = await fetch('../mcp/index.php/mcp', {
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

    // A small icon+label button that copies whatever getText() returns at click time (not
    // at creation time), so it stays correct after addToolResponse() later fills in the pre.
    // Sizing/spacing lives in formulize.css (.formulize-ai-copy-btn) rather than inline
    // styles, since themes (e.g. Anari) set a `min-width` on bare <button> elements that
    // inline styles here don't touch.
    function makeCopyButton(getText) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'formulize-ai-copy-btn';
        btn.title = S.copyBtnTitle;

        const icon = document.createElement('span');
        icon.innerText = '📋';
        btn.appendChild(icon);
        btn.appendChild(document.createTextNode(' ' + S.copyLabel));

        btn.addEventListener('click', (e) => {
            e.stopPropagation(); // don't toggle the collapsible header
            navigator.clipboard.writeText(getText()).then(() => {
                icon.innerText = '✓';
                setTimeout(() => { icon.innerText = '📋'; }, 1200);
            }).catch(() => {});
        });
        return btn;
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

        const paramsPre = document.createElement('pre');
        paramsPre.style.cssText = 'background: #f8f9fa; padding: 6px 8px; border-radius: 4px; white-space: pre-wrap; word-break: break-word; margin: 0 0 10px 0; font-size: 1em;';
        paramsPre.textContent = args && Object.keys(args).length > 0 ? JSON.stringify(args, null, 2) : S.toolNoParams;

        const paramsLabel = document.createElement('div');
        paramsLabel.style.cssText = 'font-weight: bold; margin-bottom: 4px; color: #444; display: flex; justify-content: space-between; align-items: center;';
        const paramsLabelText = document.createElement('span');
        paramsLabelText.innerText = S.toolParamsLabel;
        paramsLabel.appendChild(paramsLabelText);
        paramsLabel.appendChild(makeCopyButton(() => paramsPre.textContent));

        const responsePre = document.createElement('pre');
        responsePre.style.cssText = 'background: #f8f9fa; padding: 6px 8px; border-radius: 4px; white-space: pre-wrap; word-break: break-word; margin: 0; font-size: 1em; color: #888;';
        responsePre.textContent = S.toolWaiting;

        const responseLabel = document.createElement('div');
        responseLabel.style.cssText = 'font-weight: bold; margin-bottom: 4px; color: #555; display: flex; justify-content: space-between; align-items: center;';
        const responseLabelText = document.createElement('span');
        responseLabelText.innerText = S.toolResponseLabel;
        responseLabel.appendChild(responseLabelText);
        responseLabel.appendChild(makeCopyButton(() => responsePre.textContent));

        body.appendChild(paramsLabel);
        body.appendChild(paramsPre);
        body.appendChild(responseLabel);
        body.appendChild(responsePre);

        msgDiv._header = header;
        msgDiv._titleSpan = titleSpan;
        msgDiv._responseLabel = responseLabel;
        msgDiv._responseLabelText = responseLabelText;
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
        msgDiv._responseLabelText.innerText = hasError ? S.toolResponseError : S.toolResponseLabel;

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
        // Render the complete markdown upfront so formatting is correct from the first word.
        // DOMPurify strips any script/event-handler injection that could arrive via prompt injection.
        if (typeof marked !== 'undefined') {
            const rawHtml = marked.parse(text, { breaks: false });
            container.innerHTML = typeof DOMPurify !== 'undefined' ? DOMPurify.sanitize(rawHtml) : rawHtml;
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
            const rawHtml = marked.parse(text, { breaks: false });
            contentDiv.innerHTML = typeof DOMPurify !== 'undefined' ? DOMPurify.sanitize(rawHtml) : rawHtml;
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
include "../footer.php";
?>
