<?php


session_start();


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'db_config.php';
$pdo = getDatabaseConnection();

if (!$pdo) {
    die('Database connection failed');
}


$user_email = $_SESSION['email'];
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments — ScanCare</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: #F8FAF6;
            color: #333;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #454B1B;
            padding-bottom: 15px;
        }
        
        .header h1 {
            color: #454B1B;
            margin-bottom: 5px;
        }
        
        .user-info {
            font-size: 14px;
            color: #666;
        }
        
        .user-info strong {
            color: #454B1B;
        }
        
        .appointments-section {
            margin-top: 30px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #454B1B;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .appointment-card {
            background: #f9f9f9;
            border-left: 4px solid #454B1B;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .appointment-details {
            flex: 1;
        }
        
        .appointment-date {
            font-size: 16px;
            font-weight: 600;
            color: #454B1B;
            margin-bottom: 8px;
        }
        
        .appointment-time {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .appointment-service {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .appointment-reason {
            font-size: 13px;
            color: #999;
            font-style: italic;
        }
        
        .appointment-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .status-scheduled {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-completed {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-canceled {
            background: #fecaca;
            color: #991b1b;
        }
        
        .appointment-actions {
            text-align: right;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin-left: 10px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-cancel {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-cancel:hover {
            background: #fecaca;
        }
        
        .no-appointments {
            background: #fef3c7;
            color: #92400e;
            padding: 20px;
            border-radius: 6px;
            text-align: center;
            margin: 20px 0;
        }
        
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .success-message {
            background: #dcfce7;
            color: #166534;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Your Appointments</h1>
            <div class="user-info">
                <strong><?php echo htmlspecialchars($user_name); ?></strong> | 
                <strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?>
            </div>
        </div>
        
        <div class="appointments-section">
            <h2 class="section-title">Scheduled Appointments</h2>
            
            <?php
            
            $query = "SELECT 
                a.appointment_id,
                a.appointment_date,
                a.appointment_time,
                a.reason,
                a.status,
                s.service_name,
                s.category,
                CONCAT(u.first_name, ' ', u.last_name) as bhw_name
            FROM appointments a
            INNER JOIN services s ON a.service_id = s.service_id
            LEFT JOIN users u ON a.bhw_id = u.user_id
            WHERE a.user_id = ?
            ORDER BY a.appointment_date DESC";
            
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $appointments = $stmt->fetchAll();
            
            if (empty($appointments)) {
                echo '<div class="no-appointments">
                    <p>No appointments scheduled yet.</p>
                    <p><a href="dashboard.php" style="color: #92400e; text-decoration: underline;">Return to dashboard to schedule an appointment</a></p>
                </div>';
            } else {
                foreach ($appointments as $appointment) {
                    $appointment_datetime = $appointment['appointment_date'] . ' ' . $appointment['appointment_time'];
                    $formatted_date = date('F d, Y', strtotime($appointment['appointment_date']));
                    $formatted_time = date('h:i A', strtotime($appointment['appointment_time']));
                    
                    $status_class = 'status-' . $appointment['status'];
                    
                    echo '<div class="appointment-card">
                        <div class="appointment-details">
                            <div class="appointment-date">' . $formatted_date . ' at ' . $formatted_time . '</div>
                            <div class="appointment-service">
                                <strong>Service:</strong> ' . htmlspecialchars($appointment['service_name']) . '
                            </div>';
                            
                    if (!empty($appointment['bhw_name'])) {
                        echo '<div class="appointment-service">
                            <strong>Healthcare Worker:</strong> ' . htmlspecialchars($appointment['bhw_name']) . '
                        </div>';
                    }
                    
                    if (!empty($appointment['reason'])) {
                        echo '<div class="appointment-reason">
                            <strong>Reason:</strong> ' . htmlspecialchars($appointment['reason']) . '
                        </div>';
                    }
                    
                    echo '<span class="appointment-status ' . $status_class . '">' . strtoupper($appointment['status']) . '</span>
                        </div>
                        <div class="appointment-actions">';
                    
                    
                    if ($appointment['status'] === 'scheduled') {
                        echo '<form method="POST" action="cancel_appointment.php" style="display: inline;">
                            <input type="hidden" name="appointment_id" value="' . $appointment['appointment_id'] . '">
                            <button type="submit" class="btn btn-cancel" onclick="return confirm(\'Cancel this appointment?\');">Cancel</button>
                        </form>';
                    }
                    
                    echo '</div>
                    </div>';
                }
            }
            ?>
        </div>
    </div>
</body>
</html>

