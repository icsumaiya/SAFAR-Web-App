<?php
// Builds the SQL WHERE clause + bound params for the admin "Commission &
// Revenue" history table search/filter/pagination. Pure logic (no PDO
// call), so it can be unit tested without a real database.

class CommissionSearchQueryBuilder
{
    public const PER_PAGE = 10;

    /**
     * @param string $search   free-text search on agency name
     * @param int    $agencyId 0 = no filter, otherwise filter to this agency
     * @param int    $page     1-indexed page number
     * @return array{query:string, countQuery:string, params:array, page:int, perPage:int}
     */
    public static function build(string $search, int $agencyId, int $page): array
    {
        $where = " WHERE 1=1";
        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $where .= " AND a.company_name LIKE ?";
            $params[] = "%$search%";
        }

        if ($agencyId > 0) {
            $where .= " AND c.agency_id = ?";
            $params[] = $agencyId;
        }

        $page = max(1, $page);
        $offset = ($page - 1) * self::PER_PAGE;

        $baseFrom = " FROM commissions c
          JOIN agencies a ON c.agency_id = a.id
          JOIN bookings b ON c.booking_id = b.id
          JOIN packages p ON b.package_id = p.id";

        $query = "SELECT c.*, a.company_name, p.title AS package_title"
            . $baseFrom . $where
            . " ORDER BY c.created_at DESC LIMIT " . self::PER_PAGE . " OFFSET " . $offset;

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