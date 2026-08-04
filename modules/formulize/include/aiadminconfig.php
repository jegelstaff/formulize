<?php
/**
 * Shared configuration and key-storage helpers for the AI Assistant.
 *
 * Three separate places need the same facts about the assistant, so they live here
 * rather than being duplicated (or, worse, existing only in the browser):
 *
 *   - ai/*.php            the assistant page and its server-side proxy
 *   - mcp/mcp.php         which must enforce an admin-specified tool list server-side
 *   - include/configsettings.php  which renders the admin settings for all of it
 *
 * The tool category sets in particular used to exist only as JavaScript constants in
 * ai/index.php. Hiding a tool in the browser is not the same as denying it, so the
 * authoritative copy has to be here in PHP where the MCP server can reach it.
 *
 * @package Formulize
 * @subpackage AI
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

/**
 * The uid that owns site-wide (administrator-specified) API keys in formulize_ai_keys.
 *
 * Not 0: that is the anonymous user, and a row there would read as "the anonymous
 * user's key". uid is a signed INT and real accounts auto-increment from 1, so -1
 * can never collide with anybody under the table's (uid, provider) primary key.
 */
define('FORMULIZE_AI_SYSTEM_UID', -1);

/** Providers the assistant can talk to. Ollama is keyless. */
function formulizeAI_providers() {
    return array('claude', 'gemini', 'openai', 'ollama');
}

/** Default model per provider, used when nothing has been chosen. */
function formulizeAI_modelDefaults() {
    return array(
        'claude' => 'claude-sonnet-4-6',
        'gemini' => 'gemini-2.0-flash',
        'openai' => 'gpt-4o',
        'ollama' => 'llama3.2',
    );
}

/**
 * Default history character limits per provider (conversation history only, not the
 * system prompt or tool definitions). Set near each model's actual context window,
 * leaving headroom. Claude 200K tokens -> 600K chars; gpt-4o 128K tokens -> 400K chars;
 * Ollama varies by model and available RAM.
 */
function formulizeAI_contextWindowDefaults() {
    return array(
        'claude' => 600000,
        'gemini' => 2000000,
        'openai' => 400000,
        'ollama' => 128000,
    );
}

// --------------------------------------------------------------------------
// API key storage
//
// Keys are encrypted with AES-256-CBC using XOOPS_DB_SALT as the secret, with a
// random IV prepended to the ciphertext before base64 encoding.
//
// Two different homes, for two different kinds of key:
//   - A person's own key (ai/index.php's own settings panel) lives in
//     formulize_ai_keys, one row per (uid, provider) - there is no per-user slot in
//     the config table, so a real table is the only place this can live.
//   - The site-wide administrator key (Settings -> AI) lives in the ordinary
//     formulizeAIApiKey config item, alongside every other Formulize preference, as a
//     provider => encrypted-ciphertext JSON map. It is written by the encryption hack
//     in icms_config_Item_Handler::insert() (see formulizeAI_prepareApiKeyForConfigStorage
//     below) rather than through these functions directly - the plaintext never reaches
//     PHP anywhere outside that one save path.
//
// Writes use queryF() rather than query(). icms_core_Security::service() defines
// XOOPS_DB_PROXY on any non-POST request, and query() then refuses every statement
// that is not a SELECT - silently returning false. These helpers have to work
// wherever they are called from, not only from a POST handler.
// --------------------------------------------------------------------------

/**
 * The AES secret derived from the site's database salt.
 *
 * @return string|false Binary key, or false when the site has no salt configured
 */
function formulizeAI_encryptionSecret() {
    if (!defined('XOOPS_DB_SALT') || !XOOPS_DB_SALT) {
        return false;
    }
    return hash('sha256', XOOPS_DB_SALT, true);
}

/**
 * Encrypt an API key for storage.
 *
 * @param string $plain The API key
 * @return string|false Base64 of IV + ciphertext, or false if encryption is unavailable
 */
