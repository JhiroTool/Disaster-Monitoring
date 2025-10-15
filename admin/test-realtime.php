<?php
session_start();
require_once '../config/database.php';
require_once 'includes/auth.php';

$page_title = 'Real-Time System Test';
include 'includes/header.php';
?>

<style>
.test-container {
    max-width: 1200px;
    margin: 0 auto;
}

.test-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.test-section h3 {
    margin-top: 0;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
}

.status-success {
    background: #dcfce7;
    color: #166534;
}

.status-error {
    background: #fee2e2;
    color: #991b1b;
}

.status-warning {
    background: #fef3c7;
    color: #92400e;
}

.test-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.test-card {
    background: #f8fafc;
    padding: 15px;
    border-radius: 8px;
    border: 2px solid #e2e8f0;
}

.test-card h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.test-value {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
}

.log-container {
    background: #1e293b;
    color: #e2e8f0;
    padding: 15px;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    max-height: 400px;
    overflow-y: auto;
    margin-top: 15px;
}

.log-entry {
    padding: 4px 0;
    border-bottom: 1px solid #334155;
}

.log-entry:last-child {
    border-bottom: none;
}

.log-time {
    color: #94a3b8;
    margin-right: 10px;
}

.log-success { color: #4ade80; }
.log-error { color: #f87171; }
.log-warning { color: #fbbf24; }
.log-info { color: #60a5fa; }

.btn-test {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s;
}

.btn-test:hover {
    transform: translateY(-2px);
}

.btn-test:active {
    transform: translateY(0);
}
</style>

<div class="test-container">
    <div class="page-header">
        <h2><i class="fas fa-vial"></i> Real-Time System Test Dashboard</h2>
        <p>Monitor and test the real-time update system across all admin pages</p>
    </div>

    <!-- Connection Status -->
    <div class="test-section">
        <h3><i class="fas fa-plug"></i> Connection Status</h3>
        <div id="connection-status">
            <span class="status-indicator status-warning">
                <i class="fas fa-spinner fa-spin"></i>
                Initializing...
            </span>
        </div>
        <div class="test-grid" style="margin-top: 20px;">
            <div class="test-card">
                <h4>Connection State</h4>
                <div class="test-value" id="conn-state">—</div>
            </div>
            <div class="test-card">
                <h4>Reconnect Attempts</h4>
                <div class="test-value" id="reconnect-attempts">0</div>
            </div>
            <div class="test-card">
                <h4>Last Update</h4>
                <div class="test-value" id="last-update" style="font-size: 16px;">Never</div>
            </div>
            <div class="test-card">
                <h4>Uptime</h4>
                <div class="test-value" id="uptime">0s</div>
            </div>
        </div>
    </div>

    <!-- Live Statistics -->
    <div class="test-section">
        <h3><i class="fas fa-chart-line"></i> Live Statistics</h3>
        <div class="test-grid">
            <div class="test-card">
                <h4>Total Disasters</h4>
                <div class="test-value" id="stat-total">—</div>
            </div>
            <div class="test-card">
                <h4>Active Disasters</h4>
                <div class="test-value" id="stat-active">—</div>
            </div>
            <div class="test-card">
                <h4>Critical Disasters</h4>
                <div class="test-value" id="stat-critical">—</div>
            </div>
            <div class="test-card">
                <h4>Completion Rate</h4>
                <div class="test-value" id="stat-completion">—</div>
            </div>
            <div class="test-card">
                <h4>Total Users</h4>
                <div class="test-value" id="stat-users">—</div>
            </div>
            <div class="test-card">
                <h4>Users Need Help</h4>
                <div class="test-value" id="stat-help">—</div>
            </div>
        </div>
    </div>

    <!-- Event Log -->
    <div class="test-section">
        <h3><i class="fas fa-terminal"></i> Event Log</h3>
        <button class="btn-test" onclick="clearLog()">
            <i class="fas fa-trash"></i> Clear Log
        </button>
        <button class="btn-test" onclick="testNotification()">
            <i class="fas fa-bell"></i> Test Notification
        </button>
        <div class="log-container" id="event-log">
            <div class="log-entry">Waiting for events...</div>
        </div>
    </div>
</div>

<script>
let startTime = Date.now();
let logEntries = [];

// Update uptime counter
setInterval(() => {
    const seconds = Math.floor((Date.now() - startTime) / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    
    let uptimeStr = '';
    if (hours > 0) uptimeStr += hours + 'h ';
    if (minutes % 60 > 0) uptimeStr += (minutes % 60) + 'm ';
    uptimeStr += (seconds % 60) + 's';
    
    document.getElementById('uptime').textContent = uptimeStr;
}, 1000);

function addLog(message, type = 'info') {
    const time = new Date().toLocaleTimeString();
    const entry = `<div class="log-entry"><span class="log-time">[${time}]</span><span class="log-${type}">${message}</span></div>`;
    logEntries.unshift(entry);
    if (logEntries.length > 50) logEntries.pop();
    document.getElementById('event-log').innerHTML = logEntries.join('');
}

function clearLog() {
    logEntries = [];
    document.getElementById('event-log').innerHTML = '<div class="log-entry">Log cleared</div>';
}

function updateConnectionStatus(status, connected) {
    const statusEl = document.getElementById('connection-status');
    const stateEl = document.getElementById('conn-state');
    
    if (connected) {
        statusEl.innerHTML = '<span class="status-indicator status-success"><i class="fas fa-check-circle"></i> Connected</span>';
        stateEl.textContent = 'Connected';
        stateEl.style.color = '#10b981';
    } else {
        statusEl.innerHTML = '<span class="status-indicator status-error"><i class="fas fa-times-circle"></i> Disconnected</span>';
        stateEl.textContent = 'Disconnected';
        stateEl.style.color = '#ef4444';
    }
}

function testNotification() {
    if (window.RealtimeSystem) {
        window.RealtimeSystem.showToast('Test notification!', 'info', true);
        addLog('Test notification triggered', 'success');
    }
}

// Wait for RealtimeSystem to load
setTimeout(() => {
    if (window.RealtimeSystem) {
        addLog('✅ RealtimeSystem detected and loaded', 'success');
        
        // Register callbacks
        window.RealtimeSystem.registerCallback('onConnect', (data) => {
            addLog('🔌 Connected to real-time server', 'success');
            updateConnectionStatus('connected', true);
            document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
        });
        
        window.RealtimeSystem.registerCallback('onDisconnect', (data) => {
            addLog('🔌 Disconnected from server', 'error');
            updateConnectionStatus('disconnected', false);
        });
        
        window.RealtimeSystem.registerCallback('onUpdate', (data) => {
            addLog('📊 Data update received', 'info');
            document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
            
            if (data.stats) {
                document.getElementById('stat-total').textContent = data.stats.total_disasters || '—';
                document.getElementById('stat-active').textContent = data.stats.active_disasters || '—';
                document.getElementById('stat-critical').textContent = data.stats.critical_disasters || '—';
                document.getElementById('stat-completion').textContent = (data.stats.completion_rate || 0) + '%';
                document.getElementById('stat-users').textContent = data.stats.total_users || '—';
                document.getElementById('stat-help').textContent = data.stats.users_need_help || '—';
            }
            
            if (data.changes) {
                if (data.changes.new_reports) {
                    addLog(`🚨 ${data.changes.new_reports} new report(s) detected!`, 'warning');
                }
                if (data.changes.user_status_changed) {
                    addLog('👤 User status changed', 'info');
                }
            }
        });
        
        window.RealtimeSystem.registerCallback('onNewReport', (data) => {
            addLog(`🆕 New disaster report: ${data.count} report(s)`, 'warning');
        });
        
        window.RealtimeSystem.registerCallback('onUserStatusChange', (data) => {
            addLog('👤 User status update detected', 'info');
        });
        
        window.RealtimeSystem.registerCallback('onStatusChange', (status) => {
            addLog(`🔄 Connection status changed: ${status}`, 'info');
            const reconnectEl = document.getElementById('reconnect-attempts');
            const statusInfo = window.RealtimeSystem.getStatus();
            reconnectEl.textContent = statusInfo.reconnectAttempts || 0;
        });
        
        // Check initial status
        const status = window.RealtimeSystem.getStatus();
        updateConnectionStatus(status.connected ? 'connected' : 'disconnected', status.connected);
        addLog(`Initial connection state: ${status.connected ? 'Connected' : 'Disconnected'}`, status.connected ? 'success' : 'warning');
        
    } else {
        addLog('❌ RealtimeSystem NOT found!', 'error');
        updateConnectionStatus('error', false);
    }
}, 1000);
</script>

<?php include 'includes/footer.php'; ?>
