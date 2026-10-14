<?php
/**
 * One-time emergency password recovery for the legacy users table.
 * Remove this file and the marker after the recovery window is complete.
 */

declare(strict_types=1);

$completionMarker = __DIR__ . DIRECTORY_SEPARATOR . '.password_recovery_completed';
header('Content-Type: text/html; charset=utf-8');

if (is_file($completionMarker)) {
    http_response_code(410);
    echo '<h1>Password recovery unavailable</h1><p>This recovery script has already run and is blocked.</p>';
    exit;
}

require_once __DIR__ . '/db_config.php';

try {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        throw new RuntimeException('Database connection failed.');
    }

    if (!hasTableColumn($pdo, 'users', 'password')) {
        throw new RuntimeException('The users.password column was not found. No records were changed.');
    }

    $passwordHash = password_hash('Password123!', PASSWORD_BCRYPT);
    if ($passwordHash === false) {
        throw new RuntimeException('Password hash generation failed. No records were changed.');
    }

    $pdo->beginTransaction();
    $statement = $pdo->prepare('UPDATE users SET password = ?');
    $statement->execute([$passwordHash]);
    $updatedCount = $statement->rowCount();
    $pdo->commit();

    $markerWritten = file_put_contents($completionMarker, 'completed=' . date('c') . PHP_EOL, LOCK_EX);
    if ($markerWritten === false) {
        throw new RuntimeException('Passwords were updated, but the completion marker could not be written. Remove this file immediately and inspect filesystem permissions.');
    }

    echo '<h1>Password recovery completed</h1>';
    echo '<p>Updated <strong>' . htmlspecialchars((string) $updatedCount, ENT_QUOTES, 'UTF-8') . '</strong> users.</p>';
    echo '<p>Temporary password: <strong>Password123!</strong></p>';
    echo '<p>This endpoint is now blocked. Delete <code>fix_passwords.php</code> and the completion marker after confirming access.</p>';
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    error_log('PASSWORD_RECOVERY_ERROR: ' . $error->getMessage());
    echo '<h1>Password recovery failed</h1>';
    echo '<p>' . htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
}
