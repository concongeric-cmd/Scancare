

The BHW Dashboard SMS module has been updated to use **MoceanAPI** for sending SMS messages to residents.


1. Sign up at [MoceanAPI](https:
2. Navigate to your dashboard/settings
3. Copy your API token (also called "Access Token" or "API Key")

Open `send_sms_handler.php` and find this line (around line 72):
```php
$mocean_api_token = 'YOUR_MOCEAN_API_TOKEN_HERE'; 
```

Replace `YOUR_MOCEAN_API_TOKEN_HERE` with your actual MoceanAPI token:
```php
$mocean_api_token = 'your_actual_token_here';
```

The default sender ID is set to `'MOCEAN'`. You can change this to your registered sender ID by modifying line 74:
```php
$mocean_from = 'YOUR_SENDER_ID'; 
```


The BHW Dashboard SMS module supports three recipient types:

1. **All Residents** - Send SMS to all registered residents with valid phone numbers
2. **Upcoming Appointment Reminders** - Send SMS only to residents with appointments in the next 7 days
3. **Custom Contact List** - Send SMS to a manually entered list of phone numbers

The system automatically handles multiple phone number formats:
- International format: `+639123456789`
- Philippine country code: `639123456789`
- Domestic format: `09123456789`
- With spaces/dashes: `+63 912 345 6789`

- Maximum message length: **160 characters**
- Longer messages will be rejected with a validation error


1. Go to the **SMS Communications** module in the BHW Dashboard
2. Select a recipient type:
   - **All Residents** - Automatically fetches all residents from the database
   - **Upcoming Appointment Reminders** - Fetches residents with appointments in next 7 days
   - **Custom Contact List** - Enter phone numbers manually (comma-separated)
3. Compose your message (max 160 characters)
4. Use quick templates or compose custom message
5. Click **Send SMS Reminders**
6. A confirmation dialog will show how many SMS were sent/failed

- **Appointment Reminder** - "Your appointment is tomorrow at 9:00 AM..."
- **Clinic Announcement** - "The Rural Health Unit will open tomorrow..."
- **Medication Reminder** - "Follow-up medication reminder..."


```
POST https:
Authorization: Bearer [YOUR_TOKEN]
```

```
mocean-from=MOCEAN
mocean-to=+639927174709
mocean-text=Your message here
```

- Invalid phone numbers are automatically formatted or skipped
- API failures are logged in PHP error log
- Response includes sent/failed count for each batch
- Rate limiting: 100ms delay between requests to avoid API throttling


1. Verify your API token is correct
2. Check that your MoceanAPI account has sufficient balance/credits
3. Ensure phone numbers are in valid format (start with country code)
4. Check PHP error log for detailed error messages: `error_log()` output

1. Verify recipient phone number is correct
2. Check that sender ID is registered with MoceanAPI
3. Confirm that the resident's phone number is stored in the database
4. Check MoceanAPI dashboard for delivery reports

- Ensure `db_config.php` is properly configured
- Verify database contains the `resident` and `appointment` tables
- Check that `contact_number` field exists in `resident` table


⚠️ **IMPORTANT**: Never commit your API token to version control!

1. Keep your API token secure and confidential
2. Consider using environment variables instead of hardcoding:
   ```php
   $mocean_api_token = getenv('MOCEAN_API_TOKEN');
   ```
3. Use a `.env` file with proper gitignore settings
4. Rotate your token regularly
5. Monitor API usage in the MoceanAPI dashboard


Check MoceanAPI documentation for:
- API rate limits
- Pricing per SMS
- Account balance/credit requirements
- Monthly usage limits


- **MoceanAPI Documentation**: https:
- **MoceanAPI Support**: Contact MoceanAPI support team
- **System Error Logs**: Check PHP error log for detailed debugging


1. **send_sms_handler.php** (NEW) - Backend handler for processing SMS requests
2. **dashboard_bhw.php** (UPDATED) - Updated handleSMSSubmit() function to use new API


1. ✅ Configure your MoceanAPI token in `send_sms_handler.php`
2. ✅ Test with a small group (use custom phone numbers)
3. ✅ Monitor the PHP error log for any issues
4. ✅ Set up any cron jobs if you want automated SMS sending
5. ✅ Train BHWs on using the SMS feature



**Last Updated**: 2026-06-18
**API Provider**: MoceanAPI
**Status**: Ready for Production

