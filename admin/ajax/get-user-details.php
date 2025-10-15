<?php
session_start();
require_once '../../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Only admins can access
if (!hasRole(['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

try {
    // Fetch user details with address information
    $stmt = $pdo->prepare("
        SELECT 
            u.user_id,
            u.username_reporters,
            u.email,
            u.first_name,
            u.last_name,
            u.phone,
            u.role,
            u.status,
            u.is_active,
            u.email_verified,
            u.created_at,
            u.updated_at,
            u.last_login,
            ua.address_id,
            ua.house_no,
            ua.purok,
            ua.barangay,
            ua.city,
            ua.province,
            ua.region,
            ua.postal_code,
            ua.landmark,
            ua.is_primary,
            COUNT(d.disaster_id) as total_reports
        FROM users u
        LEFT JOIN user_addresses ua ON u.user_id = ua.user_id AND ua.is_primary = 1
        LEFT JOIN disasters d ON u.user_id = d.reported_by_user_id
        WHERE u.user_id = ?
        GROUP BY u.user_id
    ");
    
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Separate address data
    $address = null;
    if ($user['address_id']) {
        $address = [
            'address_id' => $user['address_id'],
            'house_no' => $user['house_no'],
            'purok' => $user['purok'],
            'barangay' => $user['barangay'],
            'city' => $user['city'],
            'province' => $user['province'],
            'region' => $user['region'],
            'postal_code' => $user['postal_code'],
            'landmark' => $user['landmark'],
            'is_primary' => $user['is_primary']
        ];
    }
    
    // Remove address fields from user array
    unset($user['address_id'], $user['house_no'], $user['purok'], $user['barangay'], 
          $user['city'], $user['province'], $user['region'], $user['postal_code'], 
          $user['landmark'], $user['is_primary']);
    
    // Add address to user object
    $user['address'] = $address;
    
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
    
} catch (Exception $e) {
    error_log("Get user details error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching user details'
    ]);
}
