# Real-Time Dashboard Auto-Refresh Implementation

**Date:** October 12, 2025  
**Feature:** Auto-refresh dashboard with live updates

## Overview

Implemented automatic real-time polling system for the admin dashboard to ensure administrators see new reports immediately without manual refresh.

## Key Features

### 1. **Automatic Polling**
- ⏱️ Updates every **10 seconds** automatically
- 📊 Fetches latest statistics and reports
- 🔄 Runs in background without user interaction

### 2. **Smart Pause/Resume**
- ⏸️ Pauses when user switches to another tab (saves server resources)
- ▶️ Resumes automatically when user returns to dashboard
- 🔄 Immediately refreshes data when page becomes visible again

### 3. **Visual Feedback**
- 🕐 **"Last updated"** timestamp displays current time
- ✨ **Flash animation** on stat cards when values change
- 🔢 **Animated number counting** when stats update
- 🆕 **New row highlighting** in recent reports table (green flash)
- 🔔 **Toast notification** when new reports arrive

### 4. **Manual Refresh**
- 🔄 Keep the refresh button for manual updates
- 🌀 Spinning icon animation when refreshing
- ✅ Success notification on manual refresh

## Technical Implementation

### Auto-Refresh Interval
```javascript
const REFRESH_INTERVAL = 10000; // 10 seconds (10,000 ms)
```

### Data Updated Automatically
1. **Stat Cards**
   - Total Reports
   - Active Disasters
   - Critical Alerts
   - Completion Rate

2. **Recent Reports Table**
   - Latest 10 reports
   - Highlights new rows in green

### API Endpoint Required
The code calls:
- `AdminAjax.getDashboardStats()` - Gets dashboard statistics
- `ajax/get-dashboard-stats.php?reports=true` - Gets recent reports list

**Note:** You need to ensure these endpoints exist and return proper data format.

## User Experience

### On Page Load
1. Dashboard loads with current data
2. Auto-refresh starts immediately
3. "Last updated" timestamp appears
4. Updates begin every 10 seconds

### When New Reports Arrive
1. Stat cards flash with highlight
2. Numbers animate to new values
3. Recent reports table updates
4. New rows highlighted in green
5. Toast notification slides in from right

### When User Leaves Tab
1. Auto-refresh pauses (saves resources)
2. Console logs: "Page hidden - auto-refresh paused"

### When User Returns to Tab
1. Auto-refresh resumes
2. Immediate data refresh
3. Console logs: "Page visible - auto-refresh resumed"

## Animations Added

### CSS Animations (`dashboard-modern.css`)

1. **Flash Animation**
   ```css
   @keyframes flash {
       0%, 100% { background-color: transparent; }
       50% { background-color: rgba(102, 126, 234, 0.15); }
   }
   ```

2. **Slide In/Out (Notifications)**
   ```css
   @keyframes slideInRight {
       from { transform: translateX(400px); opacity: 0; }
       to { transform: translateX(0); opacity: 1; }
   }
   ```

3. **Highlight Row (New Reports)**
   ```css
   @keyframes highlightRow {
       0% { background-color: rgba(16, 185, 129, 0.2); }
       100% { background-color: transparent; }
   }
   ```

4. **Pulse (Clock Icon)**
   ```css
   @keyframes pulse {
       0%, 100% { opacity: 1; }
       50% { opacity: 0.5; }
   }
   ```

## Performance Considerations

### Resource Optimization
- ✅ Pauses when page not visible
- ✅ Only updates changed values
- ✅ Lightweight AJAX calls
- ✅ No full page reloads

### Server Load
- 📊 1 request every 10 seconds per active admin
- 📉 Reduced when users switch tabs
- 🎯 Minimal database queries (only stats)

## Configuration

### Adjust Refresh Interval
```javascript
// Change this value in dashboard.php
const REFRESH_INTERVAL = 10000; // milliseconds

// Examples:
// 5 seconds: 5000
// 15 seconds: 15000
// 30 seconds: 30000
```

### Disable Auto-Refresh (if needed)
Comment out in `dashboard.php`:
```javascript
// startAutoRefresh(); // Disable auto-refresh
```

## Browser Compatibility

- ✅ Chrome/Edge (Modern)
- ✅ Firefox
- ✅ Safari
- ✅ Opera
- ❌ IE11 (requires polyfills)

## Future Enhancements

1. **WebSocket Integration**
   - Real-time push notifications
   - Instant updates (no polling delay)
   - Lower server load

2. **Configurable Intervals**
   - User preference for refresh rate
   - Admin settings panel
   - Per-role intervals

3. **Sound Notifications**
   - Optional alert sounds
   - Different sounds for different priorities
   - Volume control

4. **Desktop Notifications**
   - Browser notification API
   - Show alerts even when tab is hidden
   - Requires user permission

5. **Refresh Statistics**
   - Show total refreshes
   - Network usage stats
   - Last refresh duration

## Testing Checklist

### Auto-Refresh Testing
- [ ] Dashboard starts auto-refresh on load
- [ ] Stats update every 10 seconds
- [ ] "Last updated" timestamp updates correctly
- [ ] Flash animation appears on stat card changes
- [ ] Numbers animate smoothly

### Table Updates
- [ ] Recent reports table refreshes
- [ ] New rows highlighted in green
- [ ] Old rows remain visible
- [ ] Sorting/order maintained

### Notifications
- [ ] Toast notification appears for new reports
- [ ] Notification slides in from right
- [ ] Notification auto-dismisses after 5 seconds
- [ ] Multiple notifications stack properly

### Pause/Resume
- [ ] Auto-refresh pauses when switching tabs
- [ ] Auto-refresh resumes when returning to tab
- [ ] Immediate refresh on tab focus
- [ ] Console logs confirm pause/resume

### Manual Refresh
- [ ] Button triggers immediate refresh
- [ ] Spinning icon animation works
- [ ] Success notification appears
- [ ] Button disabled during refresh

---

## Files Modified

| File | Changes |
|------|---------|
| `admin/dashboard.php` | Added real-time refresh JavaScript |
| `admin/assets/css/dashboard-modern.css` | Added animation keyframes |

---

## API Requirements

### Required AJAX Endpoint Response Format

**`ajax/get-dashboard-stats.php`**
```json
{
    "success": true,
    "data": {
        "total": 150,
        "active": 45,
        "critical": 8,
        "pending": 12,
        "recent_reports": [
            {
                "disaster_id": 123,
                "tracking_id": "DM20251012-ABC123",
                "type_name": "Flood",
                "city": "Manila",
                "severity_level": "red-3",
                "severity_display": "Critical",
                "status": "ON GOING",
                "hours_ago": 2
            }
        ]
    }
}
```

---

**Last Updated:** October 12, 2025  
**Maintained By:** Development Team
