<?php
require 'db_config.php';
$pdo = getDatabaseConnection();

echo "=== FINAL NUCLEAR CLEANUP ===\n\n";

// Close ALL pending and active sessions for all users - START FRESH
$stmt = $pdo->prepare('UPDATE user_login_sessions SET status = "closed" WHERE status IN ("pending", "active")');
$stmt->execute();
echo "Closed " . $stmt->rowCount() . " pending/active sessions\n";

echo "\n=== VERIFICATION ===\n";
$check = $pdo->prepare('SELECT COUNT(*) as cnt FROM user_login_sessions WHERE status NOT IN ("closed")');
$check->execute();
$result = $check->fetch(PDO::FETCH_ASSOC);
echo "Remaining non-closed sessions: " . $result['cnt'] . "\n";

if ($result['cnt'] == 0) {
    echo "\n✅ ALL SESSIONS CLEARED! System is ready for fresh login.\n";
} else {
    echo "\n⚠️ Some sessions remain. Forcing closure...\n";
    $force = $pdo->prepare('UPDATE user_login_sessions SET status = "closed"');
    $force->execute();
    echo "Forced closure of all remaining sessions.\n";
}
?>
