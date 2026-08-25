<?php
// Fetches everything needed for the admin "Agency Details" page: the
// agency's own profile row, its packages, its bookings (joined via
// packages), and revenue calculated from real booking data (never
// hardcoded). Extracted as a service so it can be unit tested with a
// mocked PDO instead of a real database connection.

class AgencyDetailsService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAgency(int $agencyId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT a.*, u.name, u.email
             FROM agencies a JOIN users u ON a.user_id = u.id
             WHERE a.id = ?"
        );
        $stmt->execute([$agencyId]);
        $agency = $stmt->fetch();

        return $agency !== false ? $agency : null;
    }

    public function getPackages(int $agencyId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM packages WHERE agency_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$agencyId]);
        return $stmt->fetchAll();
    }

    public function getBookings(int $agencyId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT b.*, u.name AS traveler_name, p.title AS package_title
             FROM bookings b
             JOIN packages p ON b.package_id = p.id
             JOIN users u ON b.traveler_id = u.id
             WHERE p.agency_id = ?
             ORDER BY b.booking_date DESC"
        );
        $stmt->execute([$agencyId]);
        return $stmt->fetchAll();
    }

    /**
     * Revenue is the sum of package prices for that agency's *approved*
     * bookings only — calculated live from real data, never hardcoded.
     */
    public function getRevenue(int $agencyId): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(p.price), 0)
             FROM bookings b
             JOIN packages p ON b.package_id = p.id
             WHERE p.agency_id = ? AND b.status = 'approved'"
        );
        $stmt->execute([$agencyId]);
        return (float) $stmt->fetchColumn();
    }
}