<?php

use PHPUnit\Framework\TestCase;

final class PasswordResetValidatorTest extends TestCase
{
    public function testValidEmailPasses(): void
    {
        $this->assertSame('', PasswordResetValidator::validateEmail('alice@example.com'));
    }

    public function testEmptyEmailFails(): void
    {
        $this->assertNotSame('', PasswordResetValidator::validateEmail(''));
    }

    public function testInvalidEmailFormatFails(): void
    {
        $this->assertNotSame('', PasswordResetValidator::validateEmail('not-an-email'));
    }

    public function testValidPasswordPasses(): void
    {
        $this->assertSame('', PasswordResetValidator::validateNewPassword('secret123'));
    }

    public function testTooShortPasswordFails(): void
    {
        $this->assertNotSame('', PasswordResetValidator::validateNewPassword('abc'));
    }
}