function formulizeAI_encryptKey($plain) {
    $secret = formulizeAI_encryptionSecret();
    if ($secret === false) {
        return false;
    }
    $iv = random_bytes(16);
    $cipher = openssl_encrypt($plain, 'AES-256-CBC', $secret, 0, $iv);
    if ($cipher === false) {
        return false;
    }
    return base64_encode($iv . $cipher);
}

/**
 * Decrypt a stored API key.
 *
 * @param string $encrypted Base64 of IV + ciphertext, as written by formulizeAI_encryptKey()
 * @return string|false The API key, or false if it could not be decrypted
 */
function formulizeAI_decryptKey($encrypted) {
    $secret = formulizeAI_encryptionSecret();
    if ($secret === false) {
        return false;
    }
    $raw = base64_decode($encrypted);
    // 16 byte IV plus at least one byte of ciphertext
    if (strlen($raw) < 17) {
        return false;
    }
    return openssl_decrypt(substr($raw, 16), 'AES-256-CBC', $secret, 0, substr($raw, 0, 16));
}

/**
 * Store (or replace) a person's own API key.
 *
 * Personal keys only - the site-wide key is written exclusively through the config
 * save hack (see formulizeAI_prepareApiKeyForConfigStorage), never through this.
 *
 * @param int $uid The owning user
 * @param string $provider One of formulizeAI_providers()
 * @param string $plain The API key
 * @return bool Success
 */
function formulizeAI_storeKey($uid, $provider, $plain) {
    global $xoopsDB;
    if (!in_array($provider, formulizeAI_providers())) {
        return false;
    }
    $encrypted = formulizeAI_encryptKey($plain);
    if ($encrypted === false) {
        return false;
    }
    $uid = intval($uid);
    $table = $xoopsDB->prefix('formulize_ai_keys');
    $encrypted = $xoopsDB->quoteString($encrypted);
    $provider = $xoopsDB->quoteString($provider);
    $existing = $xoopsDB->query("SELECT uid FROM $table WHERE uid = $uid AND provider = $provider");
    if ($existing && $xoopsDB->fetchArray($existing)) {
        return (bool) $xoopsDB->queryF("UPDATE $table SET encrypted_key = $encrypted WHERE uid = $uid AND provider = $provider");
    }
    return (bool) $xoopsDB->queryF("INSERT INTO $table (uid, provider, encrypted_key) VALUES ($uid, $provider, $encrypted)");
}

/**
 * Decode the site-wide key config item's stored value into a provider => ciphertext map.
 * Anything that isn't valid JSON for an array (including '', and the old 'set'/''
 * marker values a site may still have from before keys moved into this config item)
 * decodes to an empty map, so a bad/legacy value never breaks reading - it just reads
 * as "no keys yet".
 *
 * @param mixed $raw The config item's raw conf_value
 * @return array provider => base64 ciphertext (as written by formulizeAI_encryptKey)
 */
function formulizeAI_decodeSiteKeyMap($raw) {
    if (!is_string($raw) || $raw === '') {
        return array();
    }
    $map = json_decode($raw, true);
    return is_array($map) ? $map : array();
}

/**
 * Build the new conf_value for the site-wide key config item (formulizeAIApiKey), from
 * whatever was posted alongside it, merged with what's already stored.
 *
 * Called from the encryption hack in icms_config_Item_Handler::insert() - see that file
 * for why it has to happen there. Never receives or returns the plaintext except in
 * memory, for the duration of this call.
 *
 * @param mixed  $existingRawConfValue The config item's current (pre-save) conf_value
 * @param string $typedKey             Raw value of formulizeAIApiKey_new ('' = keep as-is)
 * @param bool   $clearing             Whether formulizeAIApiKey_clear was checked
 * @param string $provider             The provider currently selected (formulizeAIProvider)
 * @return string The new conf_value (JSON-encoded provider => ciphertext map)
 */
