# iMSafe Disaster Monitoring System

A comprehensive disaster monitoring and emergency response system designed for Local Government Units (LGUs) to effectively manage and coordinate disaster response activities.

## Features

### Public Interface
- **Emergency Reporting**: Citizens can report disasters with location, severity, and optional image uploads
- **Report Tracking**: Real-time tracking of emergency reports with unique tracking IDs
- **Auto-tracking**: Streamlined report tracking with automatic ID population

### Administrative Panel
- **Dashboard**: Comprehensive overview of all disaster activities
- **Disaster Management**: Complete disaster lifecycle management
- **User Management**: LGU user accounts and permissions
- **Resource Tracking**: Management of emergency resources and supplies
- **Notification System**: Real-time notifications for emergency updates
- **Reporting**: Detailed analytics and reports

### Technical Features
- **Image Upload**: Optional photo documentation for emergency reports
- **Multi-level Severity Classification**: Green, Orange, and Red severity levels
- **Responsive Design**: Mobile-friendly interface for field use
- **Database Security**: Prepared statements and input sanitization
- **Error Handling**: Comprehensive error logging and user feedback

## Installation

### Prerequisites
- XAMPP/LAMPP with PHP 7.4+ and MySQL 5.7+
- Web server with file upload capability
- Modern web browser

### Setup Instructions

1. **Clone the Repository**
   ```bash
   git clone https://github.com/JhiroTool/Disaster-Monitoring.git
   cd Disaster-Monitoring
   ```

2. **Database Setup**
   ```bash
   # Import the database schema
   mysql -u root -p disaster_monitoring < disaster_monitoring.sql
   ```

3. **Configure Database Connection**
   - Edit `config/database.php` with your database credentials
   - Default settings work with standard XAMPP installation

4. **Set Directory Permissions**
   ```bash
   chmod 755 uploads/
   chmod 777 uploads/emergency_images/
   ```

5. **Access the Application**
   - Public Interface: `http://localhost/Disaster_Monitoring/`
   - Admin Panel: `http://localhost/Disaster_Monitoring/admin/`

## System Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache 2.4+ (included in XAMPP)
- **Browser**: Modern browser with JavaScript enabled
- **Storage**: Minimum 1GB for image uploads

## File Structure

```
Disaster_Monitoring/
├── admin/                  # Administrative interface
│   ├── dashboard.php       # Admin dashboard
│   ├── disasters.php       # Disaster management
│   ├── users.php          # User management
│   ├── resources.php      # Resource tracking
│   ├── notifications.php  # Notification system
│   └── assets/            # Admin-specific assets
├── assets/                # Public assets (CSS, JS, images)
├── config/                # Configuration files
│   └── database.php       # Database connection and utilities
├── uploads/               # File upload directory
│   └── emergency_images/  # Emergency report images
├── index.php             # Main landing page
├── report_emergency.php  # Emergency reporting form
└── track_report.php      # Report tracking interface
```

## Usage

### For Citizens
1. **Report Emergency**: Visit the main page and click "Report Emergency"
2. **Fill Details**: Complete the emergency report form with location and description
3. **Upload Image** (Optional): Add photos to document the emergency
4. **Track Report**: Use the provided tracking ID to monitor response status

### For LGU Administrators
1. **Login**: Access the admin panel with your credentials
2. **Monitor Reports**: View all incoming emergency reports on the dashboard
3. **Manage Response**: Update report status and coordinate response activities
4. **Resource Management**: Track and allocate emergency resources
5. **Generate Reports**: Create analytics and status reports

## Security Features

- Input sanitization and validation
- SQL injection prevention with prepared statements
- File upload security with type and size validation
- Session management for admin authentication
- Error logging for security monitoring

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is developed for educational and public service purposes. Please ensure compliance with local regulations when implementing in production environments.

## Support

For technical support or questions:
- Create an issue in the GitHub repository
- Check the documentation in the `/docs` folder (if available)
- Review the code comments for implementation details

---

**Version**: 1.0.0  
**Last Updated**: September 27, 2025  
**Compatibility**: PHP 7.4+, MySQL 5.7+
