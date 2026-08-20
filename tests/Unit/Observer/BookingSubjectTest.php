
Bookingsubjecttest · PHP
<?php
 
use PHPUnit\Framework\TestCase;
 
final class BookingSubjectTest extends TestCase
{
    public function testNotifyObserversCallsUpdateOnSingleAttachedObserver(): void
    {
        $booking = ['booking_id' => 1, 'package_id' => 5, 'status' => 'confirmed'];
 
        // Mock/stub: isolate BookingSubject from any real observer implementation.
        $observer = $this->createMock(BookingObserver::class);
        $observer->expects($this->once())
                 ->method('update')
                 ->with($booking);
 
        $subject = new BookingSubject();
        $subject->attach($observer);
        $subject->notifyObservers($booking);
    }
 
    public function testNotifyObserversCallsUpdateOnEveryAttachedObserver(): void
    {
        $booking = ['booking_id' => 2, 'package_id' => 9, 'status' => 'pending'];
 
        $observerOne = $this->createMock(BookingObserver::class);
        $observerOne->expects($this->once())->method('update')->with($booking);
 
        $observerTwo = $this->createMock(BookingObserver::class);
        $observerTwo->expects($this->once())->method('update')->with($booking);
 
        $subject = new BookingSubject();
        $subject->attach($observerOne);
        $subject->attach($observerTwo);
        $subject->notifyObservers($booking);
    }
 
    public function testNotifyObserversWithNoAttachedObserversDoesNothing(): void
    {
        $subject = new BookingSubject();
 
        // No observers attached - notifyObservers should simply not error.
        $subject->notifyObservers(['booking_id' => 1, 'package_id' => 1, 'status' => 'confirmed']);
 
        $this->assertTrue(true);
    }
 
    public function testObserverNotCalledIfNeverAttached(): void
    {
        $observer = $this->createMock(BookingObserver::class);
        $observer->expects($this->never())->method('update');
 
        $subject = new BookingSubject();
        // Intentionally not attaching $observer.
        $subject->notifyObservers(['booking_id' => 1, 'package_id' => 1, 'status' => 'confirmed']);
    }
}
 
