<?php

use PHPUnit\Framework\TestCase;

final class UserManagementValidatorTest extends TestCase
{
    public function testDeletingSelfIsBlocked(): void
    {
        $result = UserManagementValidator::validateDelete(7, 7);
        $this->assertSame('error:You cannot delete your own admin account.', $result);
    }

    public function testDeletingOtherUserIsAllowed(): void
    {
        $result = UserManagementValidator::validateDelete(3, 7);
        $this->assertSame('', $result);
    }

    public function testChangingOwnRoleIsBlocked(): void
    {
        $result = UserManagementValidator::validateRoleChange(7, 'agency', 7);
        $this->assertSame('error:You cannot change your own role.', $result);
    }

    public function testInvalidRoleIsRejected(): void
    {
        $result = UserManagementValidator::validateRoleChange(3, 'superadmin', 7);
        $this->assertSame('error:Invalid role selected.', $result);
    }

    public function testValidRoleChangeForOtherUserIsAllowed(): void
    {
        $result = UserManagementValidator::validateRoleChange(3, 'agency', 7);
        $this->assertSame('', $result);
    }

    public function testSelfCheckHappensBeforeRoleValidityCheck(): void
    {
        $result = UserManagementValidator::validateRoleChange(7, 'superadmin', 7);
        $this->assertSame('error:You cannot change your own role.', $result);
    }

    /**
     * @dataProvider validRolesProvider
     */
    public function testEachValidRoleIsAccepted(string $role): void
    {
        $this->assertSame('', UserManagementValidator::validateRoleChange(3, $role, 7));
    }

    public static function validRolesProvider(): array
    {
        return [
            ['traveler'],
            ['agency'],
            ['admin'],
        ];
    }
}