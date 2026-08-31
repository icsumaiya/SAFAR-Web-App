<?php

use PHPUnit\Framework\TestCase;

final class CouponServiceTest extends TestCase
{
    private function sampleData(): array
    {
        return [
            'code' => '  summer20  ',
            'discount_type' => 'percentage',
            'discount_value' => '20',
            'min_booking_amount' => '100',
            'max_discount_amount' => '500',
            'start_date' => '2026-01-01',
            'expiry_date' => '2026-12-31',
            'usage_limit' => '100',
            'per_user_limit' => '1',
        ];
    }

    public function testCreateInsertsUppercasedTrimmedCode(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['SUMMER20', 'percentage', '20', '100', '500', '2026-01-01', '2026-12-31', '100', '1']);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO coupons'))
            ->willReturn($stmt);
        $pdo->method('lastInsertId')->willReturn('7');

        $service = new CouponService($pdo);
        $this->assertSame(7, $service->create($this->sampleData()));
    }

    public function testCreateDefaultsOptionalFieldsWhenEmpty(): void
    {
        $data = $this->sampleData();
        $data['min_booking_amount'] = '';
        $data['max_discount_amount'] = '';
        $data['usage_limit'] = '';
        $data['per_user_limit'] = '';

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['SUMMER20', 'percentage', '20', 0, null, '2026-01-01', '2026-12-31', null, 1]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);
        $pdo->method('lastInsertId')->willReturn('1');

        $service = new CouponService($pdo);
        $service->create($data);
    }

    public function testUpdateSendsIdAsLastParam(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['SUMMER20', 'percentage', '20', '100', '500', '2026-01-01', '2026-12-31', '100', '1', 9]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('UPDATE coupons SET'))
            ->willReturn($stmt);

        $service = new CouponService($pdo);
        $service->update(9, $this->sampleData());
    }

    public function testSetActiveTrue(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([1, 3]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        (new CouponService($pdo))->setActive(3, true);
    }

    public function testSetActiveFalse(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([0, 3]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        (new CouponService($pdo))->setActive(3, false);
    }

    public function testDeleteSkipsWhenCouponHasUsageHistory(): void
    {
        $countStmt = $this->createMock(PDOStatement::class);
        $countStmt->method('execute')->with([4]);
        $countStmt->method('fetchColumn')->willReturn('2');

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('prepare')->willReturn($countStmt);

        $service = new CouponService($pdo);
        $this->assertFalse($service->delete(4));
    }

    public function testDeleteRemovesWhenNoUsageHistory(): void
    {
        $countStmt = $this->createMock(PDOStatement::class);
        $countStmt->method('execute');
        $countStmt->method('fetchColumn')->willReturn('0');

        $deleteStmt = $this->createMock(PDOStatement::class);
        $deleteStmt->expects($this->once())->method('execute')->with([4]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($countStmt, $deleteStmt);

        $service = new CouponService($pdo);
        $this->assertTrue($service->delete(4));
    }

    public function testFindByCodeUppercasesAndTrimsBeforeLookup(): void
    {
        $expected = ['id' => 1, 'code' => 'SUMMER20'];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['SUMMER20']);
        $stmt->method('fetch')->willReturn($expected);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $service = new CouponService($pdo);
        $this->assertSame($expected, $service->findByCode('  summer20  '));
    }

    public function testFindByCodeReturnsNullWhenNotFound(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $service = new CouponService($pdo);
        $this->assertNull($service->findByCode('NOPE'));
    }

    public function testCountUsageForCoupon(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([4]);
        $stmt->method('fetchColumn')->willReturn('3');

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $service = new CouponService($pdo);
        $this->assertSame(3, $service->countUsageForCoupon(4));
    }

    public function testCountUsageForUser(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([4, 10]);
        $stmt->method('fetchColumn')->willReturn('1');

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $service = new CouponService($pdo);
        $this->assertSame(1, $service->countUsageForUser(4, 10));
    }

    public function testRecordUsageInserts(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([4, 22, 10, 30.5]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO coupon_usages'))
            ->willReturn($stmt);

        $service = new CouponService($pdo);
        $service->recordUsage(4, 22, 10, 30.5);
    }
}