<?php


session_start();
require_once 'db_config.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$patientData = null;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_pfp'])) {
    try {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User session not active');
        }

        if (!isset($_FILES['profile_picture'])) {
            throw new Exception('No file selected from device');
        }

        $uploadError = $_FILES['profile_picture']['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($uploadError !== UPLOAD_ERR_OK) {
            $uploadErrorMessage = match ($uploadError) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds max allowed size',
                UPLOAD_ERR_NO_FILE => 'No file selected from device',
                UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_NO_TMP_DIR => 'Server folder permission issue',
                default => 'File upload error code: ' . $uploadError
            };
            throw new Exception($uploadErrorMessage);
        }

        $file = $_FILES['profile_picture'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new Exception('Invalid file type. Please upload JPG, PNG, or WEBP.');
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File exceeds max allowed size');
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mimeType, $allowedMimeTypes, true) || getimagesize($file['tmp_name']) === false) {
            throw new Exception('Invalid image file. Please upload JPG, PNG, or WEBP.');
        }

        $uploadDir = __DIR__ . '/assets/uploads/profile_pics/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
            throw new Exception('Failed to create destination directory: assets/uploads/profile_pics/');
        }
        if (!is_writable($uploadDir)) {
            throw new Exception('Failed to save file to server folder. Check permissions.');
        }

        $userId = (int)$_SESSION['user_id'];
        $newFileName = 'avatar_' . $userId . '_' . time() . '_' . random_int(100, 999) . '.' . $extension;
        $destination = $uploadDir . $newFileName;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to save file to server folder. Check permissions.');
        }

        $pdo = getDatabaseConnection();
        if (!$pdo) {
            throw new Exception('Database connection failed while saving the profile picture.');
        }
        $columnCheck = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'profile_picture'");
        $columnCheck->execute();
        if (!$columnCheck->fetchColumn()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL DEFAULT NULL");
        }

        $relativePath = 'assets/uploads/profile_pics/' . $newFileName;
        $update = $pdo->prepare('UPDATE users SET profile_picture = ? WHERE user_id = ?');
        if (!$update->execute([$relativePath, $userId])) {
            throw new Exception('Database update failed while saving the profile picture.');
        }

        $_SESSION['profile_picture'] = $relativePath;
        $_SESSION['profile_pic'] = $relativePath;
        header('Location: ' . $_SERVER['PHP_SELF'] . '?upload=success');
        exit;
    } catch (Throwable $uploadException) {
        $error = $uploadException->getMessage();
    }
}

if (isset($_GET['upload']) && $_GET['upload'] === 'success') {
    $success = 'Profile picture updated successfully.';
}

