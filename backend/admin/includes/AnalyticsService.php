<?php
// Read-only aggregate queries for the admin analytics dashboard (stat
// cards + chart series). Kept separate from AdminDashboardService (used
// by the legacy PHP admin/index.php) to avoid touching working code.
// All numbers are computed live from the database — never hardcoded.

class AnalyticsService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getSummary(): array
    {
        $totalRevenue = (float) $this->pdo->query("SELECT COALESCE(SUM(gross_amount),0) FROM commissions")->fetchColumn();
        $commission = (float) $this->pdo->query("SELECT COALESCE(SUM(commission_amount),0) FROM commissions")->fetchColumn();
        $agencyEarnings = (float) $this->pdo->query("SELECT COALESCE(SUM(agency_earning),0) FROM commissions")->fetchColumn();

        return [
            'total_users' => (int) $this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'total_agencies' => (int) $this->pdo->query("SELECT COUNT(*) FROM agencies")->fetchColumn(),
            'verified_agencies' => (int) $this->pdo->query("SELECT COUNT(*) FROM agencies WHERE status = 'verified'")->fetchColumn(),
            'total_packages' => (int) $this->pdo->query("SELECT COUNT(*) FROM packages WHERE type = 'tour'")->fetchColumn(),
            'total_hotels' => (int) $this->pdo->query("SELECT COUNT(*) FROM packages WHERE type = 'hotel'")->fetchColumn(),
            'total_bookings' => (int) $this->pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
            'pending_bookings' => (int) $this->pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
            'confirmed_bookings' => (int) $this->pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'approved'")->fetchColumn(),
            'cancelled_bookings' => (int) $this->pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'cancelled'")->fetchColumn(),
            'total_revenue' => $totalRevenue,
            'platform_commission' => $commission,
            'agency_earnings' => $agencyEarnings,
            'successful_payments' => (int) $this->pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'successful'")->fetchColumn(),
            'pending_payments' => (int) $this->pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn(),
        ];
    }

    public function getMonthlyBookings(int $months = 6): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DATE_FORMAT(booking_date, '%Y-%m') AS month, COUNT(*) AS total
             FROM bookings
             WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY month ORDER BY month ASC"
        );
        $stmt->execute([$months]);
        return $stmt->fetchAll();
    }

    public function getMonthlyRevenue(int $months = 6): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COALESCE(SUM(gross_amount),0) AS total
             FROM commissions
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY month ORDER BY month ASC"
        );
        $stmt->execute([$months]);
        return $stmt->fetchAll();
    }

    public function getBookingStatusDistribution(): array
    {
        $stmt = $this->pdo->query("SELECT status, COUNT(*) AS total FROM bookings GROUP BY status");
        return $stmt->fetchAll();
    }

    public function getUserGrowth(int $months = 6): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS total
             FROM users
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY month ORDER BY month ASC"
        );
        $stmt->execute([$months]);
        return $stmt->fetchAll();
    }

    public function getPopularPackages(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.title, COUNT(b.id) AS bookings_count
             FROM bookings b JOIN packages p ON b.package_id = p.id
             GROUP BY p.id, p.title
             ORDER BY bookings_count DESC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getPopularDestinations(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.location, COUNT(b.id) AS bookings_count
             FROM bookings b JOIN packages p ON b.package_id = p.id
             GROUP BY p.location
             ORDER BY bookings_count DESC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getTopAgencies(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT a.company_name, COALESCE(SUM(c.gross_amount),0) AS revenue
             FROM agencies a LEFT JOIN commissions c ON c.agency_id = a.id
             GROUP BY a.id, a.company_name
             ORDER BY revenue DESC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRevenueTrend(int $days = 30): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DATE(created_at) AS day, COALESCE(SUM(gross_amount),0) AS total
             FROM commissions
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY day ORDER BY day ASC"
        );
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
}