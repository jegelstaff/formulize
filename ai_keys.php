<?php
/**
 * API key storage endpoint for the Formulize AI Assistant.
 *
 * POST {provider, key} — encrypt and store. Empty key is a no-op (key can only be replaced, never deleted).
 * Keys are encrypted with AES-256-CBC using XOOPS_DB_SALT as the secret.
 * The decrypted key is only ever returned to ai.php server-side, never via this endpoint.
 */

include_once "mainfile.php";
if (isset(icms::$logger)) {
    icms::$logger->disableLogger();
}
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json');

if (!$xoopsUser) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$body = json_decode(file_get_contents('php://input'), true);
$provider = isset($body['provider']) ? preg_replace('/[^a-z]/', '', (string)$body['provider']) : '';
$key      = isset($body['key'])      ? trim((string)$body['key'])                                 : '';

if (!in_array($provider, ['claude', 'gemini', 'openai'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid provider']);
    exit();
}

if (!defined('XOOPS_DB_SALT') || !XOOPS_DB_SALT) {
    http_response_code(500);
    echo json_encode(['error' => 'Server not configured for key storage (XOOPS_DB_SALT missing)']);
    exit();
}

$uid   = (int)$xoopsUser->getVar('uid');
$table = $xoopsDB->prefix('formulize_ai_keys');

if ($key === '') {
    // Empty key box means "keep existing key" — the field is always blank after first save.
    // There is deliberately no delete path: a stored key can only be replaced with a new one.
    echo json_encode(['ok' => true]);
    exit();
}

$iv        = random_bytes(16);
$encrypted = base64_encode($iv . openssl_encrypt(
    $key, 'AES-256-CBC', hash('sha256', XOOPS_DB_SALT, true), 0, $iv
));
$encrypted = $xoopsDB->quoteString($encrypted);
$existing  = $xoopsDB->query("SELECT uid FROM $table WHERE uid = $uid AND provider = '$provider'");
if ($xoopsDB->fetchArray($existing)) {
    $xoopsDB->query("UPDATE $table SET encrypted_key = $encrypted WHERE uid = $uid AND provider = '$provider'");
} else {
    $xoopsDB->query("INSERT INTO $table (uid, provider, encrypted_key) VALUES ($uid, '$provider', $encrypted)");
}

echo json_encode(['ok' => true]);

