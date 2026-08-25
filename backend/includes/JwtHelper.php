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
     * NOTE: in production this must come from an environment variable,
     * never committed to source control. Kept as a constant here only
     * because this project has no .env loader yet.
     */
    private static function secret(): string
    {
        return 'safar-dev-secret-change-me-please-1234567890';
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