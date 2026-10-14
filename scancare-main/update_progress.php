<?php
require_once 'db_config.php';

$pdo = getDatabaseConnection();
if (!$pdo) {
    die("Database connection failed");
}

echo "<h1>Updating Surveillance Progress Values</h1>";

try {
    // Update to progress values that will show visible fills matching the reference
    $updateSql = "UPDATE surveillance_metrics SET
        dengue_progress = 70,      -- 24 cases, high impact
        rabies_progress = 35,      -- 10% coverage
        respiratory_progress = 25, -- 1 active case
        nutrition_progress = 0     -- No cases
        WHERE worker_id = 1";

    $pdo->exec($updateSql);
    
    echo "<p style='color: green;'><strong>✓ Progress values updated!</strong></p>";
    
    // Show the updated data
    $stmt = $pdo->query('SELECT * FROM surveillance_metrics WHERE worker_id = 1 LIMIT 1');
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Updated Data in Database:</h2>";
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    
    echo "<p><strong>Summary:</strong></p>";
    echo "<ul>";
    echo "<li>Dengue: " . $data['dengue_count'] . " cases (high) - Progress bar: " . $data['dengue_progress'] . "%</li>";
    echo "<li>Rabies: " . $data['rabies_count'] . "% coverage (Askal) - Progress bar: " . $data['rabies_progress'] . "%</li>";
    echo "<li>Respiratory: " . $data['respiratory_count'] . " cases (Baseline) - Progress bar: " . $data['respiratory_progress'] . "%</li>";
    echo "<li>Nutrition: " . $data['nutrition_count'] . " cases - Progress bar: " . $data['nutrition_progress'] . "%</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error: " . htmlspecialchars($e->getMessage()) . "</strong></p>";
}

echo "<hr>";
echo "<p><strong>Next Step:</strong> <a href='dashboard.php'><strong>Reload the Resident Dashboard with Ctrl+F5 (hard refresh)</strong></a></p>";
echo "<p>The bars should now fill with the progress values and match your reference image.</p>";
?>
