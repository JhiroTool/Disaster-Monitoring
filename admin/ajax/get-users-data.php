<?php
/**
 * Get Users Data for Real-time Updates
 * Returns current user status information
 */

session_start();
require_once '../../config/database.php';
require_once '../includes/auth.php';

// Only admins can access this
if (!hasRole(['admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    // Fetch all reporters with their current status
    $stmt = $pdo->query("
        SELECT 
            u.user_id,
            u.first_name,
            u.last_name,
            u.status,
            u.updated_at as status_updated_at
        FROM users u
        WHERE u.role = 'reporter'
        ORDER BY u.user_id
    ");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary counts
    $total_reporters = count($users);
    $reporters_need_help = count(array_filter($users, fn($u) => ($u['status'] ?? '') === 'Need help'));
    $reporters_safe = count(array_filter($users, fn($u) => ($u['status'] ?? '') === "I'm fine"));
    
    echo json_encode([
        'success' => true,
        'users' => $users,
        'counts' => [
            'total' => $total_reporters,
            'need_help' => $reporters_need_help,
            'safe' => $reporters_safe
        ],
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    error_log("Get users data error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch user data'
    ]);
}
