<?php

use PHPUnit\Framework\TestCase;

final class JwtHelperTest extends TestCase
{
    public function testIssueThenVerifyReturnsOriginalClaims(): void
    {
        $token = JwtHelper::issue(['sub' => 7, 'role' => 'admin']);
        $claims = JwtHelper::verify($token);

        $this->assertSame(7, $claims['sub']);
        $this->assertSame('admin', $claims['role']);
    }

    public function testVerifyReturnsNullForGarbageToken(): void
    {
        $this->assertNull(JwtHelper::verify('not-a-real-token'));
    }

    public function testVerifyReturnsNullForExpiredToken(): void
    {
        $token = JwtHelper::issue(['sub' => 1, 'role' => 'admin'], -10); // already expired
        $this->assertNull(JwtHelper::verify($token));
    }

    public function testIssueIncludesIatAndExpClaims(): void
    {
        $token = JwtHelper::issue(['sub' => 3, 'role' => 'traveler'], 120);
        $claims = JwtHelper::verify($token);

        $this->assertArrayHasKey('iat', $claims);
        $this->assertArrayHasKey('exp', $claims);
        $this->assertSame(120, $claims['exp'] - $claims['iat']);
    }
}