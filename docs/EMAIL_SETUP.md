# Email System Setup Guide

This guide explains how to set up the PHPMailer email system for the iMSafe Disaster Monitoring System.

## Prerequisites

1. **Composer** - PHP dependency manager
2. **SMTP credentials** - Email server settings (Gmail, SendGrid, etc.)

## Installation Steps

### 1. Install PHPMailer

Run the following command in your project root directory:

```bash
composer install
```

This will install PHPMailer and create the `vendor/` directory with autoloader.

### 2. Configure Email Settings

1. Copy the example configuration file:
   ```bash
   cp config/email_config.php config/email_config_local.php
   ```

2. Edit `config/email_config_local.php` with your actual SMTP settings:

```php
<?php
// Gmail SMTP Example
define('EMAIL_SMTP_HOST', 'smtp.gmail.com');
define('EMAIL_SMTP_PORT', 587);
define('EMAIL_SMTP_USERNAME', 'your-email@gmail.com');
define('EMAIL_SMTP_PASSWORD', 'your-app-password'); // Use App Password for Gmail
define('EMAIL_SMTP_SECURE', 'tls');

define('EMAIL_FROM_ADDRESS', 'your-email@gmail.com');
define('EMAIL_FROM_NAME', 'iMSafe Disaster Monitoring System');
define('EMAIL_REPLY_TO_ADDRESS', 'noreply@yourdomain.com');

define('BASE_URL', 'https://yourdomain.com/disaster');
define('TRACK_REPORT_URL', BASE_URL . '/track_report.php');
```

### 3. Update Database Schema

Run the SQL script to add the reporter_email column:

```sql
-- Execute the contents of sql/add_reporter_email_column.sql
```

Or manually add the column:
```sql
ALTER TABLE disasters ADD COLUMN reporter_email VARCHAR(255) NULL AFTER reporter_phone;
```

### 4. Test the Email System

1. Submit a test emergency report with an email address
2. Check the error logs for email sending status
3. Verify the email is received

## SMTP Provider Examples

### Gmail
- Host: `smtp.gmail.com`
- Port: `587`
- Security: `tls`
- Username: Your Gmail address
- Password: App Password (not your regular password)

### SendGrid
- Host: `smtp.sendgrid.net`
- Port: `587`
- Security: `tls`
- Username: `apikey`
- Password: Your SendGrid API key

### Outlook/Hotmail
- Host: `smtp-mail.outlook.com`
- Port: `587`
- Security: `tls`
- Username: Your Outlook email
- Password: Your Outlook password

## Email Template Features

The email includes:
- **Tracking ID** prominently displayed
- **Clickable link** to track report status
- **Disaster summary** (type, location, severity)
- **Professional HTML design** with responsive layout
- **Plain text fallback** for email clients that don't support HTML

## Troubleshooting

### Common Issues

1. **PHPMailer not found**
   - Run `composer install` to install dependencies
   - Check that `vendor/autoload.php` exists

2. **SMTP authentication failed**
   - Verify SMTP credentials
   - For Gmail, use App Password instead of regular password
   - Check if 2-factor authentication is enabled

3. **Email not received**
   - Check spam/junk folder
   - Verify sender email address is correct
   - Check error logs for SMTP errors

4. **Permission errors**
   - Ensure `config/email_config_local.php` is readable by web server
   - Add `config/email_config_local.php` to `.gitignore`

### Debug Mode

To enable SMTP debug mode, modify `includes/email_helper.php`:

```php
$mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable debug output
```

### Log Files

Check these locations for error logs:
- PHP error log
- Web server error log
- Application error log (check error_log() calls)

## Security Notes

1. **Never commit credentials** to version control
2. **Use environment variables** for production (future enhancement)
3. **Validate email addresses** before sending
4. **Rate limit email sends** (future enhancement)
5. **Use secure SMTP** (TLS/SSL)

## Future Enhancements

- [ ] Email templates customization
- [ ] Bulk email sending
- [ ] Email delivery tracking
- [ ] SMS integration as backup
- [ ] Email preferences management
- [ ] Admin email notifications