try {
    
    $pdo = getDatabaseConnection();
    
    if ($pdo === false) {
        $error = 'Database connection error. Please try again later.';
    } else {
        
        
        $stmt = $pdo->prepare("
            SELECT 
                user_id,
                first_name,
                middle_initial,
                last_name,
                date_of_birth,
                address,
                email,
                phone_number,
                profile_picture,
                user_role,
                is_active,
                created_at,
                last_login
            FROM users 
            WHERE user_id = ? 
            LIMIT 1
        ");
        
        
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        
        if (!$user) {
            $error = 'User account not found. Please log in again.';
            session_destroy();
            header('Location: login.php');
            exit;
        }
        
        if (!$user['is_active']) {
            $error = 'Your account has been deactivated. Please contact support.';
        }
        
        
        $patientData = [
            'userId' => htmlspecialchars($user['user_id']),
            'firstName' => htmlspecialchars($user['first_name'] ?? ''),
            'middleInitial' => htmlspecialchars($user['middle_initial'] ?? ''),
            'lastName' => htmlspecialchars($user['last_name'] ?? ''),
            'fullName' => htmlspecialchars(
                trim($user['first_name'] . ' ' . ($user['middle_initial'] ? $user['middle_initial'] . ' ' : '') . $user['last_name'])
            ),
            'dateOfBirth' => htmlspecialchars($user['date_of_birth'] ?? ''),
            'address' => htmlspecialchars($user['address'] ?? ''),
            'emailAddress' => htmlspecialchars($user['email'] ?? ''),
            'contactNumber' => htmlspecialchars($user['phone_number'] ?? ''),
            'profilePicture' => !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture'], ENT_QUOTES, 'UTF-8') : '',
            'userRole' => htmlspecialchars(ucfirst($user['user_role'] ?? '')),
            'accountStatus' => $user['is_active'] ? 'Active' : 'Inactive',
            'createdAt' => htmlspecialchars(date('M d, Y', strtotime($user['created_at']))),
            'lastLogin' => $user['last_login'] ? htmlspecialchars(date('M d, Y h:i A', strtotime($user['last_login']))) : 'Never'
        ];
    }
} catch (Exception $e) {
    $error = 'An error occurred while fetching profile data.';
    error_log("PROFILE_ERROR: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — ScanCare Healthcare Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F8FAF6;
            color: #475569;
        }
        
        .font-heading {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        
        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease-in-out;
            background-color: white;
        }

        .form-input:focus {
            outline: none;
            border-color: #454B1B;
            box-shadow: 0 0 0 3px rgba(69, 75, 27, 0.1);
        }

        .form-input:disabled,
        .form-input[readonly] {
            background-color: #f1f5f9;
            color: #64748b;
            cursor: not-allowed;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 6px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        
        .profile-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .profile-card:hover {
            box-shadow: 0 4px 12px rgba(69, 75, 27, 0.1);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #e2e8f0;
        }

        .card-header-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #454B1B 0%, #6b7333 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .card-header-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: #454B1B;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }

        .btn-primary:hover {
            background-color: #3a3f14;
            box-shadow: 0 4px 12px rgba(69, 75, 27, 0.2);
            transform: translateY(-1px);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }

        .btn-secondary:hover {
            background-color: #e2e8f0;
            border-color: #cbd5e1;
        }

        
        .qr-card {
            background: linear-gradient(135deg, #454B1B 0%, #6b7333 100%);
            border-radius: 12px;
            padding: 20px;
            color: white;
            text-align: center;
            position: sticky;
            top: 20px;
        }

        .qr-card-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.9;
            margin-bottom: 12px;
        }

        .qr-card-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .qr-card-id {
            font-size: 13px;
            font-weight: 600;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-family: 'Courier New', monospace;
        }

        .qr-placeholder {
            background-color: rgba(255, 255, 255, 0.1);
            border: 2px dashed rgba(255, 255, 255, 0.3);
            width: 100%;
            height: 120px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 12px;
        }

        .btn-download {
            width: 100%;
            padding: 12px;
            background-color: white;
            color: #454B1B;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-download:hover {
            background-color: #f1f5f9;
            transform: translateY(-1px);
        }

        
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }

        @media (min-width: 1024px) {
            .profile-grid {
                grid-template-columns: 1fr 280px;
            }
        }

        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }

        @media (min-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        
        .tabs-nav {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 1px solid #e2e8f0;
            overflow-x: auto;
        }

        .tab-btn {
            padding: 12px 16px;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            transition: all 0.2s ease-in-out;
            white-space: nowrap;
        }

        .tab-btn.active {
            color: #454B1B;
            border-bottom-color: #454B1B;
        }

        .tab-btn:hover {
            color: #454B1B;
        }

        .avatar-preview {
            width: 112px;
            height: 112px;
            border-radius: 9999px;
            object-fit: cover;
            border: 4px solid #edf3dd;
            background: #f1f5f9;
        }
    </style>
</head>
<body>
    <!
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <img src="images/SClogo.png" alt="ScanCare" class="h-8 w-8">
                <span class="text-lg font-bold text-slate-900 font-heading">ScanCare</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="text-sm text-slate-600 hover:text-slate-900 transition-colors">← Back to Dashboard</a>
            </div>
        </div>
    </nav>

    <!
    <main class="max-w-7xl mx-auto px-6 py-8">
        <!
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-900 mb-2 font-heading">My Profile</h1>
            <p class="text-slate-600">View your personal information and account details</p>
        </div>

        <?php if ($patientData): ?>
        <section class="profile-card mb-6 flex flex-col gap-5 sm:flex-row sm:items-center" aria-labelledby="profilePictureTitle">
            <div class="flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#454B1B] text-3xl font-bold text-white">
                <?php if (!empty($patientData['profilePicture'])): ?>
                    <img id="profilePicturePreview" class="avatar-preview" src="<?php echo $patientData['profilePicture']; ?>?t=<?php echo time(); ?>" alt="Profile picture">
                <?php else: ?>
                    <img id="profilePicturePreview" class="avatar-preview hidden" src="" alt="Profile picture preview">
                    <span id="profilePictureFallback" aria-hidden="true"><?php echo htmlspecialchars(strtoupper(substr($patientData['firstName'], 0, 1) . substr($patientData['lastName'], 0, 1))); ?></span>
                <?php endif; ?>
            </div>
            <div class="min-w-0 flex-1">
                <h2 id="profilePictureTitle" class="mb-1 text-lg font-bold text-slate-900">Profile Picture</h2>
                <p class="mb-4 text-sm text-slate-600">Use a JPG, PNG, or WebP image up to 5MB.</p>
                <form id="profilePictureForm" action="" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
                    <label class="btn-secondary cursor-pointer">
                        <i class="fas fa-camera" aria-hidden="true"></i> Choose Picture
                        <input id="profile_picture" type="file" name="profile_picture" accept="image/png, image/jpeg, image/jpg, image/webp" required class="hidden">
                    </label>
                    <button type="submit" name="submit_pfp" class="px-4 py-2 bg-[#454B1B] text-white rounded-lg font-semibold"><i class="fas fa-upload" aria-hidden="true"></i> Upload Profile Picture</button>
                    <span id="profilePictureStatus" class="text-sm" role="status" aria-live="polite"></span>
                </form>
            </div>
        </section>
        <?php endif; ?>

        <!
        <?php if (!empty($error)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-circle flex-shrink-0 mt-0.5"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!
        <?php if (!empty($success)): ?>
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            <div class="flex items-start gap-3">
                <i class="fas fa-check-circle flex-shrink-0 mt-0.5"></i>
                <div><?php echo htmlspecialchars($success); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!
        <?php if ($patientData): ?>
        <div class="profile-grid">
            <!
            <div>
                <!
                <div class="profile-card">
                    <div class="card-header">
                        <div class="card-header-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <h2 class="card-header-title">Personal Information</h2>
                    </div>
                    
                    <div class="space-y-4">
                        <!
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">First Name</label>
                                <div class="form-input bg-gray-50" style="background-color: #f1f5f9; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 8px;">
                                    <?php echo $patientData['firstName']; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Middle Initial</label>
                                <div class="form-input bg-gray-50" style="background-color: #f1f5f9; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 8px;">
                                    <?php echo !empty($patientData['middleInitial']) ? $patientData['middleInitial'] : '—'; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Last Name</label>
                                <div class="form-input bg-gray-50" style="background-color: #f1f5f9; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 8px;">
                                    <?php echo $patientData['lastName']; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <div class="form-input bg-gray-50" style="background-color: #f1f5f9; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 8px; font-weight: 600; color: #454B1B;">
                                    <?php echo $patientData['fullName']; ?>
                                </div>
                            </div>
                        </div>

                        <!
                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <div class="form-input bg-gray-50" style="background-color: #f1f5f9; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 8px;">
                                <?php 
                                    if (!empty($patientData['dateOfBirth']) && $patientData['dateOfBirth'] !== '1900-01-01') {
                                        echo date('F d, Y', strtotime($patientData['dateOfBirth']));
                                    } else {
                                        echo '—';
                                    }
                                ?>
                            </div>
                        </div>

                        <!
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <div class="form-input bg-gray-50" style="background-color: #f1f5f9; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 8px; word-break: break-all;">
                                    <?php echo $patientData['emailAddress']; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <div class="form-input bg-gray-50" style="background-color: #f1f5f9; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 8px;">
                                    <?php echo $patientData['contactNumber']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!
                <div class="profile-card">
                    <div class="card-header">
                        <div class="card-header-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h2 class="card-header-title">Address Information</h2>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="form-group">
                            <label class="form-label">Complete Address</label>
                            <div class="form-input bg-gray-50" style="background-color: #f1f5f9; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 8px; min-height: 80px; white-space: pre-wrap; word-wrap: break-word;">
                                <?php echo $patientData['address']; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!
                <div class="profile-card">
                    <div class="card-header">
                        <div class="card-header-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h2 class="card-header-title">Account Information</h2>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">User ID</label>
                                <div class="form-input bg-gray-50" style="background-color: #f1f5f9; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 8px; font-family: 'Courier New', monospace; font-weight: 600;">

                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Account Status</label>
                                <div class="form-input bg-gray-50" style="background-color: #f1f5f9; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 8px;">
                                    <span style="color: <?php echo ($patientData['accountStatus'] === 'Active') ? '#16a34a' : '#dc2626'; ?>; font-weight: 600;">
                                        <i class="fas fa-circle" style="font-size: 8px; margin-right: 6px;"></i> <?php echo $patientData['accountStatus']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">User Role</label>
                                <div class="form-input bg-gray-50" style="background-color: #f1f5f9; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 8px;">
                                    <?php echo $patientData['userRole']; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Member Since</label>
                                <div class="form-input bg-gray-50" style="background-color: #f1f5f9; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 8px;">
                                    <?php echo $patientData['createdAt']; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Last Login</label>
                            <div class="form-input bg-gray-50" style="background-color: #f1f5f9; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 8px;">
                                <?php echo $patientData['lastLogin']; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!
                <div class="flex gap-3 mt-6">
                    <a href="dashboard.php" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <a href="logout.php" class="btn-primary" style="background-color: #dc2626;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <!
            <div>
                <div class="qr-card">
                    <div class="qr-card-label">
                        <i class="fas fa-user-check mr-2"></i> Profile Summary
                    </div>
                    <div class="qr-card-title"><?php echo $patientData['firstName']; ?></div>
                    <div class="qr-card-id">#<?php echo $patientData['userId']; ?></div>
                    
                    <div class="qr-placeholder" style="background-color: rgba(255, 255, 255, 0.1);">
                        <div class="text-center">
                            <i class="fas fa-user-circle text-4xl mb-2 opacity-75"></i>
                            <p class="text-xs opacity-75 mt-2"><?php echo htmlspecialchars($patientData['userRole']); ?></p>
                        </div>
                    </div>

                    <div style="background-color: rgba(255, 255, 255, 0.1); padding: 12px; border-radius: 8px; text-align: center; margin-bottom: 16px;">
                        <p class="text-xs opacity-75 mb-1">Account Status</p>
                        <p style="color: #22c55e; font-weight: 700;">
                            <i class="fas fa-circle" style="font-size: 8px; margin-right: 6px;"></i> <?php echo $patientData['accountStatus']; ?>
                        </p>
                    </div>

                    <button class="btn-download" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Profile
                    </button>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!
        <div class="profile-card" style="text-align: center; padding: 40px;">
            <i class="fas fa-exclamation-circle text-3xl" style="color: #454B1B; margin-bottom: 16px;"></i>
            <p class="text-lg text-slate-700 mb-4">Unable to load profile data.</p>
            <p class="text-sm text-slate-500 mb-6"><?php echo htmlspecialchars($error); ?></p>
            <a href="dashboard.php" class="btn-primary">
                <i class="fas fa-arrow-left"></i> Return to Dashboard
            </a>
        </div>
        <?php endif; ?>
        </main>
        <script>
            const profilePictureInput = document.getElementById('profile_picture');
            const profilePicturePreview = document.getElementById('profilePicturePreview');
            const profilePictureFallback = document.getElementById('profilePictureFallback');
            const profilePictureForm = document.getElementById('profilePictureForm');
            const profilePictureStatus = document.getElementById('profilePictureStatus');

            if (profilePictureInput) {
                profilePictureInput.addEventListener('change', () => {
                    const file = profilePictureInput.files?.[0];
                    if (!file) return;
                    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
                        profilePictureStatus.textContent = 'Choose a JPG, PNG, or WebP image up to 5MB.';
                        profilePictureStatus.className = 'text-sm text-red-600';
                        profilePictureInput.value = '';
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = event => {
                        profilePicturePreview.src = event.target.result;
                        profilePicturePreview.classList.remove('hidden');
                        if (profilePictureFallback) profilePictureFallback.classList.add('hidden');
                        profilePictureStatus.textContent = file.name;
                        profilePictureStatus.className = 'text-sm text-slate-500';
                    };
                    reader.readAsDataURL(file);
                });
            }

        </script>
    </body>
</html>

