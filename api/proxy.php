<?php
/**
 * Simple Telegram API Proxy
 * Just place this file as index.php on your host.
 * Example call:
 * https://yourhost.com/botTOKEN/getMe
 */

$telegramBase = "https://api.telegram.org";

// Build full Telegram URL
$path = $_SERVER['REQUEST_URI']; // includes /botTOKEN/...
$url = $telegramBase . $path;

// Initialize cURL
$ch = curl_init($url);

// Forward HTTP method
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

// Forward raw body if exists
$input = file_get_contents("php://input");
if (!empty($input)) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
}

// Forward headers except host
$headers = [];
if (function_exists('getallheaders')) {
    foreach (getallheaders() as $k => $v) {
        if (strtolower($k) !== 'host') $headers[] = "$k: $v";
    }
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Return response
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute
$response = curl_exec($ch);
if ($response === false) {
    http_response_code(500);
    echo "cURL Error: " . curl_error($ch);
    exit;
}

// Output Telegram response
echo $response;
