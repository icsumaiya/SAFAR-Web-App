<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
// Command pattern classes & Factory import (নিশ্চিত করবেন এই ফাইলগুলো সঠিক পাথে আছে)
require_once 'includes/command/Command.php';
require_once 'includes/command/ApproveAgencyCommand.php';
require_once 'includes/command/RejectAgencyCommand.php';
require_once 'includes/command/UnverifyAgencyCommand.php';
require_once 'includes/AgencyCommandFactory.php';
requireRole('admin');

// Handle agency status change using Command Pattern via Factory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['agency_id'])) {
    $agencyId = (int) $_POST['agency_id'];
    
    $command = AgencyCommandFactory::build($_POST['action'], $pdo, $agencyId);

    if ($command) {
        $command->execute();
    }

    header("Location: agencies.php?msg=updated");
    exit();
}

// Fetch agencies with package counts
$sql = "SELECT a.*, u.name, u.email,
        (SELECT COUNT(*) FROM packages p WHERE p.agency_id = a.id) AS package_count
        FROM agencies a JOIN users u ON a.user_id = u.id";

$pending_agencies = $pdo->query("$sql WHERE a.status = 'pending' ORDER BY a.id DESC")->fetchAll();
$verified_agencies = $pdo->query("$sql WHERE a.status = 'verified' ORDER BY a.company_name ASC")->fetchAll();
$rejected_agencies = $pdo->query("$sql WHERE a.status = 'rejected' ORDER BY a.id DESC")->fetchAll();

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

        <!-- Verified -->
        <div class="card" style="padding: 30px; margin-bottom: 30px;">
            <h2 style="margin-bottom: 20px;">Verified Agencies (<?php echo count($verified_agencies); ?>)</h2>
            <?php if (count($verified_agencies) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Company Name</th>
                                <th>Contact Person</th>
                                <th>Email</th>
                                <th>Packages</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($verified_agencies as $agency): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($agency['company_name']); ?></td>
                                    <td><?php echo htmlspecialchars($agency['name']); ?></td>
                                    <td><?php echo htmlspecialchars($agency['email']); ?></td>
                                    <td><?php echo $agency['package_count']; ?></td>
                                    <td><span class="badge badge-approved">Verified</span></td>
                                    <td>
                                        <form method="POST" action="agencies.php" onsubmit="return confirm('Move this agency back to pending?');">
                                            <input type="hidden" name="agency_id" value="<?php echo $agency['id']; ?>">
                                            <button type="submit" name="action" value="unverify" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;">Un-verify</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted);">No verified agencies yet.</p>
            <?php endif; ?>
        </div>

        <!-- Rejected -->
        <div class="card" style="padding: 30px;">
            <h2 style="margin-bottom: 20px;">Rejected Agencies (<?php echo count($rejected_agencies); ?>)</h2>
            <?php if (count($rejected_agencies) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Company Name</th>
                                <th>Contact Person</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rejected_agencies as $agency): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($agency['company_name']); ?></td>
                                    <td><?php echo htmlspecialchars($agency['name']); ?></td>
                                    <td><?php echo htmlspecialchars($agency['email']); ?></td>
                                    <td><span class="badge badge-rejected">Rejected</span></td>
                                    <td>
                                        <form method="POST" action="agencies.php">
                                            <input type="hidden" name="agency_id" value="<?php echo $agency['id']; ?>">
                                            <button type="submit" name="action" value="verify" class="btn" style="background: #10b981; padding: 5px 10px; font-size: 0.8rem;">Verify Instead</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted);">No rejected agencies.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>