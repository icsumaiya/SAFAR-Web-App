<?php
// AgencyStatsObserver.php

class AgencyStatsObserver implements BookingObserver {
    public function update(array $booking): void {
        // Refresh agency dashboard counters (Total Bookings / Pending Requests)
        error_log("Agency stats refreshed for package_id: " . $booking['package_id'] . ", status: " . $booking['status']);
    }
}
?>