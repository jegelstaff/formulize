<?php
/**
 * Proxy for Anthropic Claude API calls.
 *
 * GET  → forwards to /v1/models  (model discovery)
 * POST → forwards to /v1/messages (chat completion)
 *
 * The API key is never stored server-side — it arrives in X-API-Key each request.
 */

// Match the pattern used in admin/save.php: include bootstrap, then disable the XOOPS
// debug logger and clear ALL output buffer levels (mainfile.php opens several, including
// a gzip layer). Without this, XOOPS flushes debug/log output after our JSON response,
// producing a two-line body that breaks JSON.parse in the browser.
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
    echo json_encode(['error' => ['message' => 'Not authenticated']]);
    exit();
}

$apiKey = isset($_SERVER['HTTP_X_API_KEY']) ? trim($_SERVER['HTTP_X_API_KEY']) : '';
if (!$apiKey) {
    http_response_code(400);
    echo json_encode(['error' => ['message' => 'No API key provided']]);
    exit();
}

$anthropicHeaders = [
    'x-api-key: ' . $apiKey,
    'anthropic-version: 2023-06-01',
];

// GET: list available Claude models
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $ch = curl_init('https://api.anthropic.com/v1/models?limit=100');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $anthropicHeaders);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        $errBody = json_encode(['error' => ['message' => 'Proxy curl error: ' . $curlError]]);
        http_response_code(502);
        header('Content-Length: ' . strlen($errBody));
        echo $errBody;
        exit();
    }

    http_response_code($httpCode);
    header('Content-Length: ' . strlen($response));
    echo $response;
    exit();
}

// POST: forward chat completion request
$body = file_get_contents('php://input');
if (!$body) {
    $errBody = json_encode(['error' => ['message' => 'Empty request body']]);
    http_response_code(400);
    header('Content-Length: ' . strlen($errBody));
    echo $errBody;
    exit();
}

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(
    ['Content-Type: application/json'],
    $anthropicHeaders
));

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    $errBody = json_encode(['error' => ['message' => 'Proxy curl error: ' . $curlError]]);
    http_response_code(502);
    header('Content-Length: ' . strlen($errBody));
    echo $errBody;
    exit();
}

http_response_code($httpCode);
header('Content-Length: ' . strlen($response));
echo $response;
