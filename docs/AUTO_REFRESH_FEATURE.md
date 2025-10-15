# Auto-Refresh Feature Documentation

## Overview

The admin pages now **automatically refresh** when new data is detected via the real-time system. No manual page refresh needed!

## How It Works

### Flow Diagram
```
1. New disaster report submitted
   ↓
2. SSE detects change (within 2 seconds)
   ↓
3. Toast notification appears
   ↓
4. Page auto-refreshes after 3 seconds
   ↓
5. New report visible in table
```

## Pages with Auto-Refresh

### ✅ Dashboard (`dashboard.php`)
**Trigger:** New disaster report
**Action:** Full page reload after 3 seconds
**Shows:**
- Updated stat cards
- New reports in recent reports table
- Updated charts

### ✅ Disasters Page (`disasters.php`)
**Trigger:** New disaster report
**Action:** Full page reload after 3 seconds
**Shows:**
- New disaster in the table
- Updated banner notification
- Updated statistics

### ✅ Users Page (`users.php`)
**Trigger:** User status change
**Action:** AJAX refresh (no page reload)
**Shows:**
- Updated user status badges
- Updated stat cards
- Smooth animations

## User Experience

### Before (Manual Refresh Required)
1. 🔔 Notification appears: "New disaster report!"
2. 👀 User sees notification
3. 🖱️ User clicks refresh button or F5
4. ✅ New report appears

### After (Auto-Refresh)
1. 🔔 Notification appears: "New disaster report!"
2. ⏱️ 3-second countdown
3. 🔄 Page auto-refreshes
4. ✅ New report appears automatically

## Timing

| Event | Delay | Reason |
|-------|-------|--------|
| Detection | 0-2s | SSE checks every 2 seconds |
| Notification | Instant | Toast appears immediately |
| Auto-refresh | 3s | Gives user time to read notification |
| Total | 3-5s | From report submission to visibility |

## Customization

### Change Auto-Refresh Delay

**Dashboard & Disasters:**
```javascript
// In dashboard.php or disasters.php
setTimeout(() => {
    location.reload();
}, 3000); // Change 3000 to desired milliseconds
```

**Disable Auto-Refresh:**
```javascript
// Comment out or remove the setTimeout block
// setTimeout(() => {
//     location.reload();
// }, 3000);
```

### Users Page (AJAX Refresh)
The users page uses AJAX instead of full reload for smoother UX.

To disable:
```javascript
// In users.php, comment out:
// updateUserStatuses();
```

## Benefits

✅ **No Manual Refresh** - Data appears automatically
✅ **Fast Updates** - 3-5 second total latency
✅ **User-Friendly** - Notification before refresh
✅ **Smooth UX** - Users page uses AJAX (no flicker)
✅ **Reliable** - Works across all browsers

## Technical Details

### Dashboard & Disasters
- Uses `location.reload()` for full page refresh
- Preserves scroll position (browser default)
- Clears any form inputs (expected behavior)

### Users Page
- Uses AJAX fetch to `ajax/get-users-data.php`
- Updates DOM elements without page reload
- Animated transitions for status changes
- Preserves scroll position and form state

## Testing

### Test Auto-Refresh on Dashboard
1. Open admin dashboard
2. Submit new disaster report from another browser/tab
3. Watch for notification
4. Page auto-refreshes after 3 seconds
5. New report appears in table

### Test Auto-Refresh on Disasters Page
1. Open disasters page
2. Submit new disaster report
3. Banner appears: "🚨 New Emergency Report"
4. Page auto-refreshes after 3 seconds
5. New disaster in table

### Test AJAX Refresh on Users Page
1. Open users page
2. Change user status from reporter dashboard
3. Watch stat cards update
4. User status badge changes color
5. No page reload (smooth transition)

## Troubleshooting

### Issue: Page refreshes too quickly
**Solution:** Increase timeout from 3000ms to 5000ms or more

### Issue: Page doesn't refresh
**Solution:** 
1. Check browser console for errors
2. Verify real-time system is connected
3. Check that `onNewReport` callback is registered

### Issue: Data appears but notification doesn't
**Solution:** Check that RealtimeSystem is loaded in header

### Issue: Multiple refreshes
**Solution:** Ensure only one callback is registered (check for duplicate code)

## Performance Impact

- **Network:** Minimal (only refreshes when needed)
- **CPU:** Low (3-second delay prevents rapid refreshes)
- **UX:** Excellent (users see updates automatically)

## Future Enhancements

- [ ] Add countdown timer in notification
- [ ] Option to disable auto-refresh per user
- [ ] Smart refresh (only update table rows, not full page)
- [ ] Batch updates (wait for multiple reports)
- [ ] Configurable delay in settings

## Conclusion

The auto-refresh feature ensures admins **always see the latest data** without manual intervention. Combined with real-time notifications, this provides an excellent monitoring experience! 🚀
