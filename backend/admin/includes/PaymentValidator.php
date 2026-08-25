<?php
// Validates the admin "Record Payment" form input. Pure logic, no DB/session/
// header side effects, so it can be unit tested in isolation.

class PaymentValidator
{
    public const VALID_METHODS = ['cash', 'bkash', 'nagad', 'bank_transfer', 'card'];
    public const VALID_STATUSES = ['pending', 'successful', 'failed'];

    /**
     * @param array $data Expected keys: booking_id, amount, method, status, transaction_id (optional)
     * @return string Empty string if valid, otherwise a human-readable error message.
     */
    public static function validate(array $data): string
    {
        $bookingId = $data['booking_id'] ?? null;
        $amount = $data['amount'] ?? '';
        $method = $data['method'] ?? '';
        $status = $data['status'] ?? '';

        if (empty($bookingId)) {
            return 'A booking must be specified.';
        }

        if ($amount === '' || !is_numeric($amount) || (float) $amount <= 0) {
            return 'Amount must be a valid positive number.';
        }

        if (!in_array($method, self::VALID_METHODS, true)) {
            return 'Please select a valid payment method.';
        }

        if (!in_array($status, self::VALID_STATUSES, true)) {
            return 'Please select a valid payment status.';
        }

        return '';
    }
}