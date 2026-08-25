<?php
// Builds the SQL WHERE clause + bound params for the admin "Manage Bookings"
// search/filter/pagination screen. Pure logic (no PDO call), so it can be
// unit tested without a real database.

class BookingSearchQueryBuilder
{
    public const VALID_STATUSES = ['pending', 'approved', 'rejected'];
    public const PER_PAGE = 10;

    /**
     * @param string $search       free-text search on traveler name / package title / agency name
     * @param string $statusFilter 'all' or one of VALID_STATUSES
     * @param int    $page         1-indexed page number
     * @return array{query:string, countQuery:string, params:array, page:int, perPage:int}
     */
    public static function build(string $search, string $statusFilter, int $page): array
    {
        $where = " WHERE 1=1";
        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $where .= " AND (u.name LIKE ? OR p.title LIKE ? OR a.company_name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($statusFilter !== 'all' && in_array($statusFilter, self::VALID_STATUSES, true)) {
            $where .= " AND b.status = ?";
            $params[] = $statusFilter;
        }

        $page = max(1, $page);
        $offset = ($page - 1) * self::PER_PAGE;

        $baseFrom = " FROM bookings b
          JOIN users u ON b.traveler_id = u.id
          JOIN packages p ON b.package_id = p.id
          JOIN agencies a ON p.agency_id = a.id";

        $query = "SELECT b.*, u.name AS traveler_name, p.title AS package_title, a.company_name"
            . $baseFrom . $where
            . " ORDER BY b.booking_date DESC LIMIT " . self::PER_PAGE . " OFFSET " . $offset;

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