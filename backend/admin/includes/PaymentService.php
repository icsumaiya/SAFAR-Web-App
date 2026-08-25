<?php
// DB operations for Admin Payment Management: recording a manual payment,
// looking up a booking's payment, and computing real (never hardcoded)
// stats for the stat cards. Extracted as a service so it can be unit
// tested with a mocked PDO instead of a real database connection.

class PaymentService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param array $data Expected keys: booking_id, amount, method, status, transaction_id (optional)
     */
    public function recordPayment(array $data): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO payments (booking_id, amount, method, transaction_id, status, coupon_id, discount_amount)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['booking_id'],
            $data['amount'],
            $data['method'],
            $data['transaction_id'] !== '' ? $data['transaction_id'] : null,
            $data['status'],
            $data['coupon_id'] ?? null,
            $data['discount_amount'] ?? 0,
        ]);
    }

    /**
     * Most recent payment recorded for a booking, or null if none exists.
     */
    public function getForBooking(int $bookingId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM payments WHERE booking_id = ? ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([$bookingId]);
        $payment = $stmt->fetch();

        return $payment !== false ? $payment : null;
    }

    /**
     * Real, database-driven counts + totals for the stat cards.
     * Never hardcoded.
     *
     * @return array{total_count:int, successful_count:int, failed_count:int,
     *               pending_count:int, successful_amount:float}
     */
    public function getStats(): array
    {
        $counts = $this->pdo->query(
            "SELECT status, COUNT(*) AS c FROM payments GROUP BY status"
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        $successfulAmount = (float) $this->pdo->query(
            "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'successful'"
        )->fetchColumn();

        return [
            'total_count' => array_sum($counts),
            'successful_count' => (int) ($counts['successful'] ?? 0),
            'failed_count' => (int) ($counts['failed'] ?? 0),
            'pending_count' => (int) ($counts['pending'] ?? 0),
            'successful_amount' => $successfulAmount,
        ];
    }
}