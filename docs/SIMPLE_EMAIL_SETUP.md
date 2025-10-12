# Simple Email System Setup Guide

## Overview

This guide provides instructions for setting up the email system in the iMSafe Disaster Monitoring System. You have two options:

1. **Simple Email System** (No Composer required) - Uses PHP's built-in `mail()` function
2. **PHPMailer System** (Requires Composer) - More reliable with better SMTP support

## Option 1: Simple Email System (Recommended for Quick Setup)

### Step 1: Configure Email Settings

1. **Copy the email configuration file:**
   ```bash
   cp config/email_config.php config/email_config_local.php
   ```

2. **Edit `config/email_config_local.php`** with your settings:
   ```php
   <?php
   // config/email_config_local.php
   
   // SMTP Configuration
   define('EMAIL_SMTP_HOST', 'localhost'); // or your SMTP server
   define('EMAIL_SMTP_PORT', 587);
   define('EMAIL_SMTP_USERNAME', 'your_email@example.com');
   define('EMAIL_SMTP_PASSWORD', 'your_password');
   define('EMAIL_SMTP_ENCRYPTION', 'tls'); // or 'ssl'
   
   // Email Settings
   define('EMAIL_FROM_ADDRESS', 'no-reply@imsafe.com');
   define('EMAIL_FROM_NAME', 'iMSafe Disaster Monitoring');
   define('EMAIL_REPLY_TO_ADDRESS', 'support@imsafe.com');
   define('EMAIL_CHARSET', 'UTF-8');
   define('EMAIL_ENCODING', '8bit');
   
   // Application URLs
   define('BASE_URL', 'http://localhost/disaster');
   define('TRACK_REPORT_URL', BASE_URL . '/track_report.php');
   ?>
   ```

### Step 2: Update Database Schema

Run the SQL script to add the reporter email column:

```sql
-- Add reporter_email column to disasters table
ALTER TABLE disasters
ADD COLUMN reporter_email VARCHAR(255) NULL AFTER reporter_phone;
```

### Step 3: Test the Simple Email System

1. **Run the test script:**
   ```
   http://localhost/disaster/test_simple_email.php
   ```

2. **Submit a test emergency report** with an email address to verify the system works.

### Step 4: Configure PHP Mail (if needed)

If the simple email system doesn't work, you may need to configure PHP's mail function:

#### For XAMPP on Windows:
1. **Edit `C:\xampp\php\php.ini`:**
   ```ini
   [mail function]
   SMTP = localhost
   smtp_port = 25
   sendmail_from = your_email@example.com
   ```

2. **Restart Apache** in XAMPP Control Panel

#### For Linux/Ubuntu:
1. **Install sendmail:**
   ```bash
   sudo apt-get install sendmail
   sudo sendmailconfig
   ```

#### For macOS:
1. **Install mailutils:**
   ```bash
   brew install mailutils
   ```

## Option 2: PHPMailer System (Recommended for Production)

### Step 1: Install Composer

#### Windows:
1. Download Composer installer from: https://getcomposer.org/download/
2. Run the installer
3. Restart your command prompt

#### Linux/macOS:
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Step 2: Install PHPMailer

1. **Run Composer install:**
   ```bash
   composer install
   ```

2. **This will create a `vendor/` directory** with PHPMailer and dependencies.

### Step 3: Configure SMTP Settings

1. **Copy and edit the email configuration:**
   ```bash
   cp config/email_config.php config/email_config_local.php
   ```

2. **Update `config/email_config_local.php`** with your SMTP credentials:
   ```php
   <?php
   // config/email_config_local.php
   
   return [
       'host' => 'smtp.gmail.com', // Your SMTP server
       'smtp_auth' => true,
       'username' => 'your_email@gmail.com',
       'password' => 'your_app_password', // Use app password for Gmail
       'smtp_secure' => 'tls',
       'port' => 587,
       'set_from_address' => 'no-reply@imsafe.com',
       'set_from_name' => 'iMSafe Disaster Monitoring',
       'is_html' => true,
       'alt_body_prefix' => 'This is a plain-text email. Please use an HTML-compatible email client to view the full message.'
   ];
   ?>
   ```

### Step 4: Test PHPMailer System

1. **Run the test script:**
   ```
   http://localhost/disaster/test_email.php
   ```

## Email Service Providers

### Gmail SMTP Settings:
```
Host: smtp.gmail.com
Port: 587
Encryption: TLS
Username: your_gmail@gmail.com
Password: your_app_password (not your regular password)
```

**Note:** For Gmail, you need to:
1. Enable 2-factor authentication
2. Generate an "App Password" for this application
3. Use the app password, not your regular Gmail password

### Other Popular SMTP Providers:

#### SendGrid:
```
Host: smtp.sendgrid.net
Port: 587
Username: apikey
Password: your_sendgrid_api_key
```

#### Mailgun:
```
Host: smtp.mailgun.org
Port: 587
Username: your_mailgun_smtp_username
Password: your_mailgun_smtp_password
```

## Troubleshooting

### Common Issues:

1. **"mail() function not available"**
   - Install sendmail or configure SMTP in php.ini
   - Restart web server

2. **"Connection refused"**
   - Check SMTP host and port
   - Verify firewall settings
   - Ensure SMTP credentials are correct

3. **"Authentication failed"**
   - Check username/password
   - For Gmail, use app password
   - Enable "Less secure app access" (not recommended)

4. **Emails not received**
   - Check spam folder
   - Verify sender email address
   - Test with different email providers

### Debug Mode:

Enable debug logging by adding this to your PHP files:
```php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
```

Check error logs in:
- XAMPP: `C:\xampp\apache\logs\error.log`
- Linux: `/var/log/apache2/error.log`

## Production Recommendations

1. **Use a dedicated email service** (SendGrid, Mailgun, Amazon SES)
2. **Implement email queuing** for high-volume systems
3. **Add rate limiting** to prevent spam
4. **Monitor email delivery rates**
5. **Use proper DKIM/SPF records** for better deliverability

## Security Considerations

1. **Never commit email credentials** to version control
2. **Use environment variables** for sensitive data
3. **Validate all email addresses** before sending
4. **Implement rate limiting** to prevent abuse
5. **Log all email activities** for auditing

## Support

If you encounter issues:
1. Check the error logs
2. Test with the provided test scripts
3. Verify your SMTP configuration
4. Contact your hosting provider for SMTP support
