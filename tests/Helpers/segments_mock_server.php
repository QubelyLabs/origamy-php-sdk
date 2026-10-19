<?php

/**
 * PHP built-in server handler mocking POST /v1/segments/{id}/members.
 *
 * Appends each request as one JSON line to ORIGAMY_CAPTURE_FILE, then answers
 * with the next entry of the JSON array in ORIGAMY_RESPONSES_FILE (the last
 * entry repeats). An entry is {status, body, headers} or {"echo": true}, which
 * answers like the API: every row is used except those whose email is "bad".
 */
$captureFile   = (string) getenv('ORIGAMY_CAPTURE_FILE');
$responsesFile = (string) getenv('ORIGAMY_RESPONSES_FILE');

$raw  = (string) file_get_contents('php://input');
$body = json_decode($raw, true);

$authUser = '';
$authPass = '';
if (preg_match('/^Basic\s+(.+)$/i', $_SERVER['HTTP_AUTHORIZATION'] ?? '', $m)) {
    [$authUser, $authPass] = array_pad(explode(':', (string) base64_decode($m[1]), 2), 2, '');
}

file_put_contents($captureFile, json_encode([
    'method'   => $_SERVER['REQUEST_METHOD'] ?? '',
    'path'     => (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH),
    'authUser' => $authUser,
    'authPass' => $authPass,
    'raw'      => $raw,
    'body'     => $body,
]) . "\n", FILE_APPEND | LOCK_EX);

$n         = count(file($captureFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
$responses = json_decode((string) file_get_contents($responsesFile), true) ?: [['echo' => true]];
$response  = $responses[min($n, count($responses)) - 1];

header('Content-Type: application/json');
if (!empty($response['echo'])) {
    $result = ['segment_id' => 42, 'added' => 0, 'removed' => 0, 'not_found' => 0, 'rejected' => []];
    foreach (['add', 'remove'] as $list) {
        foreach ($body[$list] ?? [] as $index => $member) {
            if (($member['email'] ?? '') === 'bad') {
                $result['rejected'][] = ['list' => $list, 'index' => $index, 'error' => 'invalid email'];
            } elseif ($list === 'add') {
                $result['added']++;
            } else {
                $result['removed']++;
            }
        }
    }
    echo json_encode($result);
    return;
}

http_response_code($response['status'] ?? 200);
foreach ($response['headers'] ?? [] as $name => $value) {
    header($name . ': ' . $value);
}
echo is_string($response['body']) ? $response['body'] : json_encode($response['body']);
