<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
// Observer classes import (নিশ্চিত করবেন এই ফাইলগুলো সঠিক পাথে আছে)
require_once '../admin/includes/observer/BookingObserver.php';
require_once '../admin/includes/observer/BookingSubject.php';
require_once '../admin/includes/observer/AgencyStatsObserver.php';
require_once '../admin/includes/observer/AdminStatsObserver.php';
require_once '../admin/includes/BookingRequestValidator.php';

header('Content-Type: application/json');

$error = BookingRequestValidator::validate(
    $_SERVER['REQUEST_METHOD'],
    isLoggedIn(),
    $_SESSION['user_role'] ?? null,
    $_POST['package_id'] ?? null
);

if ($error !== '') {
    echo json_encode(['success' => false, 'message' => $error]);
    exit();
}

$package_id = $_POST['package_id'];
$traveler_id = $_SESSION['user_id'];

try {
    // Check if booking already exists
    $stmt = $pdo->prepare("SELECT id FROM bookings WHERE traveler_id = ? AND package_id = ? AND status != 'rejected'");
    $stmt->execute([$traveler_id, $package_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You have already booked or requested this tour.']);
        exit();
    }

    $stmt = $pdo->prepare("INSERT INTO bookings (traveler_id, package_id) VALUES (?, ?)");
    $stmt->execute([$traveler_id, $package_id]);

    // Observer: notify agency/admin stats listeners that a new booking event happened
    $subject = new BookingSubject();
    $subject->attach(new AgencyStatsObserver());
    $subject->attach(new AdminStatsObserver());
    $subject->notifyObservers([
        'booking_id' => $pdo->lastInsertId(),
        'package_id' => $package_id,
        'traveler_id' => $traveler_id,
        'status' => 'pending'
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Booking request sent successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
}