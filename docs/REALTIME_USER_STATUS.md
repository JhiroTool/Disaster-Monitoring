# Real-Time User Status Updates

## Overview
The Users Management page now features **real-time status updates** that instantly reflect when reporters change their status between "I'm fine" and "Need help" - **without requiring a page refresh**.

## How It Works

### Backend (Server-Side Events)
1. **SSE Stream** (`admin/ajax/realtime-updates.php`)
   - Monitors user status changes every 2 seconds
   - Tracks `users_need_help` and `users_safe` counts
   - Broadcasts updates when changes are detected

2. **User Data Endpoint** (`admin/ajax/get-users-data.php`)
   - Provides fresh user data on demand
   - Returns individual user statuses and aggregate counts
   - Used by frontend to update the display

### Frontend (Client-Side Updates)
1. **Real-Time System** (`admin/assets/js/realtime-system.js`)
   - Maintains persistent SSE connection
   - Triggers `onUserStatusChange` callbacks
   - Provides API for pages to register event handlers

2. **Users Page** (`admin/users.php`)
   - Listens for user status change events
   - Fetches updated data via AJAX
   - Updates UI with smooth animations

## Features

### Visual Updates
- ✨ **Stat Cards**: "Need Help" and "I'm Fine" counts update with animated number transitions
- 🎯 **User Rows**: Status badges update instantly when a reporter changes status
- 💫 **Flash Animation**: Changed rows briefly highlight in yellow
- 🔔 **Notifications**: Beautiful gradient notification appears on status changes

### Performance
- ⚡ Updates detected within 2 seconds
- 🎨 Smooth animations without layout shifts
- 📊 Minimal server load (efficient queries)
- 🔄 Automatic reconnection on connection loss

## Usage Example

When a reporter updates their status from "I'm fine" to "Need help":

1. Reporter clicks their status dropdown and selects "Need help"
2. Status is saved to database
3. SSE stream detects the change (within 2 seconds)
4. All admin users on the Users page see:
   - "Need Help" count increases (animated)
   - "I'm Fine" count decreases (animated)
   - Reporter's status badge updates from green to red
   - Row flashes yellow briefly
   - Notification appears: "Reporter status updated!"

## Code Integration

### Registering for Updates
```javascript
// In your page's JavaScript
if (window.RealtimeSystem) {
    // Listen for user status changes
    window.RealtimeSystem.registerCallback('onUserStatusChange', (data) => {
        console.log('User status changed!', data);
        // Update your UI here
    });
}
```

### Available Callbacks
- `onUpdate`: General updates (includes all changes)
- `onUserStatusChange`: Specific to user status changes
- `onNewReport`: New disaster reports
- `onConnect`: Connection established
- `onDisconnect`: Connection lost

### Data Structure
```javascript
{
    stats: {
        total_users: 45,
        users_need_help: 3,
        users_safe: 42,
        // ... other stats
    },
    changes: {
        user_status_changed: true,
        users_need_help_delta: 1  // Change amount
    },
    timestamp: 1729123456
}
```

## Benefits

### For Admins
- 🚨 **Immediate Awareness**: Know instantly when reporters need help
- 📊 **Live Monitoring**: Real-time view of reporter safety status
- ⏱️ **No Manual Refresh**: Information always current
- 🎯 **Quick Response**: Respond faster to emergency situations

### For System
- 🔋 **Efficient**: Only updates when changes occur
- 🌐 **Scalable**: Single SSE connection per admin
- 🔒 **Secure**: Auth-protected endpoints
- 🔄 **Reliable**: Auto-reconnection on failures

## Technical Details

### Database Queries
```sql
-- User status count query (runs every 2 seconds)
SELECT 
    COUNT(*) as total_users,
    COUNT(CASE WHEN status = 'Need help' THEN 1 END) as users_need_help,
    COUNT(CASE WHEN status = "I'm fine" THEN 1 END) as users_safe
FROM users 
WHERE role = 'reporter'
```

### Update Flow
```
Reporter Changes Status
         ↓
    Database Updated
         ↓
SSE Stream Detects Change (every 2s)
         ↓
  Broadcast to All Admins
         ↓
   Frontend Receives Event
         ↓
    Fetch Fresh User Data
         ↓
  Update UI with Animations
         ↓
  Show Success Notification
```

## Browser Compatibility
- ✅ Chrome/Edge (Chromium)
- ✅ Firefox
- ✅ Safari
- ✅ Opera
- ⚠️ IE11 (requires polyfill)

## Troubleshooting

### Updates Not Appearing
1. Check browser console for errors
2. Verify SSE connection: Look for "✅ Real-time updates connected"
3. Check if `window.RealtimeSystem` exists
4. Verify server-side SSE endpoint is running

### Connection Issues
- The system auto-reconnects up to 5 times
- Check PHP error logs for SSE errors
- Ensure proper session handling
- Verify database connection

### Performance Issues
- SSE checks every 2 seconds (configurable)
- Only sends updates when data actually changes
- Uses efficient single-query approach
- Consider increasing check interval if needed

## Future Enhancements
- [ ] WebSocket support for even faster updates
- [ ] User location tracking updates
- [ ] Batch status updates for multiple users
- [ ] Historical status change log
- [ ] Push notifications for critical status changes

## Related Files
- `admin/ajax/realtime-updates.php` - SSE endpoint
- `admin/ajax/get-users-data.php` - User data endpoint
- `admin/assets/js/realtime-system.js` - Real-time client
- `admin/users.php` - Users management page
- `docs/REALTIME_QUICK_REFERENCE.md` - General real-time docs

---

**Last Updated**: October 13, 2025
**Version**: 1.0
**Status**: ✅ Production Ready
