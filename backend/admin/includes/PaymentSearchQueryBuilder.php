<?php
// Builds the SQL WHERE clause + bound params for the admin "Manage Payments"
// search/filter/pagination screen. Pure logic (no PDO call), so it can be
// unit tested without a real database.

class PaymentSearchQueryBuilder
{
    public const VALID_STATUSES = ['pending', 'successful', 'failed'];
    public const PER_PAGE = 10;

    /**
     * @param string $search       free-text search on traveler name / transaction id
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
            $where .= " AND (u.name LIKE ? OR pay.transaction_id LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($statusFilter !== 'all' && in_array($statusFilter, self::VALID_STATUSES, true)) {
            $where .= " AND pay.status = ?";
            $params[] = $statusFilter;
        }

        $page = max(1, $page);
        $offset = ($page - 1) * self::PER_PAGE;

        $baseFrom = " FROM payments pay
          JOIN bookings b ON pay.booking_id = b.id
          JOIN users u ON b.traveler_id = u.id
          JOIN packages p ON b.package_id = p.id";

        $query = "SELECT pay.*, u.name AS traveler_name, p.title AS package_title"
            . $baseFrom . $where
            . " ORDER BY pay.created_at DESC LIMIT " . self::PER_PAGE . " OFFSET " . $offset;

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