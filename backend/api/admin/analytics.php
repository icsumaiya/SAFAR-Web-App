<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/AnalyticsService.php';
require_once '../../admin/includes/AnalyticsApiHandler.php';

requireApiRole('admin');

$handler = new AnalyticsApiHandler($pdo);

echo json_encode($handler->handle());