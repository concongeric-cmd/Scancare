<?php
require_once 'db_config.php';

$pdo = getDatabaseConnection();
if (!$pdo) {
    die("Database connection failed");
}

// Check current data
$stmt = $pdo->query('SELECT * FROM surveillance_metrics ORDER BY updated_at DESC LIMIT 1');
$current = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h1>Current Surveillance Data</h1>";
if (!$current) {
    echo "<p style='color: red;'><strong>NO DATA IN DATABASE</strong></p>";
} else {
    echo "<pre>";
    print_r($current);
    echo "</pre>";
}

// Insert test data matching your reference image
echo "<h2>Inserting Test Data (Matching Your Reference Image)</h2>";

try {
    // Check if we have a worker_id to use
    $workerStmt = $pdo->query('SELECT user_id FROM users WHERE user_type = "bhw" LIMIT 1');
    $worker = $workerStmt->fetch(PDO::FETCH_ASSOC);
    $worker_id = $worker['user_id'] ?? 1;

    $pdo->exec("CREATE TABLE IF NOT EXISTS surveillance_metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        worker_id INT NOT NULL,
        reporting_period VARCHAR(255) DEFAULT NULL,
        dengue_count INT DEFAULT 0,
        dengue_trend VARCHAR(255) DEFAULT NULL,
        dengue_meta VARCHAR(255) DEFAULT NULL,
        dengue_progress INT DEFAULT 0,
        rabies_count INT DEFAULT 0,
        rabies_trend VARCHAR(255) DEFAULT NULL,
        rabies_meta VARCHAR(255) DEFAULT NULL,
        rabies_progress INT DEFAULT 0,
        respiratory_count INT DEFAULT 0,
        respiratory_trend VARCHAR(255) DEFAULT NULL,
        respiratory_meta VARCHAR(255) DEFAULT NULL,
        respiratory_progress INT DEFAULT 0,
        nutrition_count INT DEFAULT 0,
        nutrition_trend VARCHAR(255) DEFAULT NULL,
        nutrition_meta VARCHAR(255) DEFAULT NULL,
        nutrition_progress INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_worker_id (worker_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $testData = [
        'worker_id' => $worker_id,
        'reporting_period' => 'Current 4 Weeks',
        'dengue_count' => 24,
        'dengue_trend' => 'high',
        'dengue_meta' => 'No active cases reported.',
        'dengue_progress' => 70,  // Match reference visual fill
        'rabies_count' => 10,
        'rabies_trend' => 'Baseline',
        'rabies_meta' => '0% coverage | No residents vaccinated.',
        'rabies_progress' => 30,
        'respiratory_count' => 1,
        'respiratory_trend' => 'Baseline',
        'respiratory_meta' => 'No active cases reported.',
        'respiratory_progress' => 15,
        'nutrition_count' => 0,
        'nutrition_trend' => 'Baseline',
        'nutrition_meta' => 'No active cases reported.',
        'nutrition_progress' => 0
    ];

    $sql = "INSERT INTO surveillance_metrics (
        worker_id, reporting_period, dengue_count, dengue_trend, dengue_meta, dengue_progress,
        rabies_count, rabies_trend, rabies_meta, rabies_progress,
        respiratory_count, respiratory_trend, respiratory_meta, respiratory_progress,
        nutrition_count, nutrition_trend, nutrition_meta, nutrition_progress
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        reporting_period = VALUES(reporting_period),
        dengue_count = VALUES(dengue_count),
        dengue_trend = VALUES(dengue_trend),
        dengue_meta = VALUES(dengue_meta),
        dengue_progress = VALUES(dengue_progress),
        rabies_count = VALUES(rabies_count),
        rabies_trend = VALUES(rabies_trend),
        rabies_meta = VALUES(rabies_meta),
        rabies_progress = VALUES(rabies_progress),
        respiratory_count = VALUES(respiratory_count),
        respiratory_trend = VALUES(respiratory_trend),
        respiratory_meta = VALUES(respiratory_meta),
        respiratory_progress = VALUES(respiratory_progress),
        nutrition_count = VALUES(nutrition_count),
        nutrition_trend = VALUES(nutrition_trend),
        nutrition_meta = VALUES(nutrition_meta),
        nutrition_progress = VALUES(nutrition_progress)";

    $insertStmt = $pdo->prepare($sql);
    $result = $insertStmt->execute([
        $testData['worker_id'],
        $testData['reporting_period'],
        $testData['dengue_count'],
        $testData['dengue_trend'],
        $testData['dengue_meta'],
        $testData['dengue_progress'],
        $testData['rabies_count'],
        $testData['rabies_trend'],
        $testData['rabies_meta'],
        $testData['rabies_progress'],
        $testData['respiratory_count'],
        $testData['respiratory_trend'],
        $testData['respiratory_meta'],
        $testData['respiratory_progress'],
        $testData['nutrition_count'],
        $testData['nutrition_trend'],
        $testData['nutrition_meta'],
        $testData['nutrition_progress']
    ]);

    if ($result) {
        echo "<p style='color: green;'><strong>✓ Test data inserted successfully!</strong></p>";
        echo "<p>Worker ID: " . htmlspecialchars($worker_id) . "</p>";
        echo "<pre>";
        print_r($testData);
        echo "</pre>";
        
        // Verify
        $verifyStmt = $pdo->query('SELECT * FROM surveillance_metrics ORDER BY updated_at DESC LIMIT 1');
        $verified = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        echo "<h3>Verified Data in Database:</h3>";
        echo "<pre>";
        print_r($verified);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'><strong>✗ Failed to insert test data</strong></p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error: " . htmlspecialchars($e->getMessage()) . "</strong></p>";
}

echo "<p><a href='dashboard.php'>Back to Resident Dashboard</a> | <a href='surveillance.php'>Go to BHW Surveillance</a></p>";
?>
