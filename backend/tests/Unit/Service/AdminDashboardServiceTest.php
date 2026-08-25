<?php

use PHPUnit\Framework\TestCase;

final class AdminDashboardServiceTest extends TestCase
{
    public function testGetStatsReturnsAllFiveCountsAsIntegers(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchColumn')->willReturn('3');

        $pdo = $this->createMock(PDO::class);
        $pdo->method('query')->willReturn($stmt);

        $service = new AdminDashboardService($pdo);
        $stats = $service->getStats();

        $this->assertSame(
            ['users_count', 'packages_count', 'bookings_count', 'pending_agencies_count', 'pending_bookings_count'],
            array_keys($stats)
        );
        foreach ($stats as $key => $value) {
            $this->assertIsInt($value, "$key should be cast to int");
            $this->assertSame(3, $value);
        }
    }

    public function testGetRecentBookingsReturnsFetchAllResult(): void
    {
        $expected = [
            ['id' => 1, 'traveler_name' => 'Alice', 'package_title' => 'Beach Tour'],
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn($expected);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('query')
            ->with($this->stringContains('LIMIT 5'))
            ->willReturn($stmt);

        $service = new AdminDashboardService($pdo);
        $result = $service->getRecentBookings(5);

        $this->assertSame($expected, $result);
    }

    public function testGetRecentUsersReturnsFetchAllResult(): void
    {
        $expected = [
            ['name' => 'Bob', 'email' => 'bob@example.com', 'role' => 'traveler'],
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn($expected);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('query')
            ->with($this->stringContains('ORDER BY created_at DESC LIMIT 5'))
            ->willReturn($stmt);

        $service = new AdminDashboardService($pdo);
        $result = $service->getRecentUsers(5);

        $this->assertSame($expected, $result);
    }

    public function testGetRecentBookingsRespectsCustomLimit(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('query')
            ->with($this->stringContains('LIMIT 10'))
            ->willReturn($stmt);

        $service = new AdminDashboardService($pdo);
        $service->getRecentBookings(10);
    }
}