<?php
// Auth guard for API endpoints (JSON, not redirect-based like includes/auth.php).
// Call requireApiRole('admin') at the top of any protected api/*.php file.

// Allow the Next.js dev server to call this API (CORS).
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once __DIR__ . '/JwtHelper.php';
function requireApiRole(string $role): array
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Missing or invalid Authorization header.']);
        exit();
    }

    $claims = JwtHelper::verify($matches[1]);

    if ($claims === null) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid or expired token.']);
        exit();
    }

    if (($claims['role'] ?? null) !== $role) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You are not authorized to perform this action.']);
        exit();
    }

    return $claims;
}