<?php
require_once '../includes/db.php';
require_once '../admin/includes/strategy/FilterStrategy.php';
require_once '../admin/includes/strategy/TypeFilter.php';
require_once '../admin/includes/strategy/LocationFilter.php';
require_once '../admin/includes/strategy/PriceMaxFilter.php';
require_once '../admin/includes/strategy/FilterContext.php';
header('Content-Type: application/json');

$type = $_GET['type'] ?? 'all';
$location = $_GET['location'] ?? '';
$max_price = $_GET['price'] ?? 5000;

// Strategy: each active filter is its own class, added only if relevant
$context = new FilterContext();
$context->addStrategy(new PriceMaxFilter((float) $max_price));

if ($type === 'tour' || $type === 'hotel') {
    $context->addStrategy(new TypeFilter($type));
}

if (!empty($location)) {
    $context->addStrategy(new LocationFilter($location));
}

[$where, $params] = $context->buildQuery();

$query = "SELECT p.*, a.company_name FROM packages p JOIN agencies a ON p.agency_id = a.id WHERE $where ORDER BY p.created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format prices for JSON
    foreach ($results as &$r) {
        $r['price_formatted'] = number_format($r['price'], 2);
    }
    
    echo json_encode(['status' => 'success', 'data' => $results]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}