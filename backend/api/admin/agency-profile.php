<?php

header('Content-Type: application/json');

require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/AgencyDetailsService.php';
require_once '../../admin/includes/AgencyProfileValidator.php';
require_once '../../admin/includes/ReviewService.php';
require_once '../../admin/includes/AgencyProfileApiHandler.php';

requireApiRole('admin');

$handler = new AgencyProfileApiHandler($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $result = $handler->handlePost($input);

    http_response_code($result['status']);
    echo json_encode($result['body']);
    exit();
}

// GET — full profile for one agency:
// base info + booking stats + package count + rating/review count.
// Reuses service methods through AgencyProfileApiHandler.
$result = $handler->handleGet($_GET);

http_response_code($result['status']);
echo json_encode($result['body']);
exit();