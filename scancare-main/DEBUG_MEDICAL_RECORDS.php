<?php


session_start();
require_once 'db_config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records Debug</title>
    <script src="https:
    <link rel="stylesheet" href="https:
</head>
<body class="bg-slate-100 p-6">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">🔍 Medical Records Debug Dashboard</h1>
        
        <?php
        try {
            $pdo = getDatabaseConnection();
            if (!$pdo) {
                echo '<div class="bg-red-100 border border-red-400 text-red-700 p-4 rounded">Database connection failed</div>';
                exit;
            }
            
            
            echo '<div class="bg-white rounded-lg shadow p-6 mb-6">';
            echo '<h2 class="text-xl font-bold mb-4">📊 Table Structures</h2>';
            
            
            try {
                $result = $pdo->query("DESCRIBE PATIENT_RECORD");
                $cols = $result->fetchAll(PDO::FETCH_ASSOC);
                echo '<div class="mb-4">';
                echo '<h3 class="font-bold text-green-600">✓ PATIENT_RECORD exists</h3>';
                echo '<table class="w-full text-sm border-collapse border border-slate-300">';
                echo '<tr class="bg-slate-100"><th class="border p-2">Field</th><th class="border p-2">Type</th><th class="border p-2">Null</th><th class="border p-2">Key</th></tr>';
                foreach ($cols as $col) {
                    echo '<tr>';
                    echo '<td class="border p-2">' . htmlspecialchars($col['Field']) . '</td>';
                    echo '<td class="border p-2">' . htmlspecialchars($col['Type']) . '</td>';
                    echo '<td class="border p-2">' . htmlspecialchars($col['Null']) . '</td>';
                    echo '<td class="border p-2">' . htmlspecialchars($col['Key']) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                echo '</div>';
            } catch (Exception $e) {
                echo '<div class="text-red-600">✗ PATIENT_RECORD does not exist or error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            
            
            try {
                $result = $pdo->query("DESCRIBE medical_history");
                $cols = $result->fetchAll(PDO::FETCH_ASSOC);
                echo '<div class="mb-4">';
                echo '<h3 class="font-bold text-green-600">✓ medical_history exists</h3>';
                echo '<table class="w-full text-sm border-collapse border border-slate-300">';
                echo '<tr class="bg-slate-100"><th class="border p-2">Field</th><th class="border p-2">Type</th><th class="border p-2">Null</th><th class="border p-2">Key</th></tr>';
                foreach ($cols as $col) {
                    echo '<tr>';
                    echo '<td class="border p-2">' . htmlspecialchars($col['Field']) . '</td>';
                    echo '<td class="border p-2">' . htmlspecialchars($col['Type']) . '</td>';
                    echo '<td class="border p-2">' . htmlspecialchars($col['Null']) . '</td>';
                    echo '<td class="border p-2">' . htmlspecialchars($col['Key']) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                echo '</div>';
            } catch (Exception $e) {
                echo '<div class="text-red-600">✗ medical_history does not exist or error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            
            echo '</div>';
            
            
            echo '<div class="bg-white rounded-lg shadow p-6 mb-6">';
            echo '<h2 class="text-xl font-bold mb-4">📋 Recent Completed Appointments</h2>';
            
            $stmt = $pdo->query("
                SELECT 
                    appointment_id,
                    resident_id,
                    health_worker_id,
                    appointment_date,
                    appointment_time,
                    purpose,
                    status,
                    updated_at
                FROM APPOINTMENT 
                WHERE status = 'completed'
                ORDER BY updated_at DESC
                LIMIT 10
            ");
            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($appointments)) {
                echo '<div class="text-slate-600">No completed appointments found</div>';
            } else {
                echo '<table class="w-full text-sm border-collapse border border-slate-300">';
                echo '<tr class="bg-slate-100">';
                echo '<th class="border p-2">Apt ID</th>';
                echo '<th class="border p-2">Resident ID</th>';
                echo '<th class="border p-2">Worker ID</th>';
                echo '<th class="border p-2">Date</th>';
                echo '<th class="border p-2">Purpose</th>';
                echo '<th class="border p-2">Updated</th>';
                echo '</tr>';
                foreach ($appointments as $apt) {
                    echo '<tr>';
                    echo '<td class="border p-2">' . htmlspecialchars($apt['appointment_id']) . '</td>';
                    echo '<td class="border p-2"><strong>' . htmlspecialchars($apt['resident_id']) . '</strong></td>';
                    echo '<td class="border p-2">' . htmlspecialchars($apt['health_worker_id']) . '</td>';
                    echo '<td class="border p-2">' . htmlspecialchars($apt['appointment_date']) . '</td>';
                    echo '<td class="border p-2">' . htmlspecialchars($apt['purpose']) . '</td>';
                    echo '<td class="border p-2 text-xs">' . htmlspecialchars($apt['updated_at']) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            echo '</div>';
            
            
            echo '<div class="bg-white rounded-lg shadow p-6 mb-6">';
            echo '<h2 class="text-xl font-bold mb-4">🏥 PATIENT_RECORD Data</h2>';
            
            try {
                $stmt = $pdo->query("
                    SELECT 
                        record_id,
                        resident_id,
                        health_worker_id,
                        visit_date,
                        diagnosis,
                        created_at
                    FROM PATIENT_RECORD
                    ORDER BY created_at DESC
                    LIMIT 10
                ");
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($records)) {
                    echo '<div class="text-slate-600">No records in PATIENT_RECORD table</div>';
                } else {
                    echo '<table class="w-full text-sm border-collapse border border-slate-300">';
                    echo '<tr class="bg-slate-100">';
                    echo '<th class="border p-2">Record ID</th>';
                    echo '<th class="border p-2">Resident ID</th>';
                    echo '<th class="border p-2">Worker ID</th>';
                    echo '<th class="border p-2">Visit Date</th>';
                    echo '<th class="border p-2">Diagnosis</th>';
                    echo '<th class="border p-2">Created</th>';
                    echo '</tr>';
                    foreach ($records as $rec) {
                        echo '<tr>';
                        echo '<td class="border p-2">' . htmlspecialchars($rec['record_id']) . '</td>';
                        echo '<td class="border p-2"><strong>' . htmlspecialchars($rec['resident_id']) . '</strong></td>';
                        echo '<td class="border p-2">' . htmlspecialchars($rec['health_worker_id']) . '</td>';
                        echo '<td class="border p-2">' . htmlspecialchars($rec['visit_date']) . '</td>';
                        echo '<td class="border p-2">' . htmlspecialchars($rec['diagnosis']) . '</td>';
                        echo '<td class="border p-2 text-xs">' . htmlspecialchars($rec['created_at']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
            } catch (Exception $e) {
                echo '<div class="text-red-600">Error querying PATIENT_RECORD: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            echo '</div>';
            
            
            echo '<div class="bg-white rounded-lg shadow p-6 mb-6">';
            echo '<h2 class="text-xl font-bold mb-4">📜 medical_history Data</h2>';
            
            try {
                $stmt = $pdo->query("
                    SELECT 
                        history_id,
                        user_id,
                        visit_date,
                        visit_type,
                        diagnosis,
                        created_at
                    FROM medical_history
                    ORDER BY created_at DESC
                    LIMIT 10
                ");
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($records)) {
                    echo '<div class="text-slate-600">No records in medical_history table</div>';
                } else {
                    echo '<table class="w-full text-sm border-collapse border border-slate-300">';
                    echo '<tr class="bg-slate-100">';
                    echo '<th class="border p-2">History ID</th>';
                    echo '<th class="border p-2">User ID</th>';
                    echo '<th class="border p-2">Visit Date</th>';
                    echo '<th class="border p-2">Visit Type</th>';
                    echo '<th class="border p-2">Diagnosis</th>';
                    echo '<th class="border p-2">Created</th>';
                    echo '</tr>';
                    foreach ($records as $rec) {
                        echo '<tr>';
                        echo '<td class="border p-2">' . htmlspecialchars($rec['history_id']) . '</td>';
                        echo '<td class="border p-2"><strong>' . htmlspecialchars($rec['user_id']) . '</strong></td>';
                        echo '<td class="border p-2">' . htmlspecialchars($rec['visit_date']) . '</td>';
                        echo '<td class="border p-2">' . htmlspecialchars($rec['visit_type']) . '</td>';
                        echo '<td class="border p-2">' . htmlspecialchars($rec['diagnosis']) . '</td>';
                        echo '<td class="border p-2 text-xs">' . htmlspecialchars($rec['created_at']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
            } catch (Exception $e) {
                echo '<div class="text-red-600">Error querying medical_history: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 p-4 rounded">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
</body>
</html>

