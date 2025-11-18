<?php
/**
 * Transparent Telegram Proxy for Vercel
 * Forwards all requests and headers exactly to Laravel backend.
 */

$laravelWebhook = "https://api.ephpic.org/hooks/telegram";

// Keep the path after /api/hook.php
$path = preg_replace('#^/api/hook.php#', '', $_SERVER['REQUEST_URI']);
$path = strtok($path, '?'); // remove query string from path

// Build URL with query string
$query = $_SERVER['QUERY_STRING'] ?? '';
$url = $laravelWebhook . $path;
if ($query) {
    $url .= '?' . $query;
}

// Initialize cURL
$ch = curl_init($url);

// Forward HTTP method
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

// Forward raw body
$input = file_get_contents('php://input');
if ($input !== false && $input !== '') {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
}

// Forward all headers except host
$headers = [];
if (function_exists('getallheaders')) {
    foreach (getallheaders() as $k => $v) {
        if (strtolower($k) !== 'host') {
            $headers[] = "$k: $v";
        }
    }
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Return response
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Optional: forward response headers
curl_setopt($ch, CURLOPT_HEADER, false);

// Execute cURL
$response = curl_exec($ch);
$curlErr = curl_error($ch);

// Handle cURL errors
if ($response === false) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["error" => $curlErr]);
    exit;
}

// Return the exact backend response
// Keep Content-Type from backend if possible
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
if ($contentType) {
    header("Content-Type: $contentType");
}

echo $response;
