<?php

use PHPUnit\Framework\TestCase;

final class AdminFacadeTest extends TestCase
{
    // ---------- UserService ----------

    public function testUserServiceGetTotalUsersReturnsIntFromFetchColumn(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchColumn')->willReturn('12'); // DB may return numeric string

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('query')
            ->with('SELECT COUNT(*) FROM users')
            ->willReturn($stmt);

        $service = new UserService($pdo);

        $this->assertSame(12, $service->getTotalUsers());
    }

    // ---------- PackageService ----------

    public function testPackageServiceGetTotalPackagesReturnsInt(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchColumn')->willReturn('30');

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('query')
            ->with('SELECT COUNT(*) FROM packages')
            ->willReturn($stmt);

        $service = new PackageService($pdo);

        $this->assertSame(30, $service->getTotalPackages());
    }

    // ---------- AgencyService ----------

    public function testAgencyServiceGetPendingVerificationsBindsPendingStatus(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['pending']);
        $stmt->method('fetchColumn')->willReturn('4');

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT COUNT(*) FROM agencies WHERE status = ?')
            ->willReturn($stmt);

        $service = new AgencyService($pdo);

        $this->assertSame(4, $service->getPendingVerifications());
    }

    // ---------- BookingService ----------

    public function testBookingServiceGetTotalBookingsReturnsInt(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchColumn')->willReturn('100');

        $pdo = $this->createMock(PDO::class);
        $pdo->method('query')->with('SELECT COUNT(*) FROM bookings')->willReturn($stmt);

        $service = new BookingService($pdo);

        $this->assertSame(100, $service->getTotalBookings());
    }

    public function testBookingServiceGetPendingBookingsBindsPendingStatus(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['pending']);
        $stmt->method('fetchColumn')->willReturn('6');

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')
            ->with('SELECT COUNT(*) FROM bookings WHERE status = ?')
            ->willReturn($stmt);

        $service = new BookingService($pdo);

        $this->assertSame(6, $service->getPendingBookings());
    }

    public function testBookingServiceGetRecentBookingsUsesDefaultLimitOfFive(): void
    {
        $rows = [['booking_id' => 1], ['booking_id' => 2]];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute');
        $stmt->method('fetchAll')->willReturn($rows);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('LIMIT 5'))
            ->willReturn($stmt);

        $service = new BookingService($pdo);

        $this->assertSame($rows, $service->getRecentBookings());
    }

    public function testBookingServiceGetRecentBookingsUsesCustomLimit(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('LIMIT 10'))
            ->willReturn($stmt);

        $service = new BookingService($pdo);
        $service->getRecentBookings(10);
    }

    public function testBookingServiceGetRecentBookingsCastsNonIntLimitSafely(): void
    {
        // Edge case: $limit is concatenated directly into SQL (not bound).
        // The (int) cast in the source must prevent SQL injection via $limit.
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->logicalAnd(
                $this->stringContains('LIMIT 7'),
                $this->logicalNot($this->stringContains('DROP'))
            ))
            ->willReturn($stmt);

        $service = new BookingService($pdo);
        // @phpstan-ignore-next-line - intentionally passing a malicious-looking string
        $service->getRecentBookings((int) '7; DROP TABLE bookings;');
    }

    // ---------- AdminFacade ----------

    public function testGetDashboardStatsAggregatesAllServiceCounts(): void
    {
        $pdo = $this->createMock(PDO::class);

        $pdo->method('query')->willReturnCallback(function (string $sql) {
            $stmt = $this->createMock(PDOStatement::class);
            if (str_contains($sql, 'FROM users')) {
                $stmt->method('fetchColumn')->willReturn('50');
            } elseif (str_contains($sql, 'FROM packages')) {
                $stmt->method('fetchColumn')->willReturn('20');
            } elseif (str_contains($sql, 'FROM bookings')) {
                $stmt->method('fetchColumn')->willReturn('75');
            }
            return $stmt;
        });

        $pdo->method('prepare')->willReturnCallback(function (string $sql) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute');
            if (str_contains($sql, 'FROM agencies')) {
                $stmt->method('fetchColumn')->willReturn('3');
            } elseif (str_contains($sql, 'FROM bookings')) {
                $stmt->method('fetchColumn')->willReturn('8');
            }
            return $stmt;
        });

        $facade = new AdminFacade($pdo);
        $stats = $facade->getDashboardStats();

        $this->assertSame([
            'users_count' => 50,
            'packages_count' => 20,
            'bookings_count' => 75,
            'pending_agencies_count' => 3,
            'pending_bookings_count' => 8,
        ], $stats);
    }

    public function testGetRecentBookingsDelegatesToBookingService(): void
    {
        $rows = [['booking_id' => 99]];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn($rows);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')
            ->with($this->stringContains('LIMIT 3'))
            ->willReturn($stmt);

        $facade = new AdminFacade($pdo);

        $this->assertSame($rows, $facade->getRecentBookings(3));
    }
}