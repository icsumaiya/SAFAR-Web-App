<?php

use PHPUnit\Framework\TestCase;

final class BookingDetailsApiHandlerTest extends TestCase
{
    public function testHandleGetReturns404WhenBookingNotFound(): void
    {
        $stmt = $this->createStub(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(false);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $handler = new BookingDetailsApiHandler($pdo);
        $result = $handler->handleGet(999);

        $this->assertSame(404, $result['status']);
        $this->assertFalse($result['body']['success']);
        $this->assertSame('Booking not found.', $result['body']['error']);
    }

    public function testHandleGetReturnsBookingWithFormattedReferenceWhenFound(): void
    {
        $row = [
            'id' => 42,
            'traveler_name' => 'Alice',
            'company_name' => 'Sylhet Tours',
        ];

        $stmt = $this->createStub(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn($row);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $handler = new BookingDetailsApiHandler($pdo);
        $result = $handler->handleGet(42);

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
        $this->assertSame(42, $result['body']['data']['id']);
        $this->assertSame('BKG-000042', $result['body']['data']['reference']);
    }
}