function formulizeAI_prepareApiKeyForConfigStorage($existingRawConfValue, $typedKey, $clearing, $provider) {
    $map = formulizeAI_decodeSiteKeyMap($existingRawConfValue);

    if (!in_array($provider, formulizeAI_providers()) || $provider === 'ollama') {
        return json_encode($map); // no key applies to this provider; leave the map untouched
    }
    if ($clearing) {
        unset($map[$provider]);
        return json_encode($map);
    }
    $typedKey = trim((string) $typedKey);
    if ($typedKey === '') {
        return json_encode($map); // nothing typed - keep whatever is already stored
    }
    $encrypted = formulizeAI_encryptKey($typedKey);
    if ($encrypted !== false) {
        $map[$provider] = $encrypted;
    }
    return json_encode($map);
}

/**
 * Load and decrypt an API key.
 *
 * @param int $uid The owning user, or FORMULIZE_AI_SYSTEM_UID for the site-wide key
 * @param string $provider One of formulizeAI_providers()
 * @return string The API key, or '' when there is none
 */
function formulizeAI_loadKey($uid, $provider) {
    if (!in_array($provider, formulizeAI_providers())) {
        return '';
    }
    if ((int) $uid === FORMULIZE_AI_SYSTEM_UID) {
        $map = formulizeAI_decodeSiteKeyMap(formulizeAI_preference('formulizeAIApiKey', ''));
        if (empty($map[$provider])) {
            return '';
        }
        $decrypted = formulizeAI_decryptKey($map[$provider]);
        return $decrypted === false ? '' : $decrypted;
    }
    global $xoopsDB;
    $uid = intval($uid);
    $table = $xoopsDB->prefix('formulize_ai_keys');
    $provider = $xoopsDB->quoteString($provider);
    $result = @$xoopsDB->query("SELECT encrypted_key FROM $table WHERE uid = $uid AND provider = $provider");
    if (!$result) {
        return '';
    }
    $row = $xoopsDB->fetchArray($result);
    if (!$row) {
        return '';
    }
    $decrypted = formulizeAI_decryptKey($row['encrypted_key']);
    return $decrypted === false ? '' : $decrypted;
}

/**
 * Is a key stored, without decrypting it? Use this anywhere the answer is only
 * needed to drive the UI, so the key itself never has to be read into memory.
 *
 * @param int $uid The owning user, or FORMULIZE_AI_SYSTEM_UID for the site-wide key
 * @param string $provider One of formulizeAI_providers()
 * @return bool
 */
function formulizeAI_hasKey($uid, $provider) {
    if (!in_array($provider, formulizeAI_providers())) {
        return false;
    }
    if ((int) $uid === FORMULIZE_AI_SYSTEM_UID) {
        $map = formulizeAI_decodeSiteKeyMap(formulizeAI_preference('formulizeAIApiKey', ''));
        return !empty($map[$provider]);
    }
    global $xoopsDB;
    $uid = intval($uid);
    $table = $xoopsDB->prefix('formulize_ai_keys');
    $provider = $xoopsDB->quoteString($provider);
    $result = @$xoopsDB->query("SELECT uid FROM $table WHERE uid = $uid AND provider = $provider");
    return ($result && $xoopsDB->fetchArray($result)) ? true : false;
}

// --------------------------------------------------------------------------
// Tool categories
//
// Ported from the JavaScript constants that used to live in ai/index.php. The
// browser still needs them to drive the preset buttons, but it now receives them
// from here so there is only one definition to keep current.
// --------------------------------------------------------------------------

/**
 * The named sets the tool categories are built from.
 *
 * @return array Map of set name => array of tool names
 */
