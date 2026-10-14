<?php
require 'db_config.php';
$pdo = getDatabaseConnection();
$stmt = $pdo->query('SELECT dengue_progress, rabies_progress, respiratory_progress, nutrition_progress FROM surveillance_metrics WHERE worker_id = 1 LIMIT 1');
$data = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Dengue: " . intval($data['dengue_progress']) . "%\n";
echo "Rabies: " . intval($data['rabies_progress']) . "%\n";
echo "Respiratory: " . intval($data['respiratory_progress']) . "%\n";
echo "Nutrition: " . intval($data['nutrition_progress']) . "%\n";
?>
