<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/CancellationSearchQueryBuilder.php';
require_once '../../admin/includes/CancellationService.php';
require_once '../../admin/includes/CancellationValidator.php';

requireApiRole('admin');

$service = new CancellationService($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $cancellationId = (int) ($input['cancellation_id'] ?? 0);
    $action = $input['action'] ?? '';

    if ($cancellationId === 0 || $action === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'cancellation_id and action are required.']);
        exit();
    }

    if ($action === 'approve') {
        $amount = isset($input['refundable_amount']) && $input['refundable_amount'] !== ''
            ? (float) $input['refundable_amount']
            : null;
        $service->approve($cancellationId, $amount);
    } elseif ($action === 'reject') {
        $service->reject($cancellationId);
    } elseif ($action === 'update_refund') {
        $refundStatus = $input['refund_status'] ?? '';
        $error = CancellationValidator::validateRefundStatus($refundStatus);

        if ($error !== '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => $error]);
            exit();
        }

        $service->updateRefundStatus($cancellationId, $refundStatus);
    } else {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Unknown action.']);
        exit();
    }

    echo json_encode(['success' => true, 'message' => 'Cancellation updated.']);
    exit();
}

// GET — list cancellations (search + status filter + pagination)
$statusFilter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$page = (int) ($_GET['page'] ?? 1);

$built = CancellationSearchQueryBuilder::build($search, $statusFilter, $page);

$stmt = $pdo->prepare($built['query']);
$stmt->execute($built['params']);
$cancellations = $stmt->fetchAll();

$countStmt = $pdo->prepare($built['countQuery']);
$countStmt->execute($built['params']);
$total = (int) $countStmt->fetchColumn();

echo json_encode([
    'success' => true,
    'data' => $cancellations,
    'pagination' => [
        'page' => $built['page'],
        'per_page' => $built['perPage'],
        'total' => $total,
        'total_pages' => CancellationSearchQueryBuilder::totalPages($total),
    ],
]);