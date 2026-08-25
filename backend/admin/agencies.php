<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
// Command pattern classes import (নিশ্চিত করবেন এই ফাইলগুলো সঠিক পাথে আছে)
require_once 'includes/command/Command.php';
require_once 'includes/command/ApproveAgencyCommand.php';
require_once 'includes/command/RejectAgencyCommand.php';
require_once 'includes/command/UnverifyAgencyCommand.php';
require_once 'includes/command/SuspendAgencyCommand.php';
require_once 'includes/command/ActivateAgencyCommand.php';
require_once 'includes/AgencyCommandFactory.php';
require_once 'includes/AgencySearchQueryBuilder.php';
requireRole('admin');

// Handle agency status change using Command Pattern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['agency_id'])) {
    $agencyId = (int) $_POST['agency_id'];

    $command = AgencyCommandFactory::build($_POST['action'], $pdo, $agencyId);

    if ($command) {
        $command->execute();
    }

    header("Location: agencies.php?msg=updated");
    exit();
}

// Fetch agencies with package counts (for the quick "Pending Approvals" section)
$sql = "SELECT a.*, u.name, u.email,
        (SELECT COUNT(*) FROM packages p WHERE p.agency_id = a.id) AS package_count
        FROM agencies a JOIN users u ON a.user_id = u.id";

$pending_agencies = $pdo->query("$sql WHERE a.status = 'pending' ORDER BY a.id DESC")->fetchAll();

// Search / filter for the full agency directory below
$search = trim($_GET['search'] ?? '');
$filter_status = $_GET['filter_status'] ?? 'all';

$built = AgencySearchQueryBuilder::build($search, $filter_status);
$stmt = $pdo->prepare($built['query']);
$stmt->execute($built['params']);
$all_agencies = $stmt->fetchAll();

$active = 'agencies';
require_once '../includes/header.php';
?>

<div class="container my-4" style="display: flex; gap: 30px; align-items: flex-start;">
    <?php require_once 'includes/sidebar.php'; ?>

    <div style="flex: 1; min-width: 0; width: 100%;">
        <h1 style="margin-bottom: 25px;">Manage Agencies</h1>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
            <div class="alert alert-success">Agency status updated successfully.</div>
        <?php endif; ?>

        <!-- Pending -->
        <div class="card" style="padding: 30px; margin-bottom: 30px;">
            <h2 style="margin-bottom: 20px;">Pending Approvals (<?php echo count($pending_agencies); ?>)</h2>
            <?php if (count($pending_agencies) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Company Name</th>
                                <th>Contact Person</th>
                                <th>Email</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_agencies as $agency): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($agency['company_name']); ?></td>
                                    <td><?php echo htmlspecialchars($agency['name']); ?></td>
                                    <td><?php echo htmlspecialchars($agency['email']); ?></td>
                                    <td>
                                        <form method="POST" action="agencies.php" style="display: inline-flex; gap: 5px;">
                                            <input type="hidden" name="agency_id" value="<?php echo $agency['id']; ?>">
                                            <button type="submit" name="action" value="verify" class="btn" style="background: #10b981; padding: 5px 10px; font-size: 0.8rem;">Verify</button>
                                            <button type="submit" name="action" value="reject" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;" onclick="return confirm('Reject this agency?');">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted);">No pending agencies to approve.</p>
            <?php endif; ?>
        </div>

        <!-- Search / filter for full directory -->
        <div class="card" style="padding: 20px; margin-bottom: 20px;">
            <form method="GET" action="agencies.php" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                <div style="flex: 2; min-width: 200px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Company, contact name, or email..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 5px;">Status</label>
                    <select name="filter_status" class="form-control">
                        <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="verified" <?php echo $filter_status === 'verified' ? 'selected' : ''; ?>>Verified</option>
                        <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="suspended" <?php echo $filter_status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn">Filter</button>
                    <a href="agencies.php" class="btn btn-outline">Reset</a>
                </div>
            </form>
        </div>

        <!-- Full directory -->
        <div class="card" style="padding: 30px;">
            <h2 style="margin-bottom: 20px;">All Agencies (<?php echo count($all_agencies); ?>)</h2>

            <?php if (count($all_agencies) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Company Name</th>
                                <th>Contact Person</th>
                                <th>Email</th>
                                <th>Packages</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_agencies as $agency): ?>
                                <?php
                                    $status = $agency['status'];
                                    $badgeClass = match ($status) {
                                        'verified' => 'badge-approved',
                                        'rejected' => 'badge-rejected',
                                        'suspended' => 'badge-rejected',
                                        default => 'badge-pending',
                                    };
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($agency['company_name']); ?></td>
                                    <td><?php echo htmlspecialchars($agency['name']); ?></td>
                                    <td><?php echo htmlspecialchars($agency['email']); ?></td>
                                    <td><?php echo $agency['package_count']; ?></td>
                                    <td><span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span></td>
                                    <td>
                                        <div style="display: flex; gap: 5px; align-items: center; flex-wrap: wrap;">
                                            <a href="agency-details.php?id=<?php echo $agency['id']; ?>" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;">View Details</a>

                                            <?php if ($status === 'verified'): ?>
                                                <form method="POST" action="agencies.php" onsubmit="return confirm('Suspend this agency? They will not be able to operate until reactivated.');" style="display: inline;">
                                                    <input type="hidden" name="agency_id" value="<?php echo $agency['id']; ?>">
                                                    <button type="submit" name="action" value="suspend" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;">Suspend</button>
                                                </form>
                                            <?php elseif ($status === 'suspended'): ?>
                                                <form method="POST" action="agencies.php" style="display: inline;">
                                                    <input type="hidden" name="agency_id" value="<?php echo $agency['id']; ?>">
                                                    <button type="submit" name="action" value="activate" class="btn" style="background: #10b981; padding: 5px 10px; font-size: 0.8rem;">Activate</button>
                                                </form>
                                            <?php elseif ($status === 'pending'): ?>
                                                <form method="POST" action="agencies.php" style="display: inline-flex; gap: 5px;">
                                                    <input type="hidden" name="agency_id" value="<?php echo $agency['id']; ?>">
                                                    <button type="submit" name="action" value="verify" class="btn" style="background: #10b981; padding: 5px 10px; font-size: 0.8rem;">Verify</button>
                                                    <button type="submit" name="action" value="reject" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;" onclick="return confirm('Reject this agency?');">Reject</button>
                                                </form>
                                            <?php elseif ($status === 'rejected'): ?>
                                                <form method="POST" action="agencies.php" style="display: inline;">
                                                    <input type="hidden" name="agency_id" value="<?php echo $agency['id']; ?>">
                                                    <button type="submit" name="action" value="verify" class="btn" style="background: #10b981; padding: 5px 10px; font-size: 0.8rem;">Verify Instead</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted);">No agencies found matching your search.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>