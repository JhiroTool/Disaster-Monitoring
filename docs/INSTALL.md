# Quick Installation Guide

## Prerequisites
- XAMPP/LAMPP with PHP 7.4+ and MySQL
- Web browser
- Text editor (optional, for configuration)

## Installation Steps

### 1. Download/Clone
```bash
# If using git
git clone https://github.com/JhiroTool/Disaster-Monitoring.git

# Or download and extract ZIP file to your web directory
```

### 2. Database Setup
```bash
# Start XAMPP/LAMPP services
sudo /opt/lampp/lampp start

# Create database
mysql -u root -p -e "CREATE DATABASE disaster_monitoring;"

# Import database schema
mysql -u root -p disaster_monitoring < sql/disaster_monitoring_complete.sql
```

### 3. Configuration
Edit `config/database.php` if needed (default settings work with XAMPP):
```php
$host = 'localhost';
$dbname = 'disaster_monitoring';
$username = 'root';
$password = ''; // Your MySQL root password
```

### 4. Set Permissions
```bash
chmod 755 uploads/
chmod 777 uploads/emergency_images/
```

### 5. Access the System
- **Main Site**: `http://localhost/Disaster-Monitoring/`
- **Admin Login**: `http://localhost/Disaster-Monitoring/login.php`
- **Register as Reporter**: `http://localhost/Disaster-Monitoring/register.php`

## Default Admin Account
- **Username**: `admin`
- **Email**: `admin@imsafe.gov.ph` 
- **Password**: `admin123` (change after first login)

## First Steps
1. Login as admin
2. Change default password
3. Create additional admin accounts if needed
4. Test emergency reporting functionality
5. Configure LGUs and user assignments as needed

## Troubleshooting
- Ensure XAMPP/LAMPP is running
- Check file permissions on uploads directory
- Verify database connection in `config/database.php`
- Check PHP error logs if issues persist