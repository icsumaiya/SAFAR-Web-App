<?php
// Pure logic extracted from admin/bookings.php: no DB call, no header()/exit(),
// so it can be unit tested in isolation.

class BookingManagementHelper
{
    /**
     * Maps the submitted 'booking_action' POST value to the DB status value.
     */
    public static function resolveStatus(string $bookingAction): string
    {
        return $bookingAction === 'approve' ? 'approved' : 'rejected';
    }

    /**
     * Builds the redirect URL used after a status change, preserving the
     * current status filter (if any) in the query string.
     */
    public static function buildRedirectUrl(?string $currentStatusFilter): string
    {
        $url = 'bookings.php?msg=updated';
        if ($currentStatusFilter !== null && $currentStatusFilter !== '') {
            $url .= '&status=' . urlencode($currentStatusFilter);
        }
        return $url;
    }

    /**
     * @return array{query:string, params:array}
     */
    public static function buildListQuery(string $statusFilter): array
    {
        $query = "SELECT b.*, u.name AS traveler_name, p.title AS package_title, a.company_name
          FROM bookings b
          JOIN users u ON b.traveler_id = u.id
          JOIN packages p ON b.package_id = p.id
          JOIN agencies a ON p.agency_id = a.id
          WHERE 1=1";
        $params = [];

        if ($statusFilter !== 'all') {
            $query .= " AND b.status = ?";
            $params[] = $statusFilter;
        }
        $query .= " ORDER BY b.booking_date DESC";

        return ['query' => $query, 'params' => $params];
    }
}