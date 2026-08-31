<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/CouponSearchQueryBuilder.php';
require_once '../../admin/includes/CouponService.php';
require_once '../../admin/includes/CouponValidator.php';
require_once '../../admin/includes/CouponApiHandler.php';

requireApiRole('admin');

$handler = new CouponApiHandler($pdo, new CouponService($pdo));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $result = $handler->handlePost($input);
    http_response_code($result['status']);
    echo json_encode($result['body']);
    exit();
}

// GET — list coupons (search + status filter + pagination)
$result = $handler->handleGet($_GET);
http_response_code($result['status']);
echo json_encode($result['body']);