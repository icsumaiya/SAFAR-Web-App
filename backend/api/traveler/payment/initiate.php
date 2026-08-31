<?php
header('Content-Type: application/json');
require_once '../../../includes/db.php';
require_once '../../../includes/ApiAuth.php';
require_once '../../../admin/includes/PaymentGatewayService.php';

$claims = requireApiRole('traveler');
$travelerId = (int) $claims['sub'];

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$bookingId = (int) ($input['booking_id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT b.id, b.traveler_id, p.title, p.price, u.name, u.email
     FROM bookings b
     JOIN packages p ON b.package_id = p.id
     JOIN users u ON b.traveler_id = u.id
     WHERE b.id = ?"
);
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if ($booking === false || (int) $booking['traveler_id'] !== $travelerId) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Booking not found.']);
    exit();
}

if (!file_exists(__DIR__ . '/../../../includes/payment_config.php')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Payment gateway is not configured yet.']);
    exit();
}

$config = require __DIR__ . '/../../../includes/payment_config.php';
$gateway = new PaymentGatewayService($config);

$tranId = PaymentGatewayService::buildTranId($bookingId);

$baseUrl = 'http://localhost/SAFAR/backend/api/payment';
$payload = $gateway->buildSessionPayload([
    'amount' => $booking['price'],
    'tran_id' => $tranId,
    'success_url' => "{$baseUrl}/success.php",
    'fail_url' => "{$baseUrl}/fail.php",
    'cancel_url' => "{$baseUrl}/cancel.php",
    'customer_name' => $booking['name'],
    'customer_email' => $booking['email'],
    'product_name' => $booking['title'],
]);

$response = $gateway->callInitApi($payload);

if (($response['status'] ?? '') !== 'SUCCESS') {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'Could not start payment session.']);
    exit();
}

// Record a pending payment row up front, tied to this tran_id.
$insertStmt = $pdo->prepare(
    "INSERT INTO payments (booking_id, amount, method, status, tran_id) VALUES (?, ?, 'card', 'pending', ?)"
);
$insertStmt->execute([$bookingId, $booking['price'], $tranId]);

echo json_encode([
    'success' => true,
    'gateway_url' => $response['GatewayPageURL'],
]);