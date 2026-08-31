<?php
// Extracted from api/admin/bookings.php so the POST (status change +
// notification + confirmation email) and GET (search/list/pagination/
// counts) orchestration logic can be unit tested with a mocked PDO,
// without needing header()/exit() or a real database. NotificationService
// and EmailService are injected so tests can verify calls without any
// real DB write or file I/O.

class BookingApiHandler
{
    private PDO $pdo;
    private NotificationService $notifications;
    private EmailService $emailService;

    public function __construct(PDO $pdo, NotificationService $notifications, EmailService $emailService)
    {
        $this->pdo = $pdo;
        $this->notifications = $notifications;
        $this->emailService = $emailService;
    }

    public function handlePost(array $input): array
    {
        $bookingId = (int) ($input['booking_id'] ?? 0);
        $action = $input['booking_action'] ?? '';

        if ($bookingId === 0 || $action === '') {
            return [
                'status' => 422,
                'body' => ['success' => false, 'error' => 'booking_id and booking_action are required.'],
            ];
        }

        $status = BookingManagementHelper::resolveStatus($action);
        $stmt = $this->pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->execute([$status, $bookingId]);

        $travelerStmt = $this->pdo->prepare("SELECT traveler_id FROM bookings WHERE id = ?");
        $travelerStmt->execute([$bookingId]);
        $travelerId = (int) $travelerStmt->fetchColumn();

        if ($travelerId > 0) {
            $this->notifications->create(
                'booking_status_update',
                "Your booking #{$bookingId} was {$status}.",
                $bookingId,
                'traveler',
                $travelerId
            );

            if ($status === 'approved') {
                $userStmt = $this->pdo->prepare("SELECT name, email FROM users WHERE id = ?");
                $userStmt->execute([$travelerId]);
                $traveler = $userStmt->fetch();

                if ($traveler !== false) {
                    $this->emailService->send(
                        $traveler['email'],
                        $traveler['name'],
                        'Your SAFAR booking is confirmed',
                        "Hi {$traveler['name']},<br><br>Your booking #{$bookingId} has been confirmed. We look forward to your trip!"
                    );
                }
            }
        }

        return [
            'status' => 200,
            'body' => ['success' => true, 'message' => 'Booking status updated.'],
        ];
    }

    public function handleGet(array $query): array
    {
        $statusFilter = $query['status'] ?? 'all';
        $search = trim($query['search'] ?? '');
        $page = (int) ($query['page'] ?? 1);

        $built = BookingSearchQueryBuilder::build($search, $statusFilter, $page);

        $stmt = $this->pdo->prepare($built['query']);
        $stmt->execute($built['params']);
        $bookings = $stmt->fetchAll();

        $countStmt = $this->pdo->prepare($built['countQuery']);
        $countStmt->execute($built['params']);
        $total = (int) $countStmt->fetchColumn();

        $counts = $this->pdo->query("SELECT status, COUNT(*) AS c FROM bookings GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'data' => $bookings,
                'pagination' => [
                    'page' => $built['page'],
                    'per_page' => $built['perPage'],
                    'total' => $total,
                    'total_pages' => BookingSearchQueryBuilder::totalPages($total),
                ],
                'counts' => $counts,
            ],
        ];
    }
}