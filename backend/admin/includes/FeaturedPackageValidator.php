<?php
// Validates the admin "Set Special Offer" form. Pure logic, no DB.

class FeaturedPackageValidator
{
    public static function validateOffer(array $data): string
    {
        $discount = $data['offer_discount_percentage'] ?? '';
        $expiry = $data['offer_expiry'] ?? '';

        if ($discount === '' || !is_numeric($discount) || (float) $discount <= 0 || (float) $discount > 100) {
            return 'Discount percentage must be between 0 and 100.';
        }

        if ($expiry === '' || strtotime($expiry) === false) {
            return 'Please provide a valid offer expiry date.';
        }

        if (strtotime($expiry) < strtotime(date('Y-m-d'))) {
            return 'Offer expiry date must be in the future.';
        }

        return '';
    }
}