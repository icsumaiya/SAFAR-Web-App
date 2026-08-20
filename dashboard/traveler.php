<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireRole('traveler');

$user_id = $_SESSION['user_id'];
$search = $_GET['search'] ?? '';

// Fetch stats
$total_bookings = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE traveler_id = ?");
$total_bookings->execute([$user_id]);
$total_bookings = $total_bookings->fetchColumn();

$upcoming_trips = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE traveler_id = ? AND status IN ('pending', 'approved')");
$upcoming_trips->execute([$user_id]);
$upcoming_trips = $upcoming_trips->fetchColumn();

// Fetch traveler's bookings with optional search
$query = "
    SELECT b.id as booking_id, b.status, b.booking_date, p.title, p.price, p.location, a.company_name
    FROM bookings b
    JOIN packages p ON b.package_id = p.id
    JOIN agencies a ON p.agency_id = a.id
    WHERE b.traveler_id = ?
";
$params = [$user_id];

if ($search) {
    $query .= " AND (p.title LIKE ? OR p.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$query .= " ORDER BY b.booking_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container my-4" style="display: flex; gap: 30px; align-items: flex-start;">
    <!-- Sidebar -->
    <aside style="width: 250px; background: var(--white); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow-sm); border: 1px solid var(--glass-border);">
        <h3 style="margin-bottom: 20px; color: var(--primary);">Traveler Menu</h3>
        <ul style="list-style: none; padding: 0;">
            <li style="margin-bottom: 10px;">
                <a href="traveler.php" style="display: block; padding: 10px; border-radius: 8px; background: var(--bg-light); color: var(--primary); font-weight: 600;"><i class="fas fa-home"></i> Dashboard</a>
            </li>
            <li style="margin-bottom: 10px;">
                <a href="../explore.php" style="display: block; padding: 10px; border-radius: 8px; color: var(--text-main); transition: var(--transition);"><i class="fas fa-search"></i> Explore Tours</a>
            </li>
            <li>
                <a href="../profile.php" style="display: block; padding: 10px; border-radius: 8px; color: var(--text-main); transition: var(--transition);"><i class="fas fa-user"></i> Update Profile</a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div style="flex: 1;">
        <h1 style="margin-bottom: 10px;">Traveler Dashboard</h1>
        <p style="margin-bottom: 30px; color: var(--text-muted);">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>!</p>
        
        <!-- Stats Cards -->
        <div class="grid" style="grid-template-columns: repeat(2, 1fr); margin-bottom: 40px; text-align: center;">
            <div class="card" style="padding: 30px;">
                <h3 style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 10px;">Total Bookings</h3>
                <p style="font-size: 2.5rem; color: var(--primary); font-weight: 800;"><?php echo $total_bookings; ?></p>
            </div>
            <div class="card" style="padding: 30px;">
                <h3 style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 10px;">Upcoming Trips</h3>
                <p style="font-size: 2.5rem; color: var(--secondary); font-weight: 800;"><?php echo $upcoming_trips; ?></p>
            </div>
        </div>
        
        <div class="card" style="padding: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <h2 style="margin: 0;">My Bookings</h2>
                <form method="GET" action="traveler.php" style="display: flex; gap: 10px;">
                    <input type="text" name="search" class="form-control" placeholder="Search bookings..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 8px 12px; width: 200px;">
                    <button type="submit" class="btn" style="padding: 8px 15px;">Search</button>
                </form>
            </div>
            
            <?php if (count($bookings) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tour Package</th>
                                <th>Agency</th>
                                <th>Date Requested</th>
                                <th>Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['title']); ?><br><small style="color: var(--text-muted);"><?php echo htmlspecialchars($booking['location']); ?></small></td>
                                    <td><?php echo htmlspecialchars($booking['company_name']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></td>
                                    <td>$<?php echo number_format($booking['price'], 2); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($booking['status']); ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted);">No bookings found matching your criteria. <a href="../explore.php" style="color: var(--primary);">Explore packages</a></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
