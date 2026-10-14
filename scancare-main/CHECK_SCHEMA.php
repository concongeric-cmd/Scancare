<?php
require_once 'db_config.php';

echo "<h1>🔍 Database Debug - Check Schema</h1>";

try {
    $pdo = getDatabaseConnection();
    
    
    $result = $pdo->query("DESCRIBE users");
    $columns = $result->fetchAll();
    
    echo "<h2>Users Table Columns:</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $has_middle_initial = false;
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . ($col['Extra'] ?? '') . "</td>";
        echo "</tr>";
        
        if ($col['Field'] === 'middle_initial') {
            $has_middle_initial = true;
        }
    }
    echo "</table>";
    
    echo "<h2>Status:</h2>";
    if ($has_middle_initial) {
        echo "✅ middle_initial column EXISTS";
    } else {
        echo "❌ middle_initial column MISSING - This is the problem!";
        echo "<br><br>";
        echo "<p>Apply database/schema.sql to install the canonical schema.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>
<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    h1 { color: #333; }
    h2 { color: #667eea; margin-top: 30px; }
    table { background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 5px; }
    td { padding: 12px; }
    th { background: #667eea; color: white; }
</style>

