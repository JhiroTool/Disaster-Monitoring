# Disaster Report Notification System - Implementation Summary

## Overview
Implemented an automatic notification system that creates real-time notifications for admin users when new disaster reports are submitted.

## Features Implemented

### 1. Automatic Notification Creation
- **When**: Automatically triggered when a new disaster report is submitted via `report_emergency.php`
- **Who**: All admin and LGU admin users receive notifications
- **What**: Notifications include disaster type, location, severity, status, and tracking ID

### 2. Login-Time Notification Check
- **When**: Every time an admin logs in
- **What**: System checks for any disaster reports that don't have notifications yet
- **Result**: Creates notifications for any missed reports from the last 7 days

### 3. Smart Notification Types
Notifications are categorized by priority level:
- **CRITICAL** priority → `alert` type (red, urgent)
- **HIGH** priority → `warning` type (orange, important)
- **NORMAL/LOW** priority → `info` type (blue, informational)

### 4. Enhanced Notification Display
- Shows disaster tracking ID
- Displays disaster type and priority
- Includes clickable link to view full disaster details
- Time ago format (e.g., "5 minutes ago", "2 hours ago")
- Icon-based visual indicators
- Unread count badge

## Files Modified

### 1. `/admin/includes/notification_helper.php` (NEW)
**Purpose**: Helper functions for notification management

**Functions**:
- `createDisasterNotification($pdo, $disaster_id)` - Creates notifications for a specific disaster
- `checkAndNotifyNewReports($pdo)` - Checks for unreported disasters and creates notifications
- `getUnreadNotificationCount($pdo, $user_id)` - Gets unread notification count
- `markNotificationAsRead($pdo, $notification_id, $user_id)` - Marks notification as read
- `createStatusUpdateNotification($pdo, $disaster_id, $old_status, $new_status, $updated_by)` - Creates notification when status changes

### 2. `/login.php` (MODIFIED)
**Changes**:
- Added `require_once 'includes/notification_helper.php'`
- After successful login, calls `checkAndNotifyNewReports($pdo)` for admins
- Logs notification creation count

### 3. `/report_emergency.php` (MODIFIED)
**Changes**:
- After disaster report is saved, automatically calls `createDisasterNotification($pdo, $disaster_id)`
- Creates notifications for all admin users immediately
- Logs notification creation (doesn't break form submission if notification fails)

### 4. `/admin/ajax/get-notifications.php` (ENHANCED)
**Changes**:
- Updated to fetch `related_disaster_id` column
- Added disaster details (tracking_id, type, priority, status)
- Returns formatted notifications with icons, classes, and links
- Includes time_ago formatting
- Returns unread count

## Database Changes

### Notifications Table Updates
```sql
-- Added new column for disaster relationship
ALTER TABLE notifications 
ADD COLUMN IF NOT EXISTS related_disaster_id INT(11) NULL AFTER related_id,
ADD INDEX idx_related_disaster (related_disaster_id);

-- Updated notification types
ALTER TABLE notifications 
MODIFY COLUMN type ENUM(
    'disaster_assigned',
    'status_update',
    'escalation',
    'deadline_warning',
    'system',
    'alert',      -- NEW
    'warning',    -- NEW
    'info'        -- NEW
) NOT NULL DEFAULT 'info';
```

## How It Works

### Flow 1: New Report Submitted
```
1. User submits disaster report via report_emergency.php
2. Report is saved to disasters table
3. createDisasterNotification() is called
4. Function fetches all admin/lgu_admin users
5. Creates notification for each admin user
6. Notification includes:
   - Title: "New [Priority] Disaster Report: [Type]"
   - Message: Location, severity, status, tracking ID
   - Type: Based on priority (alert/warning/info)
   - Link: related_disaster_id → disaster_id
```

### Flow 2: Admin Login
```
1. Admin logs in successfully
2. checkAndNotifyNewReports() is called
3. Finds disasters from last 7 days without notifications
4. Creates notifications for each missed disaster
5. Logs count of notifications created
6. Admin is redirected to dashboard
```

### Flow 3: Viewing Notifications
```
1. Admin clicks notification bell icon
2. AJAX call to get-notifications.php
3. Fetches notifications with disaster details
4. Returns formatted data with:
   - Icon based on type
   - Time ago format
   - Unread status
   - Clickable link to disaster details
5. Displays in dropdown with styling
```

## Notification Message Format

Example notification message:
```
Title: New Critical Disaster Report: Earthquake

Message:
A new disaster report has been submitted.
Type: Earthquake
Location: Davao City, Davao del Sur, Region XI
Severity: Critical (Red-3)
Status: ON GOING
Tracking ID: DR-2025-10-0001
```

## Benefits

1. **Real-time Alerts**: Admins are notified immediately when reports come in
2. **Priority-based**: Critical disasters get alert-type notifications
3. **No Missed Reports**: Login check ensures all reports are notified
4. **Easy Access**: Click notification to go directly to disaster details
5. **Visual Indicators**: Icons and colors help quickly identify notification importance
6. **Scalable**: Works for any number of admins
7. **Error Handling**: Notification failures don't break report submission

## Testing Checklist

- [ ] Submit a new disaster report → Check if admins receive notification
- [ ] Login as admin → Verify notification count badge updates
- [ ] Click notification bell → Verify notifications display correctly
- [ ] Click notification link → Verify redirects to disaster details
- [ ] Test with different priority levels (critical, high, normal)
- [ ] Test with multiple admin users
- [ ] Verify old reports (7+ days) don't create notifications
- [ ] Check notification icons and colors display correctly

## Future Enhancements

Potential improvements:
1. Email notifications for critical disasters
2. SMS alerts for urgent reports
3. Push notifications via browser API
4. Notification preferences per user
5. Bulk mark as read
6. Filter notifications by type
7. Search within notifications
8. Notification sound alerts
9. Desktop notifications
10. Mobile app integration

## Maintenance Notes

- Notifications older than specified expiry date are automatically hidden
- System only creates notifications for reports from last 7 days
- Notification creation is logged for debugging
- Failed notification creation doesn't stop report submission
- Database indexes added for performance
