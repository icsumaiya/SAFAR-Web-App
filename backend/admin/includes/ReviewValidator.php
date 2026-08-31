<?php
// Server-side validation for traveler review submissions and admin
// moderation actions. Pure logic, no DB/session, so it can be unit
// tested in isolation.

class ReviewValidator
{
    public const MIN_RATING = 1;
    public const MAX_RATING = 5;
    public const VALID_MODERATION_STATUSES = ['visible', 'hidden'];

    public static function validateSubmission(array $data): string
    {
        $bookingId = $data['booking_id'] ?? null;
        $rating = $data['rating'] ?? null;
        $comment = trim($data['comment'] ?? '');

        if (empty($bookingId)) {
            return 'A booking must be specified.';
        }

        if ($rating === null || $rating === '' || !ctype_digit((string) $rating)) {
            return 'Rating must be a whole number between 1 and 5.';
        }

        $rating = (int) $rating;
        if ($rating < self::MIN_RATING || $rating > self::MAX_RATING) {
            return 'Rating must be between 1 and 5.';
        }

        if (mb_strlen($comment) > 1000) {
            return 'Comment is too long (max 1000 characters).';
        }

        return '';
    }

    public static function validateModerationStatus(string $status): string
    {
        if (!in_array($status, self::VALID_MODERATION_STATUSES, true)) {
            return 'Please select a valid review status.';
        }

        return '';
    }
}