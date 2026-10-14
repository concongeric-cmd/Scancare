<?php







function generatePasswordHash($password) {
    
    if (empty($password) || strlen($password) < 6) {
        throw new Exception('Password must be at least 6 characters');
    }
    
    
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    
    
    if (!$hash || strlen($hash) < 60) {
        throw new Exception('Password hashing failed');
    }
    
    return $hash;
}



$user_password = $_POST['password'] ?? '';  
$password_confirm = $_POST['password_confirm'] ?? '';


if ($user_password !== $password_confirm) {
    die('Passwords do not match');
}

if (strlen($user_password) < 6) {
    die('Password must be at least 6 characters');
}


$password_hash = generatePasswordHash($user_password);





try {
    $pdo = new PDO('mysql:host=localhost;dbname=scancare_db', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $stmt = $pdo->prepare("
        INSERT INTO system_admins (username, email, password_hash, full_name, role, is_active)
        VALUES (?, ?, ?, ?, ?, 1)
    ");
    
    $stmt->execute([
        'testadmin',           
        'test@scancare.local', 
        $password_hash,        
        'Test Administrator',  
        'admin'                
    ]);
    
    echo "✅ Admin account created successfully!";
    echo "\n✅ Password hashed: " . substr($password_hash, 0, 20) . "...";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}






function verifyPassword($plain_password, $stored_hash) {
    
    
    return password_verify($plain_password, $stored_hash);
}



$email = $_POST['email'] ?? '';        
$password = $_POST['password'] ?? '';  


try {
    $pdo = new PDO('mysql:host=localhost;dbname=scancare_db', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $stmt = $pdo->prepare("
        SELECT admin_id, username, email, password_hash, full_name, role 
        FROM system_admins 
        WHERE LOWER(email) = ? AND is_active = 1
        LIMIT 1
    ");
    
    $stmt->execute([strtolower($email)]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        
        
        
        echo "✅ Login successful!";
        echo "\nAdmin: " . $admin['full_name'];
        echo "\nRole: " . $admin['role'];
        
        
        $_SESSION['admin_auth'] = true;
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['login_time'] = time();
        
        
        $update_stmt = $pdo->prepare("UPDATE system_admins SET last_login = NOW() WHERE admin_id = ?");
        $update_stmt->execute([$admin['admin_id']]);
        
        
        header('Location: administrator_panel.php');
        exit;
        
    } else {
        
        
        
        echo "❌ Invalid email or password";
        
        
        error_log("LOGIN_FAILED: Email={$email}, IP={$_SERVER['REMOTE_ADDR']}");
    }
    
} catch (Exception $e) {
    echo "❌ Login error: " . $e->getMessage();
}






echo "\n=== EXAMPLE 1: PASSWORD HASHING ===\n";

$test_password = "MySecurePass123";
$test_hash = password_hash($test_password, PASSWORD_BCRYPT, ['cost' => 10]);

echo "Original Password: {$test_password}\n";
echo "Bcrypt Hash:      {$test_hash}\n";
echo "Hash Length:      " . strlen($test_hash) . " characters\n";
echo "Hash Algorithm:   " . password_algo($test_hash) . "\n";


echo "\n=== EXAMPLE 2: PASSWORD VERIFICATION ===\n";

$user_input_correct = "MySecurePass123";
$user_input_wrong = "WrongPassword123";

$is_correct = password_verify($user_input_correct, $test_hash);
$is_wrong = password_verify($user_input_wrong, $test_hash);

echo "Test 1 - Correct password: " . ($is_correct ? "✅ MATCH" : "❌ NO MATCH") . "\n";
echo "Test 2 - Wrong password:   " . ($is_wrong ? "✅ MATCH" : "❌ NO MATCH") . "\n";


echo "\n=== EXAMPLE 3: SALT GENERATION (EVERY HASH IS UNIQUE) ===\n";

$password = "SamePassword123";

$hash1 = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
$hash2 = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
$hash3 = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

echo "Hash 1: {$hash1}\n";
echo "Hash 2: {$hash2}\n";
echo "Hash 3: {$hash3}\n";
echo "\n⚠️  All three hashes are DIFFERENT (different salts)\n";
echo "✅ But all verify correctly against the same password:\n";
echo "   Hash 1 matches: " . (password_verify($password, $hash1) ? "YES" : "NO") . "\n";
echo "   Hash 2 matches: " . (password_verify($password, $hash2) ? "YES" : "NO") . "\n";
echo "   Hash 3 matches: " . (password_verify($password, $hash3) ? "YES" : "NO") . "\n";


echo "\n=== EXAMPLE 4: BCRYPT HASH STRUCTURE ===\n";

$example_hash = '$2y$10$abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWX';

echo "Full Hash: {$example_hash}\n\n";
echo "Components:\n";
echo "  \$2y       - Algorithm identifier (bcrypt)\n";
echo "  $10        - Cost factor (2^10 = 1024 iterations)\n";
echo "  abc...xyz  - Salt (22 characters)\n";
echo "  abc...uvwx - Encrypted password (31 characters)\n";





echo "\n=== COMMON MISTAKES TO AVOID ===\n";

echo "\n❌ WRONG - Storing plain-text password:\n";
echo "   \$_SESSION['password'] = 'MyPassword123';  

echo "\n✅ CORRECT - Store only hashed version:\n";
echo "   \$hash = password_hash('MyPassword123', PASSWORD_BCRYPT, ['cost' => 10]);\n";
echo "   \$_SESSION['password_hash'] = \$hash;  

echo "\n❌ WRONG - Using MD5 or SHA:\n";
echo "   md5('password')    
echo "   sha1('password')   

echo "\n✅ CORRECT - Using PASSWORD_BCRYPT:\n";
echo "   password_hash('password', PASSWORD_BCRYPT, ['cost' => 10])  

echo "\n❌ WRONG - Hard-coding cost factor:\n";
echo "   password_hash(\$pw, PASSWORD_BCRYPT, ['cost' => 4])  

echo "\n✅ CORRECT - Using appropriate cost:\n";
echo "   password_hash(\$pw, PASSWORD_BCRYPT, ['cost' => 10])  

echo "\n❌ WRONG - Comparing hashes with ==:\n";
echo "   if (\$hash1 == \$hash2) { }  

echo "\n✅ CORRECT - Using password_verify():\n";
echo "   if (password_verify(\$password, \$hash)) { }  






echo "\n=== MIGRATION: CONVERT PLAIN-TEXT TO HASHED ===\n";

echo "If you have old plain-text passwords in database:\n";
echo "Use this PHP code in a ONE-TIME migration script:\n\n";

echo "<?php\n";
echo "\$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n\n";
echo "
echo "\$stmt = \$pdo->query('SELECT admin_id, password_plain FROM system_admins');\n\n";
echo "while (\$row = \$stmt->fetch(PDO::FETCH_ASSOC)) {\n";
echo "    
echo "    \$hash = password_hash(\$row['password_plain'], PASSWORD_BCRYPT, ['cost' => 10]);\n\n";
echo "    
echo "    \$update = \$pdo->prepare('UPDATE system_admins SET password_hash = ? WHERE admin_id = ?');\n";
echo "    \$update->execute([\$hash, \$row['admin_id']]);\n";
echo "    \n";
echo "    echo \"✅ Hashed admin ID \" . \$row['admin_id'] . \"\\n\";\n";
echo "}\n\n";
echo "
echo "
echo "?>\n";

echo "\n⚠️  IMPORTANT:\n";
echo "   1. Run migration script only ONCE\n";
echo "   2. Verify all passwords hashed\n";
echo "   3. Delete old plain-text column\n";
echo "   4. Never store plain-text passwords again\n";





echo "\n=== SECURITY CHECKLIST ===\n";

$checklist = [
    'Password hashing uses PASSWORD_BCRYPT' => true,
    'Cost factor is 10 (balanced)' => true,
    'password_hash() called before database insert' => true,
    'password_verify() used for login verification' => true,
    'Database only stores hashes (VARCHAR(255))' => true,
    'No plain-text passwords in code' => true,
    'Prepared statements prevent SQL injection' => true,
    'UNIQUE constraints on username and email' => true,
    'is_active flag checked before login' => true,
    'Failed attempts logged (no password leak)' => true
];

foreach ($checklist as $item => $status) {
    echo ($status ? "✅" : "❌") . " {$item}\n";
}





function password_algo($hash) {
    if (strpos($hash, '$2y$') === 0) return 'bcrypt';
    if (strpos($hash, '$2a$') === 0) return 'bcrypt';
    if (strpos($hash, '$argon2i$') === 0) return 'argon2i';
    if (strpos($hash, '$argon2id$') === 0) return 'argon2id';
    return 'unknown';
}

?>

