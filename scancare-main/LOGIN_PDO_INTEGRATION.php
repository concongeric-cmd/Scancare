<?php


session_start();
require_once 'db_config.php';


if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'resident') {
        header('Location: dashboard.php');
    } elseif ($_SESSION['user_role'] === 'bhw') {
        header('Location: bhw_dashboard.php');
    } elseif ($_SESSION['user_role'] === 'admin') {
        header('Location: admin_dashboard.php');
    }
    exit;
}

$error = '';
$success = '';
$is_signup = isset($_GET['signup']) ? true : false;


if (isset($_GET['logged_out'])) {
    $success = 'You have been successfully logged out.';
}

if (isset($_GET['session_expired'])) {
    $error = 'Your session has expired. Please log in again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        
        
        
        
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } elseif (!validateEmail($email)) {
            $error = 'Invalid email format.';
        } else {
            try {
                $pdo = getDatabaseConnection();
                if (!$pdo) {
                    throw new Exception('Database connection failed');
                }
                
                
                $stmt = $pdo->prepare("
                    SELECT user_id, full_name, email, password, role 
                    FROM users 
                    WHERE email = ?
                ");
                
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                
                if ($user && verifyPassword($password, $user['password']) && !in_array(strtolower((string) $user['role']), ['admin', 'administrator', 'system_admin'], true)) {
                    
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['login_time'] = time();
                    
                    
                    if ($user['role'] === 'resident') {
                        header('Location: dashboard.php');
                    } elseif ($user['role'] === 'bhw') {
                        header('Location: bhw_dashboard.php');
                    } elseif ($user['role'] === 'admin') {
                        header('Location: admin_dashboard.php');
                    }
                    exit;
                } else {
                    
                    $error = 'Invalid email or password. Please check your credentials and try again.';
                }
                
                $pdo = null;
                
            } catch (PDOException $e) {
                error_log("PDO Login Error: " . $e->getMessage());
                $error = 'A database error occurred. Please try again later.';
            } catch (Exception $e) {
                error_log("Login Error: " . $e->getMessage());
                $error = 'An error occurred during login. Please try again later.';
            }
        }
        
    } elseif ($action === 'signup') {
        
        
        
        
        $full_name = sanitizeInput($_POST['full_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $password_confirm = trim($_POST['password_confirm'] ?? '');
        $user_role = sanitizeInput($_POST['user_role'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        
        
        $errors = [];
        
        if (empty($full_name) || strlen($full_name) < 2) {
            $errors[] = 'Full name must be at least 2 characters.';
        }
        
        if (empty($email) || !validateEmail($email)) {
            $errors[] = 'Valid email is required.';
        }
        
        if (empty($password) || strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        
        if ($password !== $password_confirm) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (empty($user_role) || !in_array($user_role, ['resident', 'bhw', 'admin'])) {
            $errors[] = 'Please select a valid role.';
        }
        
        if (empty($phone) || strlen($phone) < 10) {
            $errors[] = 'Valid phone number (min 10 digits) is required.';
        }
        
        if (!empty($errors)) {
            $error = implode('<br>', $errors);
        } else {
            try {
                $pdo = getDatabaseConnection();
                if (!$pdo) {
                    throw new Exception('Database connection failed');
                }
                
                
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->rowCount() > 0) {
                    $error = 'This email is already registered. Please use a different email or <a href="login.php" style="color: #454B1B; font-weight: bold;">login here</a>.';
                } else {
                    
                    $hashed_password = hashPassword($password);
                    
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO users (full_name, email, password, role)
                        VALUES (?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([$full_name, $email, $hashed_password, $user_role]);
                    $user_id = $pdo->lastInsertId();
                    
                    
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_name'] = $full_name;
                    $_SESSION['email'] = $email;
                    $_SESSION['user_role'] = $user_role;
                    $_SESSION['login_time'] = time();
                    
                    
                    if ($user_role === 'resident') {
                        header('Location: dashboard.php');
                    } elseif ($user_role === 'bhw') {
                        header('Location: bhw_dashboard.php');
                    } elseif ($user_role === 'admin') {
                        header('Location: admin_dashboard.php');
                    }
                    exit;
                }
                
                $pdo = null;
                
            } catch (PDOException $e) {
                error_log("PDO Signup Error: " . $e->getMessage());
                $error = 'A database error occurred during registration. Please try again later.';
            } catch (Exception $e) {
                error_log("Signup Error: " . $e->getMessage());
                $error = 'An error occurred during registration. Please try again later.';
            }
        }
    }
}

?>

