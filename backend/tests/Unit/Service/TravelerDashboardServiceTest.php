<?php

namespace Tests\Unit\Service;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../admin/includes/TravelerDashboardService.php';

class TravelerDashboardServiceTest extends TestCase
{
    public function testGetOverviewMapsCountsCorrectly(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetch')->willReturn([
            'total' => '5',
            'upcoming' => '2',
            'pending' => '1',
            'completed' => '1',
            'cancelled' => '1',
        ]);

        $pdo = $this->createMock(\PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $service = new \TravelerDashboardService($pdo);
        $result = $service->getOverview(7);

        $this->assertSame(
            ['total' => 5, 'upcoming' => 2, 'pending' => 1, 'completed' => 1, 'cancelled' => 1],
            $result
        );
    }

    public function testGetOverviewHandlesNoBookingsYet(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetch')->willReturn([
            'total' => null,
            'upcoming' => null,
            'pending' => null,
            'completed' => null,
            'cancelled' => null,
        ]);

        $pdo = $this->createMock(\PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $service = new \TravelerDashboardService($pdo);
        $result = $service->getOverview(7);

        $this->assertSame(
            ['total' => 0, 'upcoming' => 0, 'pending' => 0, 'completed' => 0, 'cancelled' => 0],
            $result
        );
    }

    public function testGetUpcomingTripsReturnsRows(): void
    {
        $rows = [
            ['booking_id' => 1, 'status' => 'approved', 'check_in' => '2026-09-01', 'check_out' => '2026-09-05', 'title' => 'Bali Trip', 'location' => 'Bali', 'type' => 'tour', 'company_name' => 'Safar Agency'],
        ];

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchAll')->willReturn($rows);

        $pdo = $this->createMock(\PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("status = 'approved'"))
            ->willReturn($stmt);

        $service = new \TravelerDashboardService($pdo);
        $this->assertSame($rows, $service->getUpcomingTrips(7));
    }

    public function testGetBookingsByCategoryPending(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([['booking_id' => 2, 'status' => 'pending']]);

        $pdo = $this->createMock(\PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("b.status = 'pending'"))
            ->willReturn($stmt);

        $service = new \TravelerDashboardService($pdo);
        $result = $service->getBookingsByCategory(7, 'pending');

        $this->assertCount(1, $result);
    }

    public function testGetBookingsByCategoryUnknownReturnsEmptyWithoutQuery(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $pdo->expects($this->never())->method('prepare');

        $service = new \TravelerDashboardService($pdo);
        $this->assertSame([], $service->getBookingsByCategory(7, 'not-a-real-category'));
    }

    public function testGetPaymentHistoryReturnsRows(): void
    {
        $rows = [['payment_id' => 5, 'amount' => '100.00', 'method' => 'bkash']];

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchAll')->willReturn($rows);

        $pdo = $this->createMock(\PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $service = new \TravelerDashboardService($pdo);
        $this->assertSame($rows, $service->getPaymentHistory(7));
    }

    public function testGetMyReviewsReturnsRows(): void
    {
        $rows = [['id' => 3, 'booking_id' => 1, 'rating' => 5, 'status' => 'visible']];

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchAll')->willReturn($rows);

        $pdo = $this->createMock(\PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $service = new \TravelerDashboardService($pdo);
        $this->assertSame($rows, $service->getMyReviews(7));
    }
}