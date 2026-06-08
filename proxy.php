<?php
// Erlaubte Ziel-URLs (Whitelist — nie offen lassen!)
$ALLOWED = [
    'bsh'        => 'https://wasserstand-nordsee.bsh.de/data/DE__508P.json',
    'mobilithek' => 'https://mobilithek.info:8443/mobilithek/api/v1.0/subscription/981881661821800448/clientPullService?subscriptionID=981881661821800448',
];

$target = $_GET['target'] ?? '';
if (!isset($ALLOWED[$target])) {
    http_response_code(400);
    exit('Unbekanntes Ziel');
}

$url = $ALLOWED[$target];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_USERAGENT      => 'Mozilla/5.0',
    CURLOPT_HTTPHEADER     => ['Accept: application/json, text/xml, */*'],
]);

$body   = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$ctype  = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$err    = curl_error($ch);
curl_close($ch);

if ($err || $body === false) {
    http_response_code(502);
    exit('Proxy-Fehler: ' . $err);
}

http_response_code($status);
header('Access-Control-Allow-Origin: *');
header('Content-Type: ' . ($ctype ?: 'application/octet-stream'));
header('Cache-Control: max-age=120');
echo $body;
