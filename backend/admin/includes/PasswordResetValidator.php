<?php
// Pure validation for the forgot-password / reset-password forms.

class PasswordResetValidator
{
    public static function validateEmail(string $email): string
    {
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Please provide a valid email address.';
        }
        return '';
    }

    public static function validateNewPassword(string $password): string
    {
        if (mb_strlen($password) < 6) {
            return 'Password must be at least 6 characters.';
        }
        return '';
    }
}