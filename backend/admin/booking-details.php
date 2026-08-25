<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once 'includes/BookingDetailsService.php';
require_once 'includes/PaymentService.php';
require_once 'includes/PaymentValidator.php';
requireRole('admin');

$booking_id = (int) ($_GET['id'] ?? 0);

// Handle "Record Payment" form submission
$payment_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    $postData = $_POST;
    $postData['booking_id'] = $booking_id;
    $payment_error = PaymentValidator::validate($postData);

    if ($payment_error === '') {
        (new PaymentService($pdo))->recordPayment($postData);
        header("Location: booking-details.php?id=" . $booking_id . "&msg=payment_recorded");
        exit();
    }
}

$service = new BookingDetailsService($pdo);
$booking = $service->getBooking($booking_id);

if ($booking === null) {
    header("Location: bookings.php");
    exit();
}

$payment = (new PaymentService($pdo))->getForBooking($booking_id);

$reference = BookingDetailsService::formatReference((int) $booking['id']);

$active = 'bookings';
require_once '../includes/header.php';
?>

<div class="container my-4" style="display: flex; gap: 30px; align-items: flex-start;">
    <?php require_once 'includes/sidebar.php'; ?>

    <div style="flex: 1; min-width: 0; width: 100%;">
        <a href="bookings.php" style="display: inline-block; margin-bottom: 15px; color: var(--text-muted);">&larr; Back to Manage Bookings</a>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'payment_recorded'): ?>
            <div class="alert alert-success">Payment recorded successfully.</div>
        <?php endif; ?>

        <div class="card" style="padding: 30px; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h1 style="margin-bottom: 5px;"><?php echo htmlspecialchars($reference); ?></h1>
                    <p style="color: var(--text-muted); margin: 0;">
                        Booked on <?php echo date('M j, Y g:i A', strtotime($booking['booking_date'])); ?>
                    </p>
                </div>
                <?php
                    $badgeClass = match ($booking['status']) {
                        'approved' => 'badge-approved',
                        'rejected' => 'badge-rejected',
                        default => 'badge-pending',
                    };
                ?>
                <span class="badge <?php echo $badgeClass; ?>" style="font-size: 0.9rem; padding: 8px 14px;">
                    <?php echo ucfirst($booking['status']); ?>
                </span>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <!-- Traveler -->
            <div class="card" style="padding: 25px;">
                <h2 style="margin-bottom: 15px; font-size: 1.1rem;">Traveler</h2>
                <p style="margin: 0 0 5px;"><?php echo htmlspecialchars($booking['traveler_name']); ?></p>
                <p style="margin: 0; color: var(--text-muted);"><?php echo htmlspecialchars($booking['traveler_email']); ?></p>
            </div>

            <!-- Agency -->
            <div class="card" style="padding: 25px;">
                <h2 style="margin-bottom: 15px; font-size: 1.1rem;">Agency</h2>
                <p style="margin: 0 0 5px;">
                    <a href="agency-details.php?id=<?php echo (int) $booking['agency_id']; ?>">
                        <?php echo htmlspecialchars($booking['company_name']); ?>
                    </a>
                </p>
                <p style="margin: 0 0 5px; color: var(--text-muted);"><?php echo htmlspecialchars($booking['agency_email']); ?></p>
                <?php if (!empty($booking['agency_phone'])): ?>
                    <p style="margin: 0; color: var(--text-muted);"><?php echo htmlspecialchars($booking['agency_phone']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Package -->
        <div class="card" style="padding: 30px;">
            <h2 style="margin-bottom: 15px;">Package</h2>
            <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($booking['package_title']); ?></h3>
            <p style="color: var(--text-muted);