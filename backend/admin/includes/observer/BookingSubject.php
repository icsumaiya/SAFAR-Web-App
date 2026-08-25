<?php
// BookingSubject.php

class BookingSubject {
    private array $observers = [];

    public function attach(BookingObserver $observer): void {
        $this->observers[] = $observer;
    }

    public function notifyObservers(array $booking): void {
        foreach ($this->observers as $observer) {
            $observer->update($booking);
        }
    }
}
?>