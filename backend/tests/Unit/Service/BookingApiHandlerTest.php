<?php

use PHPUnit\Framework\TestCase;

final class BookingApiHandlerTest extends TestCase
{
    public function testHandlePostReturns422WhenBookingIdMissing(): void
    {
        $pdo = $this->createMock(PDO::class);
        $notifications = $this->createMock(NotificationService::class);
        $email = $this->createMock(EmailService::class);

        $handler = new BookingApiHandler($pdo, $notifications, $email);

        $result = $handler->handlePost(['booking_action' => 'approve']);

        $this->assertSame(422, $result['status']);
        $this->assertFalse($result['body']['success']);
    }

    public function testHandlePostReturns422WhenActionMissing(): void
    {
        $pdo = $this->createMock(PDO::class);
        $notifications = $this->createMock(NotificationService::class);
        $email = $this->createMock(EmailService::class);

        $handler = new BookingApiHandler($pdo, $notifications, $email);

        $result = $handler->handlePost(['booking_id' => 5]);

        $this->assertSame(422, $result['status']);
        $this->assertFalse($result['body']['success']);
    }

    public function testHandlePostRejectsAndSkipsNotificationWhenNoTraveler(): void
    {
        $updateStmt = $this->createMock(PDOStatement::class);
        $updateStmt->expects($this->once())->method('execute')->with(['rejected', 5]);

        $travelerStmt = $this->createStub(PDOStatement::class);
        $travelerStmt->method('execute')->willReturn(true);
        $travelerStmt->method('fetchColumn')->willReturn(0);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($updateStmt, $travelerStmt);

        $notifications = $this->createMock(NotificationService::class);
        $notifications->expects($this->never())->method('create');

        $email = $this->createMock(EmailService::class);
        $email->expects($this->never())->method('send');

        $handler = new BookingApiHandler($pdo, $notifications, $email);
        $result = $handler->handlePost(['booking_id' => 5, 'booking_action' => 'reject']);

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
    }

    public function testHandlePostNotifiesTravelerOnRejectionWithoutSendingEmail(): void
    {
        $updateStmt = $this->createStub(PDOStatement::class);
        $updateStmt->method('execute')->willReturn(true);

        $travelerStmt = $this->createStub(PDOStatement::class);
        $travelerStmt->method('execute')->willReturn(true);
        $travelerStmt->method('fetchColumn')->willReturn(7);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($updateStmt, $travelerStmt);

        $notifications = $this->createMock(NotificationService::class);
        $notifications->expects($this->once())
            ->method('create')
            ->with('booking_status_update', 'Your booking #5 was rejected.', 5, 'traveler', 7);

        $email = $this->createMock(EmailService::class);
        $email->expects($this->never())->method('send');

        $handler = new BookingApiHandler($pdo, $notifications, $email);
        $result = $handler->handlePost(['booking_id' => 5, 'booking_action' => 'reject']);

        $this->assertSame(200, $result['status']);
    }

    public function testHandlePostSendsConfirmationEmailOnApproval(): void
    {
        $updateStmt = $this->createStub(PDOStatement::class);
        $updateStmt->method('execute')->willReturn(true);

        $travelerStmt = $this->createStub(PDOStatement::class);
        $travelerStmt->method('execute')->willReturn(true);
        $travelerStmt->method('fetchColumn')->willReturn(9);

        $userStmt = $this->createStub(PDOStatement::class);
        $userStmt->method('execute')->willReturn(true);
        $userStmt->method('fetch')->willReturn(['name' => 'Alice', 'email' => 'alice@example.com']);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($updateStmt, $travelerStmt, $userStmt);

        $notifications = $this->createMock(NotificationService::class);
        $notifications->expects($this->once())->method('create');

        $email = $this->createMock(EmailService::class);
        $email->expects($this->once())
            ->method('send')
            ->with('alice@example.com', 'Alice', 'Your SAFAR booking is confirmed', $this->stringContains('booking #9'));

        $handler = new BookingApiHandler($pdo, $notifications, $email);
        $result = $handler->handlePost(['booking_id' => 9, 'booking_action' => 'approve']);

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
    }

    public function testHandlePostSkipsEmailWhenApprovedTravelerRowMissing(): void
    {
        $updateStmt = $this->createStub(PDOStatement::class);
        $updateStmt->method('execute')->willReturn(true);

        $travelerStmt = $this->createStub(PDOStatement::class);
        $travelerStmt->method('execute')->willReturn(true);
        $travelerStmt->method('fetchColumn')->willReturn(9);

        $userStmt = $this->createStub(PDOStatement::class);
        $userStmt->method('execute')->willReturn(true);
        $userStmt->method('fetch')->willReturn(false);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($updateStmt, $travelerStmt, $userStmt);

        $notifications = $this->createMock(NotificationService::class);
        $notifications->expects($this->once())->method('create');

        $email = $this->createMock(EmailService::class);
        $email->expects($this->never())->method('send');

        $handler = new BookingApiHandler($pdo, $notifications, $email);
        $handler->handlePost(['booking_id' => 9, 'booking_action' => 'approve']);
    }

    public function testHandleGetReturnsBookingsPaginationAndCounts(): void
    {
        $rows = [['id' => 1, 'traveler_name' => 'Alice']];
        $counts = ['pending' => 2, 'approved' => 5];

        $listStmt = $this->createStub(PDOStatement::class);
        $listStmt->method('execute')->willReturn(true);
        $listStmt->method('fetchAll')->willReturn($rows);

        $countStmt = $this->createStub(PDOStatement::class);
        $countStmt->method('execute')->willReturn(true);
        $countStmt->method('fetchColumn')->willReturn(7);

        $countsQueryStmt = $this->createStub(PDOStatement::class);
        $countsQueryStmt->method('fetchAll')->willReturn($counts);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($listStmt, $countStmt);
        $pdo->method('query')->willReturn($countsQueryStmt);

        $notifications = $this->createMock(NotificationService::class);
        $email = $this->createMock(EmailService::class);

        $handler = new BookingApiHandler($pdo, $notifications, $email);
        $result = $handler->handleGet(['status' => 'all', 'search' => '', 'page' => 1]);

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
        $this->assertSame($rows, $result['body']['data']);
        $this->assertSame(7, $result['body']['pagination']['total']);
        $this->assertSame(1, $result['body']['pagination']['page']);
        $this->assertSame($counts, $result['body']['counts']);
    }

    public function testHandleGetDefaultsPageToOneWhenMissing(): void
    {
        $listStmt = $this->createStub(PDOStatement::class);
        $listStmt->method('execute')->willReturn(true);
        $listStmt->method('fetchAll')->willReturn([]);

        $countStmt = $this->createStub(PDOStatement::class);
        $countStmt->method('execute')->willReturn(true);
        $countStmt->method('fetchColumn')->willReturn(0);

        $countsQueryStmt = $this->createStub(PDOStatement::class);
        $countsQueryStmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($listStmt, $countStmt);
        $pdo->method('query')->willReturn($countsQueryStmt);

        $handler = new BookingApiHandler(
            $pdo,
            $this->createMock(NotificationService::class),
            $this->createMock(EmailService::class)
        );

        $result = $handler->handleGet([]);

        $this->assertSame(1, $result['body']['pagination']['page']);
    }
}