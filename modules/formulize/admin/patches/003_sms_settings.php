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
    global $xoopsDB;

    $configTable = $xoopsDB->prefix('config');
    $optionTable = $xoopsDB->prefix('configoption');
    $cat = defined('ICMS_CONF_MAILER') ? ICMS_CONF_MAILER : 6;

    // Repair any rows that were inserted with literal display strings instead of PHP constant
    // names. The system preferences page calls constant() on conf_title/conf_desc, so they
    // must be constant names. This runs unconditionally so it fixes already-upgraded installs.
    $literalFixes = array(
        'sms_provider'    => array('SMS provider',              '_MD_AM_SMS_PROVIDER',     'The service used to send text messages (SMS).',      '_MD_AM_SMS_PROVIDER_DSC'),
        'sms_account_sid' => array('SMS account SID / API key', '_MD_AM_SMS_ACCOUNT_SID',  'Account SID (Twilio) or API key (Vonage/Nexmo).',    '_MD_AM_SMS_ACCOUNT_SID_DSC'),
        'sms_auth_token'  => array('SMS auth token / API secret', '_MD_AM_SMS_AUTH_TOKEN', 'Auth token (Twilio) or API secret (Vonage/Nexmo).', '_MD_AM_SMS_AUTH_TOKEN_DSC'),
        'sms_from_number' => array('SMS from number / sender ID', '_MD_AM_SMS_FROM_NUMBER','The phone number or sender ID that text messages are sent from.', '_MD_AM_SMS_FROM_NUMBER_DSC'),
    );
    foreach ($literalFixes as $name => $fix) {
        list($oldTitle, $newTitle, $oldDesc, $newDesc) = $fix;
        $xoopsDB->queryF(
            "UPDATE $configTable SET conf_title = " . $xoopsDB->quoteString($newTitle)
            . ", conf_desc = " . $xoopsDB->quoteString($newDesc)
            . " WHERE conf_name = " . $xoopsDB->quoteString($name)
            . " AND conf_modid = 0 AND conf_catid = " . intval($cat)
            . " AND conf_title = " . $xoopsDB->quoteString($oldTitle)
        );
    }

    if ($prev_dbversion >= 4) {
        return true; // already applied
    }

    // conf_name => array(title_constant, default, desc_constant, formtype, valuetype, options[name=>value], trust-constant)
    // The trust-constant is the existing trust-folder define() this setting replaces; if it
    // is present and non-empty on an upgraded system, its value seeds the new config item.
    $items = array(
        'sms_provider'    => array('_MD_AM_SMS_PROVIDER',    'Twilio', '_MD_AM_SMS_PROVIDER_DSC',    'select',  'text', array('Twilio' => 'Twilio', 'Nexmo (Vonage)' => 'Nexmo'), 'SMS_PROVIDER'),
        'sms_account_sid' => array('_MD_AM_SMS_ACCOUNT_SID', '',       '_MD_AM_SMS_ACCOUNT_SID_DSC', 'textbox', 'text', array(), 'SMS_ACCOUNT_SID'),
        'sms_auth_token'  => array('_MD_AM_SMS_AUTH_TOKEN',  '',       '_MD_AM_SMS_AUTH_TOKEN_DSC',  'password','text', array(), 'SMS_AUTH_TOKEN'),
        'sms_from_number' => array('_MD_AM_SMS_FROM_NUMBER', '',       '_MD_AM_SMS_FROM_NUMBER_DSC', 'textbox', 'text', array(), 'SMS_FROM_NUMBER'),
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
