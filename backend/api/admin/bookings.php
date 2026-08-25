<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/BookingManagementHelper.php';
require_once '../../admin/includes/BookingSearchQueryBuilder.php';

requireApiRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $bookingId = (int) ($input['booking_id'] ?? 0);
    $action = $input['booking_action'] ?? '';

    if ($bookingId === 0 || $action === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'booking_id and booking_action are required.']);
        exit();
    }

    $status = BookingManagementHelper::resolveStatus($action);
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->execute([$status, $bookingId]);

    echo json_encode(['success' => true, 'message' => 'Booking status updated.']);
    exit();
}

// GET — list bookings (search + status filter + pagination)
$status_filter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$page = (int) ($_GET['page'] ?? 1);

$built = BookingSearchQueryBuilder::build($search, $status_filter, $page);

$stmt = $pdo->prepare($built['query']);
$stmt->execute($built['params']);
$bookings = $stmt->fetchAll();

$countStmt = $pdo->prepare($built['countQuery']);
$countStmt->execute($built['params']);
$total = (int) $countStmt->fetchColumn();

$counts = $pdo->query("SELECT status, COUNT(*) AS c FROM bookings GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

echo json_encode([
    'success' => true,
    'data' => $bookings,
    'pagination' => [
        'page' => $built['page'],
        'per_page' => $built['perPage'],
        'total' => $total,
        'total_pages' => BookingSearchQueryBuilder::totalPages($total),
    ],
    'counts' => $counts,
]);