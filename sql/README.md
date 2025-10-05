# SQL Files

This directory contains all SQL scripts and database files for the Disaster Monitoring System.

## Files Description

### Database Schema Files
- **`disaster_monitoring_complete.sql`** - Complete database schema with all tables, data, and constraints (RECOMMENDED FOR NEW INSTALLATIONS)
- **`disaster_monitoring_old.sql`** - Older version of the database schema (kept for reference)

### Migration Scripts
- **`migrate_roles.sql`** - Migrates user roles from the old 4-role system to the new admin/reporter system
- **`alter_status.sql`** - Updates disaster status values to ON GOING, IN PROGRESS, COMPLETED

## Installation Instructions

### For New Installation:
```sql
-- Import the complete database schema
SOURCE disaster_monitoring_complete.sql;
```

### For Existing Installation:
If you have an existing database and want to update to the new role system:
```sql
-- Run the role migration script
SOURCE migrate_roles.sql;
```

If you need to update disaster status values:
```sql
-- Run the status update script  
SOURCE alter_status.sql;
```

## Database Structure

The main database includes:
- **users** - Admin and reporter user accounts
- **disasters** - Emergency/disaster reports
- **lgus** - Local Government Unit information
- **notifications** - System notifications
- **announcements** - Public announcements
- **activity_logs** - System activity tracking
- **disaster_types** - Types of disasters/emergencies
- **disaster_resources** - Resources allocated to disasters
- And various other supporting tables and views

## Role System

The system uses a simplified 2-role structure:
- **admin** - Full system access, can manage all features
- **reporter** - Can report emergencies and view their reports