<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/db_config.php';

$failures = [];
$warnings = [];

$requiredEnvironment = ['APP_URL', 'DB_HOST', 'DB_USER', 'DB_NAME'];
foreach ($requiredEnvironment as $key) {
    if (getEnvironmentValue($key) === '') {
        $failures[] = $key . ' is missing';
    }
}

if (isProductionEnvironment() && getEnvironmentValue('DB_PASS') === '') {
    $failures[] = 'DB_PASS is missing';
}

if (parse_url(appUrl(), PHP_URL_SCHEME) !== 'https') {
    $failures[] = 'APP_URL must use https';
}

if (parse_url(appUrl(), PHP_URL_HOST) !== 'scancare.it.com') {
    $warnings[] = 'APP_URL is not scancare.it.com';
}

$requiredExtensions = ['curl', 'fileinfo', 'json', 'mbstring', 'openssl', 'pdo_mysql'];
foreach ($requiredExtensions as $extension) {
    if (!extension_loaded($extension)) {
        $failures[] = $extension . ' PHP extension is missing';
    }
}

$writableDirectories = [
    dirname(__DIR__) . '/logs',
    dirname(__DIR__) . '/assets/uploads/profile_pics'
];

foreach ($writableDirectories as $directory) {
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        $failures[] = $directory . ' could not be created';
        continue;
    }

    if (!is_writable($directory)) {
        $failures[] = $directory . ' is not writable';
    }
}

$pdo = getDatabaseConnection();
if (!$pdo) {
    $failures[] = 'Database connection failed';
} else {
    $requiredTables = [
        'resident',
        'health_worker',
        'system_admins',
        'qr_code',
        'appointment',
        'patient_record',
        'medical_history',
        'medicine',
        'medicine_dispensing',
        'surveillance_metrics',
        'user_login_sessions',
        'activity_logs',
        'account_requests',
        'sms_log',
        'service_request'
    ];

    foreach ($requiredTables as $table) {
        $stmt = $pdo->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1');
        $stmt->execute([$table]);
        if (!$stmt->fetchColumn()) {
            $failures[] = 'Database table is missing: ' . $table;
        }
    }
}

if (getEnvironmentValue('SEMAPHORE_API_KEY') === '' && getEnvironmentValue('FMCSMS_API_KEY') === '') {
    $warnings[] = 'No SMS provider is configured';
}

foreach ($warnings as $warning) {
    fwrite(STDOUT, 'WARNING: ' . $warning . PHP_EOL);
}

foreach ($failures as $failure) {
    fwrite(STDERR, 'FAIL: ' . $failure . PHP_EOL);
}

if ($failures) {
    exit(1);
}

fwrite(STDOUT, 'READY: application configuration and database checks passed' . PHP_EOL);
