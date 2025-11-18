<?php
/**
 * Fully transparent Telegram proxy for Vercel with logging
 */

$laravelWebhook = "https://api.ephpic.org/hooks/telegram";

// --- 1. Extract path after /api/hook.php
$path = preg_replace('#^/api/hook.php#', '', $_SERVER['REQUEST_URI']);
$path = strtok($path, '?'); // remove query string from path

// --- 2. Build full URL with query string
$query = $_SERVER['QUERY_STRING'] ?? '';
$url = $laravelWebhook . $path;
if ($query) {
    $url .= '?' . $query;
}

// --- 3. Optional: log raw request for debugging
$rawInput = file_get_contents("php://input");
$logData = [
    'time' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'url' => $url,
    'headers' => function_exists('getallheaders') ? getallheaders() : [],
    'body' => $rawInput,
];
file_put_contents('/tmp/telegram_proxy.log', json_encode($logData) . PHP_EOL, FILE_APPEND);

// --- 4. Initialize cURL
$ch = curl_init($url);

// --- 5. Forward HTTP method
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

// --- 6. Forward body if exists
if ($rawInput !== false && $rawInput !== '') {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $rawInput);
}

// --- 7. Forward headers except Host
$headers = [];
if (function_exists('getallheaders')) {
    foreach (getallheaders() as $k => $v) {
        if (strtolower($k) !== 'host') {
            $headers[] = "$k: $v";
        }
    }
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// --- 8. Return response
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);

// --- 9. Execute cURL
$response = curl_exec($ch);
$curlErr = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// --- 10. Handle errors
if ($response === false) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["error" => $curlErr]);
    exit;
}

// --- 11. Forward Laravel response headers & status code
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
if ($contentType) {
    header("Content-Type: $contentType");
} else {
    header("Content-Type: application/json");
}
http_response_code($httpCode);

// --- 12. Output Laravel response exactly
echo $response;
