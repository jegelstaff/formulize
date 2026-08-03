<?php
/**
 * File upload proxy for the OpenAI Files API.
 *
 * POST {file_data: base64, file_name: string, file_type: string}
 * → uploads to https://api.openai.com/v1/files with purpose "user_data"
 * → returns {file_id: "file-xxx"} or {error: "message"}
 *
 * The OpenAI API key is loaded server-side from formulize_ai_keys; it never
 * travels from browser to server in this request.
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

$body     = json_decode(file_get_contents('php://input'), true);
$fileData = isset($body['file_data']) ? (string)$body['file_data'] : '';
$fileName = isset($body['file_name']) ? (string)$body['file_name'] : 'upload.bin';
$fileType = isset($body['file_type']) ? (string)$body['file_type'] : 'application/octet-stream';

// Sanitise filename — strip directory components and non-safe chars
$fileName = substr(preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($fileName)), 0, 128);
if (!$fileName) $fileName = 'upload.bin';

// Only accept file types we actually support uploading
$allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($fileType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Unsupported file type: ' . $fileType]);
    exit();
}

if (!$fileData) {
    http_response_code(400);
    echo json_encode(['error' => 'No file data provided']);
    exit();
}

// The site-wide key when an administrator chose the provider, otherwise this person's
// own. Same rule the proxy applies, so an upload cannot end up billed differently from
// the conversation it belongs to.
$_aiAdminConfig = formulizeAI_adminConfig();
$apiKey = $_aiAdminConfig['providerLocked']
    ? formulizeAI_loadKey(FORMULIZE_AI_SYSTEM_UID, 'openai')
    : formulizeAI_loadKey((int)$xoopsUser->getVar('uid'), 'openai');

if (!$apiKey) {
    http_response_code(400);
    echo json_encode(['error' => $_aiAdminConfig['providerLocked']
        ? 'Your administrator has not saved an OpenAI API key.'
        : 'No OpenAI API key configured. Please save your settings first.']);
    exit();
}

// Decode base64 → binary
$binary = base64_decode($fileData, true);
if ($binary === false || !strlen($binary)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file data encoding']);
    exit();
}

// Write to a temp file so CURLFile can read it (auto-deleted on fclose)
$tmpHandle = tmpfile();
if (!$tmpHandle) {
    http_response_code(500);
    echo json_encode(['error' => 'Server could not create temp file for upload']);
    exit();
}
fwrite($tmpHandle, $binary);
$tmpPath = stream_get_meta_data($tmpHandle)['uri'];

$ch = curl_init('https://api.openai.com/v1/files');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'purpose' => 'user_data',
    'file'    => new CURLFile($tmpPath, $fileType, $fileName),
]);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
]);

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);
fclose($tmpHandle); // deletes the temp file

if ($curlError) {
    http_response_code(502);
    echo json_encode(['error' => 'Upload failed: ' . $curlError]);
    exit();
}

$data = json_decode($response, true);

if ($httpCode !== 200 || empty($data['id'])) {
    $msg = isset($data['error']['message']) ? $data['error']['message'] : ('HTTP ' . $httpCode);
    http_response_code($httpCode ?: 500);
    echo json_encode(['error' => $msg]);
    exit();
}

echo json_encode(['file_id' => $data['id']]);
