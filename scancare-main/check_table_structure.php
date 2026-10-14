<?php
require_once 'db_config.php';

try {
    $pdo = getDatabaseConnection();
    
    echo "<h2>📋 APPOINTMENTS TABLE STRUCTURE</h2>";
    
    
    $stmt = $pdo->query("DESCRIBE appointments");
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>📊 ALL APPOINTMENTS (First 10)</h2>";
    $stmt2 = $pdo->query("SELECT * FROM appointments LIMIT 10");
    
    if ($stmt2->rowCount() === 0) {
        echo "✓ Table is EMPTY (good!)";
    } else {
        echo "⚠️  Table has " . $stmt2->rowCount() . " rows:<br><br>";
        
        
        $columns = [];
        for ($i = 0; $i < $stmt2->columnCount(); $i++) {
            $meta = $stmt2->getColumnMeta($i);
            $columns[] = $meta['name'];
        }
        
        echo "<table border='1' cellpadding='10'>";
        echo "<tr>";
        foreach ($columns as $col) {
            echo "<th>" . $col . "</th>";
        }
        echo "</tr>";
        
        while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>

