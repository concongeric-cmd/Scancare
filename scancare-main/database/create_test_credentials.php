<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/db_config.php';

if (!getEnvironmentBoolean('ALLOW_TEST_ACCOUNTS', false)) {
    fwrite(STDERR, 'Set ALLOW_TEST_ACCOUNTS=true in .env before running this command.' . PHP_EOL);
    exit(1);
}

function generateTestPassword($length = 20) {
    $groups = [
        'ABCDEFGHJKLMNPQRSTUVWXYZ',
        'abcdefghijkmnopqrstuvwxyz',
        '23456789',
        '!@#$%*-_'
    ];

    $characters = '';
    foreach ($groups as $group) {
        $characters .= $group[random_int(0, strlen($group) - 1)];
    }

    $all = implode('', $groups);
    while (strlen($characters) < $length) {
        $characters .= $all[random_int(0, strlen($all) - 1)];
    }

    $array = str_split($characters);
    for ($index = count($array) - 1; $index > 0; $index--) {
        $swap = random_int(0, $index);
        [$array[$index], $array[$swap]] = [$array[$swap], $array[$index]];
    }

    return implode('', $array);
}

function generatePatientCodeForSeed(PDO $pdo) {
    for ($attempt = 0; $attempt < 100; $attempt++) {
        $code = (string) random_int(100000, 999999);
        $stmt = $pdo->prepare('SELECT 1 FROM resident WHERE patient_code = ? LIMIT 1');
        $stmt->execute([$code]);
        if (!$stmt->fetchColumn()) {
            return $code;
        }
    }

    throw new RuntimeException('Unable to generate a unique patient code.');
}

function ensureTestCredentialColumns(PDO $pdo): void {
    $requiredColumns = [
        'resident' => [
            'full_name' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'first_name' => 'VARCHAR(100) NULL',
            'last_name' => 'VARCHAR(100) NULL',
            'user_name' => 'VARCHAR(255) NULL',
            'email' => 'VARCHAR(255) NULL',
            'password_hash' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'date_of_birth' => 'DATE NULL',
            'sex' => 'VARCHAR(20) NULL',
            'address' => 'VARCHAR(500) NULL',
            'barangay' => 'VARCHAR(150) NULL',
            'city' => 'VARCHAR(150) NULL',
            'province' => 'VARCHAR(150) NULL',
            'postal_code' => 'VARCHAR(20) NULL',
            'contact_number' => 'VARCHAR(30) NULL',
            'phone' => 'VARCHAR(30) NULL',
            'is_senior_citizen' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'qr_code_id' => 'VARCHAR(32) NULL',
            'patient_code' => 'CHAR(6) NULL',
            'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1',
            'deleted_at' => 'DATETIME NULL',
        ],
        'health_worker' => [
            'full_name' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'first_name' => 'VARCHAR(100) NULL',
            'last_name' => 'VARCHAR(100) NULL',
            'email' => 'VARCHAR(255) NULL',
            'password_hash' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'role' => "VARCHAR(50) NOT NULL DEFAULT 'bhw'",
            'contact_number' => 'VARCHAR(30) NULL',
            'phone' => 'VARCHAR(30) NULL',
            'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1',
            'deleted_at' => 'DATETIME NULL',
        ],
        'system_admins' => [
            'username' => "VARCHAR(100) NOT NULL DEFAULT ''",
            'email' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'password_hash' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'full_name' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'role' => "VARCHAR(50) NOT NULL DEFAULT 'admin'",
            'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1',
        ],
    ];

    foreach ($requiredColumns as $table => $columns) {
        foreach ($columns as $column => $definition) {
            if (!ensureTableColumn($pdo, $table, $column, $definition)) {
                throw new RuntimeException("Unable to add required column {$table}.{$column}.");
            }
        }
    }
}

$pdo = getDatabaseConnection();
if (!$pdo) {
    fwrite(STDERR, 'Database connection failed.' . PHP_EOL);
    exit(1);
}

try {
    ensureTestCredentialColumns($pdo);
} catch (Throwable $error) {
    error_log('Test credential schema update failed: ' . $error->getMessage());
    fwrite(STDERR, 'Test credential schema update failed. Check the server error log.' . PHP_EOL);
    exit(1);
}

