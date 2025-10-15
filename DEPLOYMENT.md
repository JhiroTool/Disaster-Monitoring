# Deployment Guide - Hostinger

## FTP Credentials
Get these from Hostinger Panel → Files → FTP Accounts

- **Host**: srv2046.hstgr.io or ftp.yourdomain.com
- **Port**: 21
- **Username**: Your FTP username
- **Password**: Your FTP password

## Quick Upload Steps

### Option 1: FileZilla (Recommended)
1. Download FileZilla: https://filezilla-project.org/
2. Connect using FTP credentials above
3. Local site: `/opt/lampp/htdocs/Disaster-Monitoring/`
4. Remote site: `/public_html/`
5. Drag and drop files to upload

### Option 2: Manual File Manager
1. Go to: https://srv2046-files.hstgr.io/
2. Navigate to `public_html` folder
3. Click upload button
4. Select files from your project

## Files to Upload
- All `.php` files (index.php, submit.php, etc.)
- `config/` folder
- `assets/` folder (CSS, JS, images)
- Any other project files

## Important Notes
- Upload to `public_html` folder (or subdirectory like `public_html/disaster-monitoring`)
- Database is already configured correctly
- After upload, access via your domain or Hostinger temporary URL
