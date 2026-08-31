<?php
// Builds the SQL WHERE clause + bound params for the admin "Manage Agencies"
// search/filter/sort/pagination screen. Pure logic (no PDO call), so it
// can be unit tested without a real database.
//
// $page is optional and defaults to null (no LIMIT/OFFSET) to preserve
// the original unlimited-result behavior for the legacy PHP admin page,
// which still relies on counting the full result set for its tab counts.

class AgencySearchQueryBuilder
{
    public const VALID_STATUSES = ['pending', 'verified', 'rejected', 'suspended'];
    public const VALID_SORTS = ['newest', 'name_asc', 'name_desc'];
    public const PER_PAGE = 10;

    /**
     * @param string   $search       free-text search on company name / contact name / email
     * @param string   $statusFilter 'all' or one of VALID_STATUSES
     * @param string   $sort         one of VALID_SORTS, defaults to 'newest'
     * @param int|null $page         1-indexed page number, or null for no pagination
     * @return array{query:string, params:array, countQuery?:string, page?:int, perPage?:int}
     */
    public static function build(string $search, string $statusFilter, string $sort = 'newest', ?int $page = null): array
    {
        $where = " WHERE 1=1";
        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $where .= " AND (a.company_name LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($statusFilter !== 'all' && in_array($statusFilter, self::VALID_STATUSES, true)) {
            $where .= " AND a.status = ?";
            $params[] = $statusFilter;
        }

        $orderBy = match ($sort) {
            'name_asc' => "a.company_name ASC",
            'name_desc' => "a.company_name DESC",
            default => "a.id DESC",
        };

        $baseFrom = " FROM agencies a JOIN users u ON a.user_id = u.id";
        $select = "SELECT a.*, u.name, u.email,
        (SELECT COUNT(*) FROM packages p WHERE p.agency_id = a.id) AS package_count";

        $query = $select . $baseFrom . $where . " ORDER BY " . $orderBy;

        $result = ['query' => $query, 'params' => $params];

        if ($page !== null) {
            $page = max(1, $page);
            $offset = ($page - 1) * self::PER_PAGE;
            $result['query'] .= " LIMIT " . self::PER_PAGE . " OFFSET " . $offset;
            $result['countQuery'] = "SELECT COUNT(*)" . $baseFrom . $where;
            $result['page'] = $page;
            $result['perPage'] = self::PER_PAGE;
        }

        return $result;
    }

    public static function totalPages(int $totalRows): int
    {
        return (int) max(1, ceil($totalRows / self::PER_PAGE));
    }
}