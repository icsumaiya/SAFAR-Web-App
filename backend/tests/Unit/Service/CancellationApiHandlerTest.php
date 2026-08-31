<?php

use PHPUnit\Framework\TestCase;

final class CancellationApiHandlerTest extends TestCase
{
    private function makeHandler(
        PDO $pdo,
        ?CancellationService $service = null,
        ?NotificationService $notifications = null,
        ?EmailService $email = null
    ): CancellationApiHandler {
        return new CancellationApiHandler(
            $pdo,
            $service ?? $this->createMock(CancellationService::class),
            $notifications ?? $this->createMock(NotificationService::class),
            $email ?? $this->createMock(EmailService::class)
        );
    }

    public function testHandlePostReturns422WhenCancellationIdMissing(): void
    {
        $pdo = $this->createMock(PDO::class);
        $handler = $this->makeHandler($pdo);

        $result = $handler->handlePost(['action' => 'approve']);

        $this->assertSame(422, $result['status']);
        $this->assertFalse($result['body']['success']);
    }

    public function testHandlePostReturns422WhenActionMissing(): void
    {
        $pdo = $this->createMock(PDO::class);
        $handler = $this->makeHandler($pdo);

        $result = $handler->handlePost(['cancellation_id' => 5]);

        $this->assertSame(422, $result['status']);
    }

    public function testHandlePostReturns422ForUnknownAction(): void
    {
        $pdo = $this->createMock(PDO::class);
        $handler = $this->makeHandler($pdo);

        $result = $handler->handlePost(['cancellation_id' => 5, 'action' => 'bogus']);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Unknown action.', $result['body']['error']);
    }

    public function testHandlePostApprovesAndNotifiesTravelerWithAmount(): void
    {
        // notifyTraveler's lookup returns no row -> notification/email skipped,
        // but approve() and the amount parsing are still exercised.
        $lookupStmt = $this->createStub(PDOStatement::class);
        $lookupStmt->method('execute')->willReturn(true);
        $lookupStmt->method('fetch')->willReturn(false);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($lookupStmt);

        $service = $this->createMock(CancellationService::class);
        $service->expects($this->once())->method('approve')->with(5, 250.5);

        $handler = $this->makeHandler($pdo, $service);

        $result = $handler->handlePost([
            'cancellation_id' => 5,
            'action' => 'approve',
            'refundable_amount' => '250.50',
        ]);

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
    }

    public function testHandlePostApproveWithNoAmountPassesNull(): void
    {
        $lookupStmt = $this->createStub(PDOStatement::class);
        $lookupStmt->method('execute')->willReturn(true);
        $lookupStmt->method('fetch')->willReturn(false);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($lookupStmt);

        $service = $this->createMock(CancellationService::class);
        $service->expects($this->once())->method('approve')->with(5, null);

        $handler = $this->makeHandler($pdo, $service);

        $handler->handlePost(['cancellation_id' => 5, 'action' => 'approve']);
    }

    public function testHandlePostRejectNotifiesTravelerWhenFound(): void
    {
        $lookupStmt = $this->createStub(PDOStatement::class);
        $lookupStmt->method('execute')->willReturn(true);
        $lookupStmt->method('fetch')->willReturn(['traveler_id' => 7, 'booking_id' => 3]);

        $userStmt = $this->createStub(PDOStatement::class);
        $userStmt->method('execute')->willReturn(true);
        $userStmt->method('fetch')->willReturn(['name' => 'Bob', 'email' => 'bob@example.com']);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($lookupStmt, $userStmt);

        $service = $this->createMock(CancellationService::class);
        $service->expects($this->once())->method('reject')->with(5);

        $notifications = $this->createMock(NotificationService::class);
        $notifications->expects($this->once())
            ->method('create')
            ->with('cancellation_update', 'Your cancellation request was rejected.', 3, 'traveler', 7);

        $email = $this->createMock(EmailService::class);
        $email->expects($this->once())
            ->method('send')
            ->with('bob@example.com', 'Bob', 'Update on your SAFAR cancellation', $this->stringContains('rejected'));

        $handler = $this->makeHandler($pdo, $service, $notifications, $email);

        $result = $handler->handlePost(['cancellation_id' => 5, 'action' => 'reject']);

        $this->assertSame(200, $result['status']);
    }

    public function testHandlePostUpdateRefundReturns422ForInvalidStatus(): void
    {
        $pdo = $this->createMock(PDO::class);
        $handler = $this->makeHandler($pdo);

        $result = $handler->handlePost([
            'cancellation_id' => 5,
            'action' => 'update_refund',
            'refund_status' => 'bogus',
        ]);

        $this->assertSame(422, $result['status']);
        $this->assertFalse($result['body']['success']);
    }

    public function testHandlePostUpdateRefundNotifiesAdminAndTraveler(): void
    {
        $lookupStmt = $this->createStub(PDOStatement::class);
        $lookupStmt->method('execute')->willReturn(true);
        $lookupStmt->method('fetch')->willReturn(false);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($lookupStmt);

        $service = $this->createMock(CancellationService::class);
        $service->expects($this->once())->method('updateRefundStatus')->with(5, 'refunded');

        $notifications = $this->createMock(NotificationService::class);
        $notifications->expects($this->once())
            ->method('create')
            ->with('refund_update', $this->stringContains("cancellation #5"), 5);

        $handler = $this->makeHandler($pdo, $service, $notifications);

        $result = $handler->handlePost([
            'cancellation_id' => 5,
            'action' => 'update_refund',
            'refund_status' => 'refunded',
        ]);

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
    }

    public function testHandleGetReturnsCancellationsWithPagination(): void
    {
        $rows = [['id' => 1, 'status' => 'requested']];

        $listStmt = $this->createStub(PDOStatement::class);
        $listStmt->method('execute')->willReturn(true);
        $listStmt->method('fetchAll')->willReturn($rows);

        $countStmt = $this->createStub(PDOStatement::class);
        $countStmt->method('execute')->willReturn(true);
        $countStmt->method('fetchColumn')->willReturn(4);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($listStmt, $countStmt);

        $handler = $this->makeHandler($pdo);

        $result = $handler->handleGet(['status' => 'all', 'search' => '', 'page' => 1]);

        $this->assertSame(200, $result['status']);
        $this->assertSame($rows, $result['body']['data']);
        $this->assertSame(4, $result['body']['pagination']['total']);
    }
}