<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once 'includes/BookingManagementHelper.php';

requireRole('admin');

// Handle booking status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_action'], $_POST['booking_id'])) {
    $baction = BookingManagementHelper::resolveStatus($_POST['booking_action']);
    $bid = $_POST['booking_id'];
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->execute([$baction, $bid]);
    header("Location: " . BookingManagementHelper::buildRedirectUrl($_GET['status'] ?? null));
    exit();
}

$status_filter = $_GET['status'] ?? 'all';

$built = BookingManagementHelper::buildListQuery($status_filter);

$stmt = $pdo->prepare($built['query']);
$stmt->execute($built['params']);
$all_bookings = $stmt->fetchAll();

// Counts for filter tabs
$counts = $pdo->query("SELECT status, COUNT(*) AS c FROM bookings GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$total_count = array_sum($counts);

$active = 'bookings';
require_once '../includes/header.php';
?>

<div class="container my-4" style="display: flex; gap: 30px; align-items: flex-start;">
    <?php require_once 'includes/sidebar.php'; ?>

    <div style="flex: 1; min-width: 0; width: 100%;">
        <h1 style="margin-bottom: 25px;">Manage Bookings</h1>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
            <div class="alert alert-success">Booking status updated successfully.</div>
        <?php endif; ?>

        <!-- Filter tabs -->
        <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
            <a href="bookings.php?status=all" class="btn <?php echo $status_filter === 'all' ? '' : 'btn-outline'; ?>" style="padding: 8px 16px; font-size: 0.9rem;">
                All (<?php echo $total_count; ?>)
            </a>
            <a href="bookings.php?status=pending" class="btn <?php echo $status_filter === 'pending' ? '' : 'btn-outline'; ?>" style="padding: 8px 16px; font-size: 0.9rem;">
                Pending (<?php echo $counts['pending'] ?? 0; ?>)
            </a>
            <a href="bookings.php?status=approved" class="btn <?php echo $status_filter === 'approved' ? '' : 'btn-outline'; ?>" style="padding: 8px 16px; font-size: 0.9rem;">
                Approved (<?php echo $counts['approved'] ?? 0; ?>)
            </a>
            <a href="bookings.php?status=rejected" class="btn <?php echo $status_filter === 'rejected' ? '' : 'btn-outline'; ?>" style="padding: 8px 16px; font-size: 0.9rem;">
                Rejected (<?php echo $counts['rejected'] ?? 0; ?>)
            </a>
        </div>

        <div class="card" style="padding: 30px;">
            <?php if (count($all_bookings) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Traveler</th>
                                <th>Package</th>
                                <th>Agency</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_bookings as $b): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($b['traveler_name']); ?></td>
                                    <td><?php echo htmlspecialchars($b['package_title']); ?></td>
                                    <td><?php echo htmlspecialchars($b['company_name']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($b['booking_date'])); ?></td>
                                    <td><span class="badge badge-<?php echo strtolower($b['status']); ?>"><?php echo ucfirst($b['status']); ?></span></td>
                                    <td>
                                        <?php if ($b['status'] === 'pending'): ?>
                                            <form method="POST" action="bookings.php<?php echo $status_filter !== 'all' ? '?status=' . urlencode($status_filter) : ''; ?>" style="display: inline-flex; gap: 5px;">
                                                <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                <button type="submit" name="booking_action" value="approve" class="btn" style="background: #10b981; padding: 5px 10px; font-size: 0.8rem;">Approve</button>
                                                <button type="submit" name="booking_action" value="reject" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;">Reject</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 0.8rem;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted);">No bookings found for this filter.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>