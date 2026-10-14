<?php
// Simple Semaphore SMS test script
// Usage (from project root): php send_sms_test.php

$apikey = 'f43478ec3207194df9db9f90ba337fa0';
$number = '09984026995';
$message = 'Hi this is RHU II and your appointment is getting close to time';
$sender = 'SCANCARE';

$ch = curl_init('https://api.semaphore.co/api/v4/messages');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'apikey' => $apikey,
    'number' => $number,
    'message' => $message,
    'SenderName' => $sender,
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// temporarily disable SSL verification for local testing
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$err = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP CODE: $httpCode\n";
if ($err) {
    echo "cURL ERROR: $err\n";
}

echo "RESPONSE:\n";
echo $response . "\n";

// Exit code 0 for success-like responses (200), else 1
exit(($httpCode >= 200 && $httpCode < 300) ? 0 : 1);
