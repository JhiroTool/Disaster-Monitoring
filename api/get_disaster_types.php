<?php
/**
 * API Endpoint: Get active disaster types
 * Method: GET
 * Response: JSON { success: bool, data: array, message?: string }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->prepare("SELECT type_id, type_name, description FROM disaster_types WHERE is_active = 1 ORDER BY type_name ASC");
    $stmt->execute();
    $types = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $types
    ]);
} catch (Throwable $e) {
    error_log('get_disaster_types.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load disaster types at this time.'
    ]);
}
