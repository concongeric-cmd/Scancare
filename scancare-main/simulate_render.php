<?php
// Simulate what happens when resident dashboard loads

require 'db_config.php';

// This is what dashboard_resident.php does:
$surveillance_metrics = [];

try {
    $pdoMetrics = getDatabaseConnection();
    if ($pdoMetrics !== false) {
        $stmtMetrics = $pdoMetrics->query('SELECT * FROM surveillance_metrics ORDER BY updated_at DESC LIMIT 1');
        $surveillance_metrics = $stmtMetrics->fetch(PDO::FETCH_ASSOC) ?: [];
    }
} catch (Exception $e) {
    $surveillance_metrics = [];
}

// Helper function
function residentSurveillanceValue($metrics, $key, $fallback = '') {
    if (!is_array($metrics)) {
        return $fallback;
    }
    if (array_key_exists($key, $metrics) && $metrics[$key] !== null && $metrics[$key] !== '') {
        return $metrics[$key];
    }
    return $fallback;
}

// Extract values exactly like dashboard_resident.php does
$residentDengueCount = (int) residentSurveillanceValue($surveillance_metrics, 'dengue_count', 0);
$residentDengueTrend = trim((string) residentSurveillanceValue($surveillance_metrics, 'dengue_trend', 'Baseline')) ?: 'Baseline';
$residentDengueMeta = trim((string) residentSurveillanceValue($surveillance_metrics, 'dengue_meta', 'No active cases reported.')) ?: 'No active cases reported.';
$residentDengueProgress = (int) residentSurveillanceValue($surveillance_metrics, 'dengue_progress', 0);

$residentRabiesCount = (int) residentSurveillanceValue($surveillance_metrics, 'rabies_count', 0);
$residentRabiesTrend = trim((string) residentSurveillanceValue($surveillance_metrics, 'rabies_trend', 'Baseline')) ?: 'Baseline';
$residentRabiesMeta = trim((string) residentSurveillanceValue($surveillance_metrics, 'rabies_meta', '0% coverage | No residents vaccinated.')) ?: '0% coverage | No residents vaccinated.';
$residentRabiesProgress = (int) residentSurveillanceValue($surveillance_metrics, 'rabies_progress', 0);

$residentRespiratoryCount = (int) residentSurveillanceValue($surveillance_metrics, 'respiratory_count', 0);
$residentRespiratoryTrend = trim((string) residentSurveillanceValue($surveillance_metrics, 'respiratory_trend', 'Baseline')) ?: 'Baseline';
$residentRespiratoryMeta = trim((string) residentSurveillanceValue($surveillance_metrics, 'respiratory_meta', 'No active cases reported.')) ?: 'No active cases reported.';
$residentRespiratoryProgress = (int) residentSurveillanceValue($surveillance_metrics, 'respiratory_progress', 0);

$residentNutritionCount = (int) residentSurveillanceValue($surveillance_metrics, 'nutrition_count', 0);
$residentNutritionTrend = trim((string) residentSurveillanceValue($surveillance_metrics, 'nutrition_trend', 'Baseline')) ?: 'Baseline';
$residentNutritionMeta = trim((string) residentSurveillanceValue($surveillance_metrics, 'nutrition_meta', 'No active cases reported.')) ?: 'No active cases reported.';
$residentNutritionProgress = (int) residentSurveillanceValue($surveillance_metrics, 'nutrition_progress', 0);

echo "<h1>Resident Dashboard HTML Render Simulation</h1>";
echo "<p>These are the exact values that will be rendered in the resident dashboard bars:</p>";
echo "<hr>";

echo "<h2>Dengue Cases (Last 4 Weeks)</h2>";
echo "<p>Trend: <strong>" . htmlspecialchars($residentDengueTrend) . "</strong></p>";
echo "<p>Count: <strong>" . $residentDengueCount . "</strong> confirmed cases</p>";
echo "<p>Meta: " . htmlspecialchars($residentDengueMeta) . "</p>";
echo "<p><em>Bar will render with width: <strong>" . (int) $residentDengueProgress . "%</strong></em></p>";
echo "<div style='background: #f0f0f0; border: 1px solid #ccc; border-radius: 9999px; height: 12px; overflow: hidden;'>";
echo "  <div style='background: linear-gradient(to right, #454B1B, #ef4444); height: 100%; width: " . (int) $residentDengueProgress . "%; border-radius: 9999px;'></div>";
echo "</div>";

echo "<hr>";

echo "<h2>Rabies Vaccination Coverage</h2>";
echo "<p>Trend: <strong>" . htmlspecialchars($residentRabiesTrend) . "</strong></p>";
echo "<p>Count: <strong>" . $residentRabiesCount . "%</strong> coverage</p>";
echo "<p>Meta: " . htmlspecialchars($residentRabiesMeta) . "</p>";
echo "<p><em>Bar will render with width: <strong>" . (int) $residentRabiesProgress . "%</strong></em></p>";
echo "<div style='background: #f0f0f0; border: 1px solid #ccc; border-radius: 9999px; height: 12px; overflow: hidden;'>";
echo "  <div style='background: linear-gradient(to right, #454B1B, #10b981); height: 100%; width: " . (int) $residentRabiesProgress . "%; border-radius: 9999px;'></div>";
echo "</div>";

echo "<hr>";

echo "<h2>Respiratory Infections (Seasonal)</h2>";
echo "<p>Trend: <strong>" . htmlspecialchars($residentRespiratoryTrend) . "</strong></p>";
echo "<p>Count: <strong>" . $residentRespiratoryCount . "</strong> active cases</p>";
echo "<p>Meta: " . htmlspecialchars($residentRespiratoryMeta) . "</p>";
echo "<p><em>Bar will render with width: <strong>" . (int) $residentRespiratoryProgress . "%</strong></em></p>";
echo "<div style='background: #f0f0f0; border: 1px solid #ccc; border-radius: 9999px; height: 12px; overflow: hidden;'>";
echo "  <div style='background: linear-gradient(to right, #454B1B, #3b82f6); height: 100%; width: " . (int) $residentRespiratoryProgress . "%; border-radius: 9999px;'></div>";
echo "</div>";

echo "<hr>";

echo "<h2>Nutrition Deficiency (U5)</h2>";
echo "<p>Trend: <strong>" . htmlspecialchars($residentNutritionTrend) . "</strong></p>";
echo "<p>Count: <strong>" . ($residentNutritionCount > 0 ? $residentNutritionCount . " cases" : "No active cases") . "</strong></p>";
echo "<p>Meta: " . htmlspecialchars($residentNutritionMeta) . "</p>";
echo "<p><em>Bar will render with width: <strong>" . (int) $residentNutritionProgress . "%</strong></em></p>";
echo "<div style='background: #f0f0f0; border: 1px solid #ccc; border-radius: 9999px; height: 12px; overflow: hidden;'>";
echo "  <div style='background: linear-gradient(to right, #454B1B, #f97316); height: 100%; width: " . (int) $residentNutritionProgress . "%; border-radius: 9999px;'></div>";
echo "</div>";

echo "<hr>";
echo "<p><strong>This simulation shows exactly what the resident dashboard will display.</strong></p>";
echo "<p>If this matches your reference image, then the dashboard should now work correctly when you reload it.</p>";
echo "<p><a href='dashboard.php'><strong>Click here to see the actual Resident Dashboard</strong></a></p>";
?>
