<?php
// Validates coupon create/edit form data, and validates whether an
// existing coupon can be applied to a given booking amount/user. Also
// computes the discount amount (pure math, no DB), so all of this can be
// unit tested in isolation.

class CouponValidator
{
    public const VALID_TYPES = ['percentage', 'fixed'];

    public static function validateCouponData(array $data): string
    {
        $code = trim($data['code'] ?? '');
        $type = $data['discount_type'] ?? '';
        $value = $data['discount_value'] ?? '';
        $minAmount = $data['min_booking_amount'] ?? 0;
        $maxDiscount = $data['max_discount_amount'] ?? '';
        $startDate = $data['start_date'] ?? '';
        $expiryDate = $data['expiry_date'] ?? '';
        $usageLimit = $data['usage_limit'] ?? '';
        $perUserLimit = $data['per_user_limit'] ?? '';

        if ($code === '' || mb_strlen($code) > 50) {
            return 'Please provide a valid coupon code (max 50 characters).';
        }

        if (!in_array($type, self::VALID_TYPES, true)) {
            return 'Please select a valid discount type.';
        }

        if ($value === '' || !is_numeric($value) || (float) $value <= 0) {
            return 'Discount value must be a positive number.';
        }

        if ($type === 'percentage' && (float) $value > 100) {
            return 'Percentage discount cannot exceed 100.';
        }

        if ($minAmount !== '' && (!is_numeric($minAmount) || (float) $minAmount < 0)) {
            return 'Minimum booking amount must be zero or more.';
        }

        if ($maxDiscount !== '' && $maxDiscount !== null && (!is_numeric($maxDiscount) || (float) $maxDiscount < 0)) {
            return 'Maximum discount amount must be zero or more.';
        }

        if ($startDate === '' || $expiryDate === '' || strtotime($startDate) === false || strtotime($expiryDate) === false) {
            return 'Please provide valid start and expiry dates.';
        }

        if (strtotime($startDate) > strtotime($expiryDate)) {
            return 'Start date must be before or equal to the expiry date.';
        }

        if ($usageLimit !== '' && $usageLimit !== null && (!ctype_digit((string) $usageLimit) || (int) $usageLimit <= 0)) {
            return 'Usage limit must be a positive whole number.';
        }

        if ($perUserLimit !== '' && $perUserLimit !== null && (!ctype_digit((string) $perUserLimit) || (int) $perUserLimit <= 0)) {
            return 'Per-user limit must be a positive whole number.';
        }

        return '';
    }

    /**
     * @param array|null $coupon The coupon row (null if code not found)
     */
    public static function validateForUse(?array $coupon, float $bookingAmount, int $usageCountTotal, int $usageCountForUser): string
    {
        if ($coupon === null) {
            return 'Invalid coupon code.';
        }

        if (!(int) $coupon['is_active']) {
            return 'This coupon is inactive.';
        }

        $today = date('Y-m-d');

        if ($today < $coupon['start_date']) {
            return 'This coupon is not active yet.';
        }

        if ($today > $coupon['expiry_date']) {
            return 'This coupon has expired.';
        }

        if ($bookingAmount < (float) $coupon['min_booking_amount']) {
            return 'Minimum booking amount not met for this coupon.';
        }

        if ($coupon['usage_limit'] !== null && $usageCountTotal >= (int) $coupon['usage_limit']) {
            return 'This coupon has reached its usage limit.';
        }

        if ($coupon['per_user_limit'] !== null && $usageCountForUser >= (int) $coupon['per_user_limit']) {
            return 'You have already used this coupon the maximum number of times.';
        }

        return '';
    }

    public static function calculateDiscount(array $coupon, float $baseAmount): float
    {
        if ($coupon['discount_type'] === 'percentage') {
            $discount = $baseAmount * ((float) $coupon['discount_value'] / 100);
        } else {
            $discount = (float) $coupon['discount_value'];
        }

        if ($coupon['max_discount_amount'] !== null) {
            $discount = min($discount, (float) $coupon['max_discount_amount']);
        }

        $discount = min($discount, $baseAmount);

        return round($discount, 2);
    }
}