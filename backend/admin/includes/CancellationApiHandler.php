<?php
// Extracted from api/admin/cancellations.php so the POST (approve/reject/
// update_refund actions + traveler notification/email) and GET
// (search/list/pagination) orchestration logic can be unit tested with a
// mocked PDO, without needing header()/exit() or a real database.
// NotificationService and EmailService are injected so tests can verify
// calls without any real DB write or file I/O.

class CancellationApiHandler
{
    private PDO $pdo;
    private CancellationService $service;
    private NotificationService $notifications;
    private EmailService $emailService;

    public function __construct(
        PDO $pdo,
        CancellationService $service,
        NotificationService $notifications,
        EmailService $emailService
    ) {
        $this->pdo = $pdo;
        $this->service = $service;
        $this->notifications = $notifications;
        $this->emailService = $emailService;
    }

    public function handlePost(array $input): array
    {
        $cancellationId = (int) ($input['cancellation_id'] ?? 0);
        $action = $input['action'] ?? '';

        if ($cancellationId === 0 || $action === '') {
            return [
                'status' => 422,
                'body' => ['success' => false, 'error' => 'cancellation_id and action are required.'],
            ];
        }

        if ($action === 'approve') {
            $amount = isset($input['refundable_amount']) && $input['refundable_amount'] !== ''
                ? (float) $input['refundable_amount']
                : null;
            $this->service->approve($cancellationId, $amount);
            $this->notifyTraveler($cancellationId, 'Your cancellation request was approved.');
        } elseif ($action === 'reject') {
            $this->service->reject($cancellationId);
            $this->notifyTraveler($cancellationId, 'Your cancellation request was rejected.');
        } elseif ($action === 'update_refund') {
            $refundStatus = $input['refund_status'] ?? '';
            $error = CancellationValidator::validateRefundStatus($refundStatus);

            if ($error !== '') {
                return ['status' => 422, 'body' => ['success' => false, 'error' => $error]];
            }

            $this->service->updateRefundStatus($cancellationId, $refundStatus);

            $this->notifications->create(
                'refund_update',
                "Refund status updated to '{$refundStatus}' for cancellation #{$cancellationId}.",
                $cancellationId
            );
            $this->notifyTraveler($cancellationId, "Your refund status is now '{$refundStatus}'.");
        } else {
            return ['status' => 422, 'body' => ['success' => false, 'error' => 'Unknown action.']];
        }

        return ['status' => 200, 'body' => ['success' => true, 'message' => 'Cancellation updated.']];
    }

    public function handleGet(array $query): array
    {
        $statusFilter = $query['status'] ?? 'all';
        $search = trim($query['search'] ?? '');
        $page = (int) ($query['page'] ?? 1);

        $built = CancellationSearchQueryBuilder::build($search, $statusFilter, $page);

        $stmt = $this->pdo->prepare($built['query']);
        $stmt->execute($built['params']);
        $cancellations = $stmt->fetchAll();

        $countStmt = $this->pdo->prepare($built['countQuery']);
        $countStmt->execute($built['params']);
        $total = (int) $countStmt->fetchColumn();

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'data' => $cancellations,
                'pagination' => [
                    'page' => $built['page'],
                    'per_page' => $built['perPage'],
                    'total' => $total,
                    'total_pages' => CancellationSearchQueryBuilder::totalPages($total),
                ],
            ],
        ];
    }

    /**
     * Looks up the traveler behind a cancellation and notifies them
     * in-app plus by email (best-effort: silently does nothing if the
     * cancellation or traveler row can't be found).
     */
    private function notifyTraveler(int $cancellationId, string $message): void
    {
        $stmt = $this->pdo->prepare(
            "SELECT b.traveler_id, c.booking_id
             FROM cancellations c JOIN bookings b ON c.booking_id = b.id
             WHERE c.id = ?"
        );
        $stmt->execute([$cancellationId]);
        $row = $stmt->fetch();

        if ($row === false) {
            return;
        }

        $this->notifications->create(
            'cancellation_update',
            $message,
            (int) $row['booking_id'],
            'traveler',
            (int) $row['traveler_id']
        );

        $userStmt = $this->pdo->prepare("SELECT name, email FROM users WHERE id = ?");
        $userStmt->execute([$row['traveler_id']]);
        $traveler = $userStmt->fetch();

        if ($traveler !== false) {
            $this->emailService->send(
                $traveler['email'],
                $traveler['name'],
                'Update on your SAFAR cancellation',
                "Hi {$traveler['name']},<br><br>{$message}"
            );
        }
    }
}