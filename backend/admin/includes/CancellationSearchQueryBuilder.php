<?php
// Builds the SQL WHERE clause + bound params for the admin "Cancellations"
// search/filter/pagination screen. Pure logic (no PDO call), so it can be
// unit tested without a real database.

class CancellationSearchQueryBuilder
{
    public const VALID_STATUSES = ['requested', 'approved', 'rejected', 'completed'];
    public const PER_PAGE = 10;

    public static function build(string $search, string $statusFilter, int $page): array
    {
        $where = " WHERE 1=1";
        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $where .= " AND (u.name LIKE ? OR p.title LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($statusFilter !== 'all' && in_array($statusFilter, self::VALID_STATUSES, true)) {
            $where .= " AND c.status = ?";
            $params[] = $statusFilter;
        }

        $page = max(1, $page);
        $offset = ($page - 1) * self::PER_PAGE;

        $baseFrom = " FROM cancellations c
          JOIN bookings b ON c.booking_id = b.id
          JOIN users u ON b.traveler_id = u.id
          JOIN packages p ON b.package_id = p.id";

        $query = "SELECT c.*, u.name AS traveler_name, p.title AS package_title, b.status AS booking_status"
            . $baseFrom . $where
            . " ORDER BY c.requested_at DESC LIMIT " . self::PER_PAGE . " OFFSET " . $offset;

        $countQuery = "SELECT COUNT(*)" . $baseFrom . $where;

        return [
            'query' => $query,
            'countQuery' => $countQuery,
            'params' => $params,
            'page' => $page,
            'perPage' => self::PER_PAGE,
        ];
    }

    public static function totalPages(int $totalRows): int
    {
        return (int) max(1, ceil($totalRows / self::PER_PAGE));
    }
}