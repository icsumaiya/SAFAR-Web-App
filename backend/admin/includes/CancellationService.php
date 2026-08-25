<?php
// DB operations for cancellation & refund management: traveler requests,
// admin approve/reject, and refund status updates. Extracted as a service
// so it can be unit tested with a mocked PDO instead of a real connection.

class CancellationService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Returns an error message if a cancellation cannot be requested
     * (booking not found / not owned by traveler / already has an
     * active request), or '' if the request can proceed.
     */
    public function guardRequest(int $bookingId, int $travelerId): string
    {
        $stmt = $this->pdo->prepare("SELECT status FROM bookings WHERE id = ? AND traveler_id = ?");
        $stmt->execute([$bookingId, $travelerId]);
        $booking = $stmt->fetch();

        if ($booking === false) {
            return 'Booking not found.';
        }

        if (!in_array($booking['status'], ['pending', 'approved'], true)) {
            return 'This booking cannot be cancelled.';
        }

        $stmt = $this->pdo->prepare(
            "SELECT id FROM cancellations WHERE booking_id = ? AND status IN ('requested','approved') LIMIT 1"
        );
        $stmt->execute([$bookingId]);

        if ($stmt->fetch() !== false) {
            return 'A cancellation request already exists for this booking.';
        }

        return '';
    }

    public function requestCancellation(int $bookingId, string $reason): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO cancellations (booking_id, reason) VALUES (?, ?)"
        );
        $stmt->execute([$bookingId, $reason]);
    }

    /**
     * Approve a cancellation: marks the cancellation approved, marks the
     * booking cancelled, and sets an initial refund status/amount.
     */
    public function approve(int $cancellationId, ?float $refundableAmount): void
    {
        $stmt = $this->pdo->prepare("SELECT booking_id FROM cancellations WHERE id = ?");
        $stmt->execute([$cancellationId]);
        $row = $stmt->fetch();

        if ($row === false) {
            return;
        }

        $refundStatus = $refundableAmount !== null ? 'pending' : 'not_applicable';

        $stmt = $this->pdo->prepare(
            "UPDATE cancellations SET status = 'approved', refund_status = ?, refundable_amount = ? WHERE id = ?"
        );
        $stmt->execute([$refundStatus, $refundableAmount, $cancellationId]);

        $stmt = $this->pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$row['booking_id']]);
    }

    public function reject(int $cancellationId): void
    {
        $stmt = $this->pdo->prepare("UPDATE cancellations SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$cancellationId]);
    }

    public function updateRefundStatus(int $cancellationId, string $refundStatus): void
    {
        $stmt = $this->pdo->prepare("UPDATE cancellations SET refund_status = ? WHERE id = ?");
        $stmt->execute([$refundStatus, $cancellationId]);
    }
}