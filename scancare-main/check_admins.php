<?php
require_once 'db_config.php';
$pdo = getDatabaseConnection();
$query = 'SELECT admin_id, username, email, full_name, role FROM system_admins LIMIT 5';
try {
    $stmt = $pdo->query($query);
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Admins in database:\n";
    foreach ($admins as $admin) {
        echo "  - {$admin['username']} ({$admin['email']}) [{$admin['role']}]\n";
    }
    if (empty($admins)) {
        echo "  (No admins found)\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
