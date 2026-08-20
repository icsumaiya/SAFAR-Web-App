<?php
// Validates an incoming "book a package" API request. Pure logic (no session
// access, no DB call, no header()/exit()), extracted from api/book.php so it
// can be unit tested in isolation.

class BookingRequestValidator
{
    /**
     * @param string      $method     $_SERVER['REQUEST_METHOD']
     * @param bool        $isLoggedIn Result of isLoggedIn()
     * @param string|null $userRole   $_SESSION['user_role'] ?? null
     * @param mixed       $packageId  $_POST['package_id'] ?? null
     * @return string Empty string if valid, otherwise the JSON-ready error message.
     */
    public static function validate(string $method, bool $isLoggedIn, ?string $userRole, $packageId): string
    {
        if ($method !== 'POST') {
            return 'Invalid request method.';
        }

        if (!$isLoggedIn || $userRole !== 'traveler') {
            return 'You must be logged in as a traveler to book a tour.';
        }

        if (!$packageId) {
            return 'Package ID is missing.';
        }

        return '';
    }
}