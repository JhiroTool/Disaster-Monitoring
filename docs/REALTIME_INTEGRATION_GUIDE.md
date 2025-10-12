# Real-Time System Integration Guide

**Purpose:** Step-by-step guide to integrate real-time features into admin pages

---

## 🎯 Quick Reference

The `realtime-system.js` is already loaded on all admin pages via `header.php`. You just need to hook into its events!

---

## 📚 Available Methods & Events

### Global Instance
```javascript
// Access the global RealtimeSystem instance
window.realtimeSystem
```

### Callback Types
```javascript
'onUpdate'        // General updates (stats, counts, etc.)
'onNewReport'     // New disaster report created
'onStatusChange'  // Disaster status changed
'onConnect'       // Real-time connection established
'onDisconnect'    // Real-time connection lost
```

---

## 🚀 Integration Patterns

### Pattern 1: Basic Event Listening

```javascript
<script>
// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if RealtimeSystem is available
    if (window.realtimeSystem) {
        // Register callback for new reports
        window.realtimeSystem.registerCallback('onNewReport', (data) => {
            console.log('New report received:', data);
            showNewReportNotification(data);
        });
        
        console.log('✅ Real-time system integrated on [PageName]');
    } else {
        console.warn('⚠️ RealtimeSystem not available');
    }
});

function showNewReportNotification(data) {
    // Your custom notification logic here
    alert('New disaster report: ' + data.tracking_id);
}
</script>
```

### Pattern 2: Dashboard Statistics Updates

```javascript
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.realtimeSystem) {
        // Listen for stat updates
        window.realtimeSystem.registerCallback('onUpdate', (data) => {
            if (data.stats) {
                updateStatCards(data.stats);
            }
        });
    }
});

function updateStatCards(stats) {
    // Update your stat cards
    if (stats.total_disasters !== undefined) {
        document.querySelector('#total-disasters').textContent = stats.total_disasters;
    }
    if (stats.active_disasters !== undefined) {
        document.querySelector('#active-disasters').textContent = stats.active_disasters;
    }
    // Add animation
    document.querySelectorAll('.stat-card').forEach(card => {
        card.classList.add('pulse-animation');
        setTimeout(() => card.classList.remove('pulse-animation'), 500);
    });
}
</script>
```

### Pattern 3: Banner Notification System

```javascript
<script>
function showNotificationBanner(title, message, type = 'info') {
    // Remove existing banner if any
    const existing = document.getElementById('realtime-banner');
    if (existing) existing.remove();
    
    // Create banner
    const banner = document.createElement('div');
    banner.id = 'realtime-banner';
    banner.className = `notification-banner notification-${type}`;
    banner.innerHTML = `
        <div class="banner-content">
            <i class="fas fa-${type === 'info' ? 'info-circle' : 'exclamation-triangle'}"></i>
            <div>
                <strong>${title}</strong>
                <p>${message}</p>
            </div>
        </div>
        <button onclick="this.parentElement.remove()" class="banner-close">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(banner);
    
    // Auto-remove after 10 seconds
    setTimeout(() => {
        if (banner.parentElement) {
            banner.style.animation = 'slideUp 0.4s ease-out';
            setTimeout(() => banner.remove(), 400);
        }
    }, 10000);
}

// Use it with RealtimeSystem
if (window.realtimeSystem) {
    window.realtimeSystem.registerCallback('onNewReport', (data) => {
        showNotificationBanner(
            'New Disaster Report',
            `${data.disaster_name} reported in ${data.city}`,
            'warning'
        );
    });
}
</script>

<style>
.notification-banner {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    padding: 16px 20px;
    z-index: 10000;
    min-width: 400px;
    max-width: 600px;
    animation: slideDown 0.4s ease-out;
    display: flex;
    align-items: center;
    gap: 12px;
}

