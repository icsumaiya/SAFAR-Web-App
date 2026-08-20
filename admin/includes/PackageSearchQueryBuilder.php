<?php
// Builds the SQL WHERE clause + bound params for the admin packages search/filter
// screen. Pure logic (no PDO call), extracted from admin/packages.php so it can
// be unit tested without a real database.

class PackageSearchQueryBuilder
{
    /**
     * @param string $search
     * @param string $filterType   'all' or a package type
     * @param string $filterAgency 'all' or an agency id
     * @return array{query:string, params:array}
     */
    public static function build(string $search, string $filterType, string $filterAgency): array
    {
        $query = "SELECT p.*, a.company_name FROM packages p JOIN agencies a ON p.agency_id = a.id WHERE 1=1";
        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $query .= " AND (p.title LIKE ? OR p.location LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($filterType !== 'all') {
            $query .= " AND p.type = ?";
            $params[] = $filterType;
        }
        if ($filterAgency !== 'all') {
            $query .= " AND p.agency_id = ?";
            $params[] = $filterAgency;
        }
        $query .= " ORDER BY p.created_at DESC";

        return ['query' => $query, 'params' => $params];
    }
}