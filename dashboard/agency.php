<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
// Observer classes import
require_once '../admin/includes/observer/BookingObserver.php';
require_once '../admin/includes/observer/BookingSubject.php';
require_once '../admin/includes/observer/AgencyStatsObserver.php';
require_once '../admin/includes/observer/AdminStatsObserver.php';
requireRole('agency');

$user_id = $_SESSION['user_id'];

// Get Agency ID
$stmt = $pdo->prepare("SELECT id, status FROM agencies WHERE user_id = ?");
$stmt->execute([$user_id]);
$agency = $stmt->fetch();

if (!$agency) {
    die("Error: Agency profile not found.");
}

$agency_id = $agency['id'];
$is_verified = $agency['status'] === 'verified';

// Handle booking status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['booking_id'])) {
    $action = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    $booking_id = $_POST['booking_id'];
    
    // Verify this booking belongs to this agency's package
    $stmt = $pdo->prepare("UPDATE bookings b JOIN packages p ON b.package_id = p.id SET b.status = ? WHERE b.id = ? AND p.agency_id = ?");
    $stmt->execute([$action, $booking_id, $agency_id]);

    // Observer: notify agency/admin stats listeners that booking status changed
    $subject = new BookingSubject();
    $subject->attach(new AgencyStatsObserver());
    $subject->attach(new AdminStatsObserver());
    $subject->notifyObservers([
        'booking_id' => $booking_id,
        'package_id' => null,
        'traveler_id' => null,
        'status' => $action
    ]);
}

// Fetch Agency's Packages
$stmt = $pdo->prepare("SELECT * FROM packages WHERE agency_id = ? ORDER BY created_at DESC");
$stmt->execute([$agency_id]);
$packages = $stmt->fetchAll();

// Fetch Booking Requests for Agency
$stmt = $pdo->prepare("
    SELECT b.id as booking_id, b.status, b.booking_date, p.title, u.name as traveler_name, u.email as traveler_email
    FROM bookings b
    JOIN packages p ON b.package_id = p.id
    JOIN users u ON b.traveler_id = u.id
    WHERE p.agency_id = ?
    ORDER BY b.booking_date DESC
");
$stmt->execute([$agency_id]);
$bookings = $stmt->fetchAll();

// Fetch stats
$total_packages = $pdo->prepare("SELECT COUNT(*) FROM packages WHERE agency_id = ?");
$total_packages->execute([$agency_id]);
$total_packages = $total_packages->fetchColumn();

$total_bookings = $pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN packages p ON b.package_id = p.id WHERE p.agency_id = ?");
$total_bookings->execute([$agency_id]);
$total_bookings = $total_bookings->fetchColumn();

$pending_bookings = $pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN packages p ON b.package_id = p.id WHERE p.agency_id = ? AND b.status = 'pending'");
$pending_bookings->execute([$agency_id]);
$pending_bookings = $pending_bookings->fetchColumn();

require_once '../includes/header.php';
?>

<div class="container my-4" style="display: flex; gap: 30px; align-items: flex-start;">
    <!-- Sidebar -->
    <aside style="width: 250px; background: var(--white); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow-sm); border: 1px solid var(--glass-border);">
        <h3 style="margin-bottom: 20px; color: var(--primary);">Agency Menu</h3>
        <ul style="list-style: none; padding: 0;">
            <li style="margin-bottom: 10px;">
                <a href="agency.php" style="display: block; padding: 10px; border-radius: 8px; background: var(--bg-light); color: var(--primary); font-weight: 600;"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            </li>
            <?php if ($is_verified): ?>
            <li style="margin-bottom: 10px;">
                <a href="manage-package.php" style="display: block; padding: 10px; border-radius: 8px; color: var(--text-main); transition: var(--transition);"><i class="fas fa-plus"></i> Create Package</a>
            </li>
            <?php endif; ?>
            <li>
                <a href="../profile.php" style="display: block; padding: 10px; border-radius: 8px; color: var(--text-main); transition: var(--transition);"><i class="fas fa-user-edit"></i> Agency Profile</a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div style="flex: 1;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1>Agency Dashboard</h1>
            <?php if ($is_verified): ?>
                <a href="manage-package.php" class="btn">+ Add New Tour/Hotel</a>
            <?php endif; ?>
        </div>
        
        <?php if (!$is_verified): ?>
            <div class="alert alert-error">
                <strong>Notice:</strong> Your agency account is currently pending verification by an administrator. You cannot post packages until you are verified.
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 40px; text-align: center;">
            <div class="card" style="padding: 20px;">
                <h3 style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 10px;">Total Packages</h3>
                <p style="font-size: 2.5rem; color: var(--primary); font-weight: 800;"><?php echo $total_packages; ?></p>
            </div>
            <div class="card" style="padding: 20px;">
                <h3 style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 10px;">Total Bookings</h3>
                <p style="font-size: 2.5rem; color: var(--secondary); font-weight: 800;"><?php echo $total_bookings; ?></p>
            </div>
            <div class="card" style="padding: 20px;">
                <h3 style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 10px;">Pending Requests</h3>
                <p style="font-size: 2.5rem; color: var(--accent); font-weight: 800;"><?php echo $pending_bookings; ?></p>
            </div>
        </div>

        <div class="grid" style="grid-template-columns: 1fr; gap: 30px;">
            <!-- Booking Requests -->
            <div class="card" style="padding: 30px;">
                <h2 style="margin-bottom: 20px;">Recent Booking Requests</h2>
                <?php if (count($bookings) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Traveler</th>
                                    <th>Package</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($booking['traveler_name']); ?><br>
                                            <small><a href="mailto:<?php echo htmlspecialchars($booking['traveler_email']); ?>"><?php echo htmlspecialchars($booking['traveler_email']); ?></a></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($booking['title']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower($booking['status']); ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($booking['status'] === 'pending'): ?>
                                                <form method="POST" action="" style="display: inline-flex; gap: 5px;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                    <button type="submit" name="action" value="approve" class="btn" style="background: #10b981; padding: 5px 10px; font-size: 0.8rem;">Approve</button>
                                                    <button type="submit" name="action" value="reject" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;">Reject</button>
                                                </form>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted); font-size: 0.8rem;">No action needed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-muted);">No booking requests yet.</p>
                <?php endif; ?>
            </div>

            <!-- Packages Management -->
            <div class="card" style="padding: 30px;">
                <h2 style="margin-bottom: 20px;">My Packages</h2>
                <?php if (count($packages) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Price</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($packages as $pkg): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($pkg['title']); ?></td>
                                        <td><span class="badge badge-pending"><?php echo ucfirst($pkg['type'] ?? 'Tour'); ?></span></td>
                                        <td>$<?php echo number_format($pkg['price'], 2); ?></td>
                                        <td>
                                            <a href="manage-package.php?edit=<?php echo $pkg['id']; ?>" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-muted);">You haven't added any packages yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>