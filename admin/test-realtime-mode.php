<?php
session_start();
require_once '../config/database.php';
require_once 'includes/auth.php';

$page_title = 'Real-Time Mode Test';
include 'includes/header.php';
?>

<style>
.test-container {
    max-width: 800px;
    margin: 40px auto;
    padding: 30px;
}

.status-card {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.status-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 8px;
    animation: pulse 2s infinite;
}

.status-indicator.active {
    background: #10b981;
}

.status-indicator.inactive {
    background: #ef4444;
}

.mode-badge {
    display: inline-block;
    padding: 6px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 14px;
    margin-left: 10px;
}

.mode-sse {
    background: #dbeafe;
    color: #1e40af;
}

.mode-polling {
    background: #fef3c7;
    color: #92400e;
}

.log-container {
    background: #1e293b;
    color: #e2e8f0;
    padding: 20px;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    max-height: 400px;
    overflow-y: auto;
}

.log-entry {
    margin: 5px 0;
    padding: 5px;
}

.log-success { color: #10b981; }
.log-error { color: #ef4444; }
.log-warning { color: #f59e0b; }
.log-info { color: #3b82f6; }

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>

<div class="test-container">
    <h1>🔧 Real-Time System Test</h1>
    <p>This page tests which real-time mode is working on your server.</p>
    
    <div class="status-card">
        <h2>
            <span class="status-indicator" id="status-indicator"></span>
            Connection Status: <span id="connection-status">Initializing...</span>
            <span class="mode-badge" id="mode-badge">Unknown</span>
        </h2>
        
        <div style="margin-top: 20px;">
            <p><strong>Mode:</strong> <span id="current-mode">Detecting...</span></p>
            <p><strong>Last Update:</strong> <span id="last-update">Never</span></p>
            <p><strong>Updates Received:</strong> <span id="update-count">0</span></p>
        </div>
        
        <div style="margin-top: 20px;">
            <h3>Current Stats:</h3>
            <ul>
                <li>Total Disasters: <strong id="stat-total">-</strong></li>
                <li>Active Disasters: <strong id="stat-active">-</strong></li>
                <li>Critical Disasters: <strong id="stat-critical">-</strong></li>
            </ul>
        </div>
    </div>
    
    <div class="status-card">
        <h2>📋 Activity Log</h2>
        <div class="log-container" id="log-container">
            <div class="log-entry log-info">Waiting for real-time system to initialize...</div>
        </div>
    </div>
</div>

<script>
let updateCount = 0;
const logContainer = document.getElementById('log-container');

function addLog(message, type = 'info') {
    const entry = document.createElement('div');
    entry.className = `log-entry log-${type}`;
    entry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
    logContainer.insertBefore(entry, logContainer.firstChild);
    
    // Keep only last 50 entries
    while (logContainer.children.length > 50) {
        logContainer.removeChild(logContainer.lastChild);
    }
}

function updateStatus(status, mode) {
    const indicator = document.getElementById('status-indicator');
    const statusText = document.getElementById('connection-status');
    const modeBadge = document.getElementById('mode-badge');
    const currentMode = document.getElementById('current-mode');
    
    if (status === 'connected') {
        indicator.className = 'status-indicator active';
        statusText.textContent = 'Connected';
        statusText.style.color = '#10b981';
    } else {
        indicator.className = 'status-indicator inactive';
        statusText.textContent = 'Disconnected';
        statusText.style.color = '#ef4444';
    }
    
    if (mode === 'sse') {
        modeBadge.className = 'mode-badge mode-sse';
        modeBadge.textContent = 'SSE Mode';
        currentMode.textContent = 'Server-Sent Events (SSE)';
    } else if (mode === 'polling') {
        modeBadge.className = 'mode-badge mode-polling';
        modeBadge.textContent = 'Polling Mode';
        currentMode.textContent = 'HTTP Polling (Fallback)';
    }
}

// Wait for RealtimeSystem to be available
setTimeout(() => {
    if (window.RealtimeSystem) {
        addLog('✅ Real-Time System detected', 'success');
        
        // Check mode every second
        const modeChecker = setInterval(() => {
            const mode = window.RealtimeSystem.getMode();
            if (mode) {
                updateStatus('connected', mode);
                addLog(`📡 Running in ${mode.toUpperCase()} mode`, 'success');
                clearInterval(modeChecker);
            }
        }, 1000);
        
        // Register for updates
        window.RealtimeSystem.registerCallback('onUpdate', (data) => {
            updateCount++;
            document.getElementById('update-count').textContent = updateCount;
            document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
            
            if (data.stats) {
                document.getElementById('stat-total').textContent = data.stats.total_disasters || 0;
                document.getElementById('stat-active').textContent = data.stats.active_disasters || 0;
                document.getElementById('stat-critical').textContent = data.stats.critical_disasters || 0;
                
                addLog(`📊 Stats updated: ${data.stats.total_disasters} total, ${data.stats.active_disasters} active`, 'info');
            }
        });
        
        window.RealtimeSystem.registerCallback('onNewReport', (data) => {
            addLog(`🚨 New report detected! Count: ${data.count}`, 'warning');
        });
        
        window.RealtimeSystem.registerCallback('onConnect', (data) => {
            addLog('🔗 Connected to real-time updates', 'success');
        });
        
    } else {
        addLog('❌ Real-Time System not available', 'error');
        updateStatus('disconnected', null);
    }
}, 2000);
</script>

<?php include 'includes/footer.php'; ?>
