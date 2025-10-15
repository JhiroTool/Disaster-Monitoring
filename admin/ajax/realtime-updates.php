<?php
/**
 * OPTIMIZED Server-Sent Events (SSE) Endpoint for Real-Time Dashboard Updates
 * 
 * Performance optimizations:
 * - Single optimized query instead of multiple queries
 * - Lightweight checks (only COUNT, not full data)
 * - Longer check intervals for better performance
 * - Quick connection setup
 */

// Minimal session check - don't start full session yet
session_start(['read_and_close' => true]); // Read session and immediately close

// Quick auth check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'lgu_staff'])) {
    http_response_code(403);
    exit('Unauthorized');
}

// Store user info before closing session
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

require_once '../../config/database.php';

// Set headers for SSE - MUST be before any output
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

// Disable all output buffering for immediate response
while (ob_get_level()) ob_end_clean();

// Function to send SSE message - optimized
function sendSSE($event, $data) {
    echo "event: {$event}\n";
    echo "data: " . json_encode($data) . "\n\n";
    flush();
}

// OPTIMIZED: Single query to get all stats at once
function getCurrentStats($pdo) {
    try {
        // One efficient query instead of multiple separate queries
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_disasters,
                COUNT(CASE WHEN status != 'COMPLETED' THEN 1 END) as active_disasters,
                COUNT(CASE WHEN priority = 'critical' AND status != 'COMPLETED' THEN 1 END) as critical_disasters,
                COUNT(CASE WHEN status = 'COMPLETED' THEN 1 END) as completed_disasters
            FROM disasters
        ");
        
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate completion rate from already fetched data
        $total = (int)$stats['total_disasters'];
        $completed = (int)$stats['completed_disasters'];
        $completion_rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
        
        // Only fetch recent reports if needed (skip for lightweight checks)
        $recent_reports = [];
        
        // Get user status counts
        $userStatsStmt = $pdo->query("
            SELECT 
                COUNT(*) as total_users,
                COUNT(CASE WHEN status = 'Need help' THEN 1 END) as users_need_help,
                COUNT(CASE WHEN status = \"I'm fine\" THEN 1 END) as users_safe
            FROM users 
            WHERE role = 'reporter'
        ");
        $userStats = $userStatsStmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_disasters' => $total,
            'active_disasters' => (int)$stats['active_disasters'],
            'critical_disasters' => (int)$stats['critical_disasters'],
            'completion_rate' => (float)$completion_rate,
            'recent_reports' => $recent_reports,
            'total_users' => (int)($userStats['total_users'] ?? 0),
            'users_need_help' => (int)($userStats['users_need_help'] ?? 0),
            'users_safe' => (int)($userStats['users_safe'] ?? 0),
            'timestamp' => time()
        ];
    } catch (Exception $e) {
        error_log("SSE Stats Error: " . $e->getMessage());
        return null;
    }
}

// OPTIMIZED: Store only critical data for comparison
$lastTotal = 0;
$lastActive = 0;
$lastCritical = 0;
$lastUsersNeedHelp = 0;
$lastUsersSafe = 0;
$lastCheck = 0;
$checkInterval = 2; // FAST: Check every 2 seconds for quick admin response!

// Send initial connection message IMMEDIATELY
sendSSE('connected', ['message' => 'Real-time updates connected', 'timestamp' => time()]);

// Send initial stats immediately on connection
$initialStats = getCurrentStats($pdo);
if ($initialStats) {
    sendSSE('update', [
        'stats' => $initialStats,
        'changes' => ['type' => 'initial'],
        'timestamp' => time()
    ]);
    // Store initial values
    $lastTotal = $initialStats['total_disasters'];
    $lastActive = $initialStats['active_disasters'];
    $lastCritical = $initialStats['critical_disasters'];
    $lastUsersNeedHelp = $initialStats['users_need_help'];
    $lastUsersSafe = $initialStats['users_safe'];
}

// OPTIMIZED: Shorter max runtime for faster reconnections
$startTime = time();
$maxRunTime = 180; // 3 minutes max, then reconnect (was 5 minutes)

// OPTIMIZED: Main loop with better performance
while (true) {
    // Quick connection check
    if (connection_aborted()) {
        break;
    }
    
    // Check if max runtime exceeded
    if (time() - $startTime > $maxRunTime) {
        sendSSE('reconnect', ['message' => 'Reconnecting for fresh session']);
        break;
    }
    
    $currentTime = time();
    
    // OPTIMIZED: Only check database at intervals
    if ($currentTime - $lastCheck >= $checkInterval) {
        $currentStats = getCurrentStats($pdo);
        
        if ($currentStats) {
            $hasChanges = false;
            $changes = [];
            
            // OPTIMIZED: Simple comparison using stored values
            if ($lastTotal === 0) {
                // First load - send initial data
                $hasChanges = true;
                $changes['type'] = 'initial';
            } else {
                // OPTIMIZED: Only check critical changes
                if ($currentStats['total_disasters'] !== $lastTotal) {
                    $hasChanges = true;
                    $changes['new_reports'] = $currentStats['total_disasters'] - $lastTotal;
                }
                
                if ($currentStats['active_disasters'] !== $lastActive) {
                    $hasChanges = true;
                    $changes['active_changed'] = true;
                }
                
                if ($currentStats['critical_disasters'] !== $lastCritical) {
                    $hasChanges = true;
                    $changes['critical_changed'] = true;
                }
                
                // Check for user status changes
                if ($currentStats['users_need_help'] !== $lastUsersNeedHelp) {
                    $hasChanges = true;
                    $changes['user_status_changed'] = true;
                    $changes['users_need_help_delta'] = $currentStats['users_need_help'] - $lastUsersNeedHelp;
                }
                
                if ($currentStats['users_safe'] !== $lastUsersSafe) {
                    $hasChanges = true;
                    $changes['user_status_changed'] = true;
                }
            }
            
            // OPTIMIZED: Only send update if something actually changed
            if ($hasChanges) {
                sendSSE('update', [
                    'stats' => $currentStats,
                    'changes' => $changes,
                    'timestamp' => $currentTime
                ]);
                
                // Update stored values
                $lastTotal = $currentStats['total_disasters'];
                $lastActive = $currentStats['active_disasters'];
                $lastCritical = $currentStats['critical_disasters'];
                $lastUsersNeedHelp = $currentStats['users_need_help'];
                $lastUsersSafe = $currentStats['users_safe'];
            }
        }
        
        $lastCheck = $currentTime;
    }
    
    // OPTIMIZED: Send heartbeat every 30 seconds (was 15)
    if ($currentTime % 30 === 0 && ($currentTime - $lastCheck) > 1) {
        sendSSE('heartbeat', ['timestamp' => $currentTime]);
    }
    
    // OPTIMIZED: Sleep for 2 seconds (was 1) - reduces CPU usage
    sleep(2);
}

// Connection closed
sendSSE('disconnect', ['message' => 'Connection closed']);
