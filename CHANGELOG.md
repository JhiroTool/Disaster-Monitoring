# Changelog

## [2025.10.05] - Role System Simplification & Project Cleanup

### 🔄 **Major Changes**
- **Simplified Role System**: Reduced from 4 roles to 2 roles (admin, reporter)
- **Project Structure**: Reorganized files into logical directories

### ✨ **New Features**
- **Reporter Registration**: Public registration system for emergency reporters
- **Improved Navigation**: Added registration links across all pages
- **Enhanced UI**: Modern, responsive registration form

### 🗂️ **Project Organization**
- **`/sql/`**: All database files and migration scripts
- **`/docs/`**: Technical documentation and guides
- **Root level**: Main application files (login, register, etc.)

### 📋 **Role Migration**
| Old Role | New Role | Description |
|----------|----------|-------------|
| admin | admin | System administrators |
| lgu_admin | reporter | Emergency reporters |
| lgu_staff | reporter | Emergency reporters |
| responder | reporter | Emergency reporters |

### 🔧 **Technical Updates**
- Updated all PHP files to use new role system
- Modified database schema enums
- Created migration scripts for existing installations
- Updated authentication and authorization logic
- Fixed CSS classes for new role badges

### 📁 **File Changes**
**Moved:**
- `disaster_monitoring*.sql` → `sql/`
- `*.md` documentation → `docs/`
- `alter_status.sql` → `sql/`
- `migrate_roles.sql` → `sql/`

**Added:**
- `register.php` - Reporter registration page
- `sql/README.md` - SQL files documentation
- `docs/README.md` - Documentation index
- `INSTALL.md` - Quick installation guide

**Updated:**
- All navigation menus to include registration
- Role checking logic in admin files
- Database schema for simplified roles
- Main README.md with new structure

### 🛠️ **Breaking Changes**
- **Database Schema**: Role enums changed (migration script provided)
- **User Permissions**: Only admin role has admin panel access
- **File Paths**: SQL files moved to `/sql/` directory

### 📚 **Documentation**
- Enhanced README with emoji icons and better structure
- Added installation guide
- Organized technical documentation in `/docs/`
- Added SQL file documentation

## [Previous] - Initial System
- Emergency reporting system
- Admin dashboard
- Notification system
- User management
- LGU coordination
- Resource tracking