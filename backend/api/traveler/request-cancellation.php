<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/CancellationService.php';
require_once '../../admin/includes/CancellationValidator.php';

$claims = requireApiRole('traveler');
$travelerId = (int) $claims['sub'];

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$error = CancellationValidator::validateRequest($input);

if ($error !== '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $error]);
    exit();
}

$bookingId = (int) $input['booking_id'];
$reason = trim($input['reason']);

$service = new CancellationService($pdo);
$guardError = $service->guardRequest($bookingId, $travelerId);

if ($guardError !== '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $guardError]);
    exit();
}

$service->requestCancellation($bookingId, $reason);

echo json_encode(['success' => true, 'message' => 'Cancellation requested.']);