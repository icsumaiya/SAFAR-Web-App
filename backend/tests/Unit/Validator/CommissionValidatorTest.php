<?php

use PHPUnit\Framework\TestCase;

final class CommissionValidatorTest extends TestCase
{
    public function testValidPercentagePasses(): void
    {
        $this->assertSame('', CommissionValidator::validatePercentage('15.5'));
    }

    public function testZeroIsValid(): void
    {
        $this->assertSame('', CommissionValidator::validatePercentage('0'));
    }

    public function testHundredIsValid(): void
    {
        $this->assertSame('', CommissionValidator::validatePercentage('100'));
    }

    public function testEmptyValueFails(): void
    {
        $this->assertSame(
            'Commission percentage must be a number.',
            CommissionValidator::validatePercentage('')
        );
    }

    public function testNullValueFails(): void
    {
        $this->assertSame(
            'Commission percentage must be a number.',
            CommissionValidator::validatePercentage(null)
        );
    }

    public function testNonNumericValueFails(): void
    {
        $this->assertSame(
            'Commission percentage must be a number.',
            CommissionValidator::validatePercentage('abc')
        );
    }

    public function testNegativeValueFails(): void
    {
        $this->assertSame(
            'Commission percentage must be between 0 and 100.',
            CommissionValidator::validatePercentage('-5')
        );
    }

    public function testOverHundredFails(): void
    {
        $this->assertSame(
            'Commission percentage must be between 0 and 100.',
            CommissionValidator::validatePercentage('150')
        );
    }
}