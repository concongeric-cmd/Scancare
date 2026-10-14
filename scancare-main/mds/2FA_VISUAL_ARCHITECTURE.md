# Visual Architecture: Cross-Device 2FA System

## System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                    CROSS-DEVICE 2FA SYSTEM                          │
└─────────────────────────────────────────────────────────────────────┘

                          DATABASE
                      ┌──────────────┐
                      │   user_      │
                      │  login_      │
                      │ sessions     │
                      └──────────────┘
                           ▲  ▲
                    ┌──────┘  └──────┐
                    │               │
                    │ (Updates)     │ (Reads)
                    ▼               ▼

            ┌──────────────────┐    ┌────────────────────┐
            │   DEVICE A       │    │    DEVICE B        │
            │   (Logged In)    │    │ (New Login Attempt)│
            └──────────────────┘    └────────────────────┘
                    ▲                        ▲
                    │                        │
        ┌───────────┴────────────┐  ┌───────┴─────────────┐
        │                        │  │                     │
        ▼                        ▼  ▼                     ▼
    
    JavaScript Polling    HTML Waiting Page
    (5 sec intervals)     (10 sec intervals)
         │                        │
         ├─ get_pending_          ├─ check_pending_
         │  logins.php            │  status.php
         │                        │
         ├─ Show POPUP            └─ Auto-redirect
         │                           when approved
         ├─ Listen for click
         │
         └─ approve_login_
            request.php
```

## Real-Time Notification Flow

```
STEP 1: Device B Attempts Login
┌────────────────────────────────────┐
│ login.php                          │
│ User enters email + password       │
│ Clicks "Sign In"                   │
└─────────────┬──────────────────────┘
              │
              ▼
┌────────────────────────────────────┐
│ findUserByIdentifier()             │
│ verifyPasswordHash()               │
│ ✓ Credentials correct              │
└─────────────┬──────────────────────┘
              │
              ▼
┌────────────────────────────────────┐
│ handlePendingDualLogin()           │
│ Check for active sessions          │
│ ✓ Found Device A logged in         │
└─────────────┬──────────────────────┘
              │
              ▼

STEP 2: Pending Request Created
┌────────────────────────────────────┐
│ INSERT INTO user_login_sessions    │
│ status = 'pending'                 │
│ temp_token = 'a1b2c3d4...'         │
│ device_id = 'User-Agent'           │
│ ip_address = '192.168.1.100'       │
└─────────────┬──────────────────────┘
              │
              ▼
┌────────────────────────────────────┐
│ Redirect to:                       │
│ pending_login_waiting.php          │
│ (Device B shows waiting screen)    │
└─────────────┬──────────────────────┘
              │
              ▼

STEP 3: Device A Receives Notification (KEY PART!)
         (Within 5 seconds)
┌────────────────────────────────────┐
│ login-notification-system.js       │
│ Polls every 5 seconds              │
│ fetch('get_pending_logins.php')    │
└─────────────┬──────────────────────┘
              │
              ▼ (finds pending request)
┌────────────────────────────────────┐
│ showNotification(request)           │
│ Create modal with:                 │
│ - Device type (Chrome Browser)     │
│ - IP Address (192.168.1.100)       │
│ - Verification Code (A1B2C3D4...)  │
│ - Approve/Deny buttons             │
└─────────────┬──────────────────────┘
              │
              ▼
         ╔════════════════════════════╗
         ║  🔒 New Login Request   ✕  ║
         ║                            ║
         ║ DEVICE: Chrome Browser     ║
         ║ IP: 192.168.1.100          ║
         ║ CODE: A1B2C3D4...          ║
         ║                            ║
         ║ [✅ Allow]  [❌ Block]    ║
         ╚════════════════════════════╝
              │ (User clicks)
              ▼

STEP 4: User Approves on Device A
┌────────────────────────────────────┐
│ handleApproval(true)               │
│ POST to approve_login_request.php  │
│ action = 'approve'                 │
│ temp_token = 'a1b2c3d4...'         │
└─────────────┬──────────────────────┘
              │
              ▼
