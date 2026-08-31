<?php
// Builds the SQL WHERE clause + bound params for the admin "Manage
// Reviews" search/filter/sort/pagination screen. Pure logic (no PDO
// call), so it can be unit tested without a real database.

class ReviewSearchQueryBuilder
{
    public const VALID_STATUSES = ['visible', 'hidden'];
    public const VALID_SORTS = ['newest', 'rating_high', 'rating_low'];
    public const PER_PAGE = 10;

    public static function build(string $search, string $statusFilter, int $page, string $sort = 'newest'): array
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
            $where .= " AND r.status = ?";
            $params[] = $statusFilter;
        }

        $page = max(1, $page);
        $offset = ($page - 1) * self::PER_PAGE;

        $baseFrom = " FROM reviews r
          JOIN users u ON r.traveler_id = u.id
          JOIN packages p ON r.package_id = p.id";

        $orderBy = match ($sort) {
            'rating_high' => "r.rating DESC",
            'rating_low' => "r.rating ASC",
            default => "r.created_at DESC",
        };

        $query = "SELECT r.*, u.name AS traveler_name, p.title AS package_title"
            . $baseFrom . $where
            . " ORDER BY " . $orderBy . " LIMIT " . self::PER_PAGE . " OFFSET " . $offset;

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