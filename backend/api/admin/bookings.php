<?php

header('Content-Type: application/json');

require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/BookingManagementHelper.php';
require_once '../../admin/includes/BookingSearchQueryBuilder.php';
require_once '../../admin/includes/NotificationService.php';
require_once '../../admin/includes/EmailService.php';
require_once '../../admin/includes/BookingApiHandler.php';

requireApiRole('admin');

$emailConfigPath = '../../includes/email_config.php';
$emailConfig = file_exists($emailConfigPath)
    ? require $emailConfigPath
    : null;

$handler = new BookingApiHandler(
    $pdo,
    new NotificationService($pdo),
    new EmailService(
        $emailConfig,
        '../../logs/email.log'
    )
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(
        file_get_contents('php://input'),
        true
    ) ?? [];

    $result = $handler->handlePost($input);

    http_response_code($result['status']);
    echo json_encode($result['body']);
    exit();
}

// GET — list bookings
// Search + status filter + pagination
$result = $handler->handleGet($_GET);

http_response_code($result['status']);
echo json_encode($result['body']);
exit();