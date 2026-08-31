<?php
class AgencyProfileValidator
{
    private const MAX_DESCRIPTION_LENGTH = 2000;
    private const MAX_ADDRESS_LENGTH = 255;

    public static function validate(array $data): string
    {
        $description = trim($data['description'] ?? '');
        $address = trim($data['address'] ?? '');

        if (mb_strlen($description) > self::MAX_DESCRIPTION_LENGTH) {
            return 'Description must be ' . self::MAX_DESCRIPTION_LENGTH . ' characters or fewer.';
        }

        if (mb_strlen($address) > self::MAX_ADDRESS_LENGTH) {
            return 'Address must be ' . self::MAX_ADDRESS_LENGTH . ' characters or fewer.';
        }

        foreach (['website', 'facebook_url', 'instagram_url'] as $field) {
            $value = trim($data[$field] ?? '');
            if ($value !== '' && !self::isValidUrl($value)) {
                return 'Please provide a valid URL (starting with http:// or https://) for ' . $field . '.';
            }
        }

        return '';
    }

    private static function isValidUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false
            && (str_starts_with($value, 'http://') || str_starts_with($value, 'https://'));
    }
}