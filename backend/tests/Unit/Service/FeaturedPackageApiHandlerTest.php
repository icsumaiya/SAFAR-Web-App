<?php

use PHPUnit\Framework\TestCase;

final class FeaturedPackageApiHandlerTest extends TestCase
{
    private function pdoStub(): PDO
    {
        $stmt = $this->createStub(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        return $pdo;
    }

    public function testHandlePostReturns422WhenPackageIdMissing(): void
    {
        $handler = new FeaturedPackageApiHandler($this->pdoStub());

        $result = $handler->handlePost(['action' => 'set_featured']);

        $this->assertSame(422, $result['status']);
        $this->assertFalse($result['body']['success']);
        $this->assertSame('package_id is required.', $result['body']['error']);
    }

    public function testHandlePostReturns422ForUnknownAction(): void
    {
        $handler = new FeaturedPackageApiHandler($this->pdoStub());

        $result = $handler->handlePost(['package_id' => 3, 'action' => 'bogus']);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Unknown action.', $result['body']['error']);
    }

    public function testHandlePostSetFeaturedUpdatesRowAndReturns200(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([1, 3]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('is_featured'))
            ->willReturn($stmt);

        $handler = new FeaturedPackageApiHandler($pdo);

        $result = $handler->handlePost(['package_id' => 3, 'action' => 'set_featured']);

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
        $this->assertSame('Updated.', $result['body']['message']);
    }

    public function testHandlePostUnsetRecommendedUpdatesRow(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([0, 3]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('is_recommended'))
            ->willReturn($stmt);

        $handler = new FeaturedPackageApiHandler($pdo);

        $result = $handler->handlePost(['package_id' => 3, 'action' => 'unset_recommended']);

        $this->assertSame(200, $result['status']);
    }

    public function testHandlePostSetOfferReturns422ForInvalidDiscount(): void
    {
        $handler = new FeaturedPackageApiHandler($this->pdoStub());

        $result = $handler->handlePost([
            'package_id' => 3,
            'action' => 'set_offer',
            'offer_discount_percentage' => 150,
            'offer_expiry' => '2099-01-01',
        ]);

        $this->assertSame(422, $result['status']);
    }

    public function testHandlePostSetOfferUpdatesRowForValidInput(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([20.0, '2099-01-01', 3]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('offer_discount_percentage'))
            ->willReturn($stmt);

        $handler = new FeaturedPackageApiHandler($pdo);

        $result = $handler->handlePost([
            'package_id' => 3,
            'action' => 'set_offer',
            'offer_discount_percentage' => 20,
            'offer_expiry' => '2099-01-01',
        ]);

        $this->assertSame(200, $result['status']);
    }

    public function testHandlePostClearOfferUpdatesRow(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([3]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('offer_discount_percentage = NULL'))
            ->willReturn($stmt);

        $handler = new FeaturedPackageApiHandler($pdo);

        $result = $handler->handlePost(['package_id' => 3, 'action' => 'clear_offer']);

        $this->assertSame(200, $result['status']);
    }

    public function testHandleGetReturnsAllFourLists(): void
    {
        $handler = new FeaturedPackageApiHandler($this->pdoStub());

        $result = $handler->handleGet();

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
        $this->assertArrayHasKey('featured', $result['body']);
        $this->assertArrayHasKey('recommended', $result['body']);
        $this->assertArrayHasKey('special_offers', $result['body']);
        $this->assertArrayHasKey('popular', $result['body']);
    }
}