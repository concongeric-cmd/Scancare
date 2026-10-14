

This document indexes all files using the FMCSMS API for SMS notifications throughout the ScanCare system.

- **API Endpoint**: https:
- **API Key**: `fmcsms_30f4c20be95b344575c5ab9f2395867df377ca92e78db0d5`
- **Sender Name**: `ScanCare`
- **From Number**: `+639193162012`

```json
{
    "SenderName": "ScanCare",
    "ToNumber": "+639XXXXXXXXX",
    "MessageBody": "Your message here",
    "FromNumber": "+639193162012"
}
```

- `Content-Type: application/json`
- `X-API-Key: fmcsms_30f4c20be95b344575c5ab9f2395867df377ca92e78db0d5`


**Purpose**: Automated SMS notification scheduler for upcoming appointments  
**Location**: `/sms_scheduler.php`  
**Function**: `sendSMSViaFMCSMS($phone_number, $message)`

**How to run**:
- Browser: http:
- Sends SMS reminders 24 hours before appointments
- Logs all activity to `sms_scheduler.log`

**Configuration Constants**:
```php
define('FMCSMS_API_KEY', 'fmcsms_30f4c20be95b344575c5ab9f2395867df377ca92e78db0d5');
define('FMCSMS_API_URL', 'https:
define('FMCSMS_SENDER_NAME', 'ScanCare');
define('FMCSMS_FROM_NUMBER', '+639193162012');
define('NOTIFICATION_HOURS_BEFORE', 24);
```

**Purpose**: Sends SMS notification when appointment is approved  
**Location**: `/approve_appointment_handler.php`  
**Function**: `sendApprovalSMS($phone_number, $resident_name, $apt_date, $apt_time)`

**When triggered**:
- When admin/BHW approves a resident appointment
- Sends approval notification with appointment details

**Message Template**:
```
Great news [RESIDENT_NAME]! Your appointment at the Barangay Health Center has been approved.

📅 Appointment: [DATE_TIME]
📍 Location: Barangay Health Center

Please arrive 10 minutes early. Thank you!

ScanCare - Barangay Health Center
```


```
Browser URL: http:
Output: Console logs + sms_scheduler.log file
```

```php
$phone_number = '+639652955989';
$message = 'Hello! This is a test message from ScanCare.';
$result = sendSMSViaFMCSMS($phone_number, $message);

if ($result['success']) {
    echo "SMS sent successfully!";
} else {
    echo "Error: " . $result['error'];
}
```


The system handles:
- Missing phone numbers (marked as failed in database)
- Network/cURL errors
- HTTP error responses (logs to error_log)
- Database update failures

All errors are logged to:
- `sms_scheduler.log` (for scheduler)
- PHP error_log (for appointment approvals)

```sql
is_notified              TINYINT(1)    
notification_sent_at    TIMESTAMP     
notification_error      VARCHAR(255)  
```


Edit sms_scheduler.php and change `NOTIFICATION_HOURS_BEFORE` to 0 to test immediately.

```bash
tail -f sms_scheduler.log
```

```
[2026-06-18 10:30:45] ===== SMS SCHEDULER STARTED =====
[2026-06-18 10:30:45] Found 5 appointment(s) requiring SMS notification
[2026-06-18 10:30:46] 📱 [APT #14] Sending SMS to John Doe (+639652955989)...
[2026-06-18 10:30:47] ✅ [APT #14] SMS sent successfully to +639652955989
[2026-06-18 10:30:47] ✓ [APT #14] Database updated - marked as notified
```


All instances of Semaphore API (semaphore.co) have been replaced with FMCSMS API:

- ✅ sms_scheduler.php
- ✅ approve_appointment_handler.php

1. Updated API endpoint URL
2. Changed request headers (JSON format instead of form-urlencoded)
3. Updated payload field names (SenderName, ToNumber, MessageBody, FromNumber)
4. Updated API key
5. Renamed function from `sendSMSViaSemaphore` to `sendSMSViaFMCSMS`


For more information:
- FMCSMS API: https:
- ScanCare SMS Logs: `/sms_scheduler.log`


