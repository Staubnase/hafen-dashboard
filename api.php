<?php
// Da das Dashboard auf einer eigenen Subdomain läuft, wird kein CORS-Header benötigt.
// Der Proxy ist nur für same-origin Requests vorgesehen.

$ALLOWED = [
    'bsh'        => 'https://gdi.bsh.de/ldproxy/rest/services/WaterLevelForecast/collections/waterlevelforecastdata/items/hamburg_st-pauli?f=json',
    'mobilithek' => 'https://mobilithek.info:8443/mobilithek/api/v1.0/subscription/981881661821800448/clientPullService?subscriptionID=981881661821800448',
];

// Nur same-origin Requests erlauben
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$host   = $_SERVER['HTTP_HOST'] ?? '';
$allowedHost = 'https://' . $host;
if ($origin && $origin !== $allowedHost) {
    http_response_code(403);
    exit('Forbidden');
}

$target = $_GET['target'] ?? '';

// Frontend-Konfiguration (API-Keys) aus config.php — liegt nur auf dem Server
if ($target === 'config') {
    $config = file_exists(__DIR__ . '/config.php') ? include __DIR__ . '/config.php' : [];
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode(['tomtomKey' => $config['tomtom_api_key'] ?? '']);
    exit;
}

if (!isset($ALLOWED[$target])) {
    http_response_code(400);
    exit('Unbekanntes Ziel');
}

$url = $ALLOWED[$target];

$ch = curl_init($url);
$opts = [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 3,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_USERAGENT      => 'HafenDashboard/2.0',
    CURLOPT_HTTPHEADER     => ['Accept: application/json, text/xml, */*'],
];

// Mobilithek verlangt ein Client-Zertifikat (mTLS). Die Datei liegt EINE Ebene
// über dem Webroot (außerhalb des öffentlich erreichbaren Verzeichnisses) und
// wird hier nur eingebunden, wenn vorhanden — sonst läuft der Request ohne Cert
// (und Mobilithek antwortet mit 400 "No required SSL certificate was sent").
if ($target === 'mobilithek') {
    $certFile = dirname(__DIR__) . '/mobilithek-cert/keyandcerts.pem';
    $certPass = getenv('MOBILITHEK_CERT_PASSWORD') ?: '';
    if (file_exists($certFile)) {
        $opts[CURLOPT_SSLCERT]     = $certFile;
        $opts[CURLOPT_SSLCERTTYPE] = 'PEM';
        $opts[CURLOPT_SSLKEY]      = $certFile;
        $opts[CURLOPT_SSLKEYTYPE]  = 'PEM';
        if ($certPass !== '') {
            $opts[CURLOPT_KEYPASSWD] = $certPass;
        }
    }
}

curl_setopt_array($ch, $opts);

$body   = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$ctype  = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$err    = curl_error($ch);
curl_close($ch);

if ($err || $body === false) {
    http_response_code(502);
    exit('Proxy-Fehler');
}

http_response_code($status);
header('Content-Type: ' . ($ctype ?: 'application/octet-stream'));
header('Cache-Control: max-age=120, private');
header('X-Content-Type-Options: nosniff');
echo $body;
