<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../../includes/db.php';
require_once '../../admin/includes/FeaturedPackageService.php';

$service = new FeaturedPackageService($pdo);

echo json_encode([
    'success' => true,
    'featured' => $service->getFeatured(6),
    'popular' => $service->getPopular(6),
    'recommended' => $service->getRecommended(6),
    'special_offers' => $service->getSpecialOffers(6),
]);