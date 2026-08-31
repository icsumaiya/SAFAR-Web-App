<?php

use PHPUnit\Framework\TestCase;

final class CouponApiHandlerTest extends TestCase
{
    private function validCouponPayload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'SUMMER25',
            'discount_type' => 'percentage',
            'discount_value' => 25,
            'start_date' => '2026-01-01',
            'expiry_date' => '2026-12-31',
        ], $overrides);
    }

    public function testHandlePostCreateReturns422ForInvalidData(): void
    {
        $pdo = $this->createMock(PDO::class);
        $service = $this->createMock(CouponService::class);
        $service->expects($this->never())->method('create');

        $handler = new CouponApiHandler($pdo, $service);

        $result = $handler->handlePost(['action' => 'create', 'code' => '']);

        $this->assertSame(422, $result['status']);
        $this->assertFalse($result['body']['success']);
    }

    public function testHandlePostCreateCallsServiceForValidData(): void
    {
        $pdo = $this->createMock(PDO::class);
        $service = $this->createMock(CouponService::class);
        $service->expects($this->once())->method('create');

        $handler = new CouponApiHandler($pdo, $service);

        $result = $handler->handlePost($this->validCouponPayload(['action' => 'create']));

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
        $this->assertSame('Coupon saved.', $result['body']['message']);
    }

    public function testHandlePostUpdateCallsServiceWithId(): void
    {
        $pdo = $this->createMock(PDO::class);
        $service = $this->createMock(CouponService::class);
        $service->expects($this->once())->method('update')->with(9, $this->anything());

        $handler = new CouponApiHandler($pdo, $service);

        $result = $handler->handlePost($this->validCouponPayload(['action' => 'update', 'id' => 9]));

        $this->assertSame(200, $result['status']);
    }

    public function testHandlePostActivateSetsActiveTrue(): void
    {
        $pdo = $this->createMock(PDO::class);
        $service = $this->createMock(CouponService::class);
        $service->expects($this->once())->method('setActive')->with(4, true);

        $handler = new CouponApiHandler($pdo, $service);

        $result = $handler->handlePost(['action' => 'activate', 'id' => 4]);

        $this->assertSame(200, $result['status']);
        $this->assertSame('Coupon status updated.', $result['body']['message']);
    }

    public function testHandlePostDeactivateSetsActiveFalse(): void
    {
        $pdo = $this->createMock(PDO::class);
        $service = $this->createMock(CouponService::class);
        $service->expects($this->once())->method('setActive')->with(4, false);

        $handler = new CouponApiHandler($pdo, $service);

        $handler->handlePost(['action' => 'deactivate', 'id' => 4]);
    }

    public function testHandlePostDeleteReturns200WhenDeleted(): void
    {
        $pdo = $this->createMock(PDO::class);
        $service = $this->createMock(CouponService::class);
        $service->expects($this->once())->method('delete')->with(4)->willReturn(true);

        $handler = new CouponApiHandler($pdo, $service);

        $result = $handler->handlePost(['action' => 'delete', 'id' => 4]);

        $this->assertSame(200, $result['status']);
        $this->assertSame('Coupon deleted.', $result['body']['message']);
    }

    public function testHandlePostDeleteReturns422WhenCouponAlreadyUsed(): void
    {
        $pdo = $this->createMock(PDO::class);
        $service = $this->createMock(CouponService::class);
        $service->method('delete')->willReturn(false);

        $handler = new CouponApiHandler($pdo, $service);

        $result = $handler->handlePost(['action' => 'delete', 'id' => 4]);

        $this->assertSame(422, $result['status']);
        $this->assertFalse($result['body']['success']);
    }

    public function testHandlePostReturns422ForUnknownAction(): void
    {
        $pdo = $this->createMock(PDO::class);
        $service = $this->createMock(CouponService::class);

        $handler = new CouponApiHandler($pdo, $service);

        $result = $handler->handlePost(['action' => 'bogus']);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Unknown action.', $result['body']['error']);
    }

    public function testHandleGetReturnsCouponsWithPagination(): void
    {
        $rows = [['id' => 1, 'code' => 'SUMMER25']];

        $listStmt = $this->createStub(PDOStatement::class);
        $listStmt->method('execute')->willReturn(true);
        $listStmt->method('fetchAll')->willReturn($rows);

        $countStmt = $this->createStub(PDOStatement::class);
        $countStmt->method('execute')->willReturn(true);
        $countStmt->method('fetchColumn')->willReturn(1);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($listStmt, $countStmt);

        $handler = new CouponApiHandler($pdo, $this->createMock(CouponService::class));

        $result = $handler->handleGet(['search' => '', 'status' => 'all', 'page' => 1]);

        $this->assertSame(200, $result['status']);
        $this->assertSame($rows, $result['body']['data']);
        $this->assertSame(1, $result['body']['pagination']['total']);
    }
}