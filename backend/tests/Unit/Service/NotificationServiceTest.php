<?php

use PHPUnit\Framework\TestCase;

final class NotificationServiceTest extends TestCase
{
    public function testCreateInsertsWithGivenValues(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
        ->method('execute')
        ->with(['new_booking', 'New booking request received.', 5, 'admin', null]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO notifications'))
            ->willReturn($stmt);

        (new NotificationService($pdo))->create('new_booking', 'New booking request received.', 5);
    }

    public function testCreateAllowsNullReferenceId(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
        ->method('execute')
        ->with(['new_review', 'New review submitted.', null, 'admin', null]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        (new NotificationService($pdo))->create('new_review', 'New review submitted.', null);
    }

    public function testGetRecentReturnsRows(): void
    {
        $expected = [
            ['id' => 1, 'type' => 'new_booking', 'message' => 'Test', 'is_read' => 0],
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('bindValue')->with(1, 50, PDO::PARAM_INT);
        $stmt->expects($this->once())->method('execute');
        $stmt->method('fetchAll')->willReturn($expected);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('ORDER BY created_at DESC'))
            ->willReturn($stmt);

        $this->assertSame($expected, (new NotificationService($pdo))->getRecent());
    }

    public function testGetUnreadCountReturnsInt(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('query')
            ->with($this->stringContains('WHERE is_read = 0'))
            ->willReturn($this->createConfiguredMock(PDOStatement::class, ['fetchColumn' => '7']));

        $this->assertSame(7, (new NotificationService($pdo))->getUnreadCount());
    }

    public function testMarkReadUpdatesSingleRow(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([9]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SET is_read = 1 WHERE id = ?'))
            ->willReturn($stmt);

        (new NotificationService($pdo))->markRead(9);
    }

    public function testMarkAllReadExecutesUpdate(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('exec')
            ->with($this->stringContains("SET is_read = 1 WHERE is_read = 0"));

        (new NotificationService($pdo))->markAllRead();
    }
    
    public function testGetForTravelerFiltersByRecipient(): void
    {
        $expected = [['id' => 1, 'recipient_role' => 'traveler', 'recipient_id' => 9]];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->exactly(2))->method('bindValue');
        $stmt->expects($this->once())->method('execute');
        $stmt->method('fetchAll')->willReturn($expected);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("recipient_role = 'traveler'"))
            ->willReturn($stmt);

        $this->assertSame($expected, (new NotificationService($pdo))->getForTraveler(9));
    }

    public function testMarkReadForTravelerScopesToOwner(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([5, 9]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('recipient_id = ?'))
            ->willReturn($stmt);

        (new NotificationService($pdo))->markReadForTraveler(5, 9);
    }
}