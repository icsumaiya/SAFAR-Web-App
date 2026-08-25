<?php
// Validates the admin "Set Commission Percentage" form. Pure logic, no
// DB/session, so it can be unit tested in isolation.

class CommissionValidator
{
    public static function validatePercentage($value): string
    {
        if ($value === '' || $value === null || !is_numeric($value)) {
            return 'Commission percentage must be a number.';
        }

        $percentage = (float) $value;

        if ($percentage < 0 || $percentage > 100) {
            return 'Commission percentage must be between 0 and 100.';
        }

        return '';
    }
}