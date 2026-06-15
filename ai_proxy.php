<?php
/**
 * Proxy for Anthropic Claude API calls.
 *
 * GET  → forwards to /v1/models  (model discovery)
 * POST → forwards to /v1/messages (chat completion)
 *
 * The API key is never stored server-side — it arrives in X-API-Key each request.
 */

// Buffer everything so stray output from mainfile.php doesn't corrupt the JSON response
ob_start();
include_once "mainfile.php";
ob_end_clean();

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
        http_response_code(502);
        echo json_encode(['error' => ['message' => 'Proxy curl error: ' . $curlError]]);
        exit();
    }

    http_response_code($httpCode);
    echo $response;
    exit();
}

// POST: forward chat completion request
$body = file_get_contents('php://input');
if (!$body) {
    http_response_code(400);
    echo json_encode(['error' => ['message' => 'Empty request body']]);
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
    http_response_code(502);
    echo json_encode(['error' => ['message' => 'Proxy curl error: ' . $curlError]]);
    exit();
}

http_response_code($httpCode);
echo $response;
