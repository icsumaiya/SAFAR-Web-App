<?php

use PHPUnit\Framework\TestCase;

final class FeaturedPackageValidatorTest extends TestCase
{
    public function testValidOfferPasses(): void
    {
        $error = FeaturedPackageValidator::validateOffer([
            'offer_discount_percentage' => 15,
            'offer_expiry' => date('Y-m-d', strtotime('+7 days')),
        ]);
        $this->assertSame('', $error);
    }

    public function testZeroDiscountFails(): void
    {
        $error = FeaturedPackageValidator::validateOffer([
            'offer_discount_percentage' => 0,
            'offer_expiry' => date('Y-m-d', strtotime('+7 days')),
        ]);
        $this->assertNotSame('', $error);
    }

    public function testOverHundredDiscountFails(): void
    {
        $error = FeaturedPackageValidator::validateOffer([
            'offer_discount_percentage' => 150,
            'offer_expiry' => date('Y-m-d', strtotime('+7 days')),
        ]);
        $this->assertNotSame('', $error);
    }

    public function testPastExpiryDateFails(): void
    {
        $error = FeaturedPackageValidator::validateOffer([
            'offer_discount_percentage' => 10,
            'offer_expiry' => date('Y-m-d', strtotime('-1 day')),
        ]);
        $this->assertNotSame('', $error);
    }

    public function testInvalidDateFormatFails(): void
    {
        $error = FeaturedPackageValidator::validateOffer([
            'offer_discount_percentage' => 10,
            'offer_expiry' => 'not-a-date',
        ]);
        $this->assertNotSame('', $error);
    }
}