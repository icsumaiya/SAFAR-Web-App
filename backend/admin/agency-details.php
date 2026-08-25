<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once 'includes/AgencyDetailsService.php';
requireRole('admin');

$agency_id = (int) ($_GET['id'] ?? 0);

$service = new AgencyDetailsService($pdo);
$agency = $service->getAgency($agency_id);

if ($agency === null) {
    header("Location: agencies.php");
    exit();
}

$packages = $service->getPackages($agency_id);
$bookings = $service->getBookings($agency_id);
$revenue = $service->getRevenue($agency_id);

$active = 'agencies';
require_once '../includes/header.php';
?>

<div class="container my-4" style="display: flex; gap: 30px; align-items: flex-start;">
    <?php require_once 'includes/sidebar.php'; ?>

    <div style="flex: 1; min-width: 0; width: 100%;">
        <a href="agencies.php" style="display: inline-block; margin-bottom: 15px; color: var(--text-muted);">&larr; Back to Manage Agencies</a>

        <div class="card" style="padding: 30px; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h1 style="margin-bottom: 5px;"><?php echo htmlspecialchars($agency['company_name']); ?></h1>
                    <p style="color: var(--text-muted); margin: 0;">
                        Contact: <?php echo htmlspecialchars($agency['name']); ?> &middot;
                        <?php echo htmlspecialchars($agency['email']); ?>
                        <?php if (!empty($agency['phone'])): ?>
                            &middot; <?php echo htmlspecialchars($agency['phone']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <?php
                    $badgeClass = match ($agency['status']) {
                        'verified' => 'badge-approved',
                        'rejected' => 'badge-rejected',
                        'suspended' => 'badge-rejected',
                        default => 'badge-pending',
                    };
                ?>
                <span class="badge <?php echo $badgeClass; ?>" style="font-size: 0.9rem; padding: 8px 14px;">
                    <?php echo ucfirst($agency['status']); ?>
                </span>
            </div>
        </div>

        <!-- Stats -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="card" style="padding: 20px; text-align: center;">
                <div style="font-size: 1.8rem; font-weight: 700; color: var(--primary);"><?php echo count($packages); ?></div>
                <div style="color: var(--text-muted); font-size: 0.9rem;">Packages</div>
            </div>
            <div class="card" style="padding: 20px; text-align: center;">
                <div style="font-size: 1.8rem; font-weight: 700; color: var(--primary);"><?php echo count($bookings); ?></div>
                <div style="color: var(--text-muted); font-size: 0.9rem;">Total Bookings</div>
            </div>
            <div class="card" style="padding: 20px; text-align: center;">
                <div style="font-size: 1.8rem; font-weight: 700; color: var(--primary);">
                    $<?php echo number_format($revenue, 2); ?>
                </div>
                <div style="color: var(--text-muted); font-size: 0.9rem;">Revenue (approved bookings)</div>
            </div>
        </div>

        <!-- Packages -->
        <div class="card" style="padding: 30px; margin-bottom: 30px;">
            <h2 style="margin-bottom: 20px;">Packages (<?php echo count($packages); ?>)</h2>
            <?php if (count($packages) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($packages as $pkg): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pkg['title']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($pkg['type'] ?? 'tour')); ?></td>
                                    <td><?php echo htmlspecialchars($pkg['location']); ?></td>
                                    <td>$<?php echo number_format($pkg['price'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted);">This agency has not listed any packages yet.</p>
            <?php endif; ?>
        </div>

        <!-- Bookings -->
        <div class="card" style="padding: 30px;">
            <h2 style="margin-bottom: 20px;">Bookings (<?php echo count($bookings); ?>)</h2>
            <?php if (count($bookings) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Traveler</th>
                                <th>Package</th>
                                <th>Status</th>
                                <th>Booked On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <?php
                                    $bBadge = match ($b['status']) {
                                        'approved' => 'badge-approved',
                                        'rejected' => 'badge-rejected',
                                        default => 'badge-pending',
                                    };
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($b['traveler_name']); ?></td>
                                    <td><?php echo htmlspecialchars($b['package_title']); ?></td>
                                    <td><span class="badge <?php echo $bBadge; ?>"><?php echo ucfirst($b['status']); ?></span></td>
                                    <td><?php echo date('M j, Y', strtotime($b['booking_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted);">No bookings for this agency yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>