<?php
// Builds the SQL WHERE clause + bound params for the admin packages
// search/filter/sort/pagination screen (also reused by the public
// explore/homepage listing). Pure logic (no PDO call).
//
// $page is optional (defaults to null = no pagination) for the same
// backward-compatibility reason as AgencySearchQueryBuilder.

class PackageSearchQueryBuilder
{
    public const VALID_SORTS = ['newest', 'price_low', 'price_high', 'popular'];
    public const PER_PAGE = 12;

    /**
     * @param string   $search
     * @param string   $filterType   'all' or a package type
     * @param string   $filterAgency 'all' or an agency id
     * @param string   $sort         one of VALID_SORTS, defaults to 'newest'
     * @param int|null $page         1-indexed page number, or null for no pagination
     * @return array{query:string, params:array, countQuery?:string, page?:int, perPage?:int}
     */
    public static function build(
        string $search,
        string $filterType,
        string $filterAgency,
        string $sort = 'newest',
        ?int $page = null,
        string $filterStatus = 'all'
    ): array {
        $where = " WHERE 1=1";
        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $where .= " AND (p.title LIKE ? OR p.location LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($filterType !== 'all') {
            $where .= " AND p.type = ?";
            $params[] = $filterType;
        }
        if ($filterAgency !== 'all') {
            $where .= " AND p.agency_id = ?";
            $params[] = $filterAgency;
        }
        if ($filterStatus !== 'all') {
            $where .= " AND p.status = ?";
            $params[] = $filterStatus;
        }

        $baseFrom = " FROM packages p JOIN agencies a ON p.agency_id = a.id";
        $select = "SELECT p.*, a.company_name";
        $groupBy = '';
        $orderBy = "p.created_at DESC";

        if ($sort === 'price_low') {
            $orderBy = "p.price ASC";
        } elseif ($sort === 'price_high') {
            $orderBy = "p.price DESC";
        } elseif ($sort === 'popular') {
            // Popularity is derived from real booking counts, never a manual flag.
            $select = "SELECT p.*, a.company_name, COUNT(b.id) AS bookings_count";
            $baseFrom .= " LEFT JOIN bookings b ON b.package_id = p.id";
            $groupBy = " GROUP BY p.id";
            $orderBy = "bookings_count DESC";
        }

        $query = $select . $baseFrom . $where . $groupBy . " ORDER BY " . $orderBy;

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