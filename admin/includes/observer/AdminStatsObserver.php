<?php
// AdminStatsObserver.php

class AdminStatsObserver implements BookingObserver {
    public function update(array $booking): void {
        // Refresh admin panel's total bookings metric
        error_log("Admin dashboard metrics refreshed. booking_id: " . ($booking['booking_id'] ?? 'n/a'));
    }
}
?>