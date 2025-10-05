<?php
/**
 * API Endpoint: Submit an emergency report
 * Method: POST (JSON)
 * Request body example:
 * {
 *   "disaster_type": 20,
 *   "disaster_name": "Flooded Street",
 *   "severity_level": "red-2",
 *   "address": "Purok 3, Barangay Halang, Lipa City, Batangas",
 *   "city": "Lipa City",
 *   "state": "Philippines",
 *   "reporter_name": "Juan Dela Cruz",
 *   "reporter_phone": "09171234567",
 *   "alternate_contact": "09281234567",
 *   "landmark": "Near the elementary school",
 *   "people_affected": "5 families",
 *   "current_situation": "Roads are impassable due to waist-deep floods",
 *   "description": "Water keeps rising, seniors and children stranded.",
 *   "immediate_needs": ["rescue", "medical_assistance"],
 *   "latitude": 14.123456,
 *   "longitude": 121.123456
 * }
 *
 * Response: JSON { success: bool, tracking_id?: string, disaster_id?: int, message?: string }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON payload'
    ]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Helper: map severity level to display text
function mapSeverityDisplay(?string $severityLevel): string
{
    $map = [
        'green-1' => 'Favorable circumstances',
        'green-2' => 'Intact homes & accessible roads',
        'green-3' => 'Functional power & supplies',
        'green-4' => 'No flooding or major damage',
        'green-5' => 'Rebuilt infrastructure',
        'orange-1' => 'Moderate problems',
        'orange-2' => 'Minor structural damage',
        'orange-3' => 'Partially accessible roads',
        'orange-4' => 'Limited supplies & sporadic outages',
        'orange-5' => 'Minor floods & safety issues',
        'red-1' => 'Critical situations',
        'red-2' => 'Heavy devastation',
        'red-3' => 'Widespread power loss',
        'red-4' => 'Resource unavailability',
        'red-5' => 'Significant security problems'
    ];

    if (empty($severityLevel)) {
        return 'Unspecified impact level';
    }

    return $map[$severityLevel] ?? $severityLevel;
}

// Required fields
$requiredFields = ['disaster_type', 'severity_level', 'address', 'reporter_phone', 'description'];
$missing = [];

foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        $missing[] = $field;
    }
}

if (!empty($missing)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: ' . implode(', ', $missing)
    ]);
    exit;
}

try {
    $trackingId = generateTrackingId();

    $typeId = (int) $data['disaster_type'];
    $severityLevel = sanitizeInput($data['severity_level']);
    $address = sanitizeInput($data['address']);
    $city = isset($data['city']) ? sanitizeInput($data['city']) : null;
    $state = isset($data['state']) ? sanitizeInput($data['state']) : 'Philippines';
    $province = isset($data['province']) ? sanitizeInput($data['province']) : 'Philippines';
    if ($state === '') {
        $state = 'Philippines';
    }
    if ($province === '') {
        $province = 'Philippines';
    }
    $reporterName = isset($data['reporter_name']) ? sanitizeInput($data['reporter_name']) : null;
    $reporterPhone = sanitizeInput($data['reporter_phone']);
    $alternateContact = isset($data['alternate_contact']) ? sanitizeInput($data['alternate_contact']) : null;
    $landmark = isset($data['landmark']) ? sanitizeInput($data['landmark']) : null;
    $peopleAffected = isset($data['people_affected']) ? sanitizeInput($data['people_affected']) : null;
    $currentSituation = isset($data['current_situation']) ? sanitizeInput($data['current_situation']) : null;
    $description = sanitizeInput($data['description']);
    $disasterName = isset($data['disaster_name']) ? sanitizeInput($data['disaster_name']) : sanitizeInput(substr($description, 0, 100));

    if (empty($disasterName)) {
        $disasterName = 'Emergency Report';
    }

    if ($typeId <= 0 || $severityLevel === '' || $address === '' || $reporterPhone === '' || $description === '') {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid or missing required field values.'
        ]);
        exit;
    }

    $severityDisplay = mapSeverityDisplay($severityLevel);
    $immediateNeeds = $data['immediate_needs'] ?? [];

    if (is_array($immediateNeeds)) {
        $sanitizedNeeds = [];
        foreach ($immediateNeeds as $need) {
            if ($need === null || $need === '') {
                continue;
            }
            $sanitizedNeeds[] = sanitizeInput((string) $need);
        }
        $immediateNeedsJson = json_encode($sanitizedNeeds, JSON_UNESCAPED_UNICODE);
    } else {
        $immediateNeedsJson = json_encode([]);
    }

    $latitude = isset($data['latitude']) ? (float) $data['latitude'] : null;
    $longitude = isset($data['longitude']) ? (float) $data['longitude'] : null;

    $pdo->beginTransaction();

    $sql = "INSERT INTO disasters (
                tracking_id,
                disaster_name,
                type_id,
                severity_level,
                severity_display,
                address,
                city,
                province,
                state,
                latitude,
                longitude,
                landmark,
                reporter_name,
                reporter_phone,
                alternate_contact,
                description,
                immediate_needs,
                current_situation,
                people_affected,
                status,
                source,
                created_at
            ) VALUES (
                :tracking_id,
                :disaster_name,
                :type_id,
                :severity_level,
                :severity_display,
                :address,
                :city,
                :province,
                :state,
                :latitude,
                :longitude,
                :landmark,
                :reporter_name,
                :reporter_phone,
                :alternate_contact,
                :description,
                :immediate_needs,
                :current_situation,
                :people_affected,
                'ON GOING',
                'web_form',
                NOW()
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':tracking_id' => $trackingId,
        ':disaster_name' => $disasterName,
        ':type_id' => $typeId,
        ':severity_level' => $severityLevel,
        ':severity_display' => $severityDisplay,
        ':address' => $address,
        ':city' => $city,
        ':province' => $province,
        ':state' => $state,
        ':latitude' => $latitude,
        ':longitude' => $longitude,
        ':landmark' => $landmark,
        ':reporter_name' => $reporterName,
        ':reporter_phone' => $reporterPhone,
        ':alternate_contact' => $alternateContact,
        ':description' => $description,
        ':immediate_needs' => $immediateNeedsJson,
        ':current_situation' => $currentSituation,
        ':people_affected' => $peopleAffected
    ]);

    $disasterId = (int) $pdo->lastInsertId();

    $pdo->commit();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'tracking_id' => $trackingId,
        'disaster_id' => $disasterId,
        'message' => 'Emergency report submitted successfully.'
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('submit_report.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to submit emergency report.'
    ]);
}
