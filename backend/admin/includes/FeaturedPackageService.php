<?php
// DB operations for featured/recommended/special-offer package flags,
// and real (booking-count-derived) popular package lookups. Extracted
// as a service so it can be unit tested with a mocked PDO.

class FeaturedPackageService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function setFeatured(int $packageId, bool $featured): void
    {
        $stmt = $this->pdo->prepare("UPDATE packages SET is_featured = ? WHERE id = ?");
        $stmt->execute([$featured ? 1 : 0, $packageId]);
    }

    public function setRecommended(int $packageId, bool $recommended): void
    {
        $stmt = $this->pdo->prepare("UPDATE packages SET is_recommended = ? WHERE id = ?");
        $stmt->execute([$recommended ? 1 : 0, $packageId]);
    }

    public function setSpecialOffer(int $packageId, float $discountPercentage, string $expiryDate): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE packages SET offer_discount_percentage = ?, offer_expiry = ? WHERE id = ?"
        );
        $stmt->execute([$discountPercentage, $expiryDate, $packageId]);
    }

    public function clearSpecialOffer(int $packageId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE packages SET offer_discount_percentage = NULL, offer_expiry = NULL WHERE id = ?"
        );
        $stmt->execute([$packageId]);
    }

    public function getFeatured(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, a.company_name FROM packages p JOIN agencies a ON p.agency_id = a.id
             WHERE p.is_featured = 1 ORDER BY p.id DESC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRecommended(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, a.company_name FROM packages p JOIN agencies a ON p.agency_id = a.id
             WHERE p.is_recommended = 1 ORDER BY p.id DESC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getSpecialOffers(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, a.company_name FROM packages p JOIN agencies a ON p.agency_id = a.id
             WHERE p.offer_discount_percentage IS NOT NULL AND p.offer_expiry >= CURDATE()
             ORDER BY p.offer_expiry ASC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * "Popular" is never a manual flag — always derived from real
     * booking counts, so it can't be gamed or hardcoded.
     */
    public function getPopular(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, a.company_name, COUNT(b.id) AS bookings_count
             FROM packages p
             JOIN agencies a ON p.agency_id = a.id
             LEFT JOIN bookings b ON b.package_id = p.id
             GROUP BY p.id
             HAVING bookings_count > 0
             ORDER BY bookings_count DESC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}