<?php
// Validation logic for the admin "Manage Users" page. Pure logic (no
// session/DB/header side effects), extracted from admin/users.php so it can
// be unit tested in isolation.

class UserManagementValidator
{
    public const VALID_ROLES = ['traveler', 'agency', 'admin'];

    /**
     * @return string 'error:<message>' or '' if the delete is allowed to proceed.
     */
    public static function validateDelete(int $targetUserId, int $currentAdminId): string
    {
        if ($targetUserId === $currentAdminId) {
            return 'error:You cannot delete your own admin account.';
        }
        return '';
    }

    /**
     * @return string 'error:<message>' or '' if the role change is allowed to proceed.
     */
    public static function validateRoleChange(int $targetUserId, string $newRole, int $currentAdminId): string
    {
        if ($targetUserId === $currentAdminId) {
            return 'error:You cannot change your own role.';
        }
        if (!in_array($newRole, self::VALID_ROLES, true)) {
            return 'error:Invalid role selected.';
        }
        return '';
    }
}