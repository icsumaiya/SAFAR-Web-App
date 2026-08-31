<?php
// Assembles all data needed for a booking invoice: traveler, agency,
// package, travel dates, the most recent payment (with any coupon
// discount applied), and a formatted booking reference. Never exposes
// raw card data or payment secrets — only amount/method/status/txn id,
// same fields already shown elsewhere in the admin panel.

class InvoiceService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getInvoiceData(int $bookingId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT b.id AS booking_id, b.traveler_id, b.status AS booking_status,
                    b.booking_date, b.check_in, b.check_out, b.guests,
                    u.name AS traveler_name, u.email AS traveler_email,
                    p.title AS package_title, p.location AS package_location,
                    p.price AS package_price, p.type AS package_type,
                    a.company_name AS agency_name, a.phone AS agency_phone
             FROM bookings b
             JOIN users u ON b.traveler_id = u.id
             JOIN packages p ON b.package_id = p.id
             JOIN agencies a ON p.agency_id = a.id
             WHERE b.id = ?"
        );
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();

        if ($booking === false) {
            return null;
        }

        $paymentStmt = $this->pdo->prepare(
            "SELECT amount, method, status, transaction_id, discount_amount, coupon_id, created_at
             FROM payments WHERE booking_id = ? ORDER BY created_at DESC LIMIT 1"
        );
        $paymentStmt->execute([$bookingId]);
        $payment = $paymentStmt->fetch();

        $couponCode = null;
        if ($payment !== false && $payment['coupon_id'] !== null) {
            $couponStmt = $this->pdo->prepare("SELECT code FROM coupons WHERE id = ?");
            $couponStmt->execute([$payment['coupon_id']]);
            $couponCode = $couponStmt->fetchColumn() ?: null;
        }

        $baseAmount = (float) $booking['package_price'];
        $discount = $payment !== false ? (float) $payment['discount_amount'] : 0.0;
        $finalAmount = $payment !== false ? (float) $payment['amount'] : $baseAmount;

        return [
            'reference' => BookingDetailsService::formatReference($bookingId),
            'booking_id' => (int) $booking['booking_id'],
            'booking_status' => $booking['booking_status'],
            'booking_date' => $booking['booking_date'],
            'check_in' => $booking['check_in'],
            'check_out' => $booking['check_out'],
            'guests' => $booking['guests'],
            'traveler_name' => $booking['traveler_name'],
            'traveler_email' => $booking['traveler_email'],
            'agency_name' => $booking['agency_name'],
            'agency_phone' => $booking['agency_phone'],
            'package_title' => $booking['package_title'],
            'package_location' => $booking['package_location'],
            'package_type' => $booking['package_type'],
            'base_amount' => $baseAmount,
            'discount_amount' => $discount,
            'coupon_code' => $couponCode,
            'final_amount' => $finalAmount,
            'payment_status' => $payment !== false ? $payment['status'] : 'not_recorded',
            'payment_method' => $payment !== false ? $payment['method'] : null,
            'transaction_id' => $payment !== false ? $payment['transaction_id'] : null,
        ];
    }
}