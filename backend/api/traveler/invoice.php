<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/BookingDetailsService.php';
require_once '../../admin/includes/InvoiceService.php';

requireApiRole('admin');

$bookingId = (int) ($_GET['booking_id'] ?? 0);

$service = new InvoiceService($pdo);
$invoice = $service->getInvoiceData($bookingId);

if ($invoice === null) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Invoice not found.']);
    exit();
}

echo json_encode(['success' => true, 'data' => $invoice]);