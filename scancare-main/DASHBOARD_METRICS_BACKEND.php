<?php








$active_section = $_GET['section'] ?? 'dashboard';









$total_residents = 0;  
$total_staff = 0;      
$total_users = 0;      
$total_appointments = 0;
$system_uptime = '99.8%';
$db_size = '245 MB';
$last_backup = date('Y-m-d H:i:s', strtotime('-2 days'));

try {
    $pdo = getDatabaseConnection();
    
    if ($pdo === false) {
        throw new Exception('Database connection failed');
    }
    
    
    
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM RESIDENT");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_residents = intval($result['count'] ?? 0);
        error_log("Dashboard Metrics: Residents count = {$total_residents}");
    } catch (Exception $e) {
        error_log("Trying lowercase resident table... Error: " . $e->getMessage());
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM resident");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_residents = intval($result['count'] ?? 0);
            error_log("Dashboard Metrics: Residents count (lowercase) = {$total_residents}");
        } catch (Exception $e2) {
            error_log("Failed to count residents: " . $e2->getMessage());
            $total_residents = 0;
        }
    }
    
    
    
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM HEALTH_WORKER");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_staff = intval($result['count'] ?? 0);
        error_log("Dashboard Metrics: Health Workers count = {$total_staff}");
    } catch (Exception $e) {
        error_log("Trying lowercase health_worker table... Error: " . $e->getMessage());
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM health_worker");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_staff = intval($result['count'] ?? 0);
            error_log("Dashboard Metrics: Health Workers count (lowercase) = {$total_staff}");
        } catch (Exception $e2) {
            error_log("Failed to count health workers: " . $e2->getMessage());
            $total_staff = 0;
        }
    }
    
    
    
    
    
    $total_users = $total_residents + $total_staff;
    error_log("Dashboard Metrics: Combined Total Users = {$total_users} (Residents: {$total_residents} + Staff: {$total_staff})");
    
    
    
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM APPOINTMENT WHERE status = 'scheduled'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_appointments = intval($result['count'] ?? 0);
        error_log("Dashboard Metrics: Scheduled appointments = {$total_appointments}");
    } catch (Exception $e) {
        error_log("Failed to count appointments: " . $e->getMessage());
        $total_appointments = 0;
    }
    
    error_log("Admin Dashboard Metrics Complete: Total Users={$total_users}, Staff={$total_staff}, Residents={$total_residents}, Appointments={$total_appointments}");
    
} catch (Exception $e) {
    error_log("Admin Dashboard Database Error: " . $e->getMessage());
    
}

?>

