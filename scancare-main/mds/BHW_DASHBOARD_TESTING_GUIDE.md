


- [ ] Database is running and accessible
- [ ] `db_config.php` is configured correctly
- [ ] `users` table exists with at least one BHW account
- [ ] `appointments` table exists with proper schema
- [ ] `services` table is populated with health services
- [ ] `activity_logs` table exists for audit trail


- [ ] Copy `bhw_dashboard_appointment_manager.php` to `/itelec4/`
- [ ] Ensure file permissions allow PHP execution (755)

Execute the SQL commands from `BHW_APPOINTMENT_SQL_REFERENCE.sql`:

```sql

INSERT INTO services (service_name, description, category, is_available) 
VALUES (...); 


INSERT INTO users (email, password_hash, ..., user_role='bhw') 
VALUES (...);


INSERT INTO users (email, password_hash, ..., user_role='resident') 
VALUES (...);


INSERT INTO appointments (user_id, bhw_id, service_id, ...) 
VALUES (...);
```

```sql

SELECT COUNT(*) FROM appointments WHERE status = 'scheduled';


SELECT * FROM services;


SELECT * FROM users WHERE user_role = 'bhw';
```


1. [ ] Navigate to `login.php`
2. [ ] Login with BHW credentials:
   - Email: `bhw1@gmail.com`
   - Password: `TestPass123`
3. [ ] Verify redirected to `/bhw_dashboard_appointment_manager.php`
4. [ ] Confirm BHW name displays in top-right

1. [ ] On BHW dashboard, verify "Scheduled Appointments" table loads
2. [ ] Confirm unassigned appointments show with yellow "Assign" button
3. [ ] Verify appointment data displays: Patient Name, Date, Time, Service, Reason
4. [ ] Check statistics card shows correct "Unassigned" count

1. [ ] Click yellow "Assign to Me" button on an unassigned appointment
2. [ ] Confirm dialog: "Assign this appointment to yourself?"
3. [ ] Click "OK"
4. [ ] Verify success alert: "✅ Appointment assigned to you successfully!"
5. [ ] Confirm page reloads
6. [ ] Verify appointment now shows:
   - Green "Done" button (instead of yellow "Assign")
   - Assigned BHW name in "Assigned To" column
   - Unassigned count decreases by 1
   - My Assignments count increases by 1

1. [ ] On dashboard, locate an appointment with "Done" button
2. [ ] Click green "Done" button
3. [ ] Confirm dialog: "Mark this appointment as completed?"
4. [ ] Click "OK"
5. [ ] Verify success alert: "✅ Appointment marked as completed!"
6. [ ] Confirm page reloads
7. [ ] Verify appointment:
   - Disappears from "Scheduled Appointments" table
   - Appears in "Recent Completed Visits" section
   - "My Assignments" count decreases by 1
   - Completed count increases by 1

1. [ ] Logout current BHW (bhw1@gmail.com)
2. [ ] Login as different BHW (bhw2@gmail.com)
3. [ ] Verify bhw2's dashboard shows:
   - Only assignments assigned to bhw2
   - Cannot see appointments assigned to bhw1
   - Can claim new unassigned appointments
4. [ ] Logout and verify similar behavior for bhw3, bhw4, bhw5


- [ ] Query returns appointments with resident names joined correctly
```sql
SELECT a.appointment_id, u.first_name, u.last_name 
FROM appointments a 
JOIN users u ON a.user_id = u.user_id 
WHERE status = 'scheduled';
```

- [ ] BHW can only see their own assignments
```sql
SELECT * FROM appointments 
WHERE bhw_id = ? 
AND status = 'scheduled';
```

- [ ] Updates correctly modify bhw_id field
```sql
UPDATE appointments SET bhw_id = 2 
WHERE appointment_id = 1;
```

- [ ] Statistics cards update without page reload (they do after refresh)
- [ ] Buttons appear/disappear based on assignment status
- [ ] Patient names display correctly (joined from users table)
- [ ] Date/time formats are readable (e.g., "Jun 10, 2026" and "9:00 AM")
- [ ] Service names display (or "General" if null)
- [ ] Phone numbers display in patient info

- [ ] Cannot access dashboard without login (test by deleting session cookie)
- [ ] Cannot access as different user role (test resident trying to access BHW dashboard)
- [ ] Cannot manually modify appointment_id in browser (test changing data-appointment-id)
- [ ] SQL injection prevention (test with single quotes in queries)
- [ ] Authorization check prevents completing others' appointments


