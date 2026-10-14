# Cross-Device 2FA - Quick Setup & Integration Guide

## ✅ What's Already Done

The complete system is already implemented and ready to use:

1. ✅ **Pending Login Detection** - Built into `login.php`
2. ✅ **Waiting Page** - `pending_login_waiting.php` (auto-polling every 10 seconds)
3. ✅ **Approval Dashboard** - `pending_login_approvals.php` (for logged-in users)
4. ✅ **API Endpoints** - `approve_login_request.php` and `check_pending_status.php`
5. ✅ **Database Table** - `user_login_sessions` (auto-created)

## 🚀 How to Use

### For Users (Device A - Already Logged In)

When they want to manage login approvals:

1. Visit: `http://localhost/itelec4/pending_login_approvals.php`
2. See pending requests from other devices
3. Click "Approve Login" or "Deny Access"

### For New Login Attempts (Device B)

Users don't need to do anything special:

1. Go to login.php
2. Enter email/phone and password
3. If another active session exists, they're automatically redirected to waiting page
4. Waiting page auto-checks every 10 seconds
5. Once approved on Device A, login completes automatically

## 🔗 Integration with Your Dashboard

### Add Navigation Link

Add this to your dashboard's navigation or settings menu:

```html
<!-- In your dashboard.php or navigation bar -->
<?php if (!empty($_SESSION['resident_id']) || !empty($_SESSION['worker_id']) || !empty($_SESSION['admin_id'])): ?>
    <a href="pending_login_approvals.php" class="nav-link">
        <i class="fas fa-shield-check"></i> Login Approvals
    </a>
<?php endif; ?>
```

### Add Notification Badge (Optional)

Show pending approval count in the header:

```php
<?php
// In your dashboard header
if (!empty($_SESSION['user_id']) && !empty($_SESSION['user_type'])) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as pending_count 
            FROM user_login_sessions 
            WHERE user_id = ? AND user_type = ? AND status = 'pending'
        ");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_type']]);
        $result = $stmt->fetch();
        
        if ($result && $result['pending_count'] > 0) {
            echo '<span class="badge badge-danger">' . $result['pending_count'] . ' pending approval(s)</span>';
        }
    } catch (Exception $e) {
        // Silently fail if table doesn't exist yet
    }
}
?>
```

### Add to Settings Menu

```php
<!-- In your settings or account management page -->
<a href="pending_login_approvals.php" class="list-group-item list-group-item-action">
    <i class="fas fa-lock"></i> 
    Manage Login Approvals
    <?php if ($pending_count > 0): ?>
        <span class="badge badge-warning float-right"><?php echo $pending_count; ?></span>
    <?php endif; ?>
</a>
```

## 🧪 Testing

### Scenario 1: Approve a Login

```
1. Device A (Chrome):
   - Go to http://localhost/itelec4/login.php
   - Login with: testuser@gmail.com / password123
   - Successful login to dashboard

2. Device B (Firefox or Incognito):
   - Go to http://localhost/itelec4/login.php
   - Login with: testuser@gmail.com / password123
   - Redirected to pending_login_waiting.php
   - See message: "Waiting for Approval..."

3. Device A (Chrome):
   - Click link: http://localhost/itelec4/pending_login_approvals.php
   - See pending request from Device B
   - Click "Approve Login" (green button)

4. Device B (Firefox):
   - Auto-checks every 10 seconds
   - After approval, auto-redirects to dashboard
   - Successfully logged in!
```

### Scenario 2: Deny a Login

```
1. Same as above, but in step 3:
   - Click "Deny Access" (red button)

2. Device B sees:
   - Alert: "Login denied. Please try again."
   - Redirected back to login.php
```

### Scenario 3: Timeout

```
1. Device B tries to login
2. Waits for approval
3. After 10 minutes (no approval):
   - Request automatically expires
   - "Request expired. Please login again."
   - Redirected to login.php
```

## 📱 How Device Detection Works

The system automatically identifies devices:

**From User-Agent:**
- Android Device
- iPhone / iPad
- Chrome / Firefox / Safari / Edge
- Windows PC / Mac / Linux

**IP Address:**
- Stored as IPv4 or IPv6
- Displayed in approval dashboard

Example in approval dashboard:
```
Device: Chrome Browser
IP Address: 192.168.1.100
Verification Code: A1B2C3D4...
```

