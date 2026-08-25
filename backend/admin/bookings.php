<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once 'includes/BookingManagementHelper.php';
require_once 'includes/BookingSearchQueryBuilder.php';

requireRole('admin');

// Handle booking status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_action'], $_POST['booking_id'])) {
    $baction = BookingManagementHelper::resolveStatus($_POST['booking_action']);
    $bid = $_POST['booking_id'];
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->execute([$baction, $bid]);
    $redirect = BookingManagementHelper::buildRedirectUrl($_GET['status'] ?? null);
    if (!empty($_GET['search'])) {
        $redirect .= '&search=' . urlencode($_GET['search']);
    }
    header("Location: " . $redirect);
    exit();
}

$status_filter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$page = (int) ($_GET['page'] ?? 1);

$built = BookingSearchQueryBuilder::build($search, $status_filter, $page);

$stmt = $pdo->prepare($built['query']);
$stmt->execute($built['params']);
$all_bookings = $stmt->fetchAll();

$countStmt = $pdo->prepare($built['countQuery']);
$countStmt->execute($built['params']);
$total_matching = (int) $countStmt->fetchColumn();
$total_pages = BookingSearchQueryBuilder::totalPages($total_matching);
$page = $built['page'];

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

        <!-- Search -->
        <form method="GET" action="bookings.php" style="margin-bottom: 15px; display: flex; gap: 8px;">
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                   placeholder="Search by traveler, package, or agency..."
                   style="flex: 1; max-width: 350px; padding: 8px 12px; border: 1px solid var(--border, #ddd); border-radius: 6px;">
            <button type="submit" class="btn" style="padding: 8px 16px; font-size: 0.9rem;">Search</button>
            <?php if ($search !== ''): ?>
                <a href="bookings.php?status=<?php echo urlencode($status_filter); ?>" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.9rem;">Clear</a>
            <?php endif; ?>
        </form>

        <!-- Filter tabs -->
        <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
            <?php $searchQs = $search !== '' ? '&search=' . urlencode($search) : ''; ?>
            <a href="bookings.php?status=all<?php echo $searchQs; ?>" class="btn <?php echo $status_filter === 'all' ? '' : 'btn-outline'; ?>" style="padding: 8px 16px; font-size: 0.9rem;">
                All (<?php echo $total_count; ?>)
            </a>
            <a href="bookings.php?status=pending<?php echo $searchQs; ?>" class="btn <?php echo $status_filter === 'pending' ? '' : 'btn-outline'; ?>" style="padding: 8px 16px; font-size: 0.9rem;">
                Pending (<?php echo $counts['pending'] ?? 0; ?>)
            </a>
            <a href="bookings.php?status=approved<?php echo $searchQs; ?>" class="btn <?php echo $status_filter === 'approved' ? '' : 'btn-outline'; ?>" style="padding: 8px 16px; font-size: 0.9rem;">
                Approved (<?php echo $counts['approved'] ?? 0; ?>)
            </a>
            <a href="bookings.php?status=rejected<?php echo $searchQs; ?>" class="btn <?php echo $status_filter === 'rejected' ? '' : 'btn-outline'; ?>" style="padding: 8px 16px; font-size: 0.9rem;">
                Rejected (<?php echo $status_filter === 'rejected' ? '' : 'btn-outline'; ?>" style="padding: 8px 16px; font-size: 0.9rem;">
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
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap; align-items: center;">
                                            <?php if ($b['status'] === 'pending'): ?>
                                                <form method="POST" action="bookings.php<?php echo $status_filter !== 'all' ? '?status=' . urlencode($status_filter) : ''; ?>" style="display: inline-flex; gap: 5px;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                    <button type="submit" name="booking_action" value="approve" class="btn" style="background: #10b981; padding: 5px 10px; font-size: 0.8rem;">Approve</button>
                                                    <button type="submit" name="booking_action" value="reject" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;">Reject</button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="booking-details.php?id=<?php echo $b['id']; ?>" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;">View Details</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div style="display: flex; gap: 8px; justify-content: center; margin-top: 20px; flex-wrap: wrap;">
                        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                            <a href="bookings.php?status=<?php echo urlencode($status_filter); ?>&page=<?php echo $p; ?><?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>"
                               class="btn <?php echo $p === $page ? '' : 'btn-outline'; ?>" style="padding: 6px 12px; font-size: 0.85rem;">
                                <?php echo $p; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p style="color: var(--text-muted);">
                    <?php echo $search !== '' ? 'No bookings match your search.' : 'No bookings found for this filter.'; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>