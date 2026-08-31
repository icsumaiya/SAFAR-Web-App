<?php

use PHPUnit\Framework\TestCase;

final class NotificationApiHandlerTest extends TestCase
{
    public function testHandlePostReturns422ForUnknownAction(): void
    {
        $service = $this->createMock(NotificationService::class);
        $handler = new NotificationApiHandler($service);

        $result = $handler->handlePost(['action' => 'bogus']);

        $this->assertSame(422, $result['status']);
        $this->assertFalse($result['body']['success']);
        $this->assertSame('Unknown action.', $result['body']['error']);
    }

    public function testHandlePostMarkReadCallsServiceWithId(): void
    {
        $service = $this->createMock(NotificationService::class);
        $service->expects($this->once())->method('markRead')->with(7);

        $handler = new NotificationApiHandler($service);

        $result = $handler->handlePost(['action' => 'mark_read', 'id' => 7]);

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
        $this->assertSame('Updated.', $result['body']['message']);
    }

    public function testHandlePostMarkAllReadCallsService(): void
    {
        $service = $this->createMock(NotificationService::class);
        $service->expects($this->once())->method('markAllRead');

        $handler = new NotificationApiHandler($service);

        $result = $handler->handlePost(['action' => 'mark_all_read']);

        $this->assertSame(200, $result['status']);
    }

    public function testHandleGetReturnsRecentAndUnreadCount(): void
    {
        $rows = [['id' => 1, 'message' => 'New booking']];

        $service = $this->createMock(NotificationService::class);
        $service->expects($this->once())->method('getRecent')->willReturn($rows);
        $service->expects($this->once())->method('getUnreadCount')->willReturn(3);

        $handler = new NotificationApiHandler($service);

        $result = $handler->handleGet();

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
        $this->assertSame($rows, $result['body']['data']);
        $this->assertSame(3, $result['body']['unread_count']);
    }
}