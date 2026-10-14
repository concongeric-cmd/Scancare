
# ScanCare SMS Integration Guide

This guide describes the current SMS implementation for ScanCare. The system now uses Semaphore API v4 to send messages from `send_sms_handler.php`.

## Active Integration Files
- `send_sms_handler.php` — primary message send handler
- `dashboard_bhw.php` — BHW dashboard helper interface
- `send_sms_test.php` — Semaphore send test script

## Current SMS Flow
1. A BHW or admin sends a message from the UI.
2. The UI submits data to `send_sms_handler.php`.
3. `send_sms_handler.php` normalizes phone numbers and sends them to Semaphore.
4. Semaphore returns delivery data for each recipient.
5. Each message is logged in `sms_log` with status and metadata.

## Configuration
Update the constants in `send_sms_handler.php`:
```php
define('SEMAPHORE_API_KEY', '<YOUR_SEMAPHORE_API_KEY>');
define('SEMAPHORE_SENDER', 'SCANCARE');
define('SEMAPHORE_API_URL', 'https://semaphore.co/api/v4/messages');
```

## Supported Send Modes
- `mode=all` — send to all residents with contact numbers
- `mode=appointment` — send to appointment recipients for a date
- `mode=custom` — send to specified phone numbers

## Example Request
```http
POST /send_sms_handler.php
Content-Type: application/x-www-form-urlencoded

mode=custom&message=Hello+from+ScanCare&custom_numbers=09306938676,639171234567
```

## Phone Number Normalization
The handler normalizes numbers for the Philippines:
- `09306938676` → `639306938676`
- `639306938676` → `639306938676`
- `+639306938676` → `639306938676`

## Example normalization function
```php
function normalize_ph_number($number) {
    $n = preg_replace('/\D/', '', $number);
    if (strlen($n) === 10) return '63' . $n;
    if (substr($n, 0, 1) === '0') return '63' . substr($n, 1);
    if (substr($n, 0, 2) === '63') return $n;
    return null;
}
```

## Logging and monitoring
The handler logs each message to `sms_log`, including:
- `bhw_id`
- recipient
- message
- status
- network
- message_id
- mode
- `sent_at`

Use this query to inspect recent results:
```sql
SELECT * FROM sms_log ORDER BY sent_at DESC LIMIT 50;
```

## Legacy provider status
- iTexMo was previously used for SMS sending.
- The iTexMo integration is now removed from active runtime paths.
- Semaphore is the current supported SMS provider for ScanCare.

## Notes
- Ensure `SEMAPHORE_API_KEY` is kept secure.
- Do not store production API keys in files committed to source control.
- Validate session authorization before sending messages.
