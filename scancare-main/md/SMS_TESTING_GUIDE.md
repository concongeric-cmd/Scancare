# SMS Feature - Testing & Debugging Guide

## What Was Fixed

### 1. **Missing sms_log Table**
The handler was trying to log SMS sends to `sms_log` table that didn't exist. This would cause silent failures.
- **Fix**: Handler now auto-creates the table on first use if it doesn't exist

### 2. **Comprehensive Error Logging**
Handler now logs every step to PHP's error_log for debugging:
- Session variables check
- Database connection status  
- Recipient collection
- API call details
- Response codes and errors

### 3. **Better Error Display**
JavaScript now shows actual error messages with details:
- Opens browser DevTools → Console tab to see raw responses
- Shows what the server is actually returning

### 4. **Robust Response Handling**
Wrapped main logic in try-catch to ensure JSON is always returned.

---

## How to Test

### Step 1: Open Browser DevTools
1. Open the BHW Dashboard
2. Press `F12` to open Developer Tools
3. Go to **Console** tab (you'll see log messages here)

### Step 2: Try Sending SMS
1. Fill the SMS form with:
   - **Recipient Type**: "Custom Contact List"
   - **Phone Number**: A valid number (e.g., `09123456789` or `639123456789`)
   - **Message**: `Hello test` (or any short message)
2. Click "Send SMS"
3. Watch the **Console** tab for debug messages

### Step 3: Check for Errors
You should see console messages like:
```
Sending SMS: {recipientType: 'custom', messageLength: 11, customNumbers: true}
Response status: 200
Content-Type: application/json; charset=utf-8
Raw response text: {"success":true,"sent":1,"failed":0,"total":1,"message":"SMS sent: 1 successful, 0 failed..."}
Parsed data: {success: true, sent: 1, failed: 0, total: 1, message: '...'}
```

If you see an error, post the console message exactly as shown.

---

## Understanding Phone Number Normalization

The handler converts phone numbers to Semaphore API format (E.164):
- `09123456789` → `639123456789` ✅
- `+639123456789` → `639123456789` ✅  
- `639123456789` → `639123456789` ✅
- `912345678` → Invalid (too short)

**Make sure you test with a real Philippines mobile number!**

---

## If SMS Still Doesn't Send

### Check 1: Look at PHP Error Log
Location: `c:\xampp\apache\logs\php_error.log`

The handler writes detailed logs. Copy the relevant section when reporting the issue.

### Check 2: Database Connection
The handler will log "Database connected successfully" - if you don't see this, there's a DB connection issue.

### Check 3: Semaphore API Status
The handler logs the response from Semaphore. If HTTP code is not 200, check:
- API key validity: Currently using `f43478ec3207194df9db9f90ba337fa0`
- Semaphore account balance
- API rate limits

### Check 4: Session Variables
Handler logs:
- `Worker ID: [number]` - Must not be "NOT SET"
- `User Type: health_worker` - Must exactly match this

If either is missing, you're not logged in as a BHW.

---

## Next: Additional Validation

After SMS works, we need to:
1. ✅ Fix SMS feature (in progress)
2. ⬜ Audit all other broken features  
3. ⬜ Validate workflow matches flowcharts
4. ⬜ Fix any authentication/authorization issues found

