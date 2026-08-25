<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once 'includes/PaymentSearchQueryBuilder.php';
require_once 'includes/PaymentService.php';

requireRole('admin');

$status_filter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$page = (int) ($_GET['page'] ?? 1);

$built = PaymentSearchQueryBuilder::build($search, $status_filter, $page);

$stmt = $pdo->prepare($built['query']);
$stmt->execute($built['params']);
$payments = $stmt->fetchAll();

$countStmt = $pdo->prepare($built['countQuery']);
$countStmt->execute($built['params']);
$total_matching = (int) $countStmt->fetchColumn();
$total_pages = PaymentSearchQueryBuilder::totalPages($total_matching);
$page = $built['page'];

$service = new PaymentService($pdo);
$stats = $service->getStats();

$active = 'payments';
require_once '../includes/header.php';
?>

<div class="container my-4" style="display: flex; gap: 30px; align-items: flex-start;">
    <?php require_once 'includes/sidebar.php'; ?>

    <div style="flex: 1; min-width: 0; width: 100%;">
        <h1 style="margin-bottom: 25px;">Manage Payments</h1>

        <!-- Stat cards -->
        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 25px; text-align: center;">
            <div class="card" style="padding: 20px;">
                <h3 style="color: var(--text-muted); font-size: 0.9rem;">Total Payments</h3>
                <p style="font-size: 1.8rem; color: var(--primary); font-weight: bold;"><?php echo $stats['total_count']; ?></p>
            </div>
            <div class="card" style="padding: 20px;">
                <h3 style="color: var(--text-muted); font-size: 0.9rem;">Successful</h3>
                <p style="font-size: 1.8rem; color: #166534; font-weight: bold;"><?php echo $stats['successful_count']; ?></p>
            </div>
            <div class="card" style="padding: 20px;">
                <h3 style="color: var(--text-muted); font-size: 0.9rem;">Pending</h3>
                <p style="font-size: 1.8rem; color: #854d0e; font-weight: bold;"><?php echo $stats['pending_count']; ?></p>
            </div>
            <div class="card" style="padding: 20px;">
                <h3 style="color: var(--text-muted); font-size: 0.9rem;">Failed</h3>
                <p style="font-size: 1.8rem; color: #9f1239; font-weight: bold;"><?php echo $stats['failed_count']; ?></p>
            </div>
            <div class="card" style="padding: 20px;">
                <h3 style="color: var(--text-muted); font-size: 0.9rem;">Successful Amount</h3>
                <p style="font-size: 1.8rem; color: var(--primary); font-weight: bold;">$<?php echo number_format($stats['successful_amount'], 2); ?></p>
            </div>
        </div>

        <!-- Search -->
        <form method="GET" action="payments.php" style="margin-bottom: 15px; display: flex; gap: 8px;">
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                   placeholder="Search by traveler name or transaction ID..."
                   style="flex: 1; max-width: 350px; padding: 8px 12px; border: 1px solid var(--border, #ddd); border-radius: 6px;">
            <button type="submit" class="btn" style="padding: 8px 16px; font-size: 0.9rem;">Search</button>
            <?php if ($search !== ''): ?>
                <a href="payments.php?status=<?php echo urlencode($status_filter); ?>" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.9rem;">Clear</a>
            <?php endif; ?>
        </form>

        <!-- Filter tabs -->
        <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
            <?php $searchQs = $search !== '' ? '&search=' . urlencode($search) : ''; ?>
            <a href="payments.php?status=all<?php echo $searchQs; ?>" class="btn <?php echo $status_filter === 'all' ? '' : 'btn-outline'; ?>" style="padding: 8px 16px; font-size: 0.9rem;">All</a>
            <a href="payments.php?status=pending<?php echo $searchQs; ?>" class="btn <?php echo $status_filter === 'pending' ? '' : 'btn-outline'; ?>" style="padding: 8px 16px; font-size: 0.9rem;">Pending</a>
            <a href="payments.php?status=successful<?php echo $searchQs; ?>" class="btn <?php echo $status_filter === 'successful' ? '' : 'btn-outline'; ?>" style="padding: 8px 16px; font-size: 0.9rem;">Successful</a>
            <a href="payments.php?status=failed<?php echo $searchQs; ?>" class="btn <?php echo $status_filter === 'failed' ? '' : 'btn-outline'; ?>" style="padding: 8px 16px; font-size: 0.9rem;">Failed</a>
        </div>

        <div class="card" style="padding: 30px;">
            <?php if (count($payments) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Traveler</th>
                                <th>Package</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Transaction ID</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Booking</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $pay): ?>
                                <?php
                                    $payBadge = match ($pay['status']) {
                                        'successful' => 'badge-approved',
                                        'failed' => 'badge-rejected',
                                        default => 'badge-pending',
                                    };
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pay['traveler_name']); ?></td>
                                    <td><?php echo htmlspecialchars($pay['package_title']); ?></td>
                                    <td>$<?php echo number_format($pay['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $pay['method']))); ?></td>
                                    <td><?php echo htmlspecialchars($pay['transaction_id'] ?? '—'); ?></td>
                                    <td><span class="badge <?php echo $payBadge; ?>"><?php echo ucfirst($pay['status']); ?></span></td>
                                    <td><?php echo date('M j, Y', strtotime($pay['created_at'])); ?></td>
                                    <td><a href="booking-details.php?id=<?php echo $pay['booking_id']; ?>" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;">View Booking</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div style="display: flex; gap: 8px; justify-content: center; margin-top: 20px; flex-wrap: wrap;">
                        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                            <a href="payments.php?status=<?php echo urlencode($status_filter); ?>&page=<?php echo $p; ?><?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>"
                               class="btn <?php echo $p === $page ? '' : 'btn-outline'; ?>" style="padding: 6px 12px; font-size: 0.85rem;">
                                <?php echo $p; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p style="color: var(--text-muted);">
                    <?php echo $search !== '' ? 'No payments match your search.' : 'No payments recorded yet.'; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>