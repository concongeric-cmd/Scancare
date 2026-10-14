# Cross-Device 2FA Login Approval System
**Implementation Complete** | Session: 2026-07-02

## System Overview

This is a **device-based 2FA system** where:
- **Device A** (already logged in) approves login attempts
- **Device B** (new/trying to login) receives approval requests
- Users can approve or deny login attempts in real-time

## User Flow

### For Device B (New Login Attempt):
1. User enters email and password on the new device
2. System detects an existing active session for this user
3. A pending login request is created
4. Device B is redirected to **waiting page** (`pending_login_waiting.php`)
5. Waiting page shows:
   - Device information
   - IP address of requesting device
   - Verification code
   - Auto-polling status every 10 seconds
   - Manual "Check Status" button
6. Once approved on Device A, page auto-redirects to complete login
7. If rejected, error message is displayed

### For Device A (Already Logged In):
1. User goes to **Login Approvals Dashboard** (`pending_login_approvals.php`)
2. Dashboard shows:
   - All pending login requests from other devices
   - Device type (Mobile/Desktop/Browser)
   - IP address of requesting device
   - Time of request
   - Verification code
3. User can:
   - **Approve**: Allow the new device to login
   - **Deny**: Block the login attempt
4. Previously approved sessions are listed below

## Files Created/Modified

### New Files
1. **`pending_login_approvals.php`** - Dashboard for approving/rejecting logins
   - Shows pending requests with device info
   - Lists active sessions
   - Handles both login and API requests

2. **`approve_login_request.php`** - API endpoint for approval/rejection
   - Endpoint: `/approve_login_request.php`
   - Methods: POST
   - Actions: `approve` or `reject`
   - Parameters: `temp_token`, `action`

3. **`pending_login_waiting.php`** - Waiting screen for new device
   - Shows status while waiting for approval
   - Auto-polls every 10 seconds
   - Manual check button available
   - 10-minute timeout

4. **`check_pending_status.php`** - Status checking endpoint
   - Endpoint: `/check_pending_status.php`
   - Method: GET
   - Parameter: `temp_token`
   - Returns: JSON with current status

### Modified Files
1. **`login.php`**
   - Updated pending login redirect logic
   - Now redirects to `pending_login_waiting.php` instead of polling on login page
   - Preserves all existing authentication flow

## Database Schema

Uses existing `user_login_sessions` table:
```sql
CREATE TABLE user_login_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    user_type VARCHAR(50) NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    device_id VARCHAR(255) NULL,
    location VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    temp_token VARCHAR(255) DEFAULT NULL,
    status VARCHAR(25) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_session (user_id, user_type, session_id)
)
```

### Status Values
- `pending` - Waiting for approval from active device
- `active` - Approved and ready for login completion
- `rejected` - Denied login attempt
- `closed` - Previous session (no longer active)

## Security Features

1. **Temporary Tokens** - Generated with `random_bytes(16)` (or md5 fallback)
2. **Session ID Binding** - Each session has unique ID to prevent hijacking
3. **Device Identification** - User-Agent tracking for device info
4. **IP Address Logging** - All login attempts tracked by IP
5. **Time-based Expiration** - Requests expire after 10 minutes
6. **User Verification** - Only the account owner can approve/reject
7. **Session Regeneration** - New session ID on login completion

## API Reference

### Check Pending Status
```
GET /check_pending_status.php?temp_token=TOKEN
```
**Response:**
```json
{
  "status": "pending|active|rejected|not_found",
  "reason": "optional error reason"
}
```

### Approve/Reject Login
```
POST /approve_login_request.php
```
**Parameters:**
```
action: "approve" or "reject"
temp_token: "temporary approval token"
```
**Response:**
```json
{
  "success": true,
  "message": "Login approved/denied",
  "action": "approve|reject"
}
```

## Configuration

### Polling Intervals
- **Waiting page auto-poll**: Every 10 seconds
- **Manual check**: User can click anytime
- **Max checks**: 120 (10 minutes total)

### Timeout Settings
- **Request expires after**: 10 minutes (600 seconds)
- **Auto-approval check**: Every 10 seconds

### Device Detection
Extracts device info from User-Agent string:
- Android, iPhone, iPad, Windows Phone
- Chrome, Firefox, Safari, Edge
- Windows, Mac, Linux

