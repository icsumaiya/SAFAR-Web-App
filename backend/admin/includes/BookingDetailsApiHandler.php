<?php
// Extracted from api/admin/booking-details.php so the "fetch booking,
// 404 if missing, attach formatted reference" orchestration logic can be
// unit tested with a mocked PDO, without needing header()/exit() or a
// real database. Reuses BookingDetailsService — nothing duplicated.

class BookingDetailsApiHandler
{
    private BookingDetailsService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new BookingDetailsService($pdo);
    }

    /**
     * @return array{status:int, body:array}
     */
    public function handleGet(int $bookingId): array
    {
        $booking = $this->service->getBooking($bookingId);

        if ($booking === null) {
            return [
                'status' => 404,
                'body' => ['success' => false, 'error' => 'Booking not found.'],
            ];
        }

        $booking['reference'] = BookingDetailsService::formatReference((int) $booking['id']);

        return [
            'status' => 200,
            'body' => ['success' => true, 'data' => $booking],
        ];
    }
}