## 🔒 Security Details

### Session Management
- Session ID regenerated on login
- Each device has unique session
- Temporary token system for approval
- Auto-cleanup of expired requests

### Token Security
- 32-character hex tokens (random_bytes)
- One-time use
- Bound to specific user and user_type
- Expires after 10 minutes

### Validation
- Only logged-in users can approve/reject
- Approvals must match the user_id and user_type
- IP addresses and User-Agent logged for audit

## 🛠️ Configuration Options

### Change Timeout (from 10 minutes to X minutes)

Edit `check_pending_status.php`, find this line:
```php
if ($ageResult && $ageResult['age_seconds'] > 600) {
```

Change 600 to seconds you want:
- 300 = 5 minutes
- 600 = 10 minutes (current)
- 1800 = 30 minutes
- 3600 = 60 minutes

### Change Polling Interval (from 10 seconds to X seconds)

Edit `pending_login_waiting.php`, find this line:
```javascript
checkInterval = setInterval(autoCheck, 10000); // Check every 10 seconds
```

Change 10000 to milliseconds you want:
- 5000 = 5 seconds
- 10000 = 10 seconds (current)
- 15000 = 15 seconds
- 30000 = 30 seconds

## 📊 Database Queries

### Check Pending Requests

```sql
SELECT * FROM user_login_sessions 
WHERE status = 'pending' 
ORDER BY created_at DESC;
```

### Check User's Active Sessions

```sql
SELECT * FROM user_login_sessions 
WHERE user_id = '906097' 
AND user_type = 'resident' 
AND status = 'active';
```

### Check Approval History (Last 24 Hours)

```sql
SELECT * FROM user_login_sessions 
WHERE user_id = '906097' 
AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY created_at DESC;
```

### Clean Up Expired Requests

```sql
DELETE FROM user_login_sessions 
WHERE status = 'pending' 
AND created_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE);
```

## 🐛 Troubleshooting

### "No pending login requests" but expected to see one

**Cause:** User doesn't have another active session
**Solution:** 
- Make sure Device A is actually logged in
- Check session hasn't timed out
- Check cookies are enabled

### Approval button doesn't work

**Cause:** Not authenticated or form didn't submit
**Solution:**
1. Make sure you're logged in
2. Check browser console (F12) for errors
3. Verify `approve_login_request.php` exists
4. Check PHP error logs

### Waiting page doesn't auto-check

**Cause:** JavaScript issue or endpoint unreachable
**Solution:**
1. Open browser console (F12)
2. Check for JavaScript errors
3. Verify `check_pending_status.php` is accessible
4. Check network tab for API calls

### Status shows "pending" but approval was clicked

**Cause:** Database update didn't happen
**Solution:**
1. Check database connectivity
2. Verify write permissions on table
3. Check PHP error logs
4. Manually check table: `SELECT * FROM user_login_sessions WHERE status = 'pending'`

## 📞 Support

For issues or questions:

1. Check `CROSS_DEVICE_2FA_IMPLEMENTATION.md` for detailed docs
2. Review PHP error logs
3. Check database logs
4. Verify all files exist and have correct permissions

## ✨ Features Summary

| Feature | Supported |
|---------|-----------|
| Device detection | ✅ Yes |
| IP logging | ✅ Yes |
| Real-time approval | ✅ Yes |
| Auto-timeout | ✅ Yes (10 min) |
| Session binding | ✅ Yes |
| Audit logging | ✅ Device history |
| Temporary tokens | ✅ Random 32-char |
| SMS notifications | ❌ Not included |
| Push notifications | ❌ Not included |
| Device trust | ❌ Future feature |

## 🎯 Next Steps

1. **Add to Dashboard:**
   - Add navigation link to `pending_login_approvals.php`
   - Optional: Add notification badge for pending requests

2. **Test the System:**
   - Follow testing scenarios above
   - Try on different browsers/devices

3. **Monitor Usage:**
   - Check database for failed login attempts
   - Review approval patterns

4. **Optional Enhancements:**
   - Add email/SMS notifications
   - Implement device trust
   - Add admin dashboard
   - Create audit log viewer

---

**Ready to Deploy**: ✅ All systems implemented and tested
**Documentation**: ✅ Complete
**Status**: Production Ready
