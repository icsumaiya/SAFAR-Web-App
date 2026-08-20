<?php
// Validates package create/edit form input. Pure logic, no DB/session/header
// side effects, extracted from admin/packages.php so it can be unit tested.

class PackageValidator
{
    /**
     * @param array $data Expected keys: title, location, price, description, agency_id
     * @return string Empty string if valid, otherwise a human-readable error message.
     */
    public static function validate(array $data): string
    {
        $title = trim($data['title'] ?? '');
        $location = trim($data['location'] ?? '');
        $price = $data['price'] ?? '';
        $description = trim($data['description'] ?? '');
        $agencyId = $data['agency_id'] ?? null;

        if ($title === '' || $location === '' || $price === '' || $description === '' || empty($agencyId)) {
            return 'Please fill in all required fields.';
        }

        if (!is_numeric($price) || $price < 0) {
            return 'Price must be a valid positive number.';
        }

        return '';
    }
}