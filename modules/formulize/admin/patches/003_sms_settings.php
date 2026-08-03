<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Creates the SMS messaging config items (in the mailer category) so SMS provider
// credentials can be managed from the admin UI (Site → Integrations), and migrates
// any credentials that still live in one of the older locations into them.
//
// Credentials have lived in three places over time:
//   1. these config settings (where they belong now, managed in the database),
//   2. constants defined in the trust folder file (an interim arrangement),
//   3. hardcoded variables in include/2fa/sendSMS.php (the original approach).
// The migration below moves 2 or 3 into 1 automatically, so there is never anything
// for the administrator to do by hand.
//
// Idempotent: only inserts config items that don't already exist, and only migrates
// credentials when the config items are still blank. The item creation runs once,
// when the stored dbversion is below 4; the migration runs on every update, since a
// site can be carrying legacy credentials at any dbversion.
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

    if ($prev_dbversion < 4) {

        // conf_name => array(title_constant, default, desc_constant, formtype, valuetype, options[name=>value])
        // Items are created empty (apart from the provider's default); any values a site already has
        // elsewhere are brought in afterwards, by formulize_migrate_sms_credentials().
        $items = array(
            'sms_provider'    => array('_MD_AM_SMS_PROVIDER',    'Twilio', '_MD_AM_SMS_PROVIDER_DSC',    'select',  'text', array('Twilio' => 'Twilio', 'Nexmo (Vonage)' => 'Nexmo')),
            'sms_account_sid' => array('_MD_AM_SMS_ACCOUNT_SID', '',       '_MD_AM_SMS_ACCOUNT_SID_DSC', 'textbox', 'text', array()),
            'sms_auth_token'  => array('_MD_AM_SMS_AUTH_TOKEN',  '',       '_MD_AM_SMS_AUTH_TOKEN_DSC',  'password','text', array()),
            'sms_from_number' => array('_MD_AM_SMS_FROM_NUMBER', '',       '_MD_AM_SMS_FROM_NUMBER_DSC', 'textbox', 'text', array()),
        );

        foreach ($items as $name => $def) {
            list($title, $default, $desc, $formtype, $valuetype, $options) = $def;

            // skip if it already exists
            $checkSql = "SELECT conf_id FROM $configTable WHERE conf_name = " . $xoopsDB->quoteString($name)
                . " AND conf_modid = 0 AND conf_catid = " . intval($cat);
            $checkRes = $xoopsDB->queryF($checkSql);
            if ($checkRes && $xoopsDB->getRowsNum($checkRes) > 0) {
                continue;
            }

            $insertSql = "INSERT INTO $configTable (`conf_modid`, `conf_catid`, `conf_name`, `conf_title`, `conf_value`, `conf_desc`, `conf_formtype`, `conf_valuetype`, `conf_order`) VALUES (0, "
                . intval($cat) . ", "
                . $xoopsDB->quoteString($name) . ", "
                . $xoopsDB->quoteString($title) . ", "
                . $xoopsDB->quoteString($default) . ", "
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
    }

    return formulize_migrate_sms_credentials();
}

// Brings SMS credentials that are still in one of the older locations into the config settings.
//
// In priority order:
//   - if the settings already hold credentials, there is nothing to migrate and we do nothing,
//   - otherwise, if the trust folder file defines them, those values are copied in,
//   - otherwise, if the legacy include/2fa/sendSMS.php still holds them, those values are copied in.
// A site is only ever migrated from one source, and only into settings that are blank, so this is
// safe to run on every update: once the settings hold credentials, the first check short-circuits.
function formulize_migrate_sms_credentials() {
    global $xoopsDB;

    // conf_name => legacy trust-folder constant it replaced
    $credentials = array(
        'sms_account_sid' => 'SMS_ACCOUNT_SID',
        'sms_auth_token'  => 'SMS_AUTH_TOKEN',
        'sms_from_number' => 'SMS_FROM_NUMBER',
    );

    // 1. Credentials in the database already? Then leave everything alone. Note that sms_provider is
    // deliberately not consulted, since it has a non-blank default and so would always look "set".
    foreach ($credentials as $name => $constant) {
        if (formulize_readSmsConfigValue($name) !== '') {
            return true;
        }
    }

    // 2. Credentials in the trust folder file?
    $values = array();
    foreach ($credentials as $name => $constant) {
        if (defined($constant) AND trim((string) constant($constant)) !== '') {
            $values[$name] = trim((string) constant($constant));
        }
    }
    if ($values) {
        $source = 'the trust folder file';
        // the provider constant only matters alongside actual credentials
        if (defined('SMS_PROVIDER') AND trim((string) constant('SMS_PROVIDER')) !== '') {
            $values['sms_provider'] = trim((string) constant('SMS_PROVIDER'));
        }
    } else {
        // 3. Credentials still in the original sendSMS.php file? (That file was Twilio-only, and Twilio
        // is the default provider setting, so there is no provider value to bring across.)
        $values = formulize_readLegacySendSmsCredentials();
        $source = 'the legacy include/2fa/sendSMS.php file';
    }

    if (!$values) {
        return true; // nothing configured anywhere, nothing to do
    }

    $configTable = $xoopsDB->prefix('config');
    $cat = defined('ICMS_CONF_MAILER') ? ICMS_CONF_MAILER : 6;
    foreach ($values as $name => $value) {
        $updateSql = "UPDATE $configTable SET conf_value = " . $xoopsDB->quoteString($value)
            . " WHERE conf_name = " . $xoopsDB->quoteString($name)
            . " AND conf_modid = 0 AND conf_catid = " . intval($cat);
        if (!$xoopsDB->queryF($updateSql)) {
            echo '<p>003_sms_settings: failed to migrate SMS setting ' . htmlspecialchars($name) . ': ' . htmlspecialchars($xoopsDB->error()) . '</p>';
            return false;
        }
    }
    print "Copied the SMS credentials from $source into the site's text messaging settings. result: OK<br>";

    return true;
}

// Reads a single SMS config value straight from the config table. Deliberately not going through the
// config handler, since its in-memory cache may predate the rows this patch just inserted.
function formulize_readSmsConfigValue($name) {
    global $xoopsDB;
    $cat = defined('ICMS_CONF_MAILER') ? ICMS_CONF_MAILER : 6;
    $sql = "SELECT conf_value FROM " . $xoopsDB->prefix('config')
        . " WHERE conf_name = " . $xoopsDB->quoteString($name)
        . " AND conf_modid = 0 AND conf_catid = " . intval($cat);
    if ($res = $xoopsDB->queryF($sql) AND $row = $xoopsDB->fetchRow($res)) {
        return trim((string) $row[0]);
    }
    return '';
}

// Reads the credentials that were hardcoded in the original include/2fa/sendSMS.php file, which is no
// longer part of Formulize. Returns conf_name => value for whichever of them are present and non-blank.
function formulize_readLegacySendSmsCredentials() {
    $sendSmsFile = XOOPS_ROOT_PATH . '/include/2fa/sendSMS.php';
    if (!file_exists($sendSmsFile)) {
        return array();
    }
    $contents = file_get_contents($sendSmsFile);

    // conf_name => pattern matching the variable assignment in that file, ie: $id = "something";
    $patterns = array(
        'sms_account_sid' => '/\$id\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/',
        'sms_auth_token'  => '/\$token\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/',
        'sms_from_number' => '/\$from\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/',
    );

    $values = array();
    foreach ($patterns as $name => $pattern) {
        if (preg_match($pattern, $contents, $matches) AND trim($matches[1]) !== '') {
            $values[$name] = trim($matches[1]);
        }
    }
    return $values;
}
