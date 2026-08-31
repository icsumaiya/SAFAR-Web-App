<?php
// Extracted from api/admin/notifications.php so the POST (mark_read /
// mark_all_read) and GET (recent + unread count) orchestration logic can
// be unit tested with a mocked NotificationService, without needing
// header()/exit() or a real database.

class NotificationApiHandler
{
    private NotificationService $service;

    public function __construct(NotificationService $service)
    {
        $this->service = $service;
    }

    public function handlePost(array $input): array
    {
        $action = $input['action'] ?? '';

        if ($action === 'mark_read') {
            $this->service->markRead((int) ($input['id'] ?? 0));
        } elseif ($action === 'mark_all_read') {
            $this->service->markAllRead();
        } else {
            return ['status' => 422, 'body' => ['success' => false, 'error' => 'Unknown action.']];
        }

        return ['status' => 200, 'body' => ['success' => true, 'message' => 'Updated.']];
    }

    public function handleGet(): array
    {
        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'data' => $this->service->getRecent(),
                'unread_count' => $this->service->getUnreadCount(),
            ],
        ];
    }
}