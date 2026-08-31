<?php
require_once '../../includes/db.php';

$tranId = $_POST['tran_id'] ?? '';

if ($tranId !== '') {
    $stmt = $pdo->prepare("UPDATE payments SET status = 'failed' WHERE tran_id = ? AND status = 'pending'");
    $stmt->execute([$tranId]);
}

header("Location: http://localhost:3000/payment-result?status=fail&reason=gateway_failed");
exit();