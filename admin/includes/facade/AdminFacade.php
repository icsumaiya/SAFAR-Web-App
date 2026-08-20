<?php
class UserService {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getTotalUsers(): int {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }
}

class AgencyService {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getPendingVerifications(): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM agencies WHERE status = ?");
        $stmt->execute(['pending']);
        return (int) $stmt->fetchColumn();
    }
}

class PackageService {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getTotalPackages(): int {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM packages")->fetchColumn();
    }
}

class BookingService {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getTotalBookings(): int {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    }

    public function getPendingBookings(): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM bookings WHERE status = ?");
        $stmt->execute(['pending']);
        return (int) $stmt->fetchColumn();
    }

    public function getRecentBookings(int $limit = 5): array {
        $stmt = $this->pdo->prepare("SELECT b.*, u.name AS traveler_name, p.title AS package_title 
                      FROM bookings b 
                      JOIN users u ON b.traveler_id = u.id 
                      JOIN packages p ON b.package_id = p.id 
                      ORDER BY b.booking_date DESC LIMIT " . (int) $limit);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

class AdminFacade {
    private UserService $userService;
    private AgencyService $agencyService;
    private PackageService $packageService;
    private BookingService $bookingService;

    public function __construct(PDO $pdo) {
        $this->userService = new UserService($pdo);
        $this->agencyService = new AgencyService($pdo);
        $this->packageService = new PackageService($pdo);
        $this->bookingService = new BookingService($pdo);
    }

    public function getDashboardStats(): array {
        return [
            'users_count' => $this->userService->getTotalUsers(),
            'packages_count' => $this->packageService->getTotalPackages(),
            'bookings_count' => $this->bookingService->getTotalBookings(),
            'pending_agencies_count' => $this->agencyService->getPendingVerifications(),
            'pending_bookings_count' => $this->bookingService->getPendingBookings(),
        ];
    }

    public function getRecentBookings(int $limit = 5): array {
        return $this->bookingService->getRecentBookings($limit);
    }
}
?>