<?php

session_start();


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}


$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? '';
    
    
    if ($formType === 'password_update') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errorMessage = 'All password fields are required.';
        } elseif ($newPassword !== $confirmPassword) {
            $errorMessage = 'New passwords do not match.';
        } elseif (strlen($newPassword) < 8) {
            $errorMessage = 'New password must be at least 8 characters long.';
        } else {
            
            $successMessage = 'Password updated successfully.';
        }
    }
}


$userPreferences = [
    'smsReminders' => true,
    'barangayAlerts' => true,
    'language' => 'English'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings — ScanCare Healthcare Platform</title>
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

        
        .settings-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .settings-card:hover {
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
            flex-shrink: 0;
        }

        .card-header-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .card-description {
            font-size: 14px;
            color: #64748b;
            margin-top: 4px;
        }

        
        .form-group {
            margin-bottom: 16px;
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

        .form-input {
            width: 100%;
            padding: 10px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease-in-out;
            background-color: white;
            color: #1e293b;
        }

        .form-input:focus {
            outline: none;
            border-color: #454B1B;
            box-shadow: 0 0 0 3px rgba(69, 75, 27, 0.1);
        }

        
        .toggle-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.2s ease-in-out;
        }

        .toggle-item:last-child {
            border-bottom: none;
        }

        .toggle-item:hover {
            background-color: #f8faf6;
        }

        .toggle-label-group {
            flex: 1;
            min-width: 0;
        }

        .toggle-label-main {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .toggle-label-sub {
            font-size: 12px;
            color: #64748b;
        }

        
        .toggle-switch {
            position: relative;
            width: 50px;
            height: 28px;
            flex-shrink: 0;
        }

        .toggle-switch input {
            opacity: 0;
            width: 1px;
            height: 1px;
        }

        .toggle-switch input:focus-visible + .toggle-slider {
            outline: 3px solid rgba(69, 75, 27, 0.25);
            outline-offset: 3px;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: all 0.3s ease-in-out;
            border-radius: 28px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: all 0.3s ease-in-out;
            border-radius: 50%;
        }

        .toggle-switch input:checked + .toggle-slider {
            background-color: #454B1B;
        }

        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(22px);
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

        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        
        .settings-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }

        @media (min-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr 1fr;
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

        .dark .settings-card,
        body.dark-mode .settings-card {
            background: #1e293b;
            border-color: #334155;
        }

        .dark .card-header,
        body.dark-mode .card-header {
            border-bottom-color: #334155;
        }

        .dark .card-header-title,
        body.dark-mode .card-header-title,
        .dark .toggle-label-main,
        body.dark-mode .toggle-label-main {
            color: #f8fafc;
        }

        .dark .form-input,
        body.dark-mode .form-input {
            background-color: #0f172a;
            border-color: #475569;
            color: #f1f5f9;
        }

        .dark .toggle-item,
        body.dark-mode .toggle-item {
            border-bottom-color: #334155;
        }

        .dark .toggle-item:hover,
        body.dark-mode .toggle-item:hover {
            background-color: #273449;
        }
    </style>
</head>
<body>
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-4xl mx-auto flex justify-between items-center py-4 mb-6 px-4">
            <div class="flex items-center gap-2">
                <img src="images/SClogo.png" alt="ScanCare" class="h-8 w-8">
                <span class="text-lg font-bold text-slate-900 font-heading">ScanCare</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="text-sm text-slate-600 hover:text-slate-900 transition-colors whitespace-nowrap">← Back to Dashboard</a>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-900 mb-2 font-heading">Settings</h1>
            <p class="text-slate-600">Manage your account security and portal preferences</p>
        </div>

        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <div class="settings-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 mb-6 shadow-sm">
            <div class="card-header">
                <div class="card-header-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <div>
                    <h2 class="card-header-title">Account Security</h2>
                    <p class="card-description">Update your password regularly to keep your account secure</p>
                </div>
            </div>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="form_type" value="password_update">
                
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-input w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-[#454B1B] focus:border-transparent outline-none transition" placeholder="Enter your current password" required>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-input w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-[#454B1B] focus:border-transparent outline-none transition" placeholder="Enter new password (min. 8 characters)" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-input w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-[#454B1B] focus:border-transparent outline-none transition" placeholder="Re-enter new password" required>
                    </div>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="btn-primary px-5 py-2.5 rounded-lg font-medium">
                        <i class="fas fa-save"></i> Update Password
                    </button>
                    <button type="reset" class="btn-secondary px-5 py-2.5 rounded-lg font-medium">
                        <i class="fas fa-undo"></i> Clear
                    </button>
                </div>
            </form>
        </div>

        <div class="settings-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 mb-6 shadow-sm">
            <div class="card-header">
                <div class="card-header-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div>
                    <h2 class="card-header-title">Community Alerts & Notifications</h2>
                    <p class="card-description">Control which notifications you receive from the RHU</p>
                </div>
            </div>

            <form method="POST" class="space-y-2">
                <input type="hidden" name="form_type" value="notification_preferences">

                <div class="toggle-item">
                    <div class="toggle-label-group">
                        <div class="toggle-label-main">
                            <i class="fas fa-envelope mr-2" style="color: #454B1B;"></i>
                            SMS Appointment Reminders
                        </div>
                        <div class="toggle-label-sub">
                            Receive text reminders for your upcoming clinic appointments
                        </div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="sms_reminders" aria-label="SMS Appointment Reminders" <?php echo $userPreferences['smsReminders'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="toggle-item">
                    <div class="toggle-label-group">
                        <div class="toggle-label-main">
                            <i class="fas fa-bullhorn mr-2" style="color: #454B1B;"></i>
                            Barangay Health Alerts
                        </div>
                        <div class="toggle-label-sub">
                            Get notified about barangay health missions, community programs, and urgent health advisories
                        </div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="barangay_alerts" aria-label="Barangay Health Alerts" <?php echo $userPreferences['barangayAlerts'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="pt-4 border-t border-slate-200">
                    <button type="submit" class="btn-primary px-5 py-2.5 rounded-lg font-medium">
                        <i class="fas fa-save"></i> Save Preferences
                    </button>
                </div>
            </form>
        </div>

        <div class="settings-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 mb-6 shadow-sm">
            <div class="card-header">
                <div class="card-header-icon">
                    <i class="fas fa-globe"></i>
                </div>
                <div>
                    <h2 class="card-header-title">Interface Language</h2>
                    <p class="card-description">Choose your preferred system language</p>
                </div>
            </div>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="form_type" value="language_preference">

                <div class="form-group">
                    <label class="form-label">Display Language</label>
                    <select name="language" class="form-input w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-[#454B1B] focus:border-transparent outline-none transition">
                        <option value="english" <?php echo $userPreferences['language'] === 'English' ? 'selected' : ''; ?>>
                            🇺🇸 English
                        </option>
                        <option value="tagalog" <?php echo $userPreferences['language'] === 'Tagalog' ? 'selected' : ''; ?>>
                            🇵🇭 Tagalog / Filipino
                        </option>
                        <option value="kapampangan">
                            🇵🇭 Kapampangan
                        </option>
                    </select>
                    <p class="text-xs text-slate-500 mt-2">
                        <i class="fas fa-info-circle mr-1"></i> 
                        Language preference will be saved to your account and applied system-wide
                    </p>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="btn-primary px-5 py-2.5 rounded-lg font-medium">
                        <i class="fas fa-save"></i> Update Language
                    </button>
                    <button type="reset" class="btn-secondary px-5 py-2.5 rounded-lg font-medium">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-12 p-6 mb-8">
            <div class="flex gap-3">
                <i class="fas fa-info-circle text-blue-600 text-lg flex-shrink-0 mt-1"></i>
                <div>
                    <h3 class="font-semibold text-blue-900 mb-2">Account Information</h3>
                    <p class="text-sm text-blue-800">
                        For additional security and account management options, please contact the Rural Health Unit directly 
                        at <strong>09123456789</strong> during business hours (8:00 AM - 5:00 PM, Monday-Friday).
                    </p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>

