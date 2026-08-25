<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/CouponSearchQueryBuilder.php';
require_once '../../admin/includes/CouponService.php';
require_once '../../admin/includes/CouponValidator.php';

requireApiRole('admin');

$service = new CouponService($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? 'create';

    if ($action === 'create' || $action === 'update') {
        $error = CouponValidator::validateCouponData($input);

        if ($error !== '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => $error]);
            exit();
        }

        if ($action === 'create') {
            $service->create($input);
        } else {
            $service->update((int) ($input['id'] ?? 0), $input);
        }

        echo json_encode(['success' => true, 'message' => 'Coupon saved.']);
        exit();
    }

    if ($action === 'activate' || $action === 'deactivate') {
        $service->setActive((int) ($input['id'] ?? 0), $action === 'activate');
        echo json_encode(['success' => true, 'message' => 'Coupon status updated.']);
        exit();
    }

    if ($action === 'delete') {
        $deleted = $service->delete((int) ($input['id'] ?? 0));

        if (!$deleted) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'This coupon has already been used and cannot be deleted. Deactivate it instead.']);
            exit();
        }

        echo json_encode(['success' => true, 'message' => 'Coupon deleted.']);
        exit();
    }

    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Unknown action.']);
    exit();
}

// GET — list coupons (search + status filter + pagination)
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$page = (int) ($_GET['page'] ?? 1);

$built = CouponSearchQueryBuilder::build($search, $statusFilter, $page);

$stmt = $pdo->prepare($built['query']);
$stmt->execute($built['params']);
$coupons = $stmt->fetchAll();

$countStmt = $pdo->prepare($built['countQuery']);
$countStmt->execute($built['params']);
$total = (int) $countStmt->fetchColumn();

echo json_encode([
    'success' => true,
    'data' => $coupons,
    'pagination' => [
        'page' => $built['page'],
        'per_page' => $built['perPage'],
        'total' => $total,
        'total_pages' => CouponSearchQueryBuilder::totalPages($total),
    ],
]);