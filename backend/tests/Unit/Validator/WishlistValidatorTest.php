<?php

use PHPUnit\Framework\TestCase;

final class WishlistValidatorTest extends TestCase
{
    public function testValidPackageIdPasses(): void
    {
        $this->assertSame('', WishlistValidator::validate(['package_id' => 5]));
    }

    public function testMissingPackageIdFails(): void
    {
        $this->assertNotSame('', WishlistValidator::validate([]));
    }

    public function testZeroPackageIdFails(): void
    {
        $this->assertNotSame('', WishlistValidator::validate(['package_id' => 0]));
    }

    public function testNonNumericPackageIdFails(): void
    {
        $this->assertNotSame('', WishlistValidator::validate(['package_id' => 'abc']));
    }
}