.notification-info { border-left: 4px solid #3b82f6; }
.notification-warning { border-left: 4px solid #f59e0b; }
.notification-success { border-left: 4px solid #10b981; }

@keyframes slideDown {
    from {
        transform: translate(-50%, -100%);
        opacity: 0;
    }
    to {
        transform: translate(-50%, 0);
        opacity: 1;
    }
}
</style>
```

### Pattern 4: Table Auto-Refresh

```javascript
<script>
let lastUpdateTimestamp = Date.now();

if (window.realtimeSystem) {
    window.realtimeSystem.registerCallback('onUpdate', (data) => {
        // Check if our data has changed
        if (data.timestamp > lastUpdateTimestamp) {
            lastUpdateTimestamp = data.timestamp;
            refreshTable();
        }
    });
}

function refreshTable() {
    // Fetch fresh data
    fetch('ajax/get-data.php')
        .then(response => response.json())
        .then(data => {
            updateTableRows(data);
        })
        .catch(error => console.error('Error refreshing table:', error));
}

function updateTableRows(data) {
    const tbody = document.querySelector('#data-table tbody');
    // Update your table rows here
    // Add highlight animation to changed rows
    tbody.querySelectorAll('tr').forEach(row => {
        row.classList.add('row-updated');
        setTimeout(() => row.classList.remove('row-updated'), 2000);
    });
}
</script>

<style>
.row-updated {
    animation: highlightRow 2s ease-out;
}

@keyframes highlightRow {
    0% { background-color: #fef3c7; }
    100% { background-color: transparent; }
}
</style>
```

---

## 🎨 Implementation Examples by Page

### For disaster-details.php

```javascript
<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentDisasterId = <?php echo $disaster_id; ?>;
    
    if (window.realtimeSystem) {
        // Listen for status changes
        window.realtimeSystem.registerCallback('onStatusChange', (data) => {
            if (data.disaster_id == currentDisasterId) {
                // Update the status badge
                updateStatusBadge(data.new_status);
                
                // Show notification
                showNotificationBanner(
                    'Status Updated',
                    `Disaster status changed to: ${data.new_status}`,
                    'info'
                );
            }
        });
        
        console.log('✅ Real-time updates enabled for disaster #' + currentDisasterId);
    }
});

function updateStatusBadge(newStatus) {
    const statusBadge = document.querySelector('.status-badge');
    if (statusBadge) {
        statusBadge.textContent = newStatus;
        statusBadge.className = 'status-badge status-' + newStatus.toLowerCase().replace(/\s+/g, '-');
        statusBadge.classList.add('pulse-animation');
        setTimeout(() => statusBadge.classList.remove('pulse-animation'), 500);
    }
}
</script>
```

### For reports.php

```javascript
<script>
if (window.realtimeSystem) {
    window.realtimeSystem.registerCallback('onUpdate', (data) => {
        if (data.stats && data.stats.total_reports !== undefined) {
            updateReportCount(data.stats.total_reports);
        }
    });
    
    console.log('✅ Real-time report updates enabled');
}

function updateReportCount(newCount) {
    const countElement = document.querySelector('#report-count');
    if (countElement && countElement.textContent != newCount) {
        countElement.textContent = newCount;
        countElement.classList.add('count-updated');
        setTimeout(() => countElement.classList.remove('count-updated'), 1000);
    }
}
</script>
```

### For notifications.php

```javascript
<script>
if (window.realtimeSystem) {
    // The notification badge is already handled globally
    // But we can refresh the list when new notifications arrive
    window.realtimeSystem.registerCallback('onUpdate', (data) => {
        if (data.notification_count !== undefined) {
            // Optionally refresh the notifications list
            refreshNotificationsList();
        }
    });
    
    console.log('✅ Real-time notification updates enabled');
}

function refreshNotificationsList() {
    // Fetch and update notifications list
    fetch('ajax/get-notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationsList(data.notifications);
            }
        });
}
</script>
```

---

## 🔧 Testing Your Integration

### 1. Check Console
Open browser console and look for:
```
🚀 Initializing Real-Time System...
✅ SSE Connected
✅ Real-time updates enabled for [YourPage]
```

### 2. Test Connection
```javascript
// In browser console:
window.realtimeSystem.isConnected  // Should return true
```

### 3. Manual Test
```javascript
// Trigger a test callback
window.realtimeSystem.registerCallback('onUpdate', (data) => {
    console.log('TEST: Update received', data);
});
```

---

## 📋 Integration Checklist

For each page you integrate:

- [ ] Add `DOMContentLoaded` listener
- [ ] Check if `window.realtimeSystem` exists
- [ ] Register appropriate callbacks
- [ ] Test callback functions
- [ ] Add loading/error states
- [ ] Add console log for confirmation
- [ ] Test with actual data updates
- [ ] Verify no memory leaks
- [ ] Add CSS animations (optional)
- [ ] Document integration in code comments

---

## ⚠️ Best Practices

### DO:
✅ Check if `window.realtimeSystem` exists before using  
✅ Use specific event types (don't listen to everything)  
✅ Add console logs during development  
✅ Clean up callbacks if page has dynamic content  
✅ Add visual feedback for updates  
✅ Handle errors gracefully  

### DON'T:
❌ Don't modify the RealtimeSystem class directly  
❌ Don't create multiple SSE connections  
❌ Don't update DOM on every heartbeat  
❌ Don't block UI during updates  
❌ Don't forget to test offline behavior  

---

## 🐛 Troubleshooting

### Issue: "RealtimeSystem not available"
- Check if `header.php` is included
- Verify `realtime-system.js` is loaded (check Network tab)
- Check browser console for JS errors

### Issue: "No updates received"
- Verify SSE endpoint is running (`ajax/realtime-updates.php`)
- Check browser console for connection errors
- Verify session is valid

### Issue: "Updates are slow"
- Check `realtime-updates.php` sleep interval (default: 2 seconds)
- Verify database queries are optimized
- Check server resources

---

## 📞 Support

For issues or questions:
1. Check the console logs
2. Review `/docs/REALTIME_SYSTEM_USAGE_AUDIT.md`
3. Examine `realtime-system.js` source code
4. Test with browser dev tools

---

**Last Updated:** October 13, 2025
