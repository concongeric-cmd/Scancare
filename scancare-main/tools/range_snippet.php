<?php
        if (empty($identifier) || empty($password)) {
            $error = 'Please enter your email/phone and password.';
        } else {
            try {
                $pdo = getDatabaseConnection();
                
                if ($pdo === false) {
                    $error = 'Database connection error. Please try again later.';
                } else {
                    // ============================================================
                    // ATTEMPT 1: Query RESIDENT table
                    // ============================================================
                    // Fetch resident by email or phone and verify password
                    // ============================================================
                    
                    $residentColumns = "resident_id, full_name, email, password_hash, qr_code_id, contact_number";
                    $residentHasStatus = hasTableColumn($pdo, 'RESIDENT', 'is_active');
                    $residentHasDeletedAt = hasTableColumn($pdo, 'RESIDENT', 'deleted_at');
                    if ($residentHasStatus) {
                        $residentColumns .= ", is_active";
                    }
                    if ($residentHasDeletedAt) {
                        $residentColumns .= ", deleted_at";
                    }

                    $resident = null;
                    if ($identifier_is_email) {
                        $stmt = $pdo->prepare("SELECT {$residentColumns} FROM RESIDENT WHERE LOWER(email) = ? LIMIT 1");
                        $stmt->execute([$identifier_lower]);
                        $resident = $stmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        // Normalize phone to digits for comparison and try several fallbacks
                        $normalized_phone_digits = preg_replace('/\D+/', '', $identifier);

                        // 1) Try matching contact_number after stripping common characters
                        $stmt = $pdo->prepare("SELECT {$residentColumns} FROM RESIDENT WHERE REPLACE(REPLACE(REPLACE(contact_number, ' ', ''), '-', ''), '+', '') = ? LIMIT 1");
                        $stmt->execute([$normalized_phone_digits]);
                        $resident = $stmt->fetch(PDO::FETCH_ASSOC);

                        // 2) Fallback: some phone-only signups store a generated email like
                        //    phoneonly+09123...@noemail.scancare — try matching that pattern
                        if (!$resident && $normalized_phone_digits !== '') {
                            $stmt = $pdo->prepare("SELECT {$residentColumns} FROM RESIDENT WHERE email LIKE ? LIMIT 1");
                            $stmt->execute(['%' . $normalized_phone_digits . '%']);
                            $resident = $stmt->fetch(PDO::FETCH_ASSOC);
                        }
                    }
                    $skipOtherAuth = false;
                    $accountInactiveMessage = '';
                    $loginHandled = false;
                    
                    if ($resident) {
                        $accountInactive = false;
                        if ($residentHasStatus && isset($resident['is_active']) && intval($resident['is_active']) === 0) {
                            $accountInactive = true;
                        }
                        if (!$accountInactive && $residentHasDeletedAt && !empty($resident['deleted_at'])) {
                            $accountInactive = true;
                        }

                        if ($accountInactive) {
                            $accountInactiveMessage = 'Your account has been deactivated. Please contact the administrator.';
                            $skipOtherAuth = true;
                        } else {
                            // Determine which column holds the stored password hash
                            $storedHash = null;
                            if (!empty($resident['password_hash'])) {
                                $storedHash = $resident['password_hash'];
                            } elseif (!empty($resident['password'])) {
                                $storedHash = $resident['password'];
                            }

                            if ($storedHash && password_verify($password, $storedHash)) {
                                // password ok
                            } else {
                                // Failed verification - treat like no-match and continue
                                error_log('LOGIN_VERIFY_FAILED: user=' . ($identifier_lower ?: $identifier) . ' found_id=' . ($resident['resident_id'] ?? 'unknown') . ' stored_hash_present=' . (bool)$storedHash);
                                $storedHash = null;
                            }

                            if ($storedHash) {
                                $pendingLogin = handlePendingDualLogin($pdo, $resident['resident_id'], 'resident', session_id());
                                if ($pendingLogin) {
                                    $error = $pendingLogin['message'];
                                    $skipOtherAuth = true;
                                    $loginHandled = true;
                                    $isAjaxRequest = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || !empty($_POST['ajax']);
                                    if ($isAjaxRequest) {
                                        header('Content-Type: application/json');
                                        echo json_encode([
                                            'status' => 'pending',
                                            'message' => $pendingLogin['message'],
                                            'tempToken' => $pendingLogin['temp_token'],
                                            'deviceId' => $pendingLogin['device_id'],
                                            'location' => $pendingLogin['location']
                                        ]);
                                        exit;
                                    }
                                } else {
                                // ========================================================
                                // RESIDENT LOGIN SUCCESSFUL
                                // ========================================================
                                // Set session variables for resident
                                // resident_id is the primary identifier
                                // user_type = 'resident' for role-based logic
                                // ========================================================
                                
                                $_SESSION['resident_id'] = $resident['resident_id'];
                                $_SESSION['user_type'] = 'resident';
                                $_SESSION['user_id'] = $resident['resident_id'];
                                $_SESSION['user_role'] = 'resident';
                                $_SESSION['user_name'] = $resident['full_name'];
                                $_SESSION['email'] = $identifier_is_email ? $identifier_lower : $identifier;
                                $_SESSION['login_time'] = time();

                                markUserLoginActive($pdo, $resident['resident_id'], 'resident', session_id());
                                
                                error_log("RESIDENT LOGIN: Resident ID {$resident['resident_id']} logged in successfully");
                                
                                // Show success modal instead of immediate redirect
                                $show_success_modal = true;
                                $success_user_name = $resident['full_name'];
                                $success_redirect_url = 'dashboard.php';
                                $loginHandled = true;
                            }
                        }
                    }
                    
                    // If the resident account was found but inactive, do not continue to other login flows
                    if (!$loginHandled && !empty($error) && isset($resident) && $resident) {
                        // Skip admin / worker authentication for this email because it is a resident account that is blocked
                        throw new Exception($error);
                    }
                    
                    // ============================================================
                    // ATTEMPT 2: Query SYSTEM_ADMINS table (Admin Login)
                    // ============================================================
                    // Check SYSTEM_ADMINS for administrator accounts
                    // ============================================================
                    
                    $admin = null;
                    if (!$loginHandled) {
                        if ($identifier_is_email) {
                            $stmt = $pdo->prepare("
                                SELECT 
                                    admin_id,
                                    username,
                                    email,
                                    password_hash,
                                    full_name,
                                    role,
                                    is_active
                                FROM SYSTEM_ADMINS 
                                WHERE LOWER(email) = ? AND is_active = 1
                                LIMIT 1
                            ");
                            $stmt->execute([$identifier_lower]);
                            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                        }
                        
                        if (!$skipOtherAuth && $admin && password_verify($password, $admin['password_hash'])) {
                            $pendingLogin = handlePendingDualLogin($pdo, $admin['admin_id'], 'admin', session_id());
                            if ($pendingLogin) {
                                $error = $pendingLogin['message'];
                                $skipOtherAuth = true;
                                $loginHandled = true;
                                $isAjaxRequest = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || !empty($_POST['ajax']);
                                if ($isAjaxRequest) {
                                    header('Content-Type: application/json');
                                    echo json_encode([
                                        'status' => 'pending',
                                        'message' => $pendingLogin['message'],
                                        'tempToken' => $pendingLogin['temp_token'],
                                        'deviceId' => $pendingLogin['device_id'],
                                        'location' => $pendingLogin['location']
                                    ]);
                                    exit;
                                }
                            } else {
                                // ========================================================
                                // ADMIN LOGIN SUCCESSFUL
                                // ========================================================
                                // Set session variables for administrator
                                // admin_id is the primary identifier
                                // user_type = 'admin'
                                // ========================================================
                                
                                $_SESSION['admin_auth'] = true;
                                $_SESSION['admin_id'] = $admin['admin_id'];
                                $_SESSION['user_type'] = 'admin';
                                $_SESSION['user_id'] = $admin['admin_id'];
                                $_SESSION['user_role'] = 'admin';
                                $_SESSION['admin_role'] = $admin['role'];
                                $_SESSION['user_name'] = $admin['full_name'];
                                if ($identifier_is_email) {
                                    $_SESSION['email'] = $identifier_lower;
                                } else {
                                    unset($_SESSION['email']);
                                }
                                $_SESSION['login_time'] = time();

                                markUserLoginActive($pdo, $admin['admin_id'], 'admin', session_id());
                                
                                error_log("ADMIN LOGIN: Admin ID {$admin['admin_id']} logged in successfully");
                                
                                // Show success modal instead of immediate redirect
                                $show_success_modal = true;
                                $success_user_name = $admin['full_name'];
                                $success_redirect_url = 'administrator_panel.php';
                                $loginHandled = true;
                            }
                        }
                    }
                    
                    // ============================================================
                    // ATTEMPT 3: Query HEALTH_WORKER table
                    // ============================================================
                    // If resident and admin not found, check HEALTH_WORKER table
                    // This includes bhw and other health workers
                    // ============================================================
                    $healthWorkerHasStatus = hasTableColumn($pdo, 'HEALTH_WORKER', 'is_active');
                    $healthWorkerHasDeletedAt = hasTableColumn($pdo, 'HEALTH_WORKER', 'deleted_at');
                    $workerColumns = "worker_id, full_name, email, password_hash, role";
                    if ($healthWorkerHasStatus) {
                        $workerColumns .= ", is_active";
                    }
                    if ($healthWorkerHasDeletedAt) {
                        $workerColumns .= ", deleted_at";
                    }

                    if (!$loginHandled) {
                        $worker = null;
                        if ($identifier_is_email) {
                            $stmt = $pdo->prepare("
                                SELECT 
                                    {$workerColumns}
                                FROM HEALTH_WORKER 
                                WHERE LOWER(email) = ?
                                LIMIT 1
                            ");
                            $stmt->execute([$identifier_lower]);
                            $worker = $stmt->fetch(PDO::FETCH_ASSOC);
                        } else {
                            $stmt = $pdo->prepare("SELECT {$workerColumns}, contact_number FROM HEALTH_WORKER WHERE contact_number = ? LIMIT 1");
                            $stmt->execute([$identifier]);
                            $worker = $stmt->fetch(PDO::FETCH_ASSOC);
                        }
                        
                        if (!$skipOtherAuth && $worker) {
                            $workerInactive = false;
                            if ($healthWorkerHasStatus && isset($worker['is_active']) && intval($worker['is_active']) === 0) {
                                $workerInactive = true;
                            }
                            if (!$workerInactive && $healthWorkerHasDeletedAt && !empty($worker['deleted_at'])) {
                                $workerInactive = true;
                            }

                            if ($workerInactive) {
                                $error = 'Your account has been deactivated. Please contact the administrator.';
                            } elseif (password_verify($password, $worker['password_hash'])) {
                                $pendingLogin = handlePendingDualLogin($pdo, $worker['worker_id'], 'health_worker', session_id());
                                if ($pendingLogin) {
                                    $error = $pendingLogin['message'];
                                    $skipOtherAuth = true;
                                    $loginHandled = true;
                                    $isAjaxRequest = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || !empty($_POST['ajax']);
                                    if ($isAjaxRequest) {
                                        header('Content-Type: application/json');
                                        echo json_encode([
                                            'status' => 'pending',
                                            'message' => $pendingLogin['message'],
                                            'tempToken' => $pendingLogin['temp_token'],
                                            'deviceId' => $pendingLogin['device_id'],
                                            'location' => $pendingLogin['location']
                                        ]);
                                        exit;
                                    }
                                } else {
                                    // ========================================================
                                    // HEALTH WORKER LOGIN SUCCESSFUL
                                    // ========================================================
                                    // Set session variables for health worker
                                    // worker_id is the primary identifier
                                    // user_type = 'health_worker'
                                    // worker_role stores the specific role (bhw, etc.)
                                    // ========================================================
                                    
                                    $_SESSION['worker_id'] = $worker['worker_id'];
                                    $_SESSION['user_type'] = 'health_worker';
                                    $_SESSION['user_id'] = $worker['worker_id'];
                                    $_SESSION['user_role'] = 'bhw';
                                    $_SESSION['worker_role'] = $worker['role'];  // Store actual role
                                    $_SESSION['user_name'] = $worker['full_name'];
                                    if ($identifier_is_email) {
                                        $_SESSION['email'] = $identifier_lower;
                                    } else {
                                        unset($_SESSION['email']);
                                    }
                                    $_SESSION['login_time'] = time();

                                    markUserLoginActive($pdo, $worker['worker_id'], 'health_worker', session_id());
                                    
                                    error_log("HEALTH WORKER LOGIN: Worker ID {$worker['worker_id']} ({$worker['role']}) logged in successfully");
                                    
                                    // Show success modal instead of immediate redirect
                                    $show_success_modal = true;
                                    $success_user_name = $worker['full_name'];
                                    $success_redirect_url = 'dashboard_bhw.php';
                                    $loginHandled = true;
                                }
                            }
                        }
                    }
                    
                    // ============================================================
                    // LOGIN FAILED
                    // ============================================================
                    // Email not found in either table OR password incorrect
                    // ============================================================
                    
                    if (!empty($accountInactiveMessage)) {
                        $error = $accountInactiveMessage;
                    } else {
                        $error = 'Invalid email/phone or password.';
                    }
                    error_log("LOGIN FAILED: Identifier {$identifier_lower} - credentials not matched");
                }
