<?php
// Thin wrapper around firebase/php-jwt so the rest of the app only talks to
// this class, not the library directly. Pure logic (no session/db), so it
// can be unit tested by round-tripping issue() -> verify().

require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
class JwtHelper
{
    private const ALGO = 'HS256';

    /**
     * Loaded from a gitignored config file (includes/jwt_config.php),
     * never committed to source control. Falls back to a placeholder
     * only so local dev doesn't hard-crash before the file is created —
     * that placeholder is intentionally useless as a real secret.
     */
    private static function secret(): string
    {
        $configPath = __DIR__ . '/jwt_config.php';

        if (file_exists($configPath)) {
            $config = require $configPath;
            return $config['secret'];
        }

        return 'REPLACE-ME-see-includes-jwt_config.example.php-INSECURE-PLACEHOLDER';
    }

    public static function issue(array $claims, int $ttlSeconds = 3600): string
    {
        $now = time();
        $payload = array_merge($claims, [
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
        ]);

        return JWT::encode($payload, self::secret(), self::ALGO);
    }

    /**
     * @return array|null Decoded claims, or null if the token is missing/expired/invalid.
     */
    public static function verify(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key(self::secret(), self::ALGO));
            return (array) $decoded;
        } catch (\Throwable $e) {
            return null;
        }
    }
}