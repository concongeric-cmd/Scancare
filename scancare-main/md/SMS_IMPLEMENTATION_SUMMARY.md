**Date:** 2026-08-07
**System:** ScanCare Healthcare Management Platform
**Integration:** Semaphore SMS API v4

**Active Files:**
- `send_sms_handler.php` — Main SMS send route for BHW and appointment notifications
- `dashboard_bhw.php` — Semaphore SMS helper in the BHW dashboard
- `send_sms_test.php` — Simple Semaphore SMS test script

**Overview:**
The current SMS implementation uses Semaphore's API v4 to send SMS messages. Incoming phone numbers are normalized to Philippine E.164 format, batched, and sent through Semaphore. The response is logged in `sms_log` for auditing.

**Key Features:**
- Batch sending up to 1000 recipients per request
- Recipient normalization to `63XXXXXXXXXX`
- Per-message logging with status, network, and message_id
- Admin/BHW session authorization
- JSON response handling for success/failure

**Configuration:**
```php
define('SEMAPHORE_API_KEY', '<YOUR_SEMAPHORE_API_KEY>');
define('SEMAPHORE_SENDER', 'SCANCARE');
define('SEMAPHORE_API_URL', 'https://semaphore.co/api/v4/messages');
```

**Supported send modes:**
- `all` — all residents with a contact number
- `appointment` — residents with appointments on a given date
- `custom` — comma-separated recipients provided by the user

**Phone normalization examples:**
- `09306938676`  → `639306938676`
- `639306938676` → `639306938676`
- `+639306938676` → `639306938676`

**Sample SQL to review SMS logs:**
```sql
SELECT * FROM sms_log ORDER BY sent_at DESC LIMIT 20;
```

**Current provider note:**
- The prior iTexMo SMS gateway integration has been deprecated and removed.
- Semaphore is the active SMS provider for the ScanCare application.
- Any references to `sms-gateway.php`, `sms-events.php`, or iTexMo credentials should be considered obsolete.

**Usage notes:**
1. Use `dashboard_bhw.php` or other admin/BHW interfaces to send messages.
2. Verify that `send_sms_handler.php` is called only by authorized sessions.
3. Monitor the `sms_log` table for delivery status and failures.
