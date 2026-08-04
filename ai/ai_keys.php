<?php
/**
 * API key storage endpoint for the Formulize AI Assistant.
 *
 * The signed-in person's own key for a provider, saved from the assistant's settings
 * panel. A key can be replaced but not deleted, and the field is always blank on load, so
 * an empty key means "keep what is there".
 *
 * The site-wide administrator key (Settings -> AI) does not go through here - it is saved
 * through the ordinary settings save, encrypted server-side by a hack in
 * icms_config_Item_Handler::insert() (see modules/formulize/include/aiadminconfig.php's
 * formulizeAI_prepareApiKeyForConfigStorage()).
 *
 * Keys are encrypted before storage and are never returned by this endpoint. See
 * modules/formulize/include/aiadminconfig.php for the storage helpers.
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

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    $body = array();
}

$provider = isset($body['provider']) ? preg_replace('/[^a-z]/', '', (string)$body['provider']) : '';
$key      = isset($body['key']) ? trim((string)$body['key']) : '';

// Ollama takes no key, so it is not accepted here.
if (!in_array($provider, array('claude', 'gemini', 'openai'))) {
    formulizeAI_keysFail(400, 'Invalid provider');
}

if (formulizeAI_encryptionSecret() === false) {
    formulizeAI_keysFail(500, 'Server not configured for key storage (XOOPS_DB_SALT missing)');
}

// When an administrator has chosen the provider for everyone, personal keys are not used
// for anything, so accepting one would only invite people to think theirs was in play.
$adminConfig = formulizeAI_adminConfig();
if ($adminConfig['providerLocked']) {
    formulizeAI_keysFail(403, 'Your administrator has configured the AI provider for this site, so personal API keys are not used.');
}

if ($key === '') {
    // Empty key box means "keep existing key" - the field is always blank on load.
    // There is deliberately no delete path here: a stored key can only be replaced.
    echo json_encode(array('ok' => true));
    exit();
}

if (!formulizeAI_storeKey((int)$xoopsUser->getVar('uid'), $provider, $key)) {
    formulizeAI_keysFail(500, 'Could not store the key');
}

echo json_encode(array('ok' => true));
