<?php

use PHPUnit\Framework\TestCase;

final class CancellationServiceTest extends TestCase
{
    public function testGuardRequestFailsWhenBookingNotFound(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $service = new CancellationService($pdo);
        $error = $service->guardRequest(1, 99);

        $this->assertSame('Booking not found.', $error);
    }

    public function testGuardRequestFailsWhenBookingStatusNotCancellable(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn(['status' => 'rejected']);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $service = new CancellationService($pdo);
        $error = $service->guardRequest(1, 5);

        $this->assertSame('This booking cannot be cancelled.', $error);
    }

    public function testGuardRequestFailsWhenActiveRequestAlreadyExists(): void
    {
        $bookingStmt = $this->createMock(PDOStatement::class);
        $bookingStmt->method('fetch')->willReturn(['status' => 'approved']);

        $existingStmt = $this->createMock(PDOStatement::class);
        $existingStmt->method('fetch')->willReturn(['id' => 3]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($bookingStmt, $existingStmt);

        $service = new CancellationService($pdo);
        $error = $service->guardRequest(1, 5);

        $this->assertSame('A cancellation request already exists for this booking.', $error);
    }

    public function testGuardRequestPassesWhenEligible(): void
    {
        $bookingStmt = $this->createMock(PDOStatement::class);
        $bookingStmt->method('fetch')->willReturn(['status' => 'pending']);

        $existingStmt = $this->createMock(PDOStatement::class);
        $existingStmt->method('fetch')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($bookingStmt, $existingStmt);

        $service = new CancellationService($pdo);
        $error = $service->guardRequest(1, 5);

        $this->assertSame('', $error);
    }

    public function testRequestCancellationInserts(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([1, 'Change of plans']);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO cancellations'))
            ->willReturn($stmt);

        (new CancellationService($pdo))->requestCancellation(1, 'Change of plans');
    }

    public function testApproveWithRefundableAmountSetsPendingRefund(): void
    {
        $selectStmt = $this->createMock(PDOStatement::class);
        $selectStmt->method('fetch')->willReturn(['booking_id' => 7]);

        $updateCancellationStmt = $this->createMock(PDOStatement::class);
        $updateCancellationStmt->expects($this->once())
            ->method('execute')
            ->with(['pending', 100.0, 3]);

        $updateBookingStmt = $this->createMock(PDOStatement::class);
        $updateBookingStmt->expects($this->once())->method('execute')->with([7]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls(
            $selectStmt,
            $updateCancellationStmt,
            $updateBookingStmt
        );

        (new CancellationService($pdo))->approve(3, 100.0);
    }

    public function testApproveWithoutRefundableAmountSetsNotApplicable(): void
    {
        $selectStmt = $this->createMock(PDOStatement::class);
        $selectStmt->method('fetch')->willReturn(['booking_id' => 7]);

        $updateCancellationStmt = $this->createMock(PDOStatement::class);
        $updateCancellationStmt->expects($this->once())
            ->method('execute')
            ->with(['not_applicable', null, 3]);

        $updateBookingStmt = $this->createMock(PDOStatement::class);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls(
            $selectStmt,
            $updateCancellationStmt,
            $updateBookingStmt
        );

        (new CancellationService($pdo))->approve(3, null);
    }

    public function testApproveDoesNothingWhenCancellationNotFound(): void
    {
        $selectStmt = $this->createMock(PDOStatement::class);
        $selectStmt->method('fetch')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('prepare')->willReturn($selectStmt);

        (new CancellationService($pdo))->approve(999, null);
    }

    public function testRejectUpdatesStatus(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([3]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("status = 'rejected'"))
            ->willReturn($stmt);

        (new CancellationService($pdo))->reject(3);
    }

    public function testUpdateRefundStatusExecutesWithGivenValue(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['refunded', 3]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        (new CancellationService($pdo))->updateRefundStatus(3, 'refunded');
    }
}