<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once 'includes/AdminDashboardService.php';

requireRole('admin');

$dashboardService = new AdminDashboardService($pdo);
$stats = $dashboardService->getStats();
$users_count = $stats['users_count'];
$packages_count = $stats['packages_count'];
$bookings_count = $stats['bookings_count'];
$pending_agencies_count = $stats['pending_agencies_count'];
$pending_bookings_count = $stats['pending_bookings_count'];

// Recent activity (last 5 bookings)
$recent_bookings = $dashboardService->getRecentBookings(5);

// Recent signups (last 5 users)
$recent_users = $dashboardService->getRecentUsers(5);

$active = 'dashboard';
$page_title = 'Admin Dashboard';
require_once '../includes/header.php';
?>

<div class="container my-4" style="display: flex; gap: 30px; align-items: flex-start;">
    <?php require_once 'includes/sidebar.php'; ?>

    <div style="flex: 1; min-width: 0; width: 100%;">
        <h1 style="margin-bottom: 30px;">Dashboard</h1>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-success">Action completed successfully.</div>
        <?php endif; ?>

        <!-- Stat cards -->
        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; text-align: center;">
            <div class="card" style="padding: 20px;">
                <h3 style="color: var(--text-muted); font-size: 0.95rem;">Total Users</h3>
                <p style="font-size: 2rem; color: var(--primary); font-weight: bold;"><?php echo $users_count; ?></p>
            </div>
            <div class="card" style="padding: 20px;">
                <h3 style="color: var(--text-muted); font-size: 0.95rem;">Total Packages</h3>
                <p style="font-size: 2rem; color: var(--primary); font-weight: bold;"><?php echo $packages_count; ?></p>
            </div>
            <div class="card" style="padding: 20px;">
                <h3 style="color: var(--text-muted); font-size: 0.95rem;">Total Bookings</h3>
                <p style="font-size: 2rem; color: var(--primary); font-weight: bold;"><?php echo $bookings_count; ?></p>
            </div>
        </div>

        <!-- Attention-needed cards -->
        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px;">
            <a href="agencies.php" style="text-decoration: none;">
                <div class="card" style="padding: 20px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #f59e0b;">
                    <div>
                        <h3 style="color: var(--text-main); font-size: 1rem; margin-bottom: 5px;">Pending Agencies</h3>
                        <p style="color: var(--text-muted); font-size: 0.85rem;">Waiting for your approval</p>
                    </div>
                    <span style="font-size: 1.8rem; font-weight: bold; color: #f59e0b;"><?php echo $pending_agencies_count; ?></span>
                </div>
            </a>
            <a href="bookings.php" style="text-decoration: none;">
                <div class="card" style="padding: 20px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #f59e0b;">
                    <div>
                        <h3 style="color: var(--text-main); font-size: 1rem; margin-bottom: 5px;">Pending Bookings</h3>
                        <p style="color: var(--text-muted); font-size: 0.85rem;">Need approve or reject</p>
                    </div>
                    <span style="font-size: 1.8rem; font-weight: bold; color: #f59e0b;"><?php echo $pending_bookings_count; ?></span>
                </div>
            </a>
        </div>

        <!-- Recent activity -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
            <div class="card" style="padding: 25px;">
                <h2 style="margin-bottom: 15px; font-size: 1.2rem;">Recent Bookings</h2>
                <?php if (count($recent_bookings) > 0): ?>
                    <?php foreach ($recent_bookings as $b): ?>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--glass-border);">
                            <div>
                                <strong><?php echo htmlspecialchars($b['traveler_name']); ?></strong>
                                <div style="font-size: 0.85rem; color: var(--text-muted);"><?php echo htmlspecialchars($b['package_title']); ?></div>
                            </div>
                            <span class="badge badge-<?php echo strtolower($b['status']); ?>"><?php echo ucfirst($b['status']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: var(--text-muted);">No bookings yet.</p>
                <?php endif; ?>
            </div>

            <div class="card" style="padding: 25px;">
                <h2 style="margin-bottom: 15px; font-size: 1.2rem;">Recent Signups</h2>
                <?php if (count($recent_users) > 0): ?>
                    <?php foreach ($recent_users as $u): ?>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--glass-border);">
                            <div>
                                <strong><?php echo htmlspecialchars($u['name']); ?></strong>
                                <div style="font-size: 0.85rem; color: var(--text-muted);"><?php echo htmlspecialchars($u['email']); ?></div>
                            </div>
                            <span class="badge badge-pending"><?php echo ucfirst($u['role']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: var(--text-muted);">No users yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>