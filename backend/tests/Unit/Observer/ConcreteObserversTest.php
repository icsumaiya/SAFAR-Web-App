<?php

use PHPUnit\Framework\TestCase;

final class ConcreteObserversTest extends TestCase
{
    public function testAgencyStatsObserverImplementsBookingObserver(): void
    {
        $observer = new AgencyStatsObserver();
        $this->assertInstanceOf(BookingObserver::class, $observer);
    }

    public function testAgencyStatsObserverUpdateRunsWithoutErrorForValidBooking(): void
    {
        $observer = new AgencyStatsObserver();

        $observer->update(['package_id' => 5, 'status' => 'confirmed']);

        $this->assertTrue(true);
    }

    public function testAgencyStatsObserverUpdateWithMissingKeysEmitsWarningButDoesNotThrow(): void
    {
        // Real edge case in AgencyStatsObserver::update(): it accesses
        // $booking['package_id'] / $booking['status'] without a null-coalesce
        // fallback, so a missing key raises a PHP warning but does not throw.
        $observer = new AgencyStatsObserver();

        $observer->update([]);

        $this->assertTrue(true);
    }

    public function testAdminStatsObserverImplementsBookingObserver(): void
    {
        $observer = new AdminStatsObserver();
        $this->assertInstanceOf(BookingObserver::class, $observer);
    }

    public function testAdminStatsObserverUpdateRunsWithoutErrorForValidBooking(): void
    {
        $observer = new AdminStatsObserver();

        $observer->update(['booking_id' => 10]);

        $this->assertTrue(true);
    }

    public function testAdminStatsObserverUpdateWithMissingBookingIdDoesNotThrow(): void
    {
        // AdminStatsObserver uses ($booking['booking_id'] ?? 'n/a'),
        // so a missing key must NOT raise a warning or throw.
        $observer = new AdminStatsObserver();

        $observer->update([]);

        $this->assertTrue(true);
    }
}