**Solution:**
- Check `db_config.php` settings
- Verify MySQL is running
- Test with: `php -r "include 'db_config.php'; var_dump(getDatabaseConnection());"`

**Solution:**
- Run SQL INSERT statements from `BHW_APPOINTMENT_SQL_REFERENCE.sql`
- Verify services table has data before inserting appointments
- Check for foreign key constraint errors

**Solution:**
- Verify bhw_id is NULL for unassigned appointments
- Check appointment status is 'scheduled'
- Ensure appointment_id is valid

**Solution:**
- Check WHERE clause in PHP query includes: `AND (a.bhw_id IS NULL OR a.bhw_id = ?)`
- Verify current_bhw_id is correctly set from `$_SESSION['user_id']`

**Solution:**
- Check browser console (F12) for JavaScript errors
- Verify JSON response is being returned (check Network tab)
- Ensure `location.reload()` is executing

**Solution:**
- Verify user_role in session is 'bhw'
- Check login.php correctly sets user_role in session
- Try logging in again


| Action | Condition | Result |
|
| View Dashboard | BHW logged in | Shows scheduled appointments |
| Assign Appointment | bhw_id IS NULL | Sets bhw_id to current BHW |
| Mark Done | bhw_id = current & status='scheduled' | Changes status to 'completed' |
| Complete Appointment | After marking done | Moves to "Completed Visits" section |
| Access Other BHW Data | Different BHW login | Only sees own assignments |


| Email | Password | First Name | Last Name | User ID |
|
| bhw1@gmail.com | TestPass123 | Ricardo | Reyes | 2 |
| bhw2@gmail.com | TestPass123 | Jennifer | De Leon | 3 |
| bhw3@gmail.com | TestPass123 | Miguel | Fernandez | 4 |
| bhw4@gmail.com | TestPass123 | Rosemary | Villanueva | 5 |
| bhw5@gmail.com | TestPass123 | Carlos | Mercado | 6 |

| Email | User ID | Full Name |
|
| resident1@gmail.com | 7 | Maria Santos |
| resident2@gmail.com | 8 | Juan Dela Cruz |
| resident3@gmail.com | 9 | Ana Rodriguez |
| resident4@gmail.com | 10 | Pedro Bacani |
| resident5@gmail.com | 11 | Rosa Medina |
| resident6@gmail.com | 12 | Antonio Villegas |

- 4 unassigned appointments (for claiming)
- 2 pre-assigned to Ricardo Reyes (bhw1)
- Ready for completion testing


Before deploying to production:

- [ ] All test data deleted or archived
- [ ] Database backups created
- [ ] Error logging configured (not in production code)
- [ ] Session timeout settings reviewed (30 min recommended)
- [ ] HTTPS enabled on server
- [ ] Rate limiting configured to prevent AJAX abuse
- [ ] Activity logs retention policy set (e.g., 1 year)
- [ ] Backup and disaster recovery plan documented
- [ ] User training completed for BHWs
- [ ] Documentation provided to support team


1. **Console Tab**: Check for JavaScript errors
2. **Network Tab**: Monitor AJAX POST requests to `/bhw_dashboard_appointment_manager.php`
3. **Application Tab**: Verify session cookies are set

```bash

tail -f /var/log/apache2/error.log

mysql -u root scancare_db -e "SELECT * FROM appointments LIMIT 5;"
```

```php
<?php

require_once 'db_config.php';
$pdo = getDatabaseConnection();
echo $pdo ? "✅ Connected" : "❌ Failed";


$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments");
$stmt->execute();
echo "Total appointments: " . $stmt->fetchColumn();
?>
```


- Database Joins: [MySQL JOIN Documentation](https:
- PDO Prepared Statements: [PHP PDO Tutorial](https:
- AJAX with Fetch: [MDN Fetch API](https:
- Sessions in PHP: [PHP Session Documentation](https:


After successful implementation:

1. **Enhance UI**: Add real-time notifications
2. **Add Filtering**: Filter appointments by date range, service type
3. **Export Reports**: Generate PDF/Excel reports of completed appointments
4. **Mobile App**: Create mobile-friendly view
5. **Integration**: Connect with SMS/Email notifications for residents
6. **Analytics**: Build dashboard analytics for BHW performance



**Last Updated**: 2026-06-09
**Version**: 1.0
**Status**: Ready for Testing

