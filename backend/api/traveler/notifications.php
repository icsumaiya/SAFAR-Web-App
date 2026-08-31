<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/NotificationService.php';

$claims = requireApiRole('traveler');
$travelerId = (int) $claims['sub'];

$service = new NotificationService($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? '';

    if ($action === 'mark_read') {
        $service->markReadForTraveler((int) ($input['id'] ?? 0), $travelerId);
    } elseif ($action === 'mark_all_read') {
        $service->markAllReadForTraveler($travelerId);
    } else {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Unknown action.']);
        exit();
    }

    echo json_encode(['success' => true, 'message' => 'Updated.']);
    exit();
}

// GET
echo json_encode([
    'success' => true,
    'data' => $service->getForTraveler($travelerId),
    'unread_count' => $service->getUnreadCountForTraveler($travelerId),
]);