<?php

use PHPUnit\Framework\TestCase;

final class PaymentServiceTest extends TestCase
{
    public function testRecordPaymentInsertsWithNullTransactionIdWhenEmpty(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([5, '150.00', 'cash', null, 'successful', null, 0]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO payments'))
            ->willReturn($stmt);

        $service = new PaymentService($pdo);
        $service->recordPayment([
            'booking_id' => 5,
            'amount' => '150.00',
            'method' => 'cash',
            'transaction_id' => '',
            'status' => 'successful',
        ]);
    }

    public function testRecordPaymentInsertsWithGivenTransactionId(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([5, '150.00', 'bkash', 'TXN123', 'successful', null, 0]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $service = new PaymentService($pdo);
        $service->recordPayment([
            'booking_id' => 5,
            'amount' => '150.00',
            'method' => 'bkash',
            'transaction_id' => 'TXN123',
            'status' => 'successful',
        ]);
    }

    public function testGetForBookingReturnsLatestPayment(): void
    {
        $expected = ['id' => 1, 'booking_id' => 5, 'status' => 'successful'];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([5]);
        $stmt->method('fetch')->willReturn($expected);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('ORDER BY created_at DESC LIMIT 1'))
            ->willReturn($stmt);

        $service = new PaymentService($pdo);
        $this->assertSame($expected, $service->getForBooking(5));
    }

    public function testGetForBookingReturnsNullWhenNoneFound(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $service = new PaymentService($pdo);
        $this->assertNull($service->getForBooking(999));
    }

    public function testGetStatsComputesRealCountsAndAmount(): void
    {
        $countsStmt = $this->createMock(PDOStatement::class);
        $countsStmt->method('fetchAll')->willReturn([
            'pending' => 2,
            'successful' => 5,
            'failed' => 1
        ]);

        $sumStmt = $this->createMock(PDOStatement::class);
        $sumStmt->method('fetchColumn')->willReturn('7500.00');

        $pdo = $this->createMock(PDO::class);
        $pdo->method('query')->willReturnOnConsecutiveCalls(
            $countsStmt,
            $sumStmt
        );

        $service = new PaymentService($pdo);
        $stats = $service->getStats();

        $this->assertSame(8, $stats['total_count']);
        $this->assertSame(5, $stats['successful_count']);
        $this->assertSame(1, $stats['failed_count']);
        $this->assertSame(2, $stats['pending_count']);
        $this->assertSame(7500.00, $stats['successful_amount']);
    }

    public function testGetStatsHandlesNoPaymentsYet(): void
    {
        $countsStmt = $this->createMock(PDOStatement::class);
        $countsStmt->method('fetchAll')->willReturn([]);

        $sumStmt = $this->createMock(PDOStatement::class);
        $sumStmt->method('fetchColumn')->willReturn('0');

        $pdo = $this->createMock(PDO::class);
        $pdo->method('query')->willReturnOnConsecutiveCalls(
            $countsStmt,
            $sumStmt
        );

        $service = new PaymentService($pdo);
        $stats = $service->getStats();

        $this->assertSame(0, $stats['total_count']);
        $this->assertSame(0.0, $stats['successful_amount']);
    }
}