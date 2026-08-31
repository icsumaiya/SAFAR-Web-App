
<?php

use PHPUnit\Framework\TestCase;

final class AnalyticsServiceTest extends TestCase
{
    private function makeStmt($fetchColumnValue)
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchColumn')->willReturn($fetchColumnValue);
        return $stmt;
    }

    public function testGetSummaryReturnsAllRealCounts(): void
    {
        // Order matches getSummary()'s internal query() calls:
        // totalRevenue, commission, agencyEarnings, users, agencies,
        // verified, packages(tour), hotels, bookings, pending, confirmed,
        // cancelled, successful payments, pending payments
        $values = [
            '500.00',
            '50.00',
            '450.00',
            '10',
            '3',
            '2',
            '7',
            '2',
            '9',
            '2',
            '5',
            '1',
            '4',
            '1'
        ];

        $stmts = array_map(
            fn($v) => $this->makeStmt($v),
            $values
        );

        $pdo = $this->createMock(PDO::class);
        $pdo->method('query')->willReturnOnConsecutiveCalls(...$stmts);

        $summary = (new AnalyticsService($pdo))->getSummary();

        $this->assertSame(500.0, $summary['total_revenue']);
        $this->assertSame(50.0, $summary['platform_commission']);
        $this->assertSame(450.0, $summary['agency_earnings']);
        $this->assertSame(10, $summary['total_users']);
        $this->assertSame(3, $summary['total_agencies']);
        $this->assertSame(2, $summary['verified_agencies']);
        $this->assertSame(7, $summary['total_packages']);
        $this->assertSame(2, $summary['total_hotels']);
        $this->assertSame(9, $summary['total_bookings']);
        $this->assertSame(2, $summary['pending_bookings']);
        $this->assertSame(5, $summary['confirmed_bookings']);
        $this->assertSame(1, $summary['cancelled_bookings']);
        $this->assertSame(4, $summary['successful_payments']);
        $this->assertSame(1, $summary['pending_payments']);
    }

    public function testGetMonthlyBookingsReturnsSeries(): void
    {
        $expected = [
            ['month' => '2026-07', 'total' => 5],
            ['month' => '2026-08', 'total' => 8]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([6]);
        $stmt->method('fetchAll')->willReturn($expected);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $this->assertSame(
            $expected,
            (new AnalyticsService($pdo))->getMonthlyBookings()
        );
    }

    public function testGetBookingStatusDistributionReturnsGroupedRows(): void
    {
        $expected = [
            ['status' => 'pending', 'total' => 2],
            ['status' => 'approved', 'total' => 5]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn($expected);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('query')->willReturn($stmt);

        $this->assertSame(
            $expected,
            (new AnalyticsService($pdo))->getBookingStatusDistribution()
        );
    }

    public function testGetPopularPackagesUsesLimit(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('bindValue')
            ->with(1, 3, PDO::PARAM_INT);
        $stmt->expects($this->once())->method('execute');
        $stmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        (new AnalyticsService($pdo))->getPopularPackages(3);
    }

    public function testGetTopAgenciesReturnsRows(): void
    {
        $expected = [
            [
                'company_name' => 'Sylhet Tours',
                'revenue' => '300.00'
            ]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn($expected);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $this->assertSame(
            $expected,
            (new AnalyticsService($pdo))->getTopAgencies()
        );
    }

    public function testGetRevenueTrendUsesDaysParam(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([30]);
        $stmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        (new AnalyticsService($pdo))->getRevenueTrend();
    }

    public function testGetMonthlyRevenueReturnsSeries(): void
    {
        $expected = [
            ['month' => '2026-07', 'total' => '150.00'],
            ['month' => '2026-08', 'total' => '300.00']
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([6]);
        $stmt->method('fetchAll')->willReturn($expected);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $this->assertSame(
            $expected,
            (new AnalyticsService($pdo))->getMonthlyRevenue()
        );
    }

    public function testGetUserGrowthUsesMonthsParam(): void
    {
        $expected = [
            ['month' => '2026-08', 'total' => 4]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([3]);
        $stmt->method('fetchAll')->willReturn($expected);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $this->assertSame(
            $expected,
            (new AnalyticsService($pdo))->getUserGrowth(3)
        );
    }

    public function testGetPopularDestinationsUsesLimit(): void
    {
        $expected = [
            [
                'location' => 'Sylhet',
                'bookings_count' => 6
            ]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('bindValue')
            ->with(1, 4, PDO::PARAM_INT);
        $stmt->expects($this->once())
            ->method('execute');
        $stmt->method('fetchAll')->willReturn($expected);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $this->assertSame(
            $expected,
            (new AnalyticsService($pdo))->getPopularDestinations(4)
        );
    }
}