<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/CommissionSearchQueryBuilder.php';
require_once '../../admin/includes/CommissionService.php';
require_once '../../admin/includes/CommissionValidator.php';
require_once '../../admin/includes/CommissionApiHandler.php';

requireApiRole('admin');

$handler = new CommissionApiHandler($pdo, new CommissionService($pdo));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $result = $handler->handlePost($input);
    http_response_code($result['status']);
    echo json_encode($result['body']);
    exit();
}

// GET — sync new successful payments into commission records, then return list + stats
$result = $handler->handleGet($_GET);
http_response_code($result['status']);
echo json_encode($result['body']);