<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/command/Command.php';
require_once '../../admin/includes/command/ApproveAgencyCommand.php';
require_once '../../admin/includes/command/RejectAgencyCommand.php';
require_once '../../admin/includes/command/UnverifyAgencyCommand.php';
require_once '../../admin/includes/command/SuspendAgencyCommand.php';
require_once '../../admin/includes/command/ActivateAgencyCommand.php';
require_once '../../admin/includes/AgencyCommandFactory.php';
require_once '../../admin/includes/AgencySearchQueryBuilder.php';

requireApiRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $agencyId = (int) ($input['agency_id'] ?? 0);
    $action = $input['action'] ?? '';

    if ($agencyId === 0 || $action === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'agency_id and action are required.']);
        exit();
    }

    $command = AgencyCommandFactory::build($action, $pdo, $agencyId);

    if ($command === null) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Unknown action.']);
        exit();
    }

    $command->execute();
    echo json_encode(['success' => true, 'message' => 'Agency status updated.']);
    exit();
}

// GET — list agencies (search + status filter, reusing the existing query builder)
$search = trim($_GET['search'] ?? '');
$filter_status = $_GET['filter_status'] ?? 'all';

$built = AgencySearchQueryBuilder::build($search, $filter_status);
$stmt = $pdo->prepare($built['query']);
$stmt->execute($built['params']);
$agencies = $stmt->fetchAll();

echo json_encode(['success' => true, 'data' => $agencies]);