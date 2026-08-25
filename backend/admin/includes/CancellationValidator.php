<?php
// Server-side validation for cancellation requests and admin actions on them.
// Pure logic, no DB/session, so it can be unit tested in isolation.

class CancellationValidator
{
    public const VALID_STATUSES = ['requested', 'approved', 'rejected', 'completed'];
    public const VALID_REFUND_STATUSES = ['not_applicable', 'pending', 'processing', 'refunded', 'rejected'];

    public static function validateRequest(array $data): string
    {
        $bookingId = $data['booking_id'] ?? null;
        $reason = trim($data['reason'] ?? '');

        if (empty($bookingId)) {
            return 'A booking must be specified.';
        }

        if ($reason === '') {
            return 'Please provide a reason for cancellation.';
        }

        if (mb_strlen($reason) > 1000) {
            return 'Reason is too long (max 1000 characters).';
        }

        return '';
    }

    public static function validateRefundStatus(string $refundStatus): string
    {
        if (!in_array($refundStatus, self::VALID_REFUND_STATUSES, true)) {
            return 'Please select a valid refund status.';
        }

        return '';
    }
}