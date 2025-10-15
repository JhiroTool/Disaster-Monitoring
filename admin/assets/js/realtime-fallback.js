/**
 * Real-Time System with Automatic Fallback
 * Tries SSE first, falls back to polling if SSE fails (for Hostinger compatibility)
 */

(function() {
    'use strict';
    
    const RealtimeFallback = {
        mode: null, // 'sse' or 'polling'
        eventSource: null,
        pollingInterval: null,
        callbacks: {},
        lastCheckTime: null,
        retryCount: 0,
        maxRetries: 3,
        
        init() {
            console.log('🚀 Initializing Real-Time System with Fallback...');
            this.lastCheckTime = new Date().toISOString();
            this.trySSE();
        },
        
        trySSE() {
            console.log('📡 Attempting SSE connection...');
            
            try {
                this.eventSource = new EventSource('ajax/realtime-updates.php');
                
                // Set timeout to detect if SSE is not working
                const sseTimeout = setTimeout(() => {
                    if (this.mode !== 'sse') {
                        console.warn('⚠️ SSE timeout - switching to polling');
                        this.switchToPolling();
                    }
                }, 5000); // 5 second timeout
                
                this.eventSource.addEventListener('connected', (e) => {
                    clearTimeout(sseTimeout);
                    this.mode = 'sse';
                    this.retryCount = 0;
                    console.log('✅ SSE connected successfully');
                    const data = JSON.parse(e.data);
                    this.trigger('onConnect', data);
                });
                
                this.eventSource.addEventListener('update', (e) => {
                    const data = JSON.parse(e.data);
                    this.handleUpdate(data);
                });
                
                this.eventSource.addEventListener('new_report', (e) => {
                    const data = JSON.parse(e.data);
                    this.trigger('onNewReport', data);
                });
                
                this.eventSource.onerror = (error) => {
                    console.error('❌ SSE error:', error);
                    this.retryCount++;
                    
                    if (this.retryCount >= this.maxRetries) {
                        console.log('🔄 Max SSE retries reached - switching to polling');
                        this.switchToPolling();
                    }
                };
                
            } catch (error) {
                console.error('❌ SSE initialization failed:', error);
                this.switchToPolling();
            }
        },
        
        switchToPolling() {
            console.log('🔄 Switching to polling mode...');
            
            // Close SSE if active
            if (this.eventSource) {
                this.eventSource.close();
                this.eventSource = null;
            }
            
            this.mode = 'polling';
            this.startPolling();
        },
        
        startPolling() {
            console.log('✅ Polling mode activated (every 5 seconds)');
            
            // Initial poll
            this.poll();
            
            // Set up interval
            this.pollingInterval = setInterval(() => {
                this.poll();
            }, 5000); // Poll every 5 seconds
        },
        
        async poll() {
            try {
                const response = await fetch(`ajax/poll-updates.php?last_check=${encodeURIComponent(this.lastCheckTime)}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    this.handleUpdate(data);
                    this.lastCheckTime = data.server_time;
                    
                    // Check for new reports
                    if (data.new_reports > 0) {
                        this.trigger('onNewReport', {
                            count: data.new_reports,
                            stats: data.stats
                        });
                    }
                }
                
            } catch (error) {
                console.error('❌ Polling error:', error);
            }
        },
        
        handleUpdate(data) {
            if (data.stats) {
                this.trigger('onUpdate', data);
                this.updateUI(data.stats);
            }
        },
        
        updateUI(stats) {
            // Update stat cards
            const statElements = {
                'total-disasters': stats.total_disasters,
                'active-disasters': stats.active_disasters,
                'critical-disasters': stats.critical_disasters,
                'users-need-help': stats.users_need_help,
                'users-safe': stats.users_safe
            };
            
            Object.keys(statElements).forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    const newValue = statElements[id];
                    const oldValue = parseInt(element.textContent) || 0;
                    
                    if (newValue !== oldValue) {
                        element.textContent = newValue;
                        element.classList.add('stat-updated');
                        setTimeout(() => element.classList.remove('stat-updated'), 1000);
                    }
                }
            });
            
            // Update notification badge
            if (stats.notification_count !== undefined) {
                const badges = document.querySelectorAll('.notification-badge');
                badges.forEach(badge => {
                    if (stats.notification_count > 0) {
                        badge.textContent = stats.notification_count;
                        badge.style.display = 'block';
                    } else {
                        badge.style.display = 'none';
                    }
                });
            }
        },
        
        registerCallback(event, callback) {
            if (!this.callbacks[event]) {
                this.callbacks[event] = [];
            }
            this.callbacks[event].push(callback);
        },
        
        trigger(event, data) {
            if (this.callbacks[event]) {
                this.callbacks[event].forEach(callback => {
                    try {
                        callback(data);
                    } catch (error) {
                        console.error(`Error in ${event} callback:`, error);
                    }
                });
            }
        },
        
        getMode() {
            return this.mode;
        },
        
        destroy() {
            if (this.eventSource) {
                this.eventSource.close();
            }
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval);
            }
        }
    };
    
    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => RealtimeFallback.init());
    } else {
        RealtimeFallback.init();
    }
    
    // Make it globally available
    window.RealtimeFallback = RealtimeFallback;
    
    // Also expose as RealtimeSystem for backward compatibility
    window.RealtimeSystem = RealtimeFallback;
    
    console.log('✅ Real-Time Fallback System loaded');
    
})();
