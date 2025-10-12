/**
 * Universal Real-Time Update System for Admin Pages
 * Uses Server-Sent Events (SSE) to push updates instantly
 * Works on ALL admin pages (dashboard, disasters, reports, etc.)
 */

class RealtimeSystem {
    constructor() {
        this.eventSource = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 3000;
        this.isConnected = false;
        this.lastStats = null;
        this.callbacks = {
            onUpdate: [],
            onNewReport: [],
            onStatusChange: [],
            onConnect: [],
            onDisconnect: []
        };
        
        // Initialize on page load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }
    
    /**
     * Initialize the real-time system
     */
    init() {
        console.log('🚀 Initializing Real-Time System...');
        this.connectSSE();
        this.setupVisibilityHandling();
        this.updateNotificationBadge();
        
        // Auto-update notification badge every 30 seconds
        setInterval(() => this.updateNotificationBadge(), 30000);
    }
    
    /**
     * Connect to SSE endpoint
     */
    connectSSE() {
        if (this.eventSource) {
            this.eventSource.close();
        }
        
        console.log('📡 Connecting to real-time updates...');
        this.showConnectionStatus('connecting');
        
        this.eventSource = new EventSource('ajax/realtime-updates.php');
        
        // Connection established
        this.eventSource.addEventListener('connected', (e) => {
            const data = JSON.parse(e.data);
            console.log('✅ Real-time updates connected:', data);
            this.isConnected = true;
            this.reconnectAttempts = 0;
            this.showConnectionStatus('connected');
            this.triggerCallbacks('onConnect', data);
        });
        
        // Data update received
        this.eventSource.addEventListener('update', (e) => {
            const data = JSON.parse(e.data);
            console.log('📊 Update received:', data);
            
            this.handleUpdate(data);
            this.triggerCallbacks('onUpdate', data);
            
            // Check for new reports
            if (data.changes && data.changes.new_reports && data.changes.new_reports > 0) {
                this.handleNewReport(data.changes.new_reports, data.stats);
                this.triggerCallbacks('onNewReport', {
                    count: data.changes.new_reports,
                    stats: data.stats
                });
            }
        });
        
        // Heartbeat
        this.eventSource.addEventListener('heartbeat', (e) => {
            const data = JSON.parse(e.data);
            // Silent heartbeat - only log if debugging
            // console.log('💓 Heartbeat:', new Date(data.timestamp * 1000).toLocaleTimeString());
        });
        
        // Server requests reconnect
        this.eventSource.addEventListener('reconnect', (e) => {
            console.log('🔄 Server requested reconnect');
            this.eventSource.close();
            setTimeout(() => this.connectSSE(), 1000);
        });
        
        // Connection error
        this.eventSource.onerror = (error) => {
            console.error('❌ SSE connection error:', error);
            this.isConnected = false;
            this.showConnectionStatus('error');
            this.eventSource.close();
            
            // Attempt to reconnect
            if (this.reconnectAttempts < this.maxReconnectAttempts) {
                this.reconnectAttempts++;
                console.log(`🔄 Reconnecting in ${this.reconnectDelay/1000}s (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
                setTimeout(() => this.connectSSE(), this.reconnectDelay);
            } else {
                console.error('🚫 Max reconnection attempts reached');
                this.showConnectionStatus('failed');
            }
        };
    }
    
    /**
     * Handle data update
     */
    handleUpdate(data) {
        this.lastStats = data.stats;
        
        // Update notification badge if it changed
        this.updateNotificationBadge();
        
        // Update any stat displays on the page
        this.updateStatDisplays(data.stats);
        
        // Trigger page-specific updates
        if (typeof window.onRealtimeUpdate === 'function') {
            window.onRealtimeUpdate(data);
        }
    }
    
    /**
     * Handle new report notification
     */
    handleNewReport(count, stats) {
        // Show toast notification
        this.showToast(`${count} new report${count > 1 ? 's' : ''} received!`, 'success', true);
        
        // Play notification sound
        this.playNotificationSound();
        
        // Update browser notification if permitted
        this.showBrowserNotification(
            'New Disaster Report',
            `${count} new disaster report${count > 1 ? 's have' : ' has'} been submitted.`
        );
        
        // Trigger page-specific handler
        if (typeof window.onNewReport === 'function') {
            window.onNewReport(count, stats);
        }
    }
    
    /**
     * Update stat displays across the page
     */
    updateStatDisplays(stats) {
        // Update notification badge in header
        const notifBadge = document.querySelector('.notification-badge');
        const sidebarBadge = document.querySelector('.sidebar-menu .badge');
        
        // Note: notification count comes from separate query, not from stats
        // This is for disaster stats only
        
        // Update any dashboard stat cards if present
        if (stats.total_disasters !== undefined) {
            this.updateElement('total-disasters', stats.total_disasters);
        }
        if (stats.active_disasters !== undefined) {
            this.updateElement('active-disasters', stats.active_disasters);
        }
        if (stats.critical_disasters !== undefined) {
            this.updateElement('critical-disasters', stats.critical_disasters);
        }
        if (stats.completion_rate !== undefined) {
            this.updateElement('pending-disasters', stats.completion_rate);
        }
    }
    
    /**
     * Update element with smooth animation
     */
    updateElement(id, newValue) {
        const element = document.getElementById(id);
        if (!element) return;
        
        const oldValue = parseInt(element.textContent.replace(/,/g, '')) || 0;
        
        if (oldValue !== newValue) {
            // Flash animation
            const parent = element.parentElement?.parentElement;
            if (parent) {
                parent.style.animation = 'flash 0.5s ease';
                setTimeout(() => parent.style.animation = '', 500);
            }
            
            // Animate number
            this.animateValue(element, oldValue, newValue, 500);
        }
    }
    
    /**
     * Animate number counting
     */
    animateValue(element, start, end, duration) {
        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                current = end;
                clearInterval(timer);
            }
            element.textContent = Math.round(current).toLocaleString();
        }, 16);
    }
    
    /**
     * Update notification badge
     */
    async updateNotificationBadge() {
        try {
            const response = await fetch('ajax/get-notification-count.php');
            const data = await response.json();
            
            if (data.success) {
                const count = data.count || 0;
                
                // Update header badge
                const headerBadge = document.querySelector('.notifications-dropdown .notification-badge');
                if (headerBadge) {
                    if (count > 0) {
                        headerBadge.textContent = count;
                        headerBadge.style.display = 'flex';
                    } else {
                        headerBadge.style.display = 'none';
                    }
                }
                
                // Update sidebar badge
                const sidebarBadge = document.querySelector('.sidebar-menu .badge');
                if (sidebarBadge) {
                    if (count > 0) {
                        sidebarBadge.textContent = count;
                        sidebarBadge.style.display = 'inline-block';
                    } else {
                        sidebarBadge.style.display = 'none';
                    }
                }
            }
        } catch (error) {
            console.error('Error updating notification badge:', error);
        }
    }
    
    /**
     * Show connection status indicator
     */
    showConnectionStatus(status) {
        const statusConfig = {
            connecting: {
                color: '#f59e0b',
                icon: 'spinner fa-spin',
                text: 'Connecting...'
            },
            connected: {
                color: '#10b981',
                icon: 'circle',
                text: 'Real-time updates active'
            },
            error: {
                color: '#ef4444',
                icon: 'exclamation-circle',
                text: 'Reconnecting...'
            },
            failed: {
                color: '#ef4444',
                icon: 'times-circle',
                text: 'Updates unavailable'
            }
        };
        
        const config = statusConfig[status];
        
        // Update or create status indicator in header
        let indicator = document.getElementById('realtime-status-global');
        if (!indicator) {
            const headerRight = document.querySelector('.header-right .header-actions');
            if (headerRight) {
                indicator = document.createElement('div');
                indicator.id = 'realtime-status-global';
                indicator.style.cssText = `
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    font-size: 0.75rem;
                    padding: 4px 10px;
                    border-radius: 12px;
                    background: rgba(0,0,0,0.05);
                    margin-right: 10px;
                `;
                headerRight.insertBefore(indicator, headerRight.firstChild);
            }
        }
        
        if (indicator) {
            indicator.style.color = config.color;
            indicator.innerHTML = `
                <i class="fas fa-${config.icon}" style="font-size: 0.5rem;"></i>
                <span>${config.text}</span>
            `;
        }
        
        this.triggerCallbacks('onStatusChange', status);
    }
    
    /**
     * Show toast notification
     */
    showToast(message, type = 'info', withSound = false) {
        const toast = document.createElement('div');
        toast.className = `realtime-toast toast-${type}`;
        
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            info: 'info-circle',
            warning: 'exclamation-triangle'
        };
        
        const colors = {
            success: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
            error: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
            info: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
            warning: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)'
        };
        
        toast.innerHTML = `
            <i class="fas fa-${icons[type]}"></i>
            <span>${message}</span>
        `;
        
        toast.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            background: ${colors[type]};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            animation: slideInRight 0.3s ease-out;
            font-size: 14px;
            cursor: pointer;
        `;
        
        // Close on click
        toast.onclick = () => {
            toast.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        };
        
        document.body.appendChild(toast);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => toast.remove(), 300);
            }
        }, 5000);
        
        if (withSound) {
            this.playNotificationSound();
        }
    }
    