function formulizeAI_toolSets() {
    return array(

        // Everything the MCP registry defines *after* test_connection (see the
        // registration order in mcp/tools.php) - the Manage forms category. Read-only
        // lookup/inspection tools live in formInspect below instead, even when they read
        // form/screen/group/user structure rather than entry data, because Manage forms
        // is meant to be additive to Read/Write, not a grab-bag of "everything else".
        'formMgmt' => array(
            'change_form_screen_page_order', 'change_menu_item_order',
            'create_derived_value_element', 'create_form', 'create_form_screen',
            'create_groups', 'create_linked_list_element', 'create_list_element',
            'create_list_screen', 'create_menu_item', 'create_selector_element',
            'create_static_content_element', 'create_subform_interface',
            'create_table_of_elements', 'create_text_box_element', 'create_user_list_element',
            'create_users',
            'delete_element',
            'get_custom_code', 'get_form_permissions_by_group',
            'query_the_database_directly',
            'read_system_activity_log',
            'set_form_permission_inheritance', 'set_form_permissions',
            'update_application_code', 'update_application_forms',
            'update_derived_value_element', 'update_form', 'update_form_code',
            'update_form_screen', 'update_group_members', 'update_groups',
            'update_linked_list_element', 'update_list_element', 'update_list_screen',
            'update_menu_item', 'update_selector_element', 'update_static_content_element',
            'update_subform_interface', 'update_table_of_elements',
            'update_text_box_element', 'update_user_list_element', 'update_users',
        ),

        // Everything the MCP registry defines *up to and including* test_connection (see
        // the registration order in mcp/tools.php), other than the two entry-writing
        // tools in that same range (entryWrite, below). Included in Read and Write;
        // Manage forms gets only test_connection itself out of this set - see
        // formulizeAI_toolCategoryNames().
        'formInspect' => array(
            'get_application_details', 'get_element_details', 'get_entries_from_form',
            'get_form_details', 'get_screen_details',
            'list_a_users_groups', 'list_applications', 'list_form_connections',
            'list_forms', 'list_group_members', 'list_groups', 'list_menu_items',
            'list_screens', 'list_users',
            'prepare_database_values_for_human_readability', 'test_connection',
        ),

        // Tools that write entry data (added to Read data to get Read & write data)
        'entryWrite' => array('create_entries', 'update_entries'),

        // Never offered in the UI or sent to the AI
        'easterEggs' => array(
            'locate_captain_picard',
            'open_the_pod_bay_doors_hal',
            'lets_play_global_thermonuclear_war',
        ),

        // Tools whose output is folded into the system prompt and then removed from the
        // tool list. The MCP server registers this one under the local server name,
        // which defaults to 'formulize'.
        'initTools' => array('formulize'),
    );
}

/**
 * The tool names belonging to a category.
 *
 * The init tool and the easter eggs are always included, whatever the category. They
 * are not selectable and never reach the model - the browser strips the easter eggs
 * and consumes the init tool during startup - but dropping the init tool server-side
 * would silently deprive the assistant of its system instructions.
 *
 * @param string $category read|write|manage|all|custom|userspecified
 * @param array $allToolNames Every tool name available in this context
 * @param array $customList Selected names, used only when $category is 'custom'
 * @return array The tool names in that category, in $allToolNames order
 */
function formulizeAI_toolCategoryNames($category, $allToolNames, $customList = array()) {
    $sets = formulizeAI_toolSets();
    $always = array_merge($sets['initTools'], $sets['easterEggs']);

    switch ($category) {

        case 'read':
            $selected = array_filter($allToolNames, function($name) use ($sets) {
                return in_array($name, $sets['formInspect']);
            });
            break;

        case 'write':
            $selected = array_filter($allToolNames, function($name) use ($sets) {
                return in_array($name, $sets['formInspect']) || in_array($name, $sets['entryWrite']);
            });
            break;

        case 'manage':
            $selected = array_filter($allToolNames, function($name) use ($sets) {
                return in_array($name, $sets['formMgmt']) || $name === 'test_connection';
            });
            break;

        case 'custom':
            $selected = array_intersect($allToolNames, (array) $customList);
            break;

        case 'all':
        default:
            $selected = $allToolNames;
            break;
    }

    return array_values(array_unique(array_merge(
        array_values($selected),
        array_values(array_intersect($allToolNames, $always))
    )));
}

