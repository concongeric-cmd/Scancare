<?php


require_once 'db_config.php';

echo "<div style='font-family: Arial; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; min-height: 100vh;'>";

try {
    $pdo = getDatabaseConnection();
    
    if ($pdo === false) {
        throw new Exception("Database connection failed!");
    }
    
    echo "<h1>🔧 Activating Services</h1>";
    
    
    $update = $pdo->prepare("UPDATE services SET is_active = TRUE WHERE is_active = FALSE");
    $result = $update->execute();
    
    if ($result) {
        $affected = $update->rowCount();
        echo "<div style='background: white; color: #333; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
        echo "<p style='color: #4caf50; font-weight: bold; font-size: 18px;'>✅ SUCCESS!</p>";
        echo "<p>Updated <strong>" . $affected . "</strong> services to active status.</p>";
        
        
        echo "<h2 style='color: #667eea;'>Active Services:</h2>";
        $services = $pdo->query("SELECT service_id, service_name FROM services WHERE is_active = TRUE ORDER BY service_id")->fetchAll();
        
        echo "<ul style='list-style: none; padding: 0;'>";
        foreach ($services as $service) {
            echo "<li style='padding: 8px; background: #f0f0f0; margin: 5px 0; border-radius: 4px; color: #333;'>";
            echo "✅ " . htmlspecialchars($service['service_name']);
            echo "</li>";
        }
        echo "</ul>";
        
        echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin-top: 20px; color: #1976d2;'>";
        echo "<p><strong>Next Step:</strong> Go back to your dashboard and try scheduling an appointment again!</p>";
        echo "<p><a href='dashboard.php' style='color: #1976d2; text-decoration: none; font-weight: bold;'>← Back to Dashboard</a></p>";
        echo "</div>";
        
        echo "</div>";
    } else {
        echo "<p style='color: #f44336;'>Failed to update services.</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: white; color: #c62828; padding: 20px; border-radius: 8px;'>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div>";

?>

