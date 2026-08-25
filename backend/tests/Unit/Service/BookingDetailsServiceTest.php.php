<?php

use PHPUnit\Framework\TestCase;

final class BookingDetailsServiceTest extends TestCase
{
    public function testGetBookingReturnsRowWhenFound(): void
    {
        $expected = [
            'id' => 42,
            'traveler_name' => 'Alice',
            'traveler_email' => 'alice@x.com',
            'package_title' => 'Cox\'s Bazar Beach Tour',
            'company_name' => 'Sylhet Tours',
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([42]);
        $stmt->method('fetch')->willReturn($expected);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('FROM bookings b'))
            ->willReturn($stmt);

        $service = new BookingDetailsService($pdo);
        $this->assertSame($expected, $service->getBooking(42));
    }

    public function testGetBookingReturnsNullWhenNotFound(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $service = new BookingDetailsService($pdo);
        $this->assertNull($service->getBooking(999));
    }

    public function testGetBookingJoinsTravelerPackageAndAgency(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn(['id' => 1]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->logicalAnd(
                $this->stringContains('JOIN users u ON b.traveler_id = u.id'),
                $this->stringContains('JOIN packages p ON b.package_id = p.id'),
                $this->stringContains('JOIN agencies a ON p.agency_id = a.id'),
                $this->stringContains('JOIN users au ON a.user_id = au.id')
            ))
            ->willReturn($stmt);

        $service = new BookingDetailsService($pdo);
        $service->getBooking(1);
    }

    public function testFormatReferencePadsIdToSixDigits(): void
    {
        $this->assertSame('BKG-000042', BookingDetailsService::formatReference(42));
        $this->assertSame('BKG-000001', BookingDetailsService::formatReference(1));
    }

    public function testFormatReferenceDoesNotTruncateLargeIds(): void
    {
        $this->assertSame('BKG-1234567', BookingDetailsService::formatReference(1234567));
    }
}