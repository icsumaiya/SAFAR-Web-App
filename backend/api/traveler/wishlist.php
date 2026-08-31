<?php
// Session-authenticated wishlist add/remove endpoint for the PHP-rendered
// pages (explore.php, package-details.php, dashboard/traveler.php).
//
// api/traveler/wishlist.php already does the same job over JWT for a
// future SPA/mobile client, but the PHP pages authenticate travelers via
// session (see login.php / includes/auth.php), matching how the review
// and cancellation forms in dashboard/traveler.php work. Both endpoints
// share the same WishlistService/WishlistValidator so the add/remove
// logic and duplicate handling stay in one place.

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/admin/includes/WishlistValidator.php';
require_once __DIR__ . '/admin/includes/WishlistService.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['user_role'] !== 'traveler') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please log in as a traveler to use your wishlist.']);
    exit();
}

$service = new WishlistService($pdo);

// GET: used by explore.php / package-details.php on page load to know
// which hearts should render as already-saved.
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $packageIds = array_map(
        fn($row) => (int) $row['package_id'],
        $service->getForTraveler((int) $_SESSION['user_id'])
    );
    echo json_encode(['success' => true, 'package_ids' => $packageIds]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? 'add';

$error = WishlistValidator::validate($input);
if ($error !== '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $error]);
    exit();
}

$travelerId = (int) $_SESSION['user_id'];
$packageId = (int) $input['package_id'];

if ($action === 'remove') {
    $service->remove($travelerId, $packageId);
    echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Removed from wishlist.']);
    exit();
}

if (!$service->packageExists($packageId)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Package not found.']);
    exit();
}

$addError = $service->add($travelerId, $packageId);
if ($addError !== '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $addError]);
    exit();
}

echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Added to wishlist.']);