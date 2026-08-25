<?php

use PHPUnit\Framework\TestCase;

final class AgencyDetailsServiceTest extends TestCase
{
    public function testGetAgencyReturnsRowWhenFound(): void
    {
        $expected = ['id' => 5, 'company_name' => 'Sylhet Tours', 'name' => 'Sumaiya', 'email' => 's@x.com'];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([5]);
        $stmt->method('fetch')->willReturn($expected);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('FROM agencies a JOIN users u'))
            ->willReturn($stmt);

        $service = new AgencyDetailsService($pdo);
        $this->assertSame($expected, $service->getAgency(5));
    }

    public function testGetAgencyReturnsNullWhenNotFound(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $service = new AgencyDetailsService($pdo);
        $this->assertNull($service->getAgency(999));
    }

    public function testGetPackagesReturnsFetchAllResult(): void
    {
        $expected = [['id' => 1, 'title' => 'Beach Tour']];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([5]);
        $stmt->method('fetchAll')->willReturn($expected);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('FROM packages WHERE agency_id'))
            ->willReturn($stmt);

        $service = new AgencyDetailsService($pdo);
        $this->assertSame($expected, $service->getPackages(5));
    }

    public function testGetBookingsReturnsFetchAllResult(): void
    {
        $expected = [['id' => 1, 'traveler_name' => 'Alice', 'package_title' => 'Beach Tour']];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([5]);
        $stmt->method('fetchAll')->willReturn($expected);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('WHERE p.agency_id = ?'))
            ->willReturn($stmt);

        $service = new AgencyDetailsService($pdo);
        $this->assertSame($expected, $service->getBookings(5));
    }

    public function testGetRevenueReturnsFloatFromApprovedBookingsOnly(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([5]);
        $stmt->method('fetchColumn')->willReturn('15250.00');

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->logicalAnd(
                $this->stringContains("b.status = 'approved'"),
                $this->stringContains('SUM(p.price)')
            ))
            ->willReturn($stmt);

        $service = new AgencyDetailsService($pdo);
        $revenue = $service->getRevenue(5);

        $this->assertIsFloat($revenue);
        $this->assertSame(15250.00, $revenue);
    }

    public function testGetRevenueReturnsZeroWhenNoApprovedBookings(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchColumn')->willReturn('0');

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $service = new AgencyDetailsService($pdo);
        $this->assertSame(0.0, $service->getRevenue(5));
    }
}