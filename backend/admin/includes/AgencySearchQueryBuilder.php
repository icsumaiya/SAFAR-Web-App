<?php
// Builds the SQL WHERE clause + bound params for the admin "Manage Agencies"
// search/filter screen. Pure logic (no PDO call), so it can be unit tested
// without a real database.

class AgencySearchQueryBuilder
{
    public const VALID_STATUSES = ['pending', 'verified', 'rejected', 'suspended'];

    /**
     * @param string $search       free-text search on company name / contact name / email
     * @param string $statusFilter 'all' or one of VALID_STATUSES
     * @return array{query:string, params:array}
     */
    public static function build(string $search, string $statusFilter): array
    {
        $query = "SELECT a.*, u.name, u.email,
        (SELECT COUNT(*) FROM packages p WHERE p.agency_id = a.id) AS package_count
        FROM agencies a JOIN users u ON a.user_id = u.id WHERE 1=1";
        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $query .= " AND (a.company_name LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($statusFilter !== 'all' && in_array($statusFilter, self::VALID_STATUSES, true)) {
            $query .= " AND a.status = ?";
            $params[] = $statusFilter;
        }

        $query .= " ORDER BY a.id DESC";

        return ['query' => $query, 'params' => $params];
    }
}