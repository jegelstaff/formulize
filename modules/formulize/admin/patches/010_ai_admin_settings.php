<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Provisions the administrator-specified AI assistant preferences (Settings -> AI) on
// existing installs. Fresh installs get them from xoops_version.php; this adds them to
// systems installed before the settings existed.
//
// Every one of them defaults to "user specified", which is exactly how the assistant
// behaved before, so applying this patch changes nothing about a running site until an
// administrator deliberately chooses a provider or a tool set.
//
// The API key is deliberately NOT one of these settings' values: formulizeAIApiKey only
// ever holds the marker 'set' or '', while the key itself lives encrypted in
// formulize_ai_keys under FORMULIZE_AI_SYSTEM_UID. That table already exists (created in
// 001_schema_migrations) and needs no change, since its uid column is a signed INT.
//
// Idempotent: only inserts items that don't already exist. Gated to run once, when the
// stored dbversion is below 17.
function formulize_patch_010_ai_admin_settings($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 17) {
        return true; // already applied
    }

    $modid = intval(getFormulizeModId());
    if (!$modid) {
        echo '<p>Error: could not resolve the Formulize module id while provisioning the AI settings.</p>';
        return false;
    }

    $configTable = $xoopsDB->prefix('config');
    $optionTable = $xoopsDB->prefix('configoption');

    // conf_name => array(title constant, desc constant, formtype, valuetype, default value,
    //                    options as array(display constant name => stored value))
    //
    // conf_title/conf_desc store the language-constant NAMES, resolved via constant() at
    // display time, matching every other module config item. Option labels are the other
    // way round - the installer stores the resolved display string in confop_name - so
    // they are resolved here, falling back to the English text if the language file that
    // defines them has not been loaded in this request.
    $items = array(
        'formulizeAIProvider' => array(
            '_MI_formulize_AIPROVIDER', '_MI_formulize_AIPROVIDER_DESC', 'select', 'text', 'userspecified',
            array(
                '_MI_formulize_AIPROVIDER_USERSPECIFIED' => 'userspecified',
                '_MI_formulize_AIPROVIDER_CLAUDE' => 'claude',
                '_MI_formulize_AIPROVIDER_GEMINI' => 'gemini',
                '_MI_formulize_AIPROVIDER_OPENAI' => 'openai',
                '_MI_formulize_AIPROVIDER_OLLAMA' => 'ollama',
            ),
        ),
        'formulizeAIModel' => array(
            '_MI_formulize_AIMODEL', '_MI_formulize_AIMODEL_DESC', 'textbox', 'text', '', array(),
        ),
        'formulizeAIApiKey' => array(
            '_MI_formulize_AIAPIKEY', '_MI_formulize_AIAPIKEY_DESC', 'aikey', 'text', '', array(),
        ),
        'formulizeAIContextLimit' => array(
            '_MI_formulize_AICONTEXTLIMIT', '_MI_formulize_AICONTEXTLIMIT_DESC', 'textbox', 'int', '0', array(),
        ),
        'formulizeAIOllamaBaseUrl' => array(
            '_MI_formulize_AIOLLAMABASEURL', '_MI_formulize_AIOLLAMABASEURL_DESC', 'textbox', 'text', 'http://localhost:11434', array(),
        ),
        'formulizeAIToolAccess' => array(
            '_MI_formulize_AITOOLACCESS', '_MI_formulize_AITOOLACCESS_DESC', 'select', 'text', 'userspecified',
            array(
                '_MI_formulize_AITOOLACCESS_USERSPECIFIED' => 'userspecified',
                '_MI_formulize_AITOOLACCESS_READ' => 'read',
                '_MI_formulize_AITOOLACCESS_WRITE' => 'write',
                '_MI_formulize_AITOOLACCESS_MANAGE' => 'manage',
                '_MI_formulize_AITOOLACCESS_ALL' => 'all',
                '_MI_formulize_AITOOLACCESS_CUSTOM' => 'custom',
            ),
        ),
        // An 'array' valuetype is stored serialized, the same way formulizeAIAssistantGroups is.
        'formulizeAIToolList' => array(
            '_MI_formulize_AITOOLLIST', '_MI_formulize_AITOOLLIST_DESC', 'aitools', 'array', serialize(array()), array(),
        ),
    );

    // Fallback labels, used only if the modinfo language file is not loaded in this request.
    $optionFallbacks = array(
        '_MI_formulize_AIPROVIDER_USERSPECIFIED' => 'User Specified',
        '_MI_formulize_AIPROVIDER_CLAUDE' => 'Claude (Anthropic)',
        '_MI_formulize_AIPROVIDER_GEMINI' => 'Gemini (Google)',
        '_MI_formulize_AIPROVIDER_OPENAI' => 'OpenAI',
        '_MI_formulize_AIPROVIDER_OLLAMA' => 'Ollama (local model)',
        '_MI_formulize_AITOOLACCESS_USERSPECIFIED' => 'User Specified',
        '_MI_formulize_AITOOLACCESS_READ' => 'Read data',
        '_MI_formulize_AITOOLACCESS_WRITE' => 'Read and write data',
        '_MI_formulize_AITOOLACCESS_MANAGE' => 'Manage forms',
        '_MI_formulize_AITOOLACCESS_ALL' => 'All tools',
        '_MI_formulize_AITOOLACCESS_CUSTOM' => 'Choose individual tools...',
    );

    // Place the new items at the end of this module's config order.
    $orderRes = $xoopsDB->queryF("SELECT MAX(conf_order) AS m FROM $configTable WHERE conf_modid = $modid");
    $orderRow = $orderRes ? $xoopsDB->fetchArray($orderRes) : null;
    $confOrder = ($orderRow && $orderRow['m'] !== null) ? intval($orderRow['m']) + 1 : 0;

    $added = 0;
    foreach ($items as $name => $def) {
        list($title, $desc, $formtype, $valuetype, $default, $options) = $def;

        // Skip if it already exists (module config: conf_modid = formulize mid, conf_catid = 0).
        $checkRes = $xoopsDB->queryF(
            "SELECT conf_id FROM $configTable WHERE conf_name = " . $xoopsDB->quoteString($name)
            . " AND conf_modid = $modid AND conf_catid = 0"
        );
        if ($checkRes && $xoopsDB->getRowsNum($checkRes) > 0) {
            continue;
        }

        $sql = "INSERT INTO $configTable (conf_modid, conf_catid, conf_name, conf_title, conf_value, conf_desc, conf_formtype, conf_valuetype, conf_order) VALUES ("
            . $modid . ", 0, "
            . $xoopsDB->quoteString($name) . ", "
            . $xoopsDB->quoteString($title) . ", "
            . $xoopsDB->quoteString($default) . ", "
            . $xoopsDB->quoteString($desc) . ", "
            . $xoopsDB->quoteString($formtype) . ", "
            . $xoopsDB->quoteString($valuetype) . ", "
            . $confOrder
            . ")";
        if (!$xoopsDB->queryF($sql)) {
            echo '<p>010_ai_admin_settings: failed to insert config item ' . htmlspecialchars($name)
                . ': ' . htmlspecialchars($xoopsDB->error()) . '</p>';
            return false;
        }
        $confOrder++;
        $added++;

        if ($options) {
            $confId = intval($xoopsDB->getInsertId());
            foreach ($options as $labelConstant => $optValue) {
                $label = defined($labelConstant)
                    ? constant($labelConstant)
                    : (isset($optionFallbacks[$labelConstant]) ? $optionFallbacks[$labelConstant] : $optValue);
                $optSql = "INSERT INTO $optionTable (confop_name, confop_value, conf_id) VALUES ("
                    . $xoopsDB->quoteString($label) . ", "
                    . $xoopsDB->quoteString($optValue) . ", "
                    . $confId . ")";
                if (!$xoopsDB->queryF($optSql)) {
                    echo '<p>010_ai_admin_settings: failed to insert option for ' . htmlspecialchars($name)
                        . ': ' . htmlspecialchars($xoopsDB->error()) . '</p>';
                    return false;
                }
            }
        }
    }

    if ($added) {
        echo '<p>Added ' . $added . ' AI assistant settings (Settings &rarr; AI), so an administrator can'
            . ' choose the AI provider, model, API key and tools for everyone. They all start as'
            . ' "User Specified", which is how the assistant already behaves.</p>';
    }
    return true;
}
