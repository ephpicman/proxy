<?php
/**
 * Transparent Telegram Bot API Proxy for Vercel
 * Usage:
 * https://your-vercel-app.vercel.app/api/proxy.php/bot<YOUR_TOKEN>/<METHOD>
 */

$telegramBase = "https://api.telegram.org";

// Strip /api/proxy.php prefix from request URI
$path = $_SERVER['REQUEST_URI'];
$path = preg_replace('#^/api/proxy.php#', '', $path);

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
    echo json_encode(["error" => curl_error($ch)]);
    exit;
}

// Output Telegram response
header('Content-Type: application/json');
echo $response;
