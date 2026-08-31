<?php
// Validates wishlist add/remove requests. Pure logic, no DB/session.

class WishlistValidator
{
    public static function validate(array $data): string
    {
        $packageId = $data['package_id'] ?? null;

        if (empty($packageId) || !is_numeric($packageId) || (int) $packageId <= 0) {
            return 'A valid package must be specified.';
        }

        return '';
    }
}