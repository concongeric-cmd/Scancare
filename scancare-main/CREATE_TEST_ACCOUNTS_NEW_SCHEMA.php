<?php


require_once 'db_config.php';

$pdo = getDatabaseConnection();
if (!$pdo) {
    die("❌ Database connection failed!");
}


$testAccounts = [
    
    [
        'type' => 'resident',
        'full_name' => 'Maria Santos',
        'email' => 'maria.santos@test.com',
        'password' => 'password123',
        'date_of_birth' => '1985-03-15',
        'address' => 'Purok 1, Barangay Poblacion, San Jose',
        'contact_number' => '+63-917-123-4567',
        'is_senior_citizen' => 0
    ],
    [
        'type' => 'resident',
        'full_name' => 'Juan Dela Cruz',
        'email' => 'juan.delacruz@test.com',
        'password' => 'password456',
        'date_of_birth' => '1978-07-22',
        'address' => 'Purok 3, Barangay Tagumpay, San Jose',
        'contact_number' => '+63-918-987-6543',
        'is_senior_citizen' => 1
    ],
    [
        'type' => 'resident',
        'full_name' => 'Rosa Villanueva',
        'email' => 'rosa.villanueva@test.com',
        'password' => 'password789',
        'date_of_birth' => '1990-11-08',
        'address' => 'Purok 2, Barangay San Miguel, San Jose',
        'contact_number' => '+63-919-456-7890',
        'is_senior_citizen' => 0
    ],
    
    
    [
        'type' => 'health_worker',
        'full_name' => 'Dr. Anna Reyes',
        'email' => 'anna.reyes@test.com',
        'password' => 'bhwpass123',
        'role' => 'bhw',
        'contact_number' => '+63-920-111-2222'
    ],
    [
        'type' => 'health_worker',
        'full_name' => 'Nurse Miguel Torres',
        'email' => 'miguel.torres@test.com',
        'password' => 'bhwpass456',
        'role' => 'bhw',
        'contact_number' => '+63-921-333-4444'
    ],
    
    
    [
        'type' => 'health_worker',
        'full_name' => 'System Admin',
        'email' => 'admin@test.com',
        'password' => 'admin@secure123',
        'role' => 'admin',
        'contact_number' => '+63-922-555-6666'
    ]
];


$successCount = 0;
$errorCount = 0;
$duplicateCount = 0;

echo "====================================================================\n";
echo "🔧 CREATE TEST ACCOUNTS FOR NEW ERD SCHEMA\n";
echo "====================================================================\n\n";

try {
    foreach ($testAccounts as $account) {
        echo "Processing: {$account['full_name']} ({$account['type']})\n";
        echo str_repeat("-", 60) . "\n";
        
        if ($account['type'] === 'resident') {
            
            $checkStmt = $pdo->prepare("SELECT resident_id FROM RESIDENT WHERE LOWER(email) = LOWER(?)");
            $checkStmt->execute([$account['email']]);
            if ($checkStmt->rowCount() > 0) {
                echo "  ⚠️  Email already exists in RESIDENT table\n";
                $duplicateCount++;
                echo "\n";
                continue;
            }
            
            
            $passwordHash = password_hash($account['password'], PASSWORD_BCRYPT, ['cost' => 10]);
            
            
            $insertStmt = $pdo->prepare("
                INSERT INTO RESIDENT 
                (full_name, email, password_hash, date_of_birth, address, contact_number, is_senior_citizen, qr_code_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, NULL)
            ");
            
            $inserted = $insertStmt->execute([
                $account['full_name'],
                $account['email'],
                $passwordHash,
                $account['date_of_birth'],
                $account['address'],
                $account['contact_number'],
                $account['is_senior_citizen']
            ]);
            
            if ($inserted) {
                $residentId = $pdo->lastInsertId();
                echo "  ✅ Resident created successfully!\n";
                echo "  📋 Resident ID: {$residentId}\n";
                echo "  📧 Email: {$account['email']}\n";
                echo "  🔑 Password: {$account['password']}\n";
                echo "  👤 Name: {$account['full_name']}\n";
                echo "  🎂 DOB: {$account['date_of_birth']}\n";
                echo "  📍 Address: {$account['address']}\n";
                echo "  📱 Phone: {$account['contact_number']}\n";
                echo "  👴 Senior Citizen: " . ($account['is_senior_citizen'] ? 'Yes' : 'No') . "\n";
                $successCount++;
            } else {
                echo "  ❌ Failed to create resident\n";
                $errorCount++;
            }
            
        } elseif ($account['type'] === 'health_worker') {
            
            $checkStmt = $pdo->prepare("SELECT worker_id FROM HEALTH_WORKER WHERE LOWER(email) = LOWER(?)");
            $checkStmt->execute([$account['email']]);
            if ($checkStmt->rowCount() > 0) {
                echo "  ⚠️  Email already exists in HEALTH_WORKER table\n";
                $duplicateCount++;
                echo "\n";
                continue;
            }
            
            
            $passwordHash = password_hash($account['password'], PASSWORD_BCRYPT, ['cost' => 10]);
            
            
            $insertStmt = $pdo->prepare("
                INSERT INTO HEALTH_WORKER 
                (full_name, email, password_hash, role, contact_number)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $inserted = $insertStmt->execute([
                $account['full_name'],
                $account['email'],
                $passwordHash,
                $account['role'],
                $account['contact_number']
            ]);
            
            if ($inserted) {
                $workerId = $pdo->lastInsertId();
                $roleLabel = $account['role'] === 'admin' ? '⚙️ ADMIN' : '👨‍⚕️ BHW';
                echo "  ✅ Health Worker created successfully!\n";
                echo "  📋 Worker ID: {$workerId}\n";
                echo "  📧 Email: {$account['email']}\n";
                echo "  🔑 Password: {$account['password']}\n";
                echo "  👤 Name: {$account['full_name']}\n";
                echo "  🎖️  Role: {$roleLabel}\n";
                echo "  📱 Phone: {$account['contact_number']}\n";
                $successCount++;
            } else {
                echo "  ❌ Failed to create health worker\n";
                $errorCount++;
            }
        }
        
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    $errorCount++;
}


echo "\n" . str_repeat("=", 70) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 70) . "\n";
echo "✅ Successfully Created: {$successCount} accounts\n";
echo "⚠️  Duplicates Skipped: {$duplicateCount} accounts\n";
echo "❌ Errors: {$errorCount} accounts\n";
echo "\nTotal Test Accounts: " . ($successCount + $duplicateCount) . "\n";
echo str_repeat("=", 70) . "\n\n";

echo "🧪 QUICK TEST CREDENTIALS\n";
echo str_repeat("=", 70) . "\n";
echo "\n👤 RESIDENT ACCOUNTS:\n";
echo "  1. maria.santos@test.com / password123\n";
echo "  2. juan.delacruz@test.com / password456\n";
echo "  3. rosa.villanueva@test.com / password789\n";

echo "\n👨‍⚕️ BHW (HEALTH WORKER) ACCOUNTS:\n";
echo "  1. anna.reyes@test.com / bhwpass123\n";
echo "  2. miguel.torres@test.com / bhwpass456\n";

echo "\n⚙️  ADMIN ACCOUNT:\n";
echo "  1. admin@test.com / admin@secure123\n";

echo "\n" . str_repeat("=", 70) . "\n";
echo "✅ Test account creation complete! You can now test the login system.\n";
echo str_repeat("=", 70) . "\n";
?>