/**
 * Every tool name this system has, for the administrator's tool picker.
 *
 * Built from the real MCP registry rather than a hand-maintained list, so it cannot go
 * stale as tools are added - which is the whole point of letting an administrator pin a
 * tool set in the first place.
 *
 * The registry is built as a webmaster, because the administrator must be able to choose
 * from every tool anybody could ever have. That is not a grant: a person's own MCP
 * request builds its own registry from their own groups, so a tool ticked here still
 * only reaches someone whose permissions already included it. The selection is an
 * intersection, never an expansion.
 *
 * The init tool and the easter eggs are excluded - neither is meaningfully selectable.
 *
 * @return array Tool names, sorted
 */
function formulizeAI_allToolNames() {
    static $names = null;
    if ($names !== null) {
        return $names;
    }

    // mcp.php clears every output buffer at file scope unless told not to, which would
    // discard the buffering of whatever page is including us.
    if (!defined('FORMULIZE_MCP_REGISTRY_ONLY')) {
        define('FORMULIZE_MCP_REGISTRY_ONLY', 1);
    }
    include_once XOOPS_ROOT_PATH . '/mcp/mcp.php';

    $mcp = new FormulizeMCP(null, true, array(XOOPS_GROUP_ADMIN));
    $sets = formulizeAI_toolSets();
    $names = array_values(array_diff(
        array_keys($mcp->tools),
        $sets['initTools'],
        $sets['easterEggs']
    ));
    sort($names);
    return $names;
}

/**
 * Reduce a registered tool set to what an administrator allows the embedded assistant.
 *
 * This is the enforcement point, and it has to be server-side: the assistant's tool
 * picker is just a UI, and the MCP endpoint it calls is separately reachable, so a
 * request made outside the assistant would otherwise have the full set.
 *
 * Call this ONLY for session-authenticated callers - that is, the embedded assistant.
 * API-key callers are external MCP clients governed by the separate MCP Server
 * preference and its own key model, and must not be affected by this.
 *
 * @param array $tools The registered tools, keyed by tool name
 * @return array The same array, reduced to the allowed tools
 */
function formulizeAI_filterToolsForSession($tools) {
    $config = formulizeAI_adminConfig();
    if (!$config['toolsLocked']) {
        return $tools;
    }
    $allowed = formulizeAI_toolCategoryNames(
        $config['toolCategory'],
        array_keys($tools),
        $config['toolList']
    );
    return array_intersect_key($tools, array_flip($allowed));
}

// --------------------------------------------------------------------------
// Administrator configuration
// --------------------------------------------------------------------------

/**
 * Read one Formulize preference, whether or not we are running inside the module.
 *
 * Mirrors the idiom in isAIAssistantEnabled(): use $xoopsModuleConfig when it is
 * Formulize's own config, otherwise load the module's preferences directly.
 *
 * @param string $name The conf_name
 * @param mixed $default Returned when the preference does not exist yet
 * @return mixed
 */
function formulizeAI_preference($name, $default = '') {
    global $xoopsModuleConfig;
    static $fallbackConfig = null;

    // formulizeAIAssistantEnabled is the marker that $xoopsModuleConfig is Formulize's
    if (isset($xoopsModuleConfig['formulizeAIAssistantEnabled'])) {
        return isset($xoopsModuleConfig[$name]) ? $xoopsModuleConfig[$name] : $default;
    }

    if ($fallbackConfig === null) {
        $config_handler = xoops_gethandler('config');
        $fallbackConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
    }
    return isset($fallbackConfig[$name]) ? $fallbackConfig[$name] : $default;
}

/**
 * The resolved administrator configuration for the assistant.
 *
 * Every setting defaults to "user specified", so a site that has never touched these
 * preferences - including one where the settings have not been created yet - behaves
 * exactly as it did before they existed.
 *
 * @return array
 */
