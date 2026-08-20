<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
// Observer classes import (নিশ্চিত করবেন এই ফাইলগুলো সঠিক পাথে আছে)
require_once '../admin/includes/observer/BookingObserver.php';
require_once '../admin/includes/observer/BookingSubject.php';
require_once '../admin/includes/observer/AgencyStatsObserver.php';
require_once '../admin/includes/observer/AdminStatsObserver.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

if (!isLoggedIn() || $_SESSION['user_role'] !== 'traveler') {
    echo json_encode(['success' => false, 'message' => 'You must be logged in as a traveler to book a tour.']);
    exit();
}

$package_id = $_POST['package_id'] ?? null;
$traveler_id = $_SESSION['user_id'];

if (!$package_id) {
    echo json_encode(['success' => false, 'message' => 'Package ID is missing.']);
    exit();
}

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