┌────────────────────────────────────┐
│ UPDATE user_login_sessions         │
│ SET status = 'active'              │
│ WHERE temp_token = '...'           │
└─────────────┬──────────────────────┘
              │
              ▼

STEP 5: Device B Detects Approval
┌────────────────────────────────────┐
│ pending_login_waiting.php          │
│ Auto-checks every 10 seconds       │
│ fetch('check_pending_status.php')  │
│ Returns: { status: 'active' }      │
└─────────────┬──────────────────────┘
              │
              ▼
┌────────────────────────────────────┐
│ SUCCESS! Status changed             │
│ Redirect to:                       │
│ login.php?complete_pending=1       │
└─────────────┬──────────────────────┘
              │
              ▼
┌────────────────────────────────────┐
│ Complete Login Process              │
│ Set Session Variables               │
│ $_SESSION['resident_id'] = ...     │
│ $_SESSION['user_type'] = ...       │
│ Session Regenerate                 │
└─────────────┬──────────────────────┘
              │
              ▼
┌────────────────────────────────────┐
│ Redirect to Dashboard               │
│ Device B fully logged in! ✓         │
└────────────────────────────────────┘
```

## File Interaction Diagram

```
┌────────────────────────────────────────────────────────────────┐
│                      USER BROWSER                              │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│  Device A (Logged In)          Device B (New Device)          │
│  ┌──────────────────────┐     ┌──────────────────────┐       │
│  │ dashboard.php        │     │ login.php            │       │
│  │ + NotiSystem.js ◄────┼─────► (attempts login)     │       │
│  │                      │     │                      │       │
│  │ [POPUP]             │     │ Creates pending      │       │
│  │ ┌─────────────────┐ │     │ request              │       │
│  │ │ Approve/Deny    │ │     │                      │       │
│  │ └────────┬────────┘ │     └──────────┬───────────┘       │
│  └─────────┬───────────┘               │                    │
│            │                           │                    │
│            ▼                           ▼                    │
│  ┌──────────────────────┐     ┌──────────────────────┐       │
│  │ approve_login_       │     │ pending_login_       │       │
│  │ request.php          │     │ waiting.php          │       │
│  └─────────┬────────────┘     └─────────┬────────────┘       │
│            │                           │                    │
└────────────┼───────────────────────────┼────────────────────┘
             │                           │
             └───────────┬───────────────┘
                         │
                         ▼
         ┌───────────────────────────────┐
         │     DATABASE: MySQL           │
         │  user_login_sessions table    │
         │  (pending, active, rejected)  │
         └───────────────────────────────┘
```

## Time Flow Diagram

```
TIME ──────────────────────────────────────────────────► (11 seconds)

T=0s   │ Device B enters email/password
       │ Clicks "Sign In"
       ▼
       ┌─ Check credentials
       │  Found: Device A has active session
       │  Create pending request
       └─ Redirect to waiting page

T=2s   │ Device A still on dashboard
       │ JavaScript running in background
       ▼
       └─ Polling every 5 seconds...

T=5s   │ Javascript finds NEW pending request!
       ▼
       ┌─ POPUP APPEARS on Device A ◄── THIS IS THE FIX!
       │  Shows device info, IP, code
       │  User reads the popup

T=6s   │ User sees popup
       │ Recognizes it's them
       │ Clicks "Yes, Allow It"
       ▼
       ├─ Send approval to database
       │  status = 'active'
       │  Update timestamp

T=10s  │ Device B checks status (every 10 sec)
       │ Finds: status = 'active'
       ▼
       ├─ Complete the login process
       │  Set session variables
       │  Regenerate session

T=11s  │ Device B redirected to dashboard
       ▼
       ✓ Both devices now logged in!
```

## Device Detection Example

```
Device B User-Agent String:
"Mozilla/5.0 (Windows NT 10.0; Win64; x64) 
 AppleWebKit/537.36 (KHTML, like Gecko) 
 Chrome/126.0.0.0 Safari/537.36"

↓ Parsing in extractDeviceInfo()

Result: "Chrome Browser"

