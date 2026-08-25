<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/BookingDetailsService.php';

requireApiRole('admin');

$bookingId = (int) ($_GET['id'] ?? 0);

$service = new BookingDetailsService($pdo);
$booking = $service->getBooking($bookingId);

if ($booking === null) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Booking not found.']);
    exit();
}

$booking['reference'] = BookingDetailsService::formatReference((int) $booking['id']);

echo json_encode(['success' => true, 'data' => $booking]);