    /**
     * Play notification sound
     */
    playNotificationSound() {
        try {
            // Simple beep sound
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIGWi77OacSwwMUKfl77RgGgU7k9jyx3oqBSh+zPHXi0AIEH');
            audio.volume = 0.3;
            audio.play().catch(e => {
                // Silent fail if audio doesn't play
            });
        } catch (e) {
            // Silent fail
        }
    }
    
    /**
     * Show browser notification
     */
    async showBrowserNotification(title, body) {
        if (!('Notification' in window)) {
            return;
        }
        
        if (Notification.permission === 'granted') {
            new Notification(title, {
                body: body,
                icon: '../assets/images/icon2.png',
                badge: '../assets/images/icon2.png',
                tag: 'disaster-report',
                requireInteraction: false
            });
        } else if (Notification.permission !== 'denied') {
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                new Notification(title, {
                    body: body,
                    icon: '../assets/images/icon2.png'
                });
            }
        }
    }
    
    /**
     * Setup visibility handling
     */
    setupVisibilityHandling() {
        let hiddenTimeout = null;
        
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Close connection after 30 seconds of being hidden
                hiddenTimeout = setTimeout(() => {
                    if (this.eventSource) {
                        console.log('📴 Closing SSE (page hidden)');
                        this.eventSource.close();
                        this.isConnected = false;
                    }
                }, 30000);
            } else {
                // Clear timeout and reconnect if needed
                clearTimeout(hiddenTimeout);
                if (!this.eventSource || this.eventSource.readyState === EventSource.CLOSED) {
                    console.log('📱 Reconnecting SSE (page visible)');
                    this.connectSSE();
                }
            }
        });
        
        // Close connection when leaving page
        window.addEventListener('beforeunload', () => {
            if (this.eventSource) {
                this.eventSource.close();
            }
        });
    }
    
    /**
     * Register callback for events
     */
    on(event, callback) {
        if (this.callbacks[event]) {
            this.callbacks[event].push(callback);
        }
    }
    
    /**
     * Trigger callbacks
     */
    triggerCallbacks(event, data) {
        if (this.callbacks[event]) {
            this.callbacks[event].forEach(callback => {
                try {
                    callback(data);
                } catch (e) {
                    console.error(`Error in ${event} callback:`, e);
                }
            });
        }
    }
    
    /**
     * Disconnect SSE
     */
    disconnect() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
            this.isConnected = false;
            console.log('📴 Real-time updates disconnected');
        }
    }
    
    /**
     * Get connection status
     */
    getStatus() {
        return {
            connected: this.isConnected,
            lastStats: this.lastStats,
            reconnectAttempts: this.reconnectAttempts
        };
    }
}

// Create global instance
window.RealtimeSystem = new RealtimeSystem();

// Add slideInRight/slideOutRight animations if not present
if (!document.querySelector('#realtime-animations')) {
    const style = document.createElement('style');
    style.id = 'realtime-animations';
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        @keyframes flash {
            0%, 100% { background-color: inherit; }
            50% { background-color: rgba(16, 185, 129, 0.1); }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    `;
    document.head.appendChild(style);
}

console.log('✅ Real-Time System loaded');
