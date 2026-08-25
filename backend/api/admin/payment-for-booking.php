<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/PaymentService.php';

requireApiRole('admin');

$bookingId = (int) ($_GET['booking_id'] ?? 0);

if ($bookingId === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'booking_id is required.']);
    exit();
}

$payment = (new PaymentService($pdo))->getForBooking($bookingId);

echo json_encode(['success' => true, 'data' => $payment]);
