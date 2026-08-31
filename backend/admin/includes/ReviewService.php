<?php
// DB operations for review submission (traveler) and moderation (admin),
// plus per-package and per-agency rating aggregates. Extracted as a service
// so it can be unit tested with a mocked PDO instead of a real connection.

class ReviewService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Returns an error message if a review cannot be submitted for this
     * booking (not found / not owned by traveler / not approved yet /
     * already reviewed), plus the booking's package_id when eligible.
     *
     * @return array{error:string, package_id:?int}
     */
    public function guardSubmission(int $bookingId, int $travelerId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT status, package_id FROM bookings WHERE id = ? AND traveler_id = ?"
        );
        $stmt->execute([$bookingId, $travelerId]);
        $booking = $stmt->fetch();

        if ($booking === false) {
            return ['error' => 'Booking not found.', 'package_id' => null];
        }

        if ($booking['status'] !== 'approved') {
            return ['error' => 'You can only review a completed booking.', 'package_id' => null];
        }

        $stmt = $this->pdo->prepare("SELECT id FROM reviews WHERE booking_id = ?");
        $stmt->execute([$bookingId]);

        if ($stmt->fetch() !== false) {
            return ['error' => 'You have already reviewed this booking.', 'package_id' => null];
        }

        return [
            'error' => '',
            'package_id' => (int) $booking['package_id']
        ];
    }

    public function submitReview(
        int $bookingId,
        int $travelerId,
        int $packageId,
        int $rating,
        string $comment
    ): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO reviews (booking_id, traveler_id, package_id, rating, comment)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $bookingId,
            $travelerId,
            $packageId,
            $rating,
            $comment !== '' ? $comment : null
        ]);
    }

    public function setStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE reviews SET status = ? WHERE id = ?"
        );

        $stmt->execute([$status, $id]);
    }

    /**
     * Average rating, review count, and 1-5 star distribution for a
     * package — only counting visible reviews.
     */
    public function getForPackage(int $packageId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(AVG(rating), 0) AS avg_rating, COUNT(*) AS review_count
             FROM reviews
             WHERE package_id = ? AND status = 'visible'"
        );

        $stmt->execute([$packageId]);
        $summary = $stmt->fetch();

        $stmt = $this->pdo->prepare(
            "SELECT rating, COUNT(*) AS count
             FROM reviews
             WHERE package_id = ? AND status = 'visible'
             GROUP BY rating"
        );

        $stmt->execute([$packageId]);
        $rows = $stmt->fetchAll();

        $distribution = [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0
        ];

        foreach ($rows as $row) {
            $distribution[(int) $row['rating']] = (int) $row['count'];
        }

        return [
            'average_rating' => round((float) $summary['avg_rating'], 2),
            'review_count' => (int) $summary['review_count'],
            'distribution' => $distribution,
        ];
    }

    /**
     * Returns the average rating and review count for an agency.
     * Only visible reviews are counted.
     *
     * @return array{average_rating:float, review_count:int}
     */
    public function getForAgency(int $agencyId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(AVG(r.rating), 0) AS avg_rating,
                    COUNT(*) AS review_count
             FROM reviews r
             JOIN packages p ON r.package_id = p.id
             WHERE p.agency_id = ? AND r.status = 'visible'"
        );

        $stmt->execute([$agencyId]);
        $summary = $stmt->fetch();

        return [
            'average_rating' => round((float) $summary['avg_rating'], 2),
            'review_count' => (int) $summary['review_count'],
        ];
    }

    /**
     * Real, database-driven counts for the admin stat cards. Never
     * hardcoded.
     *
     * @return array{
     *     total_count:int,
     *     visible_count:int,
     *     hidden_count:int,
     *     average_rating:float
     * }
     */
    public function getStats(): array
    {
        $counts = $this->pdo->query(
            "SELECT status, COUNT(*) AS c
             FROM reviews
             GROUP BY status"
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        $averageRating = (float) $this->pdo->query(
            "SELECT COALESCE(AVG(rating), 0)
             FROM reviews
             WHERE status = 'visible'"
        )->fetchColumn();

        return [
            'total_count' => array_sum($counts),
            'visible_count' => (int) ($counts['visible'] ?? 0),
            'hidden_count' => (int) ($counts['hidden'] ?? 0),
            'average_rating' => round($averageRating, 2),
        ];
    }
}