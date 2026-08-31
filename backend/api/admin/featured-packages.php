<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/FeaturedPackageValidator.php';
require_once '../../admin/includes/FeaturedPackageService.php';
require_once '../../admin/includes/FeaturedPackageApiHandler.php';

requireApiRole('admin');

$handler = new FeaturedPackageApiHandler($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $result = $handler->handlePost($input);
} else {
    $result = $handler->handleGet();
}

http_response_code($result['status']);
echo json_encode($result['body']);