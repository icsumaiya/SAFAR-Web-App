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
require_once '../../admin/includes/EmailService.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim($input['email'] ?? '');

$error = PasswordResetValidator::validateEmail($email);
if ($error !== '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $error]);
    exit();
}

$service = new PasswordResetService($pdo);
$user = $service->findUserByEmail($email);

// Always respond the same way whether or not the email exists —
// prevents leaking which emails are registered.
if ($user !== null) {
    $token = $service->createToken((int) $user['id']);
    $resetLink = "http://localhost:3000/reset-password?token={$token}";

    $emailConfigPath = __DIR__ . '/../../includes/email_config.php';
    $emailConfig = file_exists($emailConfigPath) ? require $emailConfigPath : null;
    $emailService = new EmailService($emailConfig, __DIR__ . '/../../logs/email.log');

    $emailService->send(
        $user['email'],
        $user['name'],
        'Reset your SAFAR password',
        "Hi {$user['name']},<br><br>Click the link below to reset your password (valid for 1 hour):<br>"
        . "<a href=\"{$resetLink}\">{$resetLink}</a><br><br>If you didn't request this, ignore this email."
    );
}

echo json_encode([
    'success' => true,
    'message' => 'If that email is registered, a reset link has been sent.',
]);