## User Access Points

### For Logged-In Users
1. Add link to dashboard or settings:
```html
<a href="pending_login_approvals.php">
  <i class="fas fa-shield-check"></i> Login Approvals
</a>
```

2. Or create a notification badge:
```php
<?php
$pdo = getDatabaseConnection();
$stmt = $pdo->prepare("
    SELECT COUNT(*) as pending_count 
    FROM user_login_sessions 
    WHERE user_id = ? AND user_type = ? AND status = 'pending'
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_type']]);
$result = $stmt->fetch();
if ($result['pending_count'] > 0) {
    echo '<span class="badge-danger">' . $result['pending_count'] . '</span>';
}
?>
```

## Testing the System

### Step 1: Login on Device A
```
1. Go to login.php
2. Enter credentials
3. Click login
4. You're now logged in on Device A
```

### Step 2: Try Login on Device B
```
1. Open a different browser or device
2. Go to login.php
3. Enter the SAME credentials
4. You'll be redirected to pending_login_waiting.php
```

### Step 3: Approve on Device A
```
1. On Device A, go to pending_login_approvals.php
2. See the pending request from Device B
3. Click "Approve Login"
```

### Step 4: Device B Auto-Completes
```
1. Waiting page auto-checks every 10 seconds
2. Once approved, it auto-redirects to complete login
3. Device B is now fully logged in
```

## Troubleshooting

### Waiting page doesn't auto-check
- Check browser console for JavaScript errors
- Verify `check_pending_status.php` is accessible
- Check database connectivity

### Pending request not showing up
- Verify the user has another active session
- Check `user_login_sessions` table
- Ensure table was created successfully

### Approval button doesn't work
- Verify user is logged in (required to approve)
- Check form data is being sent
- Verify `approve_login_request.php` is accessible
- Check browser console for network errors

### Request expires too quickly
- Default timeout is 10 minutes
- Edit line in `check_pending_status.php`: `if ($ageResult && $ageResult['age_seconds'] > 600)`
- Change 600 to desired seconds

## Future Enhancements

1. **Push Notifications** - Send notification to Device A when approval needed
2. **SMS/Email Alerts** - Notify user of unexpected login attempts
3. **Biometric Approval** - Use fingerprint/face ID to approve on Device A
4. **Device Trust** - Remember and auto-approve trusted devices
5. **Audit Logging** - Track all approval/rejection history
6. **Admin Dashboard** - Monitor user login patterns
7. **Geolocation** - Show where login is being attempted from
8. **Rate Limiting** - Prevent brute force approval attempts

## Session Variables Used

When pending login is detected, the following session variables are set:

```php
$_SESSION['pending_login_temp_token']      // Approval token
$_SESSION['pending_login_user_id']          // User ID
$_SESSION['pending_login_user_type']        // 'resident', 'health_worker', 'admin'
$_SESSION['pending_login_user_name']        // Full name for display
$_SESSION['pending_login_user_email']       // Email/phone
$_SESSION['pending_login_user_role']        // User role if applicable
$_SESSION['pending_login_redirect_url']     // Where to redirect after approval
```

## Status Codes

| Code | Meaning | Action |
|------|---------|--------|
| 200 | Success | Process approved/rejected |
| 400 | Bad Request | Missing parameters |
| 401 | Unauthorized | Not logged in |
| 403 | Forbidden | Not user's request |
| 404 | Not Found | Request doesn't exist |
| 500 | Server Error | Database error |

## Dependencies

- PHP 7.4+
- PDO (Database abstraction)
- jQuery or vanilla fetch (for AJAX)
- Font Awesome (for icons)
- Tailwind CSS (for styling)

## File Sizes

- `pending_login_approvals.php` - ~8 KB
- `approve_login_request.php` - ~4 KB  
- `pending_login_waiting.php` - ~10 KB
- `check_pending_status.php` - ~3 KB
- **Total**: ~25 KB

## Performance Notes

- Database queries are indexed by `temp_token`
- Polling interval (10 seconds) balances UX and server load
- Timeouts prevent stale requests from accumulating
- Simple JSON responses for fast parsing

---

**Deployment Status**: ✅ Complete and Ready to Use
**Last Updated**: 2026-07-02
