<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/ReviewService.php';
require_once '../../admin/includes/ReviewValidator.php';
require_once '../../admin/includes/NotificationService.php';

$claims = requireApiRole('traveler');
$travelerId = (int) $claims['sub'];

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$error = ReviewValidator::validateSubmission($input);

if ($error !== '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $error]);
    exit();
}

$bookingId = (int) $input['booking_id'];
$rating = (int) $input['rating'];
$comment = trim($input['comment'] ?? '');

$service = new ReviewService($pdo);
$guard = $service->guardSubmission($bookingId, $travelerId);

if ($guard['error'] !== '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $guard['error']]);
    exit();
}

$service->submitReview($bookingId, $travelerId, $guard['package_id'], $rating, $comment);
(new NotificationService($pdo))->create(
    'new_review',
    "New {$rating}-star review submitted.",
    $bookingId
);

echo json_encode(['success' => true, 'message' => 'Review submitted.']);