function formulizeAI_adminConfig() {

    $provider = formulizeAI_preference('formulizeAIProvider', 'userspecified');
    if (!in_array($provider, formulizeAI_providers())) {
        $provider = 'userspecified';
    }
    $providerLocked = ($provider !== 'userspecified');

    $toolCategory = formulizeAI_preference('formulizeAIToolAccess', 'userspecified');
    if (!in_array($toolCategory, array('read', 'write', 'manage', 'all', 'custom'))) {
        $toolCategory = 'userspecified';
    }
    $toolsLocked = ($toolCategory !== 'userspecified');

    // A config item of valuetype 'array' that posts nothing is stored as array('')
    // rather than array() by the core preferences handler, so filter empties out.
    $toolList = formulizeAI_preference('formulizeAIToolList', array());
    $toolList = array_values(array_filter((array) $toolList, 'strlen'));

    $contextLimit = intval(formulizeAI_preference('formulizeAIContextLimit', 0));
    if ($contextLimit <= 0 && $providerLocked) {
        $defaults = formulizeAI_contextWindowDefaults();
        $contextLimit = $defaults[$provider];
    }

    $model = trim(formulizeAI_preference('formulizeAIModel', ''));
    if ($model === '' && $providerLocked) {
        $modelDefaults = formulizeAI_modelDefaults();
        $model = $modelDefaults[$provider];
    }

    return array(
        'providerLocked' => $providerLocked,
        'provider' => $providerLocked ? $provider : '',
        'model' => $providerLocked ? $model : '',
        'contextLimit' => $providerLocked ? $contextLimit : 0,
        'ollamaBaseUrl' => formulizeAI_ollamaBaseUrl(),
        'toolsLocked' => $toolsLocked,
        'toolCategory' => $toolCategory,
        'toolList' => $toolList,
    );
}

/**
 * The Ollama endpoint the server-side proxy should talk to.
 *
 * Note this is the Formulize server's view of Ollama, not the browser's - which is the
 * point, since the assistant used to fetch localhost:11434 from the browser, meaning
 * the user's own machine, and was blocked as mixed content on any HTTPS site.
 *
 * @return string A validated base URL, falling back to the default if the setting is unusable
 */
function formulizeAI_ollamaBaseUrl() {
    $default = 'http://localhost:11434';
    $url = trim(formulizeAI_preference('formulizeAIOllamaBaseUrl', $default));
    if ($url === '') {
        return $default;
    }
    $parts = parse_url($url);
    if (!$parts || empty($parts['host']) || !isset($parts['scheme'])
        || !in_array(strtolower($parts['scheme']), array('http', 'https'))) {
        return $default;
    }
    return rtrim($url, '/');
}

/**
 * The administrator configuration in the shape the assistant's JavaScript needs.
 *
 * This is injected into the page, so it must never contain an API key. Whether a key
 * exists is all the browser is entitled to know, and all it needs in order to decide
 * between offering the chat and explaining that setup is incomplete.
 *
 * @param int $uid The current user
 * @return array
 */
function formulizeAI_adminConfigForClient($uid) {
    $config = formulizeAI_adminConfig();

    // The site-wide key when the administrator has chosen the provider, otherwise the
    // user's own keys, so the UI can tell "no key yet" from "ready to go".
    $keyPresent = array();
    foreach (formulizeAI_providers() as $provider) {
        if ($provider === 'ollama') {
            $keyPresent[$provider] = true; // keyless
        } elseif ($config['providerLocked']) {
            $keyPresent[$provider] = formulizeAI_hasKey(FORMULIZE_AI_SYSTEM_UID, $provider);
        } else {
            $keyPresent[$provider] = formulizeAI_hasKey($uid, $provider);
        }
    }

    $config['keyPresent'] = $keyPresent;
    $config['toolSets'] = formulizeAI_toolSets();
    $config['modelDefaults'] = formulizeAI_modelDefaults();
    $config['contextWindowDefaults'] = formulizeAI_contextWindowDefaults();

    // The browser has no business knowing the server-side Ollama endpoint, and no way
    // to use it now that all traffic goes through the proxy.
    unset($config['ollamaBaseUrl']);

    return $config;
}
