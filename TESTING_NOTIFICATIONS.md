# Testing the Notification System

## Quick Start Testing Guide

### Step 1: Check Database Setup
Your database has been updated with:
- ✅ `related_disaster_id` column added to notifications table
- ✅ New notification types: 'alert', 'warning', 'info'
- ✅ Index created for performance

### Step 2: Test New Report Notification

1. **Submit a new disaster report**:
   - Go to: `http://localhost/Disaster-Monitoring/`
   - Fill out the emergency report form
   - Submit the report
   - Note the tracking ID

2. **Login as admin**:
   - Go to: `http://localhost/Disaster-Monitoring/admin/login.php`
   - Login with admin credentials
   - Look at the notification bell icon (top right)
   - You should see a badge with notification count

3. **View the notification**:
   - Click the bell icon
   - You should see a notification like:
     ```
     New [Critical/High/Normal] Disaster Report: [Disaster Type]
     A new disaster report has been submitted...
     [5 minutes ago]
     ```
   - Click the notification to view disaster details

### Step 3: Test Login-Time Check

1. **Create some test reports** (while logged out)
2. **Login as admin**
3. Check server logs for message:
   ```
   Created X notifications for new disaster reports
   ```
4. All reports from last 7 days should have notifications

### Step 4: Verify Notification Details

Check that each notification includes:
- ✅ Disaster type (Earthquake, Flood, etc.)
- ✅ Location (City, Province, Region)
- ✅ Severity level
- ✅ Status (ON GOING, IN PROGRESS, COMPLETED)
- ✅ Tracking ID
- ✅ Clickable link to disaster details
- ✅ Time ago format

### Step 5: Test Different Priority Levels

Submit reports with different priorities:

1. **Critical Priority** → Should show:
   - Icon: ⚠️ (exclamation triangle)
   - Color: Red
   - Type: Alert

2. **High Priority** → Should show:
   - Icon: ❗ (exclamation circle)
   - Color: Orange
   - Type: Warning

3. **Normal/Low Priority** → Should show:
   - Icon: ℹ️ (info circle)
   - Color: Blue
   - Type: Info

## Expected Behavior

### When you submit a report:
```
1. Report saved to database ✓
2. Notification created for all admins ✓
3. Notification visible in admin panel ✓
4. Badge count updated ✓
```

### When admin logs in:
```
1. Login successful ✓
2. System checks for unreported disasters ✓
3. Creates missing notifications ✓
4. Logs notification count ✓
5. Redirects to dashboard ✓
```

### When clicking notification:
```
1. Notification dropdown opens ✓
2. Shows recent 20 notifications ✓
3. Unread notifications highlighted ✓
4. Click → redirects to disaster details ✓
```

## Troubleshooting

### No notifications appearing?

**Check 1**: Verify database column exists
```sql
DESCRIBE notifications;
-- Should show 'related_disaster_id' column
```

**Check 2**: Check error logs
```bash
tail -f /opt/lampp/logs/error_log
```

**Check 3**: Verify user role
```sql
SELECT user_id, username, role FROM users WHERE role IN ('admin', 'lgu_admin');
-- You must be logged in as admin or lgu_admin
```

**Check 4**: Check notifications table
```sql
SELECT COUNT(*) FROM notifications;
SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5;
```

### Notification created but not showing?

**Check 1**: Verify is_active flag
```sql
SELECT * FROM notifications WHERE is_active = FALSE;
-- Should be empty or notifications should be active
```

**Check 2**: Check expiry date
```sql
SELECT * FROM notifications WHERE expires_at < NOW();
-- These won't show
```

**Check 3**: Clear browser cache
- Hard refresh: `Ctrl + Shift + R` (Windows/Linux)
- Or: `Cmd + Shift + R` (Mac)

### Notification shows but no disaster details?

**Check**: Verify foreign key relationship
```sql
SELECT n.*, d.tracking_id, d.disaster_name 
FROM notifications n
LEFT JOIN disasters d ON n.related_disaster_id = d.disaster_id
WHERE n.related_disaster_id IS NOT NULL
LIMIT 5;
```

## Success Indicators

You'll know the system is working when:

1. ✅ Badge shows number of unread notifications
2. ✅ Notifications display in dropdown
3. ✅ Clicking notification navigates to disaster details
4. ✅ New reports automatically create notifications
5. ✅ Login checks for missed reports
6. ✅ Priority levels display correctly
7. ✅ Time ago format works
8. ✅ Icons and colors match notification types

## What You Should See

### Notification Bell (Header)
```
🔔 (5)  ← Badge shows unread count
```

### Notification Dropdown
```
┌─────────────────────────────────────┐
│ Notifications             Mark all  │
├─────────────────────────────────────┤
│ ⚠️ New Critical Disaster: Earthquake│
│    Davao City - 5 minutes ago    🔴 │
├─────────────────────────────────────┤
│ ❗ New High Disaster: Flood         │
│    Manila - 1 hour ago           🟠 │
├─────────────────────────────────────┤
│ ℹ️ New Disaster: Landslide          │
│    Cebu - 2 hours ago            🔵 │
├─────────────────────────────────────┤
│          View all notifications     │
└─────────────────────────────────────┘
```

## Manual Test Checklist

- [ ] Submit disaster report while logged out
- [ ] Login as admin
- [ ] Check notification badge appears
- [ ] Click notification bell
- [ ] Verify notifications display
- [ ] Click a notification
- [ ] Verify redirects to disaster details
- [ ] Check disaster tracking ID matches
- [ ] Verify priority colors correct
- [ ] Test mark as read functionality
- [ ] Submit multiple reports with different priorities
- [ ] Verify all admins receive notifications
- [ ] Check time ago format updates
- [ ] Test with expired notifications
- [ ] Verify inactive notifications don't show

## Need Help?

Check the comprehensive documentation:
- See: `NOTIFICATION_SYSTEM_DOCS.md`

Or check error logs:
```bash
tail -f /opt/lampp/logs/error_log
tail -f /opt/lampp/logs/php_error_log
```

## Quick SQL Queries for Testing

```sql
-- Count total notifications
SELECT COUNT(*) as total FROM notifications;

-- Count by type
SELECT type, COUNT(*) as count FROM notifications GROUP BY type;

-- Recent notifications with disaster details
SELECT n.title, n.type, n.created_at, d.tracking_id, d.disaster_name
FROM notifications n
JOIN disasters d ON n.related_disaster_id = d.disaster_id
ORDER BY n.created_at DESC
LIMIT 10;

-- Check unread count for a user
SELECT COUNT(*) as unread 
FROM notifications 
WHERE user_id = 1 AND is_read = FALSE;

-- Admins who should receive notifications
SELECT user_id, username, email, role 
FROM users 
WHERE role IN ('admin', 'lgu_admin') 
AND is_active = TRUE;
```

Happy Testing! 🎉
