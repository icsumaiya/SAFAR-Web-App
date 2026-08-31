<?php

use PHPUnit\Framework\TestCase;

final class CouponValidatorTest extends TestCase
{
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'code' => 'SUMMER25',
            'discount_type' => 'percentage',
            'discount_value' => '25',
            'min_booking_amount' => '1000',
            'max_discount_amount' => '2000',
            'start_date' => '2026-01-01',
            'expiry_date' => '2026-12-31',
            'usage_limit' => '100',
            'per_user_limit' => '1',
        ], $overrides);
    }

    // ---- validateCouponData ----

    public function testValidDataPasses(): void
    {
        $this->assertSame('', CouponValidator::validateCouponData($this->validData()));
    }

    public function testEmptyCodeFails(): void
    {
        $this->assertSame(
            'Please provide a valid coupon code (max 50 characters).',
            CouponValidator::validateCouponData($this->validData(['code' => '']))
        );
    }

    public function testTooLongCodeFails(): void
    {
        $this->assertSame(
            'Please provide a valid coupon code (max 50 characters).',
            CouponValidator::validateCouponData($this->validData(['code' => str_repeat('A', 51)]))
        );
    }

    public function testInvalidDiscountTypeFails(): void
    {
        $this->assertSame(
            'Please select a valid discount type.',
            CouponValidator::validateCouponData($this->validData(['discount_type' => 'flat']))
        );
    }

    public function testEmptyDiscountValueFails(): void
    {
        $this->assertSame(
            'Discount value must be a positive number.',
            CouponValidator::validateCouponData($this->validData(['discount_value' => '']))
        );
    }

    public function testNonNumericDiscountValueFails(): void
    {
        $this->assertSame(
            'Discount value must be a positive number.',
            CouponValidator::validateCouponData($this->validData(['discount_value' => 'abc']))
        );
    }

    public function testZeroDiscountValueFails(): void
    {
        $this->assertSame(
            'Discount value must be a positive number.',
            CouponValidator::validateCouponData($this->validData(['discount_value' => '0']))
        );
    }

    public function testPercentageOverHundredFails(): void
    {
        $this->assertSame(
            'Percentage discount cannot exceed 100.',
            CouponValidator::validateCouponData($this->validData(['discount_value' => '150']))
        );
    }

    public function testFixedDiscountOverHundredPasses(): void
    {
        $this->assertSame(
            '',
            CouponValidator::validateCouponData($this->validData([
                'discount_type' => 'fixed',
                'discount_value' => '5000',
            ]))
        );
    }

    public function testNegativeMinBookingAmountFails(): void
    {
        $this->assertSame(
            'Minimum booking amount must be zero or more.',
            CouponValidator::validateCouponData($this->validData(['min_booking_amount' => '-10']))
        );
    }

    public function testNegativeMaxDiscountAmountFails(): void
    {
        $this->assertSame(
            'Maximum discount amount must be zero or more.',
            CouponValidator::validateCouponData($this->validData(['max_discount_amount' => '-5']))
        );
    }

    public function testEmptyMaxDiscountAmountIsAllowed(): void
    {
        $this->assertSame(
            '',
            CouponValidator::validateCouponData($this->validData(['max_discount_amount' => '']))
        );
    }

    public function testInvalidStartDateFails(): void
    {
        $this->assertSame(
            'Please provide valid start and expiry dates.',
            CouponValidator::validateCouponData($this->validData(['start_date' => 'not-a-date']))
        );
    }

    public function testInvalidExpiryDateFails(): void
    {
        $this->assertSame(
            'Please provide valid start and expiry dates.',
            CouponValidator::validateCouponData($this->validData(['expiry_date' => '']))
        );
    }

    public function testStartDateAfterExpiryDateFails(): void
    {
        $this->assertSame(
            'Start date must be before or equal to the expiry date.',
            CouponValidator::validateCouponData($this->validData([
                'start_date' => '2026-12-31',
                'expiry_date' => '2026-01-01',
            ]))
        );
    }

    public function testStartDateEqualToExpiryDatePasses(): void
    {
        $this->assertSame(
            '',
            CouponValidator::validateCouponData($this->validData([
                'start_date' => '2026-06-01',
                'expiry_date' => '2026-06-01',
            ]))
        );
    }

    public function testNonIntegerUsageLimitFails(): void
    {
        $this->assertSame(
            'Usage limit must be a positive whole number.',
            CouponValidator::validateCouponData($this->validData(['usage_limit' => '10.5']))
        );
    }

    public function testZeroUsageLimitFails(): void
    {
        $this->assertSame(
            'Usage limit must be a positive whole number.',
            CouponValidator::validateCouponData($this->validData(['usage_limit' => '0']))
        );
    }

    public function testEmptyUsageLimitIsAllowed(): void
    {
        $this->assertSame(
            '',
            CouponValidator::validateCouponData($this->validData(['usage_limit' => '']))
        );
    }

    public function testZeroPerUserLimitFails(): void
    {
        $this->assertSame(
            'Per-user limit must be a positive whole number.',
            CouponValidator::validateCouponData($this->validData(['per_user_limit' => '0']))
        );
    }

    // ---- validateForUse ----

    private function activeCoupon(array $overrides = []): array
    {
        return array_merge([
            'is_active' => 1,
            'start_date' => '2020-01-01',
            'expiry_date' => '2099-12-31',
            'min_booking_amount' => '1000',
            'usage_limit' => null,
            'per_user_limit' => null,
        ], $overrides);
    }

    public function testValidateForUseFailsWhenCouponNotFound(): void
    {
        $this->assertSame(
            'Invalid coupon code.',
            CouponValidator::validateForUse(null, 5000.0, 0, 0)
        );
    }

    public function testValidateForUseFailsWhenInactive(): void
    {
        $coupon = $this->activeCoupon(['is_active' => 0]);
        $this->assertSame(
            'This coupon is inactive.',
            CouponValidator::validateForUse($coupon, 5000.0, 0, 0)
        );
    }

    public function testValidateForUseFailsWhenNotYetStarted(): void
    {
        $coupon = $this->activeCoupon(['start_date' => '2099-01-01']);
        $this->assertSame(
            'This coupon is not active yet.',
            CouponValidator::validateForUse($coupon, 5000.0, 0, 0)
        );
    }

    public function testValidateForUseFailsWhenExpired(): void
    {
        $coupon = $this->activeCoupon(['expiry_date' => '2020-01-01']);
        $this->assertSame(
            'This coupon has expired.',
            CouponValidator::validateForUse($coupon, 5000.0, 0, 0)
        );
    }

    public function testValidateForUseFailsWhenBelowMinBookingAmount(): void
    {
        $coupon = $this->activeCoupon(['min_booking_amount' => '5000']);
        $this->assertSame(
            'Minimum booking amount not met for this coupon.',
            CouponValidator::validateForUse($coupon, 1000.0, 0, 0)
        );
    }

    public function testValidateForUseFailsWhenUsageLimitReached(): void
    {
        $coupon = $this->activeCoupon(['usage_limit' => 10]);
        $this->assertSame(
            'This coupon has reached its usage limit.',
            CouponValidator::validateForUse($coupon, 5000.0, 10, 0)
        );
    }

    public function testValidateForUseFailsWhenPerUserLimitReached(): void
    {
        $coupon = $this->activeCoupon(['per_user_limit' => 1]);
        $this->assertSame(
            'You have already used this coupon the maximum number of times.',
            CouponValidator::validateForUse($coupon, 5000.0, 0, 1)
        );
    }

    public function testValidateForUsePassesWhenEligible(): void
    {
        $coupon = $this->activeCoupon(['usage_limit' => 10, 'per_user_limit' => 2]);
        $this->assertSame(
            '',
            CouponValidator::validateForUse($coupon, 5000.0, 3, 0)
        );
    }

    // ---- calculateDiscount ----

    public function testCalculateDiscountPercentage(): void
    {
        $coupon = ['discount_type' => 'percentage', 'discount_value' => '10', 'max_discount_amount' => null];
        $this->assertSame(500.0, CouponValidator::calculateDiscount($coupon, 5000.0));
    }

    public function testCalculateDiscountFixed(): void
    {
        $coupon = ['discount_type' => 'fixed', 'discount_value' => '300', 'max_discount_amount' => null];
        $this->assertSame(300.0, CouponValidator::calculateDiscount($coupon, 5000.0));
    }

    public function testCalculateDiscountPercentageCappedByMaxDiscount(): void
    {
        $coupon = ['discount_type' => 'percentage', 'discount_value' => '50', 'max_discount_amount' => '1000'];
        $this->assertSame(1000.0, CouponValidator::calculateDiscount($coupon, 5000.0));
    }

    public function testCalculateDiscountNeverExceedsBaseAmount(): void
    {
        $coupon = ['discount_type' => 'fixed', 'discount_value' => '9000', 'max_discount_amount' => null];
        $this->assertSame(5000.0, CouponValidator::calculateDiscount($coupon, 5000.0));
    }

    public function testCalculateDiscountRoundsToTwoDecimals(): void
    {
        $coupon = ['discount_type' => 'percentage', 'discount_value' => '33.333', 'max_discount_amount' => null];
        $this->assertSame(333.33, CouponValidator::calculateDiscount($coupon, 1000.0));
    }
}