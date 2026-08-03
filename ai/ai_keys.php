<?php
/**
 * API key storage endpoint for the Formulize AI Assistant.
 *
 * Two scopes:
 *
 *   scope=user (default) - the signed-in person's own key for a provider, saved from the
 *   assistant's settings panel. A key can be replaced but not deleted, and the field is
 *   always blank on load, so an empty key means "keep what is there".
 *
 *   scope=system - the site-wide key an administrator sets in Settings -> AI, stored
 *   against FORMULIZE_AI_SYSTEM_UID. Webmaster only, and it does support deletion
 *   (op=clear), because an administrator has to be able to take a key back out.
 *
 * Keys are encrypted before storage and are never returned by this endpoint, in either
 * scope. See modules/formulize/include/aiadminconfig.php for the storage helpers.
 */

include_once "../mainfile.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/aiadminconfig.php";
if (isset(icms::$logger)) {
    icms::$logger->disableLogger();
}
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json');

function formulizeAI_keysFail($code, $message) {
    http_response_code($code);
    echo json_encode(array('error' => $message));
    exit();
}

if (!$xoopsUser) {
    formulizeAI_keysFail(401, 'Not authenticated');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    formulizeAI_keysFail(405, 'Method not allowed');
}

// The assistant posts JSON; the admin settings page posts a normal form encoding.
$body = array();
$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (strpos($contentType, 'application/json') !== false) {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body)) {
        $body = array();
    }
} else {
    $body = $_POST;
}

$scope    = isset($body['scope']) ? (string)$body['scope'] : 'user';
$op       = isset($body['op']) ? (string)$body['op'] : 'save';
$provider = isset($body['provider']) ? preg_replace('/[^a-z]/', '', (string)$body['provider']) : '';
$key      = isset($body['key']) ? trim((string)$body['key']) : '';

// Ollama takes no key, so it is not accepted here in either scope.
if (!in_array($provider, array('claude', 'gemini', 'openai'))) {
    formulizeAI_keysFail(400, 'Invalid provider');
}

if (formulizeAI_encryptionSecret() === false) {
    formulizeAI_keysFail(500, 'Server not configured for key storage (XOOPS_DB_SALT missing)');
}

if ($scope === 'system') {

    // Match the gate on the settings page itself (admin/configsubject.php checks for
    // XOOPS_GROUP_ADMIN). Deliberately not $xoopsUser->isAdmin(), which is scoped to the
    // current module's module_admin right - that would let a Formulize module admin who
    // cannot even open the AI settings page write the site-wide key.
    if (!in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
        formulizeAI_keysFail(403, 'Only webmasters can change the site-wide AI key');
    }

    // The settings form has to submit straight after this call, so the token must survive.
    // icms::$security->check() only skips clearing the token for XMLHttpRequests, which is
    // why the caller sends X-Requested-With - see the submit handler that posts here.
    if (!icms::$security->check()) {
        formulizeAI_keysFail(403, 'Security token was not valid. Reload the settings page and try again.');
    }

    if ($op === 'clear') {
        if (!formulizeAI_deleteKey(FORMULIZE_AI_SYSTEM_UID, $provider)) {
            formulizeAI_keysFail(500, 'Could not remove the stored key');
        }
        echo json_encode(array('ok' => true, 'keyPresent' => false));
        exit();
    }

    if ($key === '') {
        // Nothing typed and no clear requested: leave the stored key alone.
        echo json_encode(array('ok' => true, 'keyPresent' => formulizeAI_hasKey(FORMULIZE_AI_SYSTEM_UID, $provider)));
        exit();
    }

    if (!formulizeAI_storeKey(FORMULIZE_AI_SYSTEM_UID, $provider, $key)) {
        formulizeAI_keysFail(500, 'Could not store the key');
    }
    echo json_encode(array('ok' => true, 'keyPresent' => true));
    exit();
}

// --- user scope ---

// When an administrator has chosen the provider for everyone, personal keys are not used
// for anything, so accepting one would only invite people to think theirs was in play.
$adminConfig = formulizeAI_adminConfig();
if ($adminConfig['providerLocked']) {
    formulizeAI_keysFail(403, 'Your administrator has configured the AI provider for this site, so personal API keys are not used.');
}

if ($key === '') {
    // Empty key box means "keep existing key" - the field is always blank after first save.
    // There is deliberately no delete path in this scope: a stored key can only be replaced.
    echo json_encode(array('ok' => true));
    exit();
}

if (!formulizeAI_storeKey((int)$xoopsUser->getVar('uid'), $provider, $key)) {
    formulizeAI_keysFail(500, 'Could not store the key');
}

echo json_encode(array('ok' => true));
