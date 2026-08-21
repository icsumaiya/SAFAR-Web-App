<?php
// Fetches all data needed by the admin dashboard (stat counts + recent
// activity). Extracted from admin/index.php so it can be unit tested with a
// mocked PDO instead of a real database connection.

class AdminDashboardService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getStats(): array
    {
        return [
            'users_count' => (int) $this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'packages_count' => (int) $this->pdo->query("SELECT COUNT(*) FROM packages")->fetchColumn(),
            'bookings_count' => (int) $this->pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
            'pending_agencies_count' => (int) $this->pdo->query("SELECT COUNT(*) FROM agencies WHERE status = 'pending'")->fetchColumn(),
            'pending_bookings_count' => (int) $this->pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
        ];
    }

    public function getRecentBookings(int $limit = 5): array
    {
        $stmt = $this->pdo->query("SELECT b.*, u.name AS traveler_name, p.title AS package_title 
                     FROM bookings b 
                     JOIN users u ON b.traveler_id = u.id 
                     JOIN packages p ON b.package_id = p.id 
                     ORDER BY b.booking_date DESC LIMIT " . (int) $limit);
        return $stmt->fetchAll();
    }

    public function getRecentUsers(int $limit = 5): array
    {
        $stmt = $this->pdo->query("SELECT name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT " . (int) $limit);
        return $stmt->fetchAll();
    }
}