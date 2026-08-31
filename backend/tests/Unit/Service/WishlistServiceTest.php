<?php

use PHPUnit\Framework\TestCase;

final class WishlistServiceTest extends TestCase
{
    public function testPackageExistsReturnsTrueWhenFound(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn(['id' => 5]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $this->assertTrue((new WishlistService($pdo))->packageExists(5));
    }

    public function testPackageExistsReturnsFalseWhenNotFound(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $this->assertFalse((new WishlistService($pdo))->packageExists(999));
    }

    public function testAddInsertsWhenNotAlreadyInWishlist(): void
    {
        $checkStmt = $this->createMock(PDOStatement::class);
        $checkStmt->method('fetch')->willReturn(false);

        $insertStmt = $this->createMock(PDOStatement::class);
        $insertStmt->expects($this->once())->method('execute')->with([3, 7]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($checkStmt, $insertStmt);

        $error = (new WishlistService($pdo))->add(3, 7);

        $this->assertSame('', $error);
    }

    public function testAddReturnsErrorWhenAlreadyInWishlist(): void
    {
        $checkStmt = $this->createMock(PDOStatement::class);
        $checkStmt->method('fetch')->willReturn(['id' => 1]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('prepare')->willReturn($checkStmt);

        $error = (new WishlistService($pdo))->add(3, 7);

        $this->assertSame('This item is already in your wishlist.', $error);
    }

    public function testRemoveExecutesDelete(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([3, 7]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('DELETE FROM wishlist'))
            ->willReturn($stmt);

        (new WishlistService($pdo))->remove(3, 7);
    }

    public function testGetForTravelerReturnsRows(): void
    {
        $expected = [['wishlist_id' => 1, 'title' => 'Sylhet Tour', 'type' => 'tour']];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([3]);
        $stmt->method('fetchAll')->willReturn($expected);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('prepare')->willReturn($stmt);

        $this->assertSame($expected, (new WishlistService($pdo))->getForTraveler(3));
    }
}