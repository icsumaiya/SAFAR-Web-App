<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../includes/ApiAuth.php';
require_once '../../admin/includes/PaymentSearchQueryBuilder.php';
require_once '../../admin/includes/PaymentService.php';
require_once '../../admin/includes/PaymentValidator.php';
require_once '../../admin/includes/CouponService.php';
require_once '../../admin/includes/CouponValidator.php';
require_once '../../admin/includes/NotificationService.php';

requireApiRole('admin');

$service = new PaymentService($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $couponService = new CouponService($pdo);
    $appliedCoupon = null;
    $discountAmount = 0.0;

    $couponCode = trim($input['coupon_code'] ?? '');
    if ($couponCode !== '') {
        $coupon = $couponService->findByCode($couponCode);
        $baseAmount = (float) ($input['amount'] ?? 0);

        $bookingStmt = $pdo->prepare("SELECT traveler_id FROM bookings WHERE id = ?");
        $bookingStmt->execute([(int) ($input['booking_id'] ?? 0)]);
        $travelerId = (int) ($bookingStmt->fetchColumn() ?: 0);

        $usageTotal = $coupon ? $couponService->countUsageForCoupon((int) $coupon['id']) : 0;
        $usageForUser = $coupon ? $couponService->countUsageForUser((int) $coupon['id'], $travelerId) : 0;

        $couponError = CouponValidator::validateForUse($coupon, $baseAmount, $usageTotal, $usageForUser);

        if ($couponError !== '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => $couponError]);
            exit();
        }

        $discountAmount = CouponValidator::calculateDiscount($coupon, $baseAmount);
        $input['coupon_id'] = $coupon['id'];
        $input['discount_amount'] = $discountAmount;
        $input['amount'] = round($baseAmount - $discountAmount, 2);
        $appliedCoupon = $coupon;
    }

    $error = PaymentValidator::validate($input);

    if ($error !== '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => $error]);
        exit();
    }

    $service->recordPayment($input);

    if (($input['status'] ?? '') === 'successful') {
        $notificationService = new NotificationService($pdo);
        $notificationService->create(
            'payment_successful',
            "Payment of \${$input['amount']} recorded for booking #{$input['booking_id']}.",
            (int) $input['booking_id']
        );

        $travelerStmt = $pdo->prepare("SELECT traveler_id FROM bookings WHERE id = ?");
        $travelerStmt->execute([(int) $input['booking_id']]);
        $travelerId = (int) $travelerStmt->fetchColumn();

        if ($travelerId > 0) {
            $notificationService->create(
                'payment_successful',
                "Your payment of \${$input['amount']} was confirmed for booking #{$input['booking_id']}.",
                (int) $input['booking_id'],
                'traveler',
                $travelerId
            );

            require_once '../../admin/includes/EmailService.php';
            $userStmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
            $userStmt->execute([$travelerId]);
            $traveler = $userStmt->fetch();

            if ($traveler !== false) {
                $emailConfigPath = '../../includes/email_config.php';
                $emailConfig = file_exists($emailConfigPath) ? require $emailConfigPath : null;
                (new EmailService($emailConfig, '../../logs/email.log'))->send(
                    $traveler['email'],
                    $traveler['name'],
                    'Payment confirmation — SAFAR',
                    "Hi {$traveler['name']},<br><br>We've received your payment of \${$input['amount']} for booking #{$input['booking_id']}. Thank you!"
                );
            }
        }
    }

    if ($appliedCoupon !== null) {
        $bookingStmt = $pdo->prepare("SELECT traveler_id FROM bookings WHERE id = ?");
        $bookingStmt->execute([(int) $input['booking_id']]);
        $travelerId = (int) $bookingStmt->fetchColumn();

        $couponService->recordUsage((int) $appliedCoupon['id'], (int) $input['booking_id'], $travelerId, $discountAmount);
    }

    echo json_encode(['success' => true, 'message' => 'Payment recorded.', 'discount_applied' => $discountAmount]);
    exit();
}

// GET — list payments (search + status filter + pagination) — unchanged
$status_filter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$page = (int) ($_GET['page'] ?? 1);

$built = PaymentSearchQueryBuilder::build($search, $status_filter, $page);

$stmt = $pdo->prepare($built['query']);
$stmt->execute($built['params']);
$payments = $stmt->fetchAll();

$countStmt = $pdo->prepare($built['countQuery']);
$countStmt->execute($built['params']);
$total = (int) $countStmt->fetchColumn();

echo json_encode([
    'success' => true,
    'data' => $payments,
    'pagination' => [
        'page' => $built['page'],
        'per_page' => $built['perPage'],
        'total' => $total,
        'total_pages' => PaymentSearchQueryBuilder::totalPages($total),
    ],
    'stats' => $service->getStats(),
]);