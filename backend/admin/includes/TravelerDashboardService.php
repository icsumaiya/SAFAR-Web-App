<?php
// DB operations for the traveler-facing dashboard (Phase 11): overview
// stats, upcoming trips, booking history by category, payment history, and
// the traveler's own reviews. Read-only aggregation over existing tables —
// no new tables needed. Extracted as a service so it can be unit tested
// with a mocked PDO instead of a real connection.

class TravelerDashboardService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Top-level counts for the dashboard overview cards.
     *
     * 'completed' has no dedicated bookings.status value — it's computed
     * as an approved booking whose check_out date has already passed.
     *
     * @return array{total:int, upcoming:int, active:int, completed:int, cancelled:int}
     */
    public function getOverview(int $travelerId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'approved' AND (check_out IS NULL OR check_out >= CURDATE()) THEN 1 ELSE 0 END) AS upcoming,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'approved' AND check_out IS NOT NULL AND check_out < CURDATE() THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status IN ('cancelled', 'rejected') THEN 1 ELSE 0 END) AS cancelled
             FROM bookings
             WHERE traveler_id = ?"
        );
        $stmt->execute([$travelerId]);
        $row = $stmt->fetch();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'upcoming' => (int) ($row['upcoming'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
            'completed' => (int) ($row['completed'] ?? 0),
            'cancelled' => (int) ($row['cancelled'] ?? 0),
        ];
    }

    /**
     * Approved bookings with a future (or open-ended) check-out date,
     * soonest first — for the "Upcoming Trips" section.
     *
     * Includes payment_status from that booking's most recent payment
     * (a booking can have more than one payment row — e.g. a failed
     * attempt followed by a successful one — so this follows the same
     * "latest by created_at" convention PaymentService already uses).
     */
    public function getUpcomingTrips(int $travelerId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT b.id AS booking_id, b.status, b.check_in, b.check_out,
                    p.title, p.location, p.type, a.company_name,
                    (SELECT pay.status FROM payments pay
                     WHERE pay.booking_id = b.id
                     ORDER BY pay.created_at DESC LIMIT 1) AS payment_status
             FROM bookings b
             JOIN packages p ON b.package_id = p.id
             JOIN agencies a ON p.agency_id = a.id
             WHERE b.traveler_id = ?
               AND b.status = 'approved'
               AND (b.check_out IS NULL OR b.check_out >= CURDATE())
             ORDER BY b.check_in IS NULL, b.check_in ASC"
        );
        $stmt->execute([$travelerId]);

        return $stmt->fetchAll();
    }

    /**
     * All bookings for one dashboard tab. $category must be one of
     * 'pending', 'active', 'completed', 'cancelled', or 'all'.
     */
    public function getBookingsByCategory(int $travelerId, string $category): array
    {
        $where = "b.traveler_id = ?";
        $params = [$travelerId];

        switch ($category) {
            case 'pending':
                $where .= " AND b.status = 'pending'";
                break;
            case 'active':
                $where .= " AND b.status = 'approved' AND (b.check_out IS NULL OR b.check_out >= CURDATE())";
                break;
            case 'completed':
                $where .= " AND b.status = 'approved' AND b.check_out IS NOT NULL AND b.check_out < CURDATE()";
                break;
            case 'cancelled':
                $where .= " AND b.status IN ('cancelled', 'rejected')";
                break;
            case 'all':
                break;
            default:
                return [];
        }

        $stmt = $this->pdo->prepare(
            "SELECT b.id AS booking_id, b.status, b.booking_date, b.check_in, b.check_out,
                    p.title, p.location, p.type, p.price, a.company_name
             FROM bookings b
             JOIN packages p ON b.package_id = p.id
             JOIN agencies a ON p.agency_id = a.id
             WHERE $where
             ORDER BY b.booking_date DESC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Payment history across all of the traveler's bookings, most recent
     * first.
     */
    public function getPaymentHistory(int $travelerId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT pay.id AS payment_id, pay.amount, pay.method, pay.transaction_id,
                    pay.status, pay.discount_amount, pay.created_at,
                    b.id AS booking_id, p.title
             FROM payments pay
             JOIN bookings b ON pay.booking_id = b.id
             JOIN packages p ON b.package_id = p.id
             WHERE b.traveler_id = ?
             ORDER BY pay.created_at DESC"
        );
        $stmt->execute([$travelerId]);

        return $stmt->fetchAll();
    }

    /**
     * Reviews the traveler has submitted, including hidden ones (they
     * should still see their own moderated review), most recent first.
     */
    public function getMyReviews(int $travelerId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT r.id, r.booking_id, r.rating, r.comment, r.status, r.created_at, p.title
             FROM reviews r
             JOIN bookings b ON r.booking_id = b.id
             JOIN packages p ON b.package_id = p.id
             WHERE r.traveler_id = ?
             ORDER BY r.created_at DESC"
        );
        $stmt->execute([$travelerId]);

        return $stmt->fetchAll();
    }
}