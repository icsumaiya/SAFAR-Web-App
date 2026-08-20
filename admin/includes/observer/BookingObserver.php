<?php
// BookingObserver.php

interface BookingObserver {
    public function update(array $booking): void;
}
?>