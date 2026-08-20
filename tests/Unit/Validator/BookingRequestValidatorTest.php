<?php

use PHPUnit\Framework\TestCase;

final class BookingRequestValidatorTest extends TestCase
{
    public function testValidRequestReturnsEmptyString(): void
    {
        $result = BookingRequestValidator::validate('POST', true, 'traveler', '5');
        $this->assertSame('', $result);
    }

    public function testNonPostMethodFails(): void
    {
        $result = BookingRequestValidator::validate('GET', true, 'traveler', '5');
        $this->assertSame('Invalid request method.', $result);
    }

    public function testNotLoggedInFails(): void
    {
        $result = BookingRequestValidator::validate('POST', false, null, '5');
        $this->assertSame('You must be logged in as a traveler to book a tour.', $result);
    }

    public function testLoggedInButWrongRoleFails(): void
    {
        $result = BookingRequestValidator::validate('POST', true, 'agency', '5');
        $this->assertSame('You must be logged in as a traveler to book a tour.', $result);
    }

    public function testMissingPackageIdFails(): void
    {
        $result = BookingRequestValidator::validate('POST', true, 'traveler', null);
        $this->assertSame('Package ID is missing.', $result);
    }

    public function testEmptyStringPackageIdFails(): void
    {
        $result = BookingRequestValidator::validate('POST', true, 'traveler', '');
        $this->assertSame('Package ID is missing.', $result);
    }

    public function testZeroPackageIdIsTreatedAsMissing(): void
    {
        $result = BookingRequestValidator::validate('POST', true, 'traveler', '0');
        $this->assertSame('Package ID is missing.', $result);
    }

    public function testMethodCheckHappensBeforeAuthCheck(): void
    {
        $result = BookingRequestValidator::validate('GET', false, null, null);
        $this->assertSame('Invalid request method.', $result);
    }
}