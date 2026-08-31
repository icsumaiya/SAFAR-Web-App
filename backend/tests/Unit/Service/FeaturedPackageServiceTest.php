<?php

use PHPUnit\Framework\TestCase;

final class FeaturedPackageServiceTest extends TestCase
{
    public function testSetFeaturedExecutesWithCorrectFlag(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([1, 5]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('is_featured'))
            ->willReturn($stmt);

        (new FeaturedPackageService($pdo))->setFeatured(5, true);
    }

    public function testUnsetFeaturedExecutesWithZero(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([0, 5]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        (new FeaturedPackageService($pdo))->setFeatured(5, false);
    }

    public function testSetSpecialOfferExecutesWithDiscountAndExpiry(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([15.0, '2026-12-31', 5]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        (new FeaturedPackageService($pdo))->setSpecialOffer(5, 15.0, '2026-12-31');
    }

    public function testClearSpecialOfferSetsNulls(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([5]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('= NULL'))
            ->willReturn($stmt);

        (new FeaturedPackageService($pdo))->clearSpecialOffer(5);
    }

    public function testGetPopularOnlyIncludesPackagesWithBookings(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('bindValue')->with(1, 10, PDO::PARAM_INT);
        $stmt->expects($this->once())->method('execute');
        $stmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('HAVING bookings_count > 0'))
            ->willReturn($stmt);

        (new FeaturedPackageService($pdo))->getPopular();
    }

    public function testGetSpecialOffersOnlyIncludesNonExpired(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('offer_expiry >= CURDATE()'))
            ->willReturn($stmt);

        (new FeaturedPackageService($pdo))->getSpecialOffers();
    }
}