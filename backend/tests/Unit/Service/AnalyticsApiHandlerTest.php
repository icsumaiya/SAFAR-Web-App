<?php

use PHPUnit\Framework\TestCase;

final class AnalyticsApiHandlerTest extends TestCase
{
    public function testHandleAssemblesSummaryAndAllCharts(): void
    {
        $queryStmt = $this->createStub(PDOStatement::class);
        $queryStmt->method('fetchColumn')->willReturn(0);

        $prepStmt = $this->createStub(PDOStatement::class);
        $prepStmt->method('execute')->willReturn(true);
        $prepStmt->method('fetchAll')->willReturn([]);
        $prepStmt->method('bindValue')->willReturn(true);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('query')->willReturn($queryStmt);
        $pdo->method('prepare')->willReturn($prepStmt);

        $handler = new AnalyticsApiHandler($pdo);
        $result = $handler->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('charts', $result);

        foreach ([
            'monthly_bookings',
            'monthly_revenue',
            'booking_status_distribution',
            'user_growth',
            'popular_packages',
            'popular_destinations',
            'top_agencies',
            'revenue_trend',
        ] as $key) {
            $this->assertArrayHasKey($key, $result['charts']);
        }
    }
}