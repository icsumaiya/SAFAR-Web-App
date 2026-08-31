<?php
require_once '../../includes/db.php';
require_once '../../admin/includes/PaymentGatewayService.php';
require_once '../../admin/includes/NotificationService.php';

$tranId = $_POST['tran_id'] ?? '';
$valId = $_POST['val_id'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM payments WHERE tran_id = ? AND status = 'pending'");
$stmt->execute([$tranId]);
$payment = $stmt->fetch();

$frontendBase = 'http://localhost:3000';

if ($payment === false || $valId === '') {
    header("Location: {$frontendBase}/payment-result?status=fail&reason=not_found");
    exit();
}

$config = require '../../includes/payment_config.php';
$gateway = new PaymentGatewayService($config);
$validation = $gateway->callValidationApi($valId);

if (!PaymentGatewayService::isValidationSuccessful($validation, (float) $payment['amount'])) {
    $failStmt = $pdo->prepare("UPDATE payments SET status = 'failed', val_id = ? WHERE tran_id = ?");
    $failStmt->execute([$valId, $tranId]);
    header("Location: {$frontendBase}/payment-result?status=fail&reason=validation_failed");
    exit();
}

$updateStmt = $pdo->prepare("UPDATE payments SET status = 'successful', val_id = ? WHERE tran_id = ?");
$updateStmt->execute([$valId, $tranId]);

$bookingStmt = $pdo->prepare("SELECT traveler_id FROM bookings WHERE id = ?");
$bookingStmt->execute([$payment['booking_id']]);
$travelerId = (int) $bookingStmt->fetchColumn();

$notificationService = new NotificationService($pdo);
$notificationService->create(
    'payment_successful',
    "Online payment of \${$payment['amount']} confirmed for booking #{$payment['booking_id']}.",
    (int) $payment['booking_id']
);
if ($travelerId > 0) {
    $notificationService->create(
        'payment_successful',
        "Your online payment was confirmed for booking #{$payment['booking_id']}.",
        (int) $payment['booking_id'],
        'traveler',
        $travelerId
    );
}

header("Location: {$frontendBase}/payment-result?status=success&booking_id={$payment['booking_id']}");
exit();