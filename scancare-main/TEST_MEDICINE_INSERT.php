<?php
require_once 'db_config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🧪 TEST MEDICINE INSERT</h1>";
echo "<hr>";


try {
    $pdo = getDatabaseConnection();
    
    $medicine_name = "Test Medicine - " . time();
    $quantity = 100;
    $condition = "Test condition";
    
    echo "<p><strong>Attempting to insert:</strong></p>";
    echo "<pre>";
    echo "Medicine Name: $medicine_name\n";
    echo "Quantity: $quantity\n";
    echo "Condition: $condition\n";
    echo "</pre>";
    
    $stmt = $pdo->prepare("
        INSERT INTO medicine (medicine_name, quantity_stock, `condition`) 
        VALUES (?, ?, ?)
    ");
    
    if ($stmt->execute([$medicine_name, $quantity, $condition])) {
        echo "<div style='color: green; background: #e6ffe6; padding: 15px; border-radius: 5px;'>";
        echo "<strong>✓ SUCCESS!</strong> Medicine inserted!<br>";
        echo "Medicine ID: " . $pdo->lastInsertId() . "<br>";
        echo "</div>";
        
        
        echo "<h3>All Medicines in Database:</h3>";
        $stmt = $pdo->query("SELECT medicine_id, medicine_name, quantity_stock, `condition` FROM medicine ORDER BY medicine_id DESC");
        $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>ID</th>";
        echo "<th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Name</th>";
        echo "<th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Stock</th>";
        echo "<th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Condition</th>";
        echo "</tr>";
        
        foreach ($medicines as $med) {
            echo "<tr>";
            echo "<td style='border: 1px solid #ddd; padding: 10px;'>" . $med['medicine_id'] . "</td>";
            echo "<td style='border: 1px solid #ddd; padding: 10px;'>" . $med['medicine_name'] . "</td>";
            echo "<td style='border: 1px solid #ddd; padding: 10px;'>" . $med['quantity_stock'] . "</td>";
            echo "<td style='border: 1px solid #ddd; padding: 10px;'>" . $med['condition'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<div style='color: red; background: #ffe6e6; padding: 15px; border-radius: 5px;'>";
        echo "<strong>✗ FAILED!</strong> Medicine NOT inserted<br>";
        echo "SQL Error: " . $stmt->errorInfo()[2] . "<br>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 15px; border-radius: 5px;'>";
    echo "<strong>✗ ERROR:</strong> " . $e->getMessage();
    echo "</div>";
}
?>

