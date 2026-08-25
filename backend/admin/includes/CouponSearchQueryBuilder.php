<?php
// Builds the SQL WHERE clause + bound params for the admin "Manage
// Coupons" search/filter/pagination screen. Pure logic (no PDO call).

class CouponSearchQueryBuilder
{
    public const PER_PAGE = 10;

    public static function build(string $search, string $statusFilter, int $page): array
    {
        $where = " WHERE 1=1";
        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $where .= " AND code LIKE ?";
            $params[] = "%$search%";
        }

        if ($statusFilter === 'active') {
            $where .= " AND is_active = 1";
        } elseif ($statusFilter === 'inactive') {
            $where .= " AND is_active = 0";
        }

        $page = max(1, $page);
        $offset = ($page - 1) * self::PER_PAGE;

        $query = "SELECT * FROM coupons" . $where
            . " ORDER BY created_at DESC LIMIT " . self::PER_PAGE . " OFFSET " . $offset;

        $countQuery = "SELECT COUNT(*) FROM coupons" . $where;

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