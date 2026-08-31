<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/ReviewSearchQueryBuilder.php';
require_once '../../admin/includes/ReviewService.php';
require_once '../../admin/includes/ReviewValidator.php';

requireApiRole('admin');

$service = new ReviewService($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int) ($input['id'] ?? 0);
    $status = $input['status'] ?? '';

    $error = ReviewValidator::validateModerationStatus($status);

    if ($error !== '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => $error]);
        exit();
    }

    $service->setStatus($id, $status);
    echo json_encode(['success' => true, 'message' => 'Review status updated.']);
    exit();
}

// GET — list reviews (search + status filter + pagination + stats)
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$page = (int) ($_GET['page'] ?? 1);

$built = ReviewSearchQueryBuilder::build($search, $statusFilter, $page);

$stmt = $pdo->prepare($built['query']);
$stmt->execute($built['params']);
$reviews = $stmt->fetchAll();

$countStmt = $pdo->prepare($built['countQuery']);
$countStmt->execute($built['params']);
$total = (int) $countStmt->fetchColumn();

echo json_encode([
    'success' => true,
    'data' => $reviews,
    'pagination' => [
        'page' => $built['page'],
        'per_page' => $built['perPage'],
        'total' => $total,
        'total_pages' => ReviewSearchQueryBuilder::totalPages($total),
    ],
    'stats' => $service->getStats(),
]);