// Keep seeded credentials stable. Re-running this script must not invalidate
// another tester's login by replacing the stored password with a new random one.
$residentPassword = 'TestResident!2026';
$workerPassword = 'TestBhw!2026';
$adminPassword = 'TestAdmin!2026';
$patientCode = generatePatientCodeForSeed($pdo);
$qrId = random_int(1000000000,9999999999);

try {
    $pdo->beginTransaction();

    $qrStmt = $pdo->prepare('INSERT INTO qr_code (qr_id) VALUES (?)');
    $qrStmt->execute([$qrId]);

    $residentStmt = $pdo->prepare("INSERT INTO resident
        (full_name, first_name, last_name, user_name, email, password_hash, date_of_birth, sex, address, barangay, city, province, postal_code, contact_number, phone, is_senior_citizen, qr_code_id, patient_code, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            full_name = VALUES(full_name),
            first_name = VALUES(first_name),
            last_name = VALUES(last_name),
            user_name = VALUES(user_name),
            password_hash = VALUES(password_hash),
            contact_number = VALUES(contact_number),
            phone = VALUES(phone),
            patient_code = VALUES(patient_code),
            qr_code_id = VALUES(qr_code_id),
            is_active = 1,
            deleted_at = NULL");
    $residentStmt->execute([
        'Test Resident',
        'Test',
        'Resident',
        'Test Resident',
        'test.resident@scancare.it.com',
        password_hash($residentPassword, PASSWORD_BCRYPT, ['cost' => 12]),
        '1995-01-15',
        'Other',
        'Test Account',
        'Test Barangay',
        'Test City',
        'Test Province',
        '0000',
        '00000000001',
        '00000000001',
        $qrId,
        $patientCode
    ]);

    $workerStmt = $pdo->prepare("INSERT INTO health_worker
        (full_name, first_name, last_name, email, password_hash, role, contact_number, phone, is_active)
        VALUES (?, ?, ?, ?, ?, 'bhw', ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            full_name = VALUES(full_name),
            first_name = VALUES(first_name),
            last_name = VALUES(last_name),
            password_hash = VALUES(password_hash),
            role = 'bhw',
            contact_number = VALUES(contact_number),
            phone = VALUES(phone),
            is_active = 1,
            deleted_at = NULL");
    $workerStmt->execute([
        'Test Health Worker',
        'Test',
        'Health Worker',
        'test.bhw@scancare.it.com',
        password_hash($workerPassword, PASSWORD_BCRYPT, ['cost' => 12]),
        '00000000002',
        '00000000002'
    ]);

    $adminStmt = $pdo->prepare("INSERT INTO system_admins
        (username, email, password_hash, full_name, role, is_active)
        VALUES (?, ?, ?, ?, 'super_admin', 1)
        ON DUPLICATE KEY UPDATE
            password_hash = VALUES(password_hash),
            full_name = VALUES(full_name),
            role = 'super_admin',
            is_active = 1");
    $adminStmt->execute([
        'testadmin',
        'test.admin@scancare.it.com',
        password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]),
        'Test System Administrator'
    ]);

    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Test credential creation failed: ' . $error->getMessage());
    fwrite(STDERR, 'Test credential creation failed. Check the server error log.' . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, 'Resident email: test.resident@scancare.it.com' . PHP_EOL);
fwrite(STDOUT, 'Resident password: ' . $residentPassword . PHP_EOL);
fwrite(STDOUT, 'BHW email: test.bhw@scancare.it.com' . PHP_EOL);
fwrite(STDOUT, 'BHW password: ' . $workerPassword . PHP_EOL);
fwrite(STDOUT, 'Admin username: testadmin' . PHP_EOL);
fwrite(STDOUT, 'Admin email: test.admin@scancare.it.com' . PHP_EOL);
fwrite(STDOUT, 'Admin password: ' . $adminPassword . PHP_EOL);
fwrite(STDOUT, 'Set ALLOW_TEST_ACCOUNTS=false after saving these credentials.' . PHP_EOL);
