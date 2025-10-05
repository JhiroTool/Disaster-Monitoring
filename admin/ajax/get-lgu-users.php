<?php
session_start();
require_once '../../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_GET['lgu_id']) || !hasRole(['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$lgu_id = intval($_GET['lgu_id']);

try {
    $stmt = $pdo->prepare("
        SELECT user_id, CONCAT(first_name, ' ', last_name) as full_name 
        FROM users 
        WHERE lgu_id = ? AND role = 'reporter' AND is_active = TRUE 
        ORDER BY full_name
    ");
    $stmt->execute([$lgu_id]);
    $users = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
    
} catch (Exception $e) {
    error_log("Get LGU users error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching users'
    ]);
}
?>