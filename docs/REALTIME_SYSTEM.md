# Real-Time System Documentation

## Overview

The iMSafe Disaster Monitoring System uses **Server-Sent Events (SSE)** for real-time updates across all admin pages. This provides instant notifications when new disaster reports are submitted, user statuses change, or any critical data is updated.

## Architecture

### Components

1. **RealtimeSystem Class** (`admin/assets/js/realtime-system.js`)
   - Client-side JavaScript class that manages SSE connections
   - Automatically loads on all admin pages via header
   - Provides callback registration for custom page-specific handlers

2. **SSE Endpoint** (`admin/ajax/realtime-updates.php`)
   - Server-side PHP script that streams updates
   - Checks database every 2 seconds for changes
   - Sends only changed data to minimize bandwidth

3. **Notification Badge Updater** (`admin/ajax/get-notification-count.php`)
   - Updates notification badges in real-time
   - Called automatically every 30 seconds

## How It Works

### Connection Flow

```
1. Page loads → RealtimeSystem initializes
2. Connects to realtime-updates.php via EventSource
3. Server sends 'connected' event
4. Server checks database every 2 seconds
5. When changes detected → sends 'update' event
6. Client receives update → triggers callbacks
7. Page-specific handlers update UI
```

### Event Types

| Event | Description | Data Included |
|-------|-------------|---------------|
| `connected` | Initial connection established | timestamp, message |
| `update` | Data changed in database | stats, changes, timestamp |
| `heartbeat` | Keep-alive signal (every 30s) | timestamp |
| `reconnect` | Server requests reconnection | message |

### Statistics Tracked

**Disaster Stats:**
- `total_disasters` - Total number of disaster reports
- `active_disasters` - Non-completed disasters
- `critical_disasters` - Critical priority disasters
- `completion_rate` - Percentage of completed reports

**User Stats:**
- `total_users` - Total reporters
- `users_need_help` - Users with "Need help" status
- `users_safe` - Users with "I'm fine" status

## Implementation on Admin Pages

### Automatic Features (Works on ALL pages)

✅ **Connection Status Indicator** - Shows in header
✅ **Notification Badge Updates** - Auto-updates every 30s
✅ **Toast Notifications** - For new reports
✅ **Browser Notifications** - Desktop notifications (if permitted)
✅ **Sound Alerts** - Audio notification for new reports

### Page-Specific Integration

#### Dashboard (`dashboard.php`)
```javascript
// Already integrated - updates stat cards automatically
```

#### Users Page (`users.php`)
```javascript
window.RealtimeSystem.registerCallback('onUserStatusChange', (data) => {
    updateUserStatuses(); // Refresh user status badges
});
```

#### Disasters Page (`disasters.php`)
```javascript
window.RealtimeSystem.registerCallback('onNewReport', (data) => {
    handleNewDisasterNotification(data); // Show notification & reload table
});
```

#### Reports Page (`reports.php`)
```javascript
window.RealtimeSystem.registerCallback('onUpdate', (data) => {
    // Update charts and statistics
});
```

## Adding Real-Time to New Pages

### Step 1: Register Callbacks

Add this JavaScript to your page:

```javascript
// Wait for RealtimeSystem to initialize
setTimeout(() => {
    if (window.RealtimeSystem) {
        // Listen for general updates
        window.RealtimeSystem.registerCallback('onUpdate', (data) => {
            console.log('Update received:', data);
            // Your custom update logic here
        });
        
        // Listen for new reports
        window.RealtimeSystem.registerCallback('onNewReport', (data) => {
            console.log('New report:', data);
            // Handle new report notification
        });
        
        // Listen for user status changes
        window.RealtimeSystem.registerCallback('onUserStatusChange', (data) => {
            console.log('User status changed:', data);
            // Handle user status update
        });
    }
}, 1000);
```

### Step 2: Update UI Elements

