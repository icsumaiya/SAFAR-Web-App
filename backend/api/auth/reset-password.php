<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once '../../includes/db.php';
require_once '../../admin/includes/PasswordResetValidator.php';
require_once '../../admin/includes/PasswordResetService.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$token = trim($input['token'] ?? '');
$newPassword = $input['new_password'] ?? '';

$error = PasswordResetValidator::validateNewPassword($newPassword);
if ($error !== '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $error]);
    exit();
}

$service = new PasswordResetService($pdo);
$userId = $service->validateToken($token);

if ($userId === null) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'This reset link is invalid or has expired.']);
    exit();
}

$service->resetPassword($token, $userId, password_hash($newPassword, PASSWORD_DEFAULT));

echo json_encode(['success' => true, 'message' => 'Password has been reset. You can now log in.']);