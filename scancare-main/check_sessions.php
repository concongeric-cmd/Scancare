<?php
require 'db_config.php';
$pdo = getDatabaseConnection();

echo "=== SESSIONS IN DATABASE ===\n\n";
$stmt = $pdo->prepare('SELECT user_id, user_type, status, COUNT(*) as cnt FROM user_login_sessions GROUP BY user_id, user_type, status ORDER BY user_id');
$stmt->execute();
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['user_id'] . " (" . $row['user_type'] . "): " . $row['status'] . " x" . $row['cnt'] . "\n";
}

echo "\n=== CLEARING ALL SESSIONS OLDER THAN 10 MINUTES ===\n";
$cleanup = $pdo->prepare('UPDATE user_login_sessions SET status = "closed" WHERE updated_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)');
$cleanup->execute();
echo "Cleaned up " . $cleanup->rowCount() . " old sessions\n";

echo "\n=== REMAINING SESSIONS ===\n";
$stmt = $pdo->prepare('SELECT user_id, user_type, status, COUNT(*) as cnt FROM user_login_sessions WHERE status != "closed" GROUP BY user_id, user_type, status ORDER BY user_id');
$stmt->execute();
$remaining = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($remaining)) {
    echo "No active sessions\n";
} else {
    foreach ($remaining as $row) {
        echo $row['user_id'] . " (" . $row['user_type'] . "): " . $row['status'] . " x" . $row['cnt'] . "\n";
    }
}
echo "\nDone!\n";
?>
