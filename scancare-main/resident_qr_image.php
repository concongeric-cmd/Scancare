<?php
require_once __DIR__ . '/app_config.php';
require_once __DIR__ . '/db_config.php';

$patientCode = trim($_GET['code'] ?? '');
if ($patientCode === '') {
    http_response_code(400);
    exit('Invalid resident QR code.');
}

$pdo = getDatabaseConnection();
if (!$pdo) {
    http_response_code(500);
    exit('Database connection unavailable.');
}

$stmt = $pdo->prepare('SELECT resident_id FROM resident WHERE patient_code = ? LIMIT 1');
$stmt->execute([$patientCode]);
$residentId = (int) ($stmt->fetchColumn() ?: 0);
if ($residentId <= 0) {
    http_response_code(404);
    exit('Resident not found.');
}

$qrCodeUrl = appUrl('resident_qr.php?code=' . urlencode($patientCode));
$qrRequestUrl = 'https://api.qrserver.com/v1/create-qr-code/?format=png&size=500x500&margin=20&ecc=H&data=' . urlencode($qrCodeUrl);

$qrImageData = false;
if (function_exists('curl_init')) {
    $curl = curl_init($qrRequestUrl);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'ScanCare-QR-Proxy/1.0',
    ]);
    $curlResponse = curl_exec($curl);
    $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($curlResponse !== false && $httpStatus >= 200 && $httpStatus < 300 && !empty($curlResponse)) {
        $qrImageData = $curlResponse;
    } elseif ($curlError !== '') {
        error_log('QR fetch error: ' . $curlError);
    }
}

if ($qrImageData === false) {
    $qrImageData = @file_get_contents($qrRequestUrl);
}

if ($qrImageData === false || strlen($qrImageData) < 64 || substr($qrImageData, 0, 8) !== "\x89PNG\r\n\x1a\n") {
    http_response_code(502);
    exit('Unable to generate QR code image.');
}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=300');
header('Content-Length: ' . strlen($qrImageData));
header('X-Content-Type-Options: nosniff');
ob_clean();
flush();
echo $qrImageData;
