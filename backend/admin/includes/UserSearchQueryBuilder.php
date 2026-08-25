<?php
// Builds the SQL WHERE clause + bound params for the admin "Manage Users"
// search/filter screen. Pure logic (no PDO call), extracted from
// admin/users.php so it can be unit tested without a real database.

class UserSearchQueryBuilder
{
    /**
     * @return array{query:string, params:array}
     */
    public static function build(string $search, string $filterRole): array
    {
        $query = "SELECT u.*, 
          (SELECT COUNT(*) FROM bookings bk WHERE bk.traveler_id = u.id) AS booking_count
          FROM users u WHERE 1=1";
        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $query .= " AND (u.name LIKE ? OR u.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($filterRole !== 'all') {
            $query .= " AND u.role = ?";
            $params[] = $filterRole;
        }
        $query .= " ORDER BY u.created_at DESC";

        return ['query' => $query, 'params' => $params];
    }
}