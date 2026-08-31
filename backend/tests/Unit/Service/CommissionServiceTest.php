<?php

use PHPUnit\Framework\TestCase;

final class CommissionServiceTest extends TestCase
{
    public function testSyncCommissionsInsertsRecordForEachPendingPaymentAtCurrentRate(): void
    {
        $percentageStmt = $this->createMock(PDOStatement::class);
        $percentageStmt->method('fetchColumn')->willReturn('10');

        $pendingStmt = $this->createMock(PDOStatement::class);
        $pendingStmt->method('fetchAll')->willReturn([
            ['payment_id' => 101, 'booking_id' => 5, 'amount' => '1000.00', 'agency_id' => 3],
            ['payment_id' => 102, 'booking_id' => 6, 'amount' => '500.00', 'agency_id' => 4],
        ]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('query')->willReturnOnConsecutiveCalls($percentageStmt, $pendingStmt);

        $insertStmt = $this->createMock(PDOStatement::class);
        $insertStmt->expects($this->exactly(2))
            ->method('execute')
            ->willReturnOnConsecutiveCalls(...array_fill(0, 2, true));

        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO commissions'))
            ->willReturn($insertStmt);

        $synced = (new CommissionService($pdo))->syncCommissions();

        $this->assertSame(2, $synced);
    }

    public function testSyncCommissionsComputesCommissionAndAgencyEarningCorrectly(): void
    {
        $percentageStmt = $this->createMock(PDOStatement::class);
        $percentageStmt->method('fetchColumn')->willReturn('20');

        $pendingStmt = $this->createMock(PDOStatement::class);
        $pendingStmt->method('fetchAll')->willReturn([
            ['payment_id' => 101, 'booking_id' => 5, 'amount' => '1000.00', 'agency_id' => 3],
        ]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('query')->willReturnOnConsecutiveCalls($percentageStmt, $pendingStmt);

        $insertStmt = $this->createMock(PDOStatement::class);
        $insertStmt->expects($this->once())
            ->method('execute')
            ->with([5, 101, 3, 1000.0, 20.0, 200.0, 800.0]);

        $pdo->method('prepare')->willReturn($insertStmt);

        (new CommissionService($pdo))->syncCommissions();
    }

    public function testSyncCommissionsReturnsZeroWhenNothingPending(): void
    {
        $percentageStmt = $this->createMock(PDOStatement::class);
        $percentageStmt->method('fetchColumn')->willReturn('10');

        $pendingStmt = $this->createMock(PDOStatement::class);
        $pendingStmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('query')->willReturnOnConsecutiveCalls($percentageStmt, $pendingStmt);
        $pdo->expects($this->never())->method('prepare');

        $synced = (new CommissionService($pdo))->syncCommissions();

        $this->assertSame(0, $synced);
    }

    public function testGetSummaryReturnsTotals(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn([
            'total_sales' => '15000.00',
            'total_commission' => '1500.00',
            'total_agency_earnings' => '13500.00',
        ]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('query')->willReturn($stmt);

        $summary = (new CommissionService($pdo))->getSummary();

        $this->assertSame(15000.0, $summary['total_sales']);
        $this->assertSame(1500.0, $summary['total_commission']);
        $this->assertSame(13500.0, $summary['total_agency_earnings']);
    }

    public function testGetByAgencyReturnsGroupedRows(): void
    {
        $rows = [
            ['company_name' => 'Sylhet Tours', 'gross' => '5000.00', 'commission' => '500.00', 'earning' => '4500.00'],
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn($rows);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('query')->willReturn($stmt);

        $result = (new CommissionService($pdo))->getByAgency();

        $this->assertSame($rows, $result);
    }

    public function testGetPercentageReturnsStoredValue(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchColumn')->willReturn('15.5');

        $pdo = $this->createMock(PDO::class);
        $pdo->method('query')->willReturn($stmt);

        $this->assertSame(15.5, (new CommissionService($pdo))->getPercentage());
    }

    public function testGetPercentageFallsBackToDefaultWhenNoSettingsRow(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchColumn')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('query')->willReturn($stmt);

        $this->assertSame(10.0, (new CommissionService($pdo))->getPercentage());
    }

    public function testUpdatePercentageUpsertsSettingsRow(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([12.5]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('ON DUPLICATE KEY UPDATE'))
            ->willReturn($stmt);

        (new CommissionService($pdo))->updatePercentage(12.5);
    }
}