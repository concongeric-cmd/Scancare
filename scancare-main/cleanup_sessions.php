<?php
require 'db_config.php';
$pdo = getDatabaseConnection();

echo "=== NUCLEAR CLEANUP - CLOSING ALL OLD SESSIONS ===\n\n";

// Close ALL pending sessions older than 5 minutes
$closePending = $pdo->prepare('UPDATE user_login_sessions SET status = "closed" WHERE status = "pending" AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)');
$closePending->execute();
echo "Closed " . $closePending->rowCount() . " pending sessions\n";

// Close ALL active sessions older than 5 minutes
$closeActive = $pdo->prepare('UPDATE user_login_sessions SET status = "closed" WHERE status = "active" AND updated_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)');
$closeActive->execute();
echo "Closed " . $closeActive->rowCount() . " active sessions\n";

// Close ALL rejected sessions
$closeRejected = $pdo->prepare('UPDATE user_login_sessions SET status = "closed" WHERE status = "rejected"');
$closeRejected->execute();
echo "Closed " . $closeRejected->rowCount() . " rejected sessions\n";

echo "\n=== REMAINING ACTIVE/PENDING SESSIONS ===\n";
$stmt = $pdo->prepare('SELECT user_id, user_type, status, COUNT(*) as cnt FROM user_login_sessions WHERE status NOT IN ("closed") GROUP BY user_id, user_type, status');
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($results)) {
    echo "All sessions cleaned! Ready for fresh login.\n";
} else {
    foreach ($results as $row) {
        echo $row['user_id'] . " (" . $row['user_type'] . "): " . $row['status'] . " x" . $row['cnt'] . "\n";
    }
}
echo "\nCleanup complete!\n";
?>
