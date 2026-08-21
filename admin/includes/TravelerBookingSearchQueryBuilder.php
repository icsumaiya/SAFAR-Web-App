<?php
// Builds the SQL WHERE clause + bound params for the traveler dashboard's
// "My Bookings" search. Pure logic (no PDO call), extracted from
// dashboard/traveler.php so it can be unit tested without a real database.

class TravelerBookingSearchQueryBuilder
{
    /**
     * @return array{query:string, params:array}
     */
    public static function build(int $userId, string $search): array
    {
        $query = "
    SELECT b.id as booking_id, b.status, b.booking_date, p.title, p.price, p.location, a.company_name
    FROM bookings b
    JOIN packages p ON b.package_id = p.id
    JOIN agencies a ON p.agency_id = a.id
    WHERE b.traveler_id = ?
";
        $params = [$userId];

        if ($search !== '') {
            $query .= " AND (p.title LIKE ? OR p.location LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $query .= " ORDER BY b.booking_date DESC";

        return ['query' => $query, 'params' => $params];
    }
}