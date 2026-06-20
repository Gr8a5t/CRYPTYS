<?php
// A simple local PHP proxy to bypass browser Adblockers and CORS restrictions
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$url = 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum,pepe,floki,bonk&vs_currencies=usd&include_24hr_change=true';

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
// Disguise the request as a normal web browser to avoid bot blocks
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($response === false || empty($response)) {
    // If PHP itself is blocked by a strict Campus Firewall, return an error array
    echo json_encode([
        "proxy_error" => true, 
        "message" => "PHP cURL failed: " . $error
    ]);
} else {
    // Pass the pristine JSON straight to the dashboard
    echo $response;
}
