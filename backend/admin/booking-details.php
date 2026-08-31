<?php

header('Content-Type: application/json');

require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/BookingDetailsService.php';
require_once '../../admin/includes/BookingDetailsApiHandler.php';

requireApiRole('admin');

$bookingId = (int) ($_GET['id'] ?? 0);

$handler = new BookingDetailsApiHandler($pdo);

$result = $handler->handleGet($bookingId);

http_response_code($result['status']);
echo json_encode($result['body']);
exit();