┌─────────────────┐
│ Device: Chrome  │
│ IP: 192.168.X.X │
│ Code: A1B2C3D4  │
└─────────────────┘
```

## Database State Changes

```
BEFORE Login Attempt:
┌──────────────────────────────┐
│ user_login_sessions          │
├──────────────────────────────┤
│ user_id: 906097              │
│ status: 'active'             │  (Device A)
│ session_id: '...'            │
│ created_at: 2026-07-02 14:00 │
└──────────────────────────────┘

DURING Login Attempt:
┌──────────────────────────────┐  ┌──────────────────────────────┐
│ ACTIVE (Device A)            │  │ PENDING (Device B)           │
├──────────────────────────────┤  ├──────────────────────────────┤
│ user_id: 906097              │  │ user_id: 906097              │
│ status: 'active'             │  │ status: 'pending'            │
│ session_id: 'abc123...'      │  │ session_id: 'def456...'      │
│ created_at: 2026-07-02 14:00 │  │ temp_token: 'a1b2c3d4...'    │
│ ip_address: 192.168.1.50     │  │ ip_address: 192.168.1.100    │
│ device_id: 'Mozilla...'      │  │ device_id: 'Chrome...'       │
└──────────────────────────────┘  │ created_at: 2026-07-02 14:05 │
                                   └──────────────────────────────┘

AFTER Approval:
┌──────────────────────────────┐  ┌──────────────────────────────┐
│ ACTIVE (Device A)            │  │ ACTIVE (Device B - Approved) │
├──────────────────────────────┤  ├──────────────────────────────┤
│ user_id: 906097              │  │ user_id: 906097              │
│ status: 'active'             │  │ status: 'active'             │
│ session_id: 'abc123...'      │  │ session_id: 'def456...'      │
│ created_at: 2026-07-02 14:00 │  │ temp_token: NULL             │
│ ip_address: 192.168.1.50     │  │ ip_address: 192.168.1.100    │
│ device_id: 'Mozilla...'      │  │ device_id: 'Chrome...'       │
└──────────────────────────────┘  │ updated_at: 2026-07-02 14:06 │
                                   └──────────────────────────────┘
```

## Request/Response Cycles

```
┌─────────────────────────────────────────────────────────────────┐
│           JavaScript Polling Cycle (Every 5 Seconds)            │
└─────────────────────────────────────────────────────────────────┘

fetch('get_pending_logins.php')
        │
        ├─ Request Headers:
        │  GET /get_pending_logins.php
        │  Cookie: PHPSESSID=...
        │
        └─ Server Response (JSON):
           {
             "success": true,
             "pending": [
               {
                 "id": 1,
                 "temp_token": "a1b2c3d4e5f6g7h8...",
                 "device_id": "Mozilla/5.0 (Windows...)",
                 "ip_address": "192.168.1.100",
                 "created_at": "2026-07-02 14:05:30",
                 "age_seconds": 45
               }
             ],
             "count": 1
           }

           ├─ If count > 0: SHOW POPUP
           └─ If count = 0: Keep polling silently


┌─────────────────────────────────────────────────────────────────┐
│          User Clicks Approve Button                             │
└─────────────────────────────────────────────────────────────────┘

fetch('approve_login_request.php', {
  method: 'POST',
  body: new FormData({
    action: 'approve',
    temp_token: 'a1b2c3d4e5f6g7h8...'
  })
})
        │
        └─ Server Response (JSON):
           {
             "success": true,
             "message": "Login approved. Device can now complete login.",
             "action": "approve"
           }

           ├─ Update UI to show success
           └─ Hide popup


┌─────────────────────────────────────────────────────────────────┐
│          Device B Polls Status (Every 10 Seconds)               │
└─────────────────────────────────────────────────────────────────┘

fetch('check_pending_status.php?temp_token=a1b2c3d4...')
        │
        └─ Server Response (JSON):
           { "status": "active" }  ← APPROVED!

           ├─ Call login.php?complete_pending=1
           ├─ Complete login process
           └─ Auto-redirect to dashboard
```

---

**Status**: ✅ Complete Architecture  
**Date**: 2026-07-02
