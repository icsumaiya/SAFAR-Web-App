<?php
// DB operations for the forgot-password flow: token issuance, lookup,
// and password reset. Extracted as a service so it can be unit tested
// with a mocked PDO.

class PasswordResetService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, name, email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function createToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);

        $stmt = $this->pdo->prepare(
            "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)"
        );
        $stmt->execute([$userId, $token, $expiresAt]);

        return $token;
    }

    /**
     * @return int|null user_id if the token is valid, unused, and not expired
     */
    public function validateToken(string $token): ?int
    {
        $stmt = $this->pdo->prepare(
            "SELECT user_id, expires_at, used FROM password_resets WHERE token = ?"
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch();

        if ($row === false || (int) $row['used'] === 1) {
            return null;
        }

        if (strtotime($row['expires_at']) < time()) {
            return null;
        }

        return (int) $row['user_id'];
    }

    public function resetPassword(string $token, int $userId, string $passwordHash): void
    {
        $updateUser = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateUser->execute([$passwordHash, $userId]);

        $markUsed = $this->pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
        $markUsed->execute([$token]);
    }
}