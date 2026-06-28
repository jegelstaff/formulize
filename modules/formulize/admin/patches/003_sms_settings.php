<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Creates the SMS messaging config items (in the mailer category) so SMS provider
// credentials can be managed from the admin UI (Site → Integrations) instead of
// only via trust-folder constants. The SMS providers read these config values
// first and fall back to the trust constants when blank, so existing installs
// keep working until they fill the settings in.
//
// Idempotent: only inserts items that don't already exist. Runs once, when the
// stored dbversion is below 4.
function formulize_patch_003_sms_settings($prev_dbversion, $required_dbversion) {
    if ($prev_dbversion >= 4) {
        return true; // already applied
    }
    global $xoopsDB;

    $configTable = $xoopsDB->prefix('config');
    $optionTable = $xoopsDB->prefix('configoption');
    $cat = defined('ICMS_CONF_MAILER') ? ICMS_CONF_MAILER : 6;

    // conf_name => array(title, default, description, formtype, valuetype, options[name=>value], trust-constant)
    // The trust-constant is the existing trust-folder define() this setting replaces; if it
    // is present and non-empty on an upgraded system, its value seeds the new config item.
    $items = array(
        'sms_provider'    => array('SMS provider', 'Twilio', 'The service used to send text messages (SMS).', 'select', 'text', array('Twilio' => 'Twilio', 'Nexmo (Vonage)' => 'Nexmo'), 'SMS_PROVIDER'),
        'sms_account_sid' => array('SMS account SID / API key', '', 'Account SID (Twilio) or API key (Vonage/Nexmo).', 'textbox', 'text', array(), 'SMS_ACCOUNT_SID'),
        'sms_auth_token'  => array('SMS auth token / API secret', '', 'Auth token (Twilio) or API secret (Vonage/Nexmo).', 'password', 'text', array(), 'SMS_AUTH_TOKEN'),
        'sms_from_number' => array('SMS from number / sender ID', '', 'The phone number or sender ID that text messages are sent from.', 'textbox', 'text', array(), 'SMS_FROM_NUMBER'),
    );

    foreach ($items as $name => $def) {
        list($title, $default, $desc, $formtype, $valuetype, $options, $constant) = $def;

        // skip if it already exists
        $checkSql = "SELECT conf_id FROM $configTable WHERE conf_name = " . $xoopsDB->quoteString($name)
            . " AND conf_modid = 0 AND conf_catid = " . intval($cat);
        $checkRes = $xoopsDB->queryF($checkSql);
        if ($checkRes && $xoopsDB->getRowsNum($checkRes) > 0) {
            continue;
        }

        // Seed from the existing trust-folder constant when it is defined and non-empty,
        // so upgraded systems that configured SMS via the trust file keep their values.
        $value = ($constant !== '' && defined($constant) && (string) constant($constant) !== '')
            ? (string) constant($constant)
            : $default;

        $insertSql = "INSERT INTO $configTable (`conf_modid`, `conf_catid`, `conf_name`, `conf_title`, `conf_value`, `conf_desc`, `conf_formtype`, `conf_valuetype`, `conf_order`) VALUES (0, "
            . intval($cat) . ", "
            . $xoopsDB->quoteString($name) . ", "
            . $xoopsDB->quoteString($title) . ", "
            . $xoopsDB->quoteString($value) . ", "
            . $xoopsDB->quoteString($desc) . ", "
            . $xoopsDB->quoteString($formtype) . ", "
            . $xoopsDB->quoteString($valuetype) . ", 0)";
        if (!$xoopsDB->queryF($insertSql)) {
            echo '<p>003_sms_settings: failed to insert config item ' . htmlspecialchars($name) . ': ' . htmlspecialchars($xoopsDB->error()) . '</p>';
            return false;
        }

        if ($options) {
            $confId = $xoopsDB->getInsertId();
            foreach ($options as $optName => $optValue) {
                $optSql = "INSERT INTO $optionTable (`confop_name`, `confop_value`, `conf_id`) VALUES ("
                    . $xoopsDB->quoteString($optName) . ", "
                    . $xoopsDB->quoteString($optValue) . ", "
                    . intval($confId) . ")";
                if (!$xoopsDB->queryF($optSql)) {
                    echo '<p>003_sms_settings: failed to insert option for ' . htmlspecialchars($name) . ': ' . htmlspecialchars($xoopsDB->error()) . '</p>';
                    return false;
                }
            }
        }
    }

    return true;
}
