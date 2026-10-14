


1. Open **phpMyAdmin**
2. Select **scancare_db** database
3. Click **SQL** tab
4. Paste this:

```sql
ALTER TABLE users 
ADD COLUMN last_logout TIMESTAMP NULL COMMENT 'Most recent logout timestamp' 
AFTER last_login;
```

5. Click **Execute**
6. ✅ Done! Column added.


The following files have already been updated:
- ✅ `logout.php` - Now records logout timestamp
- ✅ `login.php` - Gmail support with case-insensitive email

No additional code changes needed!


1. Go to http:
2. Email: `yourname@gmail.com`
3. Fill other fields
4. Click "Create Account"
5. ✅ Should register successfully

1. Go to http:
2. Email: `yourname@gmail.com` (or UPPERCASE - doesn't matter!)
3. Password: your password
4. Click "Sign In"
5. ✅ Should log in successfully

1. Click **Logout** button
2. Open phpMyAdmin
3. Go to `scancare_db` → `users` table
4. Scroll RIGHT to see columns
5. ✅ You'll see `last_login` and `last_logout` with timestamps!


**Location:** scancare_db → users table

**Columns you'll see:**
```
user_id | email | ... | last_login | last_logout

1 | test@gmail.com | ... | 2026-06-08 15:15:30 | 2026-06-08 15:25:45
2 | user@yahoo.com | ... | 2026-06-08 14:00:00 | 2026-06-08 14:45:30
3 | admin@outlook.com | ... | 2026-06-08 15:30:00 | (NULL = still logged in)
```


✅ **Gmail Support**
- yourname@gmail.com
- yourname+label@gmail.com
- Works with any email provider

✅ **Case-Insensitive Email**
- test@gmail.com
- TEST@GMAIL.COM
- Test@Gmail.Com
All work the same!

✅ **Logout Timestamp**
- Automatically recorded in database
- Visible in phpMyAdmin
- Format: 2026-06-08 15:25:45


| File | What Changed |
|
| **logout.php** | Records last_logout timestamp |
| **login.php** | Gmail support + case-insensitive email |
| **(Database)** | New last_logout column in users table |


You're all set. The system now:
- ✅ Accepts Gmail signup
- ✅ Allows Gmail login
- ✅ Tracks when users logout
- ✅ Shows logout timestamps in phpMyAdmin

**Status: ✅ Ready to Use**

