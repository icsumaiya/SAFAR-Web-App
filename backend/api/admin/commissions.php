<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/CommissionSearchQueryBuilder.php';
require_once '../../admin/includes/CommissionService.php';
require_once '../../admin/includes/CommissionValidator.php';

requireApiRole('admin');

$service = new CommissionService($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $error = CommissionValidator::validatePercentage($input['commission_percentage'] ?? null);

    if ($error !== '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => $error]);
        exit();
    }

    $service->updatePercentage((float) $input['commission_percentage']);
    echo json_encode(['success' => true, 'message' => 'Commission percentage updated.']);
    exit();
}

// GET — sync new successful payments into commission records, then return list + stats
$synced = $service->syncCommissions();

$search = trim($_GET['search'] ?? '');
$agencyId = (int) ($_GET['agency_id'] ?? 0);
$page = (int) ($_GET['page'] ?? 1);

$built = CommissionSearchQueryBuilder::build($search, $agencyId, $page);

$stmt = $pdo->prepare($built['query']);
$stmt->execute($built['params']);
$commissions = $stmt->fetchAll();

$countStmt = $pdo->prepare($built['countQuery']);
$countStmt->execute($built['params']);
$total = (int) $countStmt->fetchColumn();

echo json_encode([
    'success' => true,
    'synced' => $synced,
    'data' => $commissions,
    'pagination' => [
        'page' => $built['page'],
        'per_page' => $built['perPage'],
        'total' => $total,
        'total_pages' => CommissionSearchQueryBuilder::totalPages($total),
    ],
    'summary' => $service->getSummary(),
    'by_agency' => $service->getByAgency(),
    'commission_percentage' => $service->getPercentage(),
]);