The system automatically updates elements with these IDs:
- `total-disasters`
- `active-disasters`
- `critical-disasters`
- `pending-disasters`
- `total-users`
- `users-need-help`
- `users-safe`

Just add these IDs to your stat cards!

## Available Callbacks

| Callback | When Triggered | Use Case |
|----------|----------------|----------|
| `onConnect` | SSE connection established | Show connection status |
| `onDisconnect` | Connection lost | Handle reconnection |
| `onUpdate` | Any data changed | Update statistics |
| `onNewReport` | New disaster report | Show alert, reload table |
| `onUserStatusChange` | User status updated | Refresh user list |
| `onStatusChange` | Connection status changed | Update UI indicators |

## Testing

### Test Page
Access the real-time test dashboard:
```
http://localhost/Disaster-Monitoring/admin/test-realtime
```

Features:
- ✅ Connection status monitoring
- ✅ Live statistics display
- ✅ Event log viewer
- ✅ Test notifications
- ✅ Uptime counter

### Manual Testing

1. **Test New Report Detection:**
   - Open admin dashboard
   - Submit a new disaster report from another browser/device
   - Should see toast notification within 2 seconds

2. **Test User Status Change:**
   - Open users page
   - Change user status from reporter dashboard
   - Should see status badge update within 2 seconds

3. **Test Connection Recovery:**
   - Open any admin page
   - Restart Apache/MySQL
   - Should automatically reconnect within 3-5 seconds

## Performance Optimization

### Current Settings
- **Check Interval:** 2 seconds (fast response)
- **Heartbeat:** Every 30 seconds
- **Max Runtime:** 3 minutes (then reconnects)
- **Reconnect Attempts:** 5 max
- **Reconnect Delay:** 3 seconds

### Bandwidth Usage
- **Idle:** ~1 KB every 30 seconds (heartbeat only)
- **Active:** ~2-5 KB per update (only sends changed data)
- **Average:** < 10 KB/minute per connected admin

## Troubleshooting

### Issue: "RealtimeSystem not available"
**Solution:** Check that `realtime-system.js` is loaded in header.php

### Issue: No updates received
**Solution:** 
1. Check browser console for errors
2. Verify `realtime-updates.php` is accessible
3. Check database connection
4. Ensure user is logged in as admin

### Issue: Connection keeps dropping
**Solution:**
1. Check Apache timeout settings
2. Verify firewall not blocking SSE
3. Check PHP max_execution_time
4. Review server logs

### Issue: Updates delayed
**Solution:**
1. Check server load
2. Verify database query performance
3. Consider increasing check interval if needed

## Browser Compatibility

✅ **Supported:**
- Chrome/Edge 90+
- Firefox 85+
- Safari 14+
- Opera 75+

❌ **Not Supported:**
- Internet Explorer (use modern browser)

## Security

- ✅ Session-based authentication
- ✅ Role-based access (admin/lgu_staff only)
- ✅ No sensitive data in events
- ✅ Automatic connection cleanup
- ✅ CSRF protection via session

## API Reference

### RealtimeSystem Methods

```javascript
// Register callback
window.RealtimeSystem.registerCallback(event, callback)

// Get connection status
window.RealtimeSystem.getStatus()
// Returns: { connected: boolean, lastStats: object, reconnectAttempts: number }

// Disconnect manually
window.RealtimeSystem.disconnect()

// Show toast notification
window.RealtimeSystem.showToast(message, type, withSound)
// type: 'success', 'error', 'info', 'warning'
```

## Future Enhancements

- [ ] WebSocket support for even faster updates
- [ ] Configurable check intervals per page
- [ ] Real-time chat for coordinators
- [ ] Live disaster map updates
- [ ] Push notifications to mobile devices

## Conclusion

The real-time system is **fully operational** across all admin pages. It provides instant updates with minimal server load and bandwidth usage. No additional configuration needed - it works automatically!

For questions or issues, check the test dashboard at `/admin/test-realtime`.
