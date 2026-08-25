<?php
// Fetches everything needed for the admin "Booking Details" page: the
// booking's own row, the traveler's contact info, the package's full
// info, and the agency's contact info — all via real joined data, never
// hardcoded. Extracted as a service so it can be unit tested with a
// mocked PDO instead of a real database connection.

class BookingDetailsService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getBooking(int $bookingId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT b.*,
                    u.name AS traveler_name, u.email AS traveler_email,
                    p.title AS package_title, p.location AS package_location,
                    p.price AS package_price, p.description AS package_description,
                    a.id AS agency_id, a.company_name, a.phone AS agency_phone, au.email AS agency_email
             FROM bookings b
             JOIN users u ON b.traveler_id = u.id
             JOIN packages p ON b.package_id = p.id
             JOIN agencies a ON p.agency_id = a.id
             JOIN users au ON a.user_id = au.id
             WHERE b.id = ?"
        );
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();

        return $booking !== false ? $booking : null;
    }

    /**
     * Human-friendly booking reference built from the real booking id
     * (never a fake/random value), e.g. id 42 -> "BKG-000042".
     */
    public static function formatReference(int $bookingId): string
    {
        return 'BKG-' . str_pad((string) $bookingId, 6, '0', STR_PAD_LEFT);
    }
}