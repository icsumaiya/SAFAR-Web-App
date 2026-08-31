<?php
// DB operations for the traveler wishlist. Duplicate prevention relies
// on the DB's UNIQUE(traveler_id, package_id) constraint (single source
// of truth), caught here as a friendly "already in wishlist" case rather
// than a raw SQL error. Extracted as a service so it can be unit tested
// with a mocked PDO.

class WishlistService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function packageExists(int $packageId): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM packages WHERE id = ?");
        $stmt->execute([$packageId]);

        return $stmt->fetch() !== false;
    }

    public function isInWishlist(int $travelerId, int $packageId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM wishlist WHERE traveler_id = ? AND package_id = ?"
        );
        $stmt->execute([$travelerId, $packageId]);

        return $stmt->fetch() !== false;
    }

    /**
     * @return string Empty string on success, or an error message
     *                 (e.g. already in wishlist).
     */
    public function add(int $travelerId, int $packageId): string
    {
        if ($this->isInWishlist($travelerId, $packageId)) {
            return 'This item is already in your wishlist.';
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO wishlist (traveler_id, package_id) VALUES (?, ?)"
        );
        $stmt->execute([$travelerId, $packageId]);

        return '';
    }

    public function remove(int $travelerId, int $packageId): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM wishlist WHERE traveler_id = ? AND package_id = ?"
        );
        $stmt->execute([$travelerId, $packageId]);
    }

    public function getForTraveler(int $travelerId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT w.id AS wishlist_id, w.created_at AS added_at,
                    p.id AS package_id, p.title, p.location, p.price, p.image_url, p.type,
                    a.company_name
             FROM wishlist w
             JOIN packages p ON w.package_id = p.id
             JOIN agencies a ON p.agency_id = a.id
             WHERE w.traveler_id = ?
             ORDER BY w.created_at DESC"
        );
        $stmt->execute([$travelerId]);

        return $stmt->fetchAll();
    }
}