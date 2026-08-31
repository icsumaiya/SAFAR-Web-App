<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/CancellationService.php';
require_once '../../admin/includes/CancellationValidator.php';
require_once '../../admin/includes/NotificationService.php';

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

(new NotificationService($pdo))->create(
    'cancellation_request',
    "Cancellation requested for booking #{$bookingId}.",
    $bookingId
);

require_once '../../admin/includes/EmailService.php';
$userStmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$userStmt->execute([$travelerId]);
$traveler = $userStmt->fetch();

if ($traveler !== false) {
    $emailConfigPath = '../../includes/email_config.php';
    $emailConfig = file_exists($emailConfigPath) ? require $emailConfigPath : null;
    (new EmailService($emailConfig, '../../logs/email.log'))->send(
        $traveler['email'],
        $traveler['name'],
        'Cancellation request received — SAFAR',
        "Hi {$traveler['name']},<br><br>We've received your cancellation request for booking #{$bookingId}. We'll review it shortly."
    );
}

echo json_encode(['success' => true, 'message' => 'Cancellation requested.']);