<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/PackageValidator.php';
require_once '../../admin/includes/PackageFactory.php';
require_once '../../admin/includes/PackageSearchQueryBuilder.php';
require_once '../../admin/includes/NotificationService.php';

requireApiRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? 'create';

    if ($action === 'delete') {
        $id = (int) ($input['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Package deleted.']);
        exit();
    }

    if ($action === 'approve' || $action === 'reject') {
        $id = (int) ($input['id'] ?? 0);
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';

        $pkgStmt = $pdo->prepare("SELECT title, agency_id FROM packages WHERE id = ?");
        $pkgStmt->execute([$id]);
        $pkgRow = $pkgStmt->fetch();

        if (!$pkgRow) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Package not found.']);
            exit();
        }

        $stmt = $pdo->prepare("UPDATE packages SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);

        (new NotificationService($pdo))->create(
            $action === 'approve' ? 'package_approved' : 'package_rejected',
            "Package \"{$pkgRow['title']}\" was {$newStatus}.",
            $id,
            'agency',
            (int) $pkgRow['agency_id']
        );

        echo json_encode(['success' => true, 'message' => "Package {$newStatus}."]);
        exit();
    }

    // create / update
    $error = PackageValidator::validate($input);
    if ($error !== '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => $error]);
        exit();
    }

    $type = $input['type'] ?? 'tour';
    $agencyId = $input['agency_id'] ?? null;
    $editId = $input['id'] ?? null;

    try {
        $pkg = PackageFactory::createPackage($type, $input);
    } catch (InvalidArgumentException $e) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit();
    }

    if ($editId) {
        $stmt = $pdo->prepare(
            "UPDATE packages SET agency_id=?, title=?, location=?, price=?, description=?, image_url=?, type=? WHERE id=?"
        );
        $stmt->execute([$agencyId, $pkg->title, $pkg->location, $pkg->price, $pkg->description, $pkg->image_url, $pkg->type, $editId]);
        echo json_encode(['success' => true, 'message' => 'Package updated.']);
        exit();
    }

    // Admin is creating this directly, so it's pre-approved — no separate
    // review step needed for packages the admin builds themselves.
    $stmt = $pdo->prepare(
        "INSERT INTO packages (agency_id, title, location, price, description, image_url, type, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'approved')"
    );
    $stmt->execute([$agencyId, $pkg->title, $pkg->location, $pkg->price, $pkg->description, $pkg->image_url, $pkg->type]);
    echo json_encode(['success' => true, 'message' => 'Package created.']);
    exit();
}

// GET — list (search + type/agency/status filter + sort + pagination)
$search = trim($_GET['search'] ?? '');
$filterType = $_GET['filter_type'] ?? 'all';
$filterAgency = $_GET['filter_agency'] ?? 'all';
$filterStatus = $_GET['filter_status'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';
$page = (int) ($_GET['page'] ?? 1);

$built = PackageSearchQueryBuilder::build($search, $filterType, $filterAgency, $sort, $page, $filterStatus);

$stmt = $pdo->prepare($built['query']);
$stmt->execute($built['params']);
$packages = $stmt->fetchAll();

$countStmt = $pdo->prepare($built['countQuery']);
$countStmt->execute($built['params']);
$totalRows = (int) $countStmt->fetchColumn();

$verifiedAgencies = $pdo->query("SELECT id, company_name FROM agencies WHERE status = 'verified' ORDER BY company_name")->fetchAll();
$allAgencies = $pdo->query("SELECT id, company_name FROM agencies ORDER BY company_name")->fetchAll();

// Counts per status so the admin UI can show tab badges like "Pending (3)".
$statusCounts = ['all' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
$countsRows = $pdo->query("SELECT status, COUNT(*) AS c FROM packages GROUP BY status")->fetchAll();
foreach ($countsRows as $row) {
    $statusCounts[$row['status']] = (int) $row['c'];
    $statusCounts['all'] += (int) $row['c'];
}

echo json_encode([
    'success' => true,
    'data' => $packages,
    'pagination' => [
        'page' => $built['page'],
        'per_page' => $built['perPage'],
        'total' => $totalRows,
        'total_pages' => PackageSearchQueryBuilder::totalPages($totalRows),
    ],
    'sort' => $sort,
    'verified_agencies' => $verifiedAgencies,
    'all_agencies' => $allAgencies,
    'status_counts' => $statusCounts,
]);