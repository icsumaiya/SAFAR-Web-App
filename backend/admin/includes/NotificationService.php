<?php
// DB operations for the admin in-app notification system. Notifications
// are created directly at the point each real event happens (new
// booking, payment, review, etc.) — never generated from fake/hardcoded
// data. Extracted as a service so it can be unit tested with a mocked PDO.

class NotificationService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(
        string $type,
        string $message,
        ?int $referenceId = null,
        string $recipientRole = 'admin',
        ?int $recipientId = null
    ): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO notifications (type, message, reference_id, recipient_role, recipient_id)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$type, $message, $referenceId, $recipientRole, $recipientId]);
    }

    /**
     * @return array{data:array, total:int}
     */
    public function getRecent(int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM notifications ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getUnreadCount(): int
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM notifications WHERE is_read = 0"
        )->fetchColumn();
    }

    public function markRead(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function markAllRead(): void
    {
        $this->pdo->exec("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
    }
    
    public function getForTraveler(int $travelerId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM notifications
             WHERE recipient_role = 'traveler' AND recipient_id = ?
             ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->bindValue(1, $travelerId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getUnreadCountForTraveler(int $travelerId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM notifications
             WHERE recipient_role = 'traveler' AND recipient_id = ? AND is_read = 0"
        );
        $stmt->execute([$travelerId]);

        return (int) $stmt->fetchColumn();
    }

    public function markReadForTraveler(int $id, int $travelerId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE notifications SET is_read = 1 WHERE id = ? AND recipient_role = 'traveler' AND recipient_id = ?"
        );
        $stmt->execute([$id, $travelerId]);
    }

    public function markAllReadForTraveler(int $travelerId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE notifications SET is_read = 1 WHERE recipient_role = 'traveler' AND recipient_id = ? AND is_read = 0"
        );
        $stmt->execute([$travelerId]);
    }
}