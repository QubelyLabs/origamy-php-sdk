<?php

/**
 * Minimal PHP built-in server handler.
 * Captures each incoming HTTP request and writes details to a temp file.
 * The temp file path is read from the ORIGAMY_CAPTURE_FILE env variable.
 */
$tmpFile = getenv('ORIGAMY_CAPTURE_FILE');

$body = (string) file_get_contents('php://input');

$method      = $_SERVER['REQUEST_METHOD'] ?? '';
$path        = isset($_SERVER['REQUEST_URI'])
    ? (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
    : '';
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

$authUser = '';
$authPass = '';
$authOK   = false;
$rawAuth  = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/^Basic\s+(.+)$/i', $rawAuth, $m)) {
    $decoded  = (string) base64_decode($m[1]);
    $parts    = explode(':', $decoded, 2);
    $authUser = $parts[0];
    $authPass = $parts[1] ?? '';
    $authOK   = true;
}

$data = json_encode([
    'method'      => $method,
    'path'        => $path,
    'contentType' => $contentType,
    'authUser'    => $authUser,
    'authPass'    => $authPass,
    'authOK'      => $authOK,
    'body'        => json_decode($body, true),
]);

if ($tmpFile !== false && $tmpFile !== '') {
    file_put_contents($tmpFile, $data);
}

http_response_code(200);
header('Content-Type: application/json');
echo '{}';
