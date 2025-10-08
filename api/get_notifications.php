<?php
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $notifications_stmt = $pdo->query("
        SELECT n.*,
               l.lgu_name,
               CONCAT(u.first_name, ' ', u.last_name) AS created_by_name,
               (SELECT COUNT(*)
                  FROM notifications nr
                 WHERE nr.notification_id = n.notification_id
                   AND nr.is_read = 1) AS read_count,
               (SELECT COUNT(*)
                  FROM notifications nt
                 WHERE nt.notification_id = n.notification_id) AS total_recipients
          FROM notifications n
          LEFT JOIN lgus l ON n.target_lgu_id = l.lgu_id
          LEFT JOIN users u ON n.created_by = u.user_id
         ORDER BY n.created_at DESC
    ");
    $notifications = $notifications_stmt->fetchAll();

    $stats_stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT notification_id) AS total_notifications,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) AS active_notifications,
            COUNT(CASE WHEN expires_at IS NULL OR expires_at > NOW() THEN 1 END) AS valid_notifications,
            COUNT(CASE WHEN expires_at IS NOT NULL AND expires_at <= NOW() THEN 1 END) AS expired_notifications
        FROM notifications
    ");
    $stats = $stats_stmt->fetch();

    $typeColors = [
        'system' => '#2563eb',
        'disaster_assigned' => '#0f766e',
        'status_update' => '#ca8a04',
        'escalation' => '#dc2626',
        'deadline_warning' => '#d97706'
    ];

    $typeIcons = [
        'system' => 'fas fa-bullhorn',
        'disaster_assigned' => 'fas fa-people-arrows',
        'status_update' => 'fas fa-clipboard-check',
        'escalation' => 'fas fa-fire-alt',
        'deadline_warning' => 'fas fa-hourglass-half'
    ];

    $statusIcons = [
        'active' => 'fas fa-circle-check',
        'expired' => 'fas fa-hourglass-end',
        'inactive' => 'fas fa-pause-circle'
    ];

    $notificationsPayload = [];
    $maxVisibleLines = 3;

    foreach ($notifications as $notification) {
        $targetDescription = 'All Users';
        if (!empty($notification['target_role']) && !empty($notification['lgu_name'])) {
            $targetDescription = ucfirst($notification['target_role']) . ' @ ' . $notification['lgu_name'];
        } elseif (!empty($notification['target_role'])) {
            $targetDescription = ucfirst($notification['target_role']) . ' (All LGUs)';
        } elseif (!empty($notification['lgu_name'])) {
            $targetDescription = $notification['lgu_name'] . ' (All Roles)';
        }

        $type = $notification['type'] ?? 'system';
        $typeLabel = ucfirst(str_replace('_', ' ', $type));
        $accentColor = $typeColors[$type] ?? '#2563eb';
        $typeIcon = $typeIcons[$type] ?? 'fas fa-bell';

        $readCount = (int) ($notification['read_count'] ?? 0);
        $totalRecipients = (int) ($notification['total_recipients'] ?? 0);
        $readRate = $totalRecipients > 0
            ? round(($readCount / $totalRecipients) * 100, 1)
            : 0.0;

        $expiresAt = $notification['expires_at'] ?? null;
        $hasExpiry = !empty($expiresAt);
        $isExpired = $hasExpiry && strtotime($expiresAt) <= time();

        $statusClass = $notification['is_active'] ? ($isExpired ? 'expired' : 'active') : 'inactive';
        $statusText = $notification['is_active'] ? ($isExpired ? 'Expired' : 'Active') : 'Inactive';
        $statusIcon = $statusIcons[$statusClass] ?? 'fas fa-info-circle';

        $targetIcon = 'fas fa-users';
        if (!empty($notification['target_role']) && !empty($notification['lgu_name'])) {
            $targetIcon = 'fas fa-people-group';
        } elseif (!empty($notification['target_role'])) {
            $targetIcon = 'fas fa-user-shield';
        } elseif (!empty($notification['lgu_name'])) {
            $targetIcon = 'fas fa-map-marker-alt';
        }

        $rawMessage = trim($notification['message'] ?? '');
        $messageLines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $rawMessage))));
        $messageMode = 'empty';
        $messageSingle = '';
        $messagePreviewLines = [];
        $additionalLinesCount = 0;

        if (!empty($messageLines)) {
            if (count($messageLines) === 1) {
                $messageMode = 'single';
                $messageSingle = $messageLines[0];
            } else {
                $messageMode = 'list';
                $messagePreviewLines = array_slice($messageLines, 0, $maxVisibleLines);
                if (count($messageLines) > $maxVisibleLines) {
                    $additionalLinesCount = count($messageLines) - $maxVisibleLines;
                }
            }
        }

        $createdAt = $notification['created_at'] ?? null;
        $createdAtFormatted = $createdAt ? date('M d, Y H:i', strtotime($createdAt)) : '';
        $createdAtIso = $createdAt ? date('c', strtotime($createdAt)) : '';

        $expiresAtFormatted = $hasExpiry ? date('M d, Y H:i', strtotime($expiresAt)) : null;
        $expiresAtIso = $hasExpiry ? date('c', strtotime($expiresAt)) : null;

        $payload = [
            'id' => (int) ($notification['notification_id'] ?? 0),
            'title' => $notification['title'] ?? '',
            'message' => $notification['message'] ?? '',
            'type' => $type,
            'type_label' => $typeLabel,
            'target_role' => $notification['target_role'] ?? 'all',
            'target_lgu_id' => $notification['target_lgu_id'] ?? 'all',
            'target_description' => $targetDescription,
            'expires_at' => $expiresAt,
            'expires_at_formatted' => $expiresAtFormatted,
            'expires_at_local' => $hasExpiry ? date('Y-m-d\TH:i', strtotime($expiresAt)) : '',
            'is_active' => (int) ($notification['is_active'] ?? 0),
            'status_text' => $statusText,
            'status_class' => $statusClass,
            'created_at' => $createdAt,
            'created_at_formatted' => $createdAtFormatted,
            'created_by' => $notification['created_by_name'] ?? 'System',
            'read_count' => $readCount,
            'total_recipients' => $totalRecipients,
            'read_rate' => $readRate,
            'message_lines' => $messageLines,
            'type_icon' => $typeIcon,
            'target_icon' => $targetIcon,
            'status_icon' => $statusIcon,
            'accent_color' => $accentColor
        ];

        $notificationsPayload[] = [
            'id' => (int) ($notification['notification_id'] ?? 0),
            'title' => $notification['title'] ?? '',
            'type' => $type,
            'type_label' => $typeLabel,
            'type_icon' => $typeIcon,
            'accent_color' => $accentColor,
            'target_description' => $targetDescription,
            'target_icon' => $targetIcon,
            'total_recipients' => $totalRecipients,
            'read_count' => $readCount,
            'read_rate' => $readRate,
            'status_class' => $statusClass,
            'status_text' => $statusText,
            'status_icon' => $statusIcon,
            'created_at_formatted' => $createdAtFormatted,
            'created_at_iso' => $createdAtIso,
            'created_by' => $notification['created_by_name'] ?? 'System',
            'has_expiry' => $hasExpiry,
            'is_expired' => $isExpired,
            'expires_at_formatted' => $expiresAtFormatted,
            'expires_at_iso' => $expiresAtIso,
            'message_mode' => $messageMode,
            'message_single' => $messageSingle,
            'message_preview_lines' => $messagePreviewLines,
            'additional_lines_count' => $additionalLinesCount,
            'payload' => $payload
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'notifications' => $notificationsPayload,
            'stats' => [
                'total_notifications' => (int) ($stats['total_notifications'] ?? 0),
                'active_notifications' => (int) ($stats['active_notifications'] ?? 0),
                'valid_notifications' => (int) ($stats['valid_notifications'] ?? 0),
                'expired_notifications' => (int) ($stats['expired_notifications'] ?? 0)
            ],
            'refreshed_at' => date('c')
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('get_notifications.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load notifications right now.'
    ]);
}
