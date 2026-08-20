<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireRole('admin');

$current_admin_id = $_SESSION['user_id'];
$msg = '';

// ---------- Delete user ----------
if (isset($_GET['delete_user'])) {
    $del_id = (int)$_GET['delete_user'];
    if ($del_id === (int)$current_admin_id) {
        $msg = 'error:You cannot delete your own admin account.';
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$del_id]);
        $msg = 'success:User deleted successfully.';
    }
}

// ---------- Change role ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'], $_POST['user_id'])) {
    $uid = (int)$_POST['user_id'];
    $new_role = $_POST['new_role'];
    $valid_roles = ['traveler', 'agency', 'admin'];

    if ($uid === (int)$current_admin_id) {
        $msg = 'error:You cannot change your own role.';
    } elseif (!in_array($new_role, $valid_roles, true)) {
        $msg = 'error:Invalid role selected.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $uid]);
        $msg = 'success:Role updated successfully.';
    }
}

// ---------- Search ----------
$search = trim($_GET['search'] ?? '');
$filter_role = $_GET['filter_role'] ?? 'all';

$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM bookings bk WHERE bk.traveler_id = u.id) AS booking_count
          FROM users u WHERE 1=1";
$params = [];

if ($search !== '') {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter_role !== 'all') {
    $query .= " AND u.role = ?";
    $params[] = $filter_role;
}
$query .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$all_users = $stmt->fetchAll();

$active = 'users';
require_once '../includes/header.php';
?>

<div class="container my-4" style="display: flex; gap: 30px; align-items: flex-start;">
    <?php require_once 'includes/sidebar.php'; ?>

    <div style="flex: 1; min-width: 0; width: 100%;">
        <h1 style="margin-bottom: 25px;">Manage Users</h1>

        <?php if ($msg): ?>
            <?php [$type, $text] = explode(':', $msg, 2); ?>
            <div class="alert alert-<?php echo $type === 'error' ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($text); ?></div>
        <?php endif; ?>

        <!-- Search / filter -->
        <div class="card" style="padding: 20px; margin-bottom: 20px;">
            <form method="GET" action="users.php" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                <div style="flex: 2; min-width: 200px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Name or email..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 5px;">Role</label>
                    <select name="filter_role" class="form-control">
                        <option value="all" <?php echo $filter_role === 'all' ? 'selected' : ''; ?>>All Roles</option>
                        <option value="traveler" <?php echo $filter_role === 'traveler' ? 'selected' : ''; ?>>Traveler</option>
                        <option value="agency" <?php echo $filter_role === 'agency' ? 'selected' : ''; ?>>Agency</option>
                        <option value="admin" <?php echo $filter_role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn">Filter</button>
                    <a href="users.php" class="btn btn-outline">Reset</a>
                </div>
            </form>
        </div>

        <div class="card" style="padding: 30px;">
            <h2 style="margin-bottom: 20px;">All Users (<?php echo count($all_users); ?>)</h2>

            <?php if (count($all_users) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Bookings</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_users as $u): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($u['name']); ?>
                                        <?php if ((int)$u['id'] === (int)$current_admin_id): ?>
                                            <span style="font-size: 0.75rem; color: var(--text-muted);">(you)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><span class="badge badge-<?php echo strtolower($u['role']) === 'admin' ? 'approved' : 'pending'; ?>"><?php echo ucfirst($u['role']); ?></span></td>
                                    <td><?php echo $u['booking_count']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                                    <td>
                                        <?php if ((int)$u['id'] !== (int)$current_admin_id): ?>
                                            <div style="display: flex; gap: 5px; align-items: center; flex-wrap: wrap;">
                                                <form method="POST" action="users.php" style="display: inline-flex; gap: 5px;">
                                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                    <select name="new_role" class="form-control" style="padding: 5px; font-size: 0.8rem; width: auto;">
                                                        <option value="traveler" <?php echo $u['role'] === 'traveler' ? 'selected' : ''; ?>>Traveler</option>
                                                        <option value="agency" <?php echo $u['role'] === 'agency' ? 'selected' : ''; ?>>Agency</option>
                                                        <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                    </select>
                                                    <button type="submit" name="change_role" value="1" class="btn" style="padding: 5px 10px; font-size: 0.8rem;">Update</button>
                                                </form>
                                                <a href="users.php?delete_user=<?php echo $u['id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;" onclick="return confirm('Delete this user permanently? This cannot be undone.');">Delete</a>
                                            </div>
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
                <p style="color: var(--text-muted);">No users found matching your search.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>