<?php


require_once 'db_config.php';

echo "<div style='font-family: Arial; padding: 20px; background: #f5f5f5;'>";
echo "<h1>Services Table Check</h1>";

try {
    $pdo = getDatabaseConnection();
    
    
    $check = $pdo->query("SHOW TABLES LIKE 'services'");
    
    if ($check->rowCount() > 0) {
        echo "<h2>Available Services:</h2>";
        
        $services = $pdo->query("SELECT * FROM services")->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($services)) {
            echo "<p style='color: red;'><strong>⚠️ Services table is EMPTY!</strong></p>";
            echo "<p>You need to insert default services first.</p>";
            
            echo "<h3>SQL to insert default services:</h3>";
            echo "<pre style='background: white; padding: 15px; border-radius: 5px; overflow-x: auto;'>" . htmlspecialchars("INSERT INTO services (service_name, description, is_active) VALUES
('General Check-up', 'Routine health check-up', TRUE),
('Vaccination', 'Immunization services', TRUE),
('Follow-up', 'Follow-up consultation', TRUE),
('Health Concerns', 'Consultation for specific health concerns', TRUE);") . "</pre>";
        } else {
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
            echo "<tr style='background: #667eea; color: white;'>";
            echo "<th>Service ID</th>";
            echo "<th>Service Name</th>";
            echo "<th>Description</th>";
            echo "<th>Active</th>";
            echo "</tr>";
            
            foreach ($services as $service) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($service['service_id'] ?? $service['id'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($service['service_name'] ?? $service['name'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($service['description'] ?? 'N/A') . "</td>";
                echo "<td>" . (($service['is_active'] ?? $service['active'] ?? 0) ? '✅ Yes' : '❌ No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color: red;'><strong>❌ Services table does NOT exist</strong></p>";
        echo "<p>You need to create it. Click the button below.</p>";
        echo "<button onclick=\"alert('Create services table first then refresh')\" style='padding: 10px 20px; background: #d32f2f; color: white; border: none; border-radius: 5px; cursor: pointer;'>Create Services Table</button>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";
?>

