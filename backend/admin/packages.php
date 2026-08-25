<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once 'includes/PackageFactory.php';
require_once 'includes/PackageValidator.php';
require_once 'includes/PackageSearchQueryBuilder.php';
requireRole('admin');

// ---------- Delete ----------
if (isset($_GET['delete_package'])) {
    $del_id = $_GET['delete_package'];
    $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ?");
    $stmt->execute([$del_id]);
    header("Location: packages.php?msg=deleted");
    exit();
}

// ---------- Create / Edit form state ----------
$show_form = isset($_GET['new']) || isset($_GET['edit']);
$edit_id = $_GET['edit'] ?? null;
$type = $_GET['type'] ?? 'tour';
$package = null;
$error = '';
$success = '';

if ($edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
    $stmt->execute([$edit_id]);
    $package = $stmt->fetch();
    if (!$package) {
        die("Package not found.");
    }
    $type = $package['type'] ?? 'tour';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_package'])) {
    $title = trim($_POST['title']);
    $location = trim($_POST['location']);
    $price = $_POST['price'];
    $description = trim($_POST['description']);
    $image_url = trim($_POST['image_url']);
    $pkg_type = $_POST['type'] ?? 'tour';
    $agency_id = $_POST['agency_id'] ?? null;
    $post_edit_id = $_POST['edit_id'] ?? null;

    $error = PackageValidator::validate($_POST);
    if ($error !== '') {
        $show_form = true;
        $edit_id = $post_edit_id;
        $package = $_POST;
    } else {
        // Factory Method: builds the Package object from submitted data
        $pkg = PackageFactory::createPackage($pkg_type, $_POST);

        if ($post_edit_id) {
            $stmt = $pdo->prepare("UPDATE packages SET agency_id=?, title=?, location=?, price=?, description=?, image_url=?, type=? WHERE id=?");
            $stmt->execute([$agency_id, $pkg->title, $pkg->location, $pkg->price, $pkg->description, $pkg->image_url, $pkg->type, $post_edit_id]);
            header("Location: packages.php?msg=updated");
            exit();
        } else {
            $stmt = $pdo->prepare("INSERT INTO packages (agency_id, title, location, price, description, image_url, type) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$agency_id, $pkg->title, $pkg->location, $pkg->price, $pkg->description, $pkg->image_url, $pkg->type]);
            header("Location: packages.php?msg=created");
            exit();
        }
    }
}

// Agencies dropdown (verified only)
$agencies = $pdo->query("SELECT id, company_name FROM agencies WHERE status = 'verified'")->fetchAll();

// ---------- List + Search/Filter ----------
$search = trim($_GET['search'] ?? '');
$filter_type = $_GET['filter_type'] ?? 'all';
$filter_agency = $_GET['filter_agency'] ?? 'all';

$built = PackageSearchQueryBuilder::build($search, $filter_type, $filter_agency);

$stmt = $pdo->prepare($built['query']);
$stmt->execute($built['params']);
$all_packages = $stmt->fetchAll();

// All agencies (for filter dropdown, including non-verified so old data still shows a name)
$all_agencies_for_filter = $pdo->query("SELECT id, company_name FROM agencies ORDER BY company_name")->fetchAll();

$active = 'packages';
require_once '../includes/header.php';
?>

<div class="container my-4" style="display: flex; gap: 30px; align-items: flex-start;">
    <?php require_once 'includes/sidebar.php'; ?>

    <div style="flex: 1; min-width: 0; width: 100%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
            <h1 style="margin: 0;">Manage Packages</h1>
            <div style="display: flex; gap: 10px;">
                <a href="packages.php?new=1&type=tour" class="btn">Create Tour</a>
                <a href="packages.php?new=1&type=hotel" class="btn btn-accent">Create Hotel</a>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php
                echo match ($_GET['msg']) {
                    'deleted' => 'Package deleted successfully.',
                    'created' => 'Package created successfully.',
                    'updated' => 'Package updated successfully.',
                    default => 'Done.'
                };
                ?>
            </div>
        <?php endif; ?>

        <?php if ($show_form): ?>
            <!-- ============ CREATE / EDIT FORM ============ -->
            <div class="card" style="padding: 30px; max-width: 650px; margin-bottom: 30px;">
                <h2 style="color: var(--primary); margin-bottom: 20px;">
                    <?php echo $edit_id ? 'Edit Package' : 'Create New ' . ucfirst(htmlspecialchars($type)); ?>
                </h2>

                <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

                <form method="POST" action="packages.php">
                    <input type="hidden" name="save_package" value="1">
                    <input type="hidden" name="edit_id" value="<?php echo htmlspecialchars($edit_id ?? ''); ?>">

                    <div class="form-group">
                        <label>Package Type</label>
                        <select name="type" class="form-control" required>
                            <option value="tour" <?php echo ($type === 'tour') ? 'selected' : ''; ?>>Tour Package</option>
                            <option value="hotel" <?php echo ($type === 'hotel') ? 'selected' : ''; ?>>Hotel Listing</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Assign to Agency</label>
                        <select name="agency_id" class="form-control" required>
                            <option value="">-- Select Agency --</option>
                            <?php foreach ($agencies as $ag): ?>
                                <option value="<?php echo $ag['id']; ?>" <?php echo (isset($package['agency_id']) && $package['agency_id'] == $ag['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ag['company_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($package['title'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" class="form-control" required value="<?php echo htmlspecialchars($package['location'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Price ($)</label>
                        <input type="number" step="0.01" min="0" name="price" class="form-control" required value="<?php echo htmlspecialchars($package['price'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Image URL (Optional)</label>
                        <input type="url" name="image_url" class="form-control" placeholder="https://..." value="<?php echo htmlspecialchars($package['image_url'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($package['description'] ?? ''); ?></textarea>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn" style="flex: 1;"><?php echo $edit_id ? 'Update Package' : 'Create Package'; ?></button>
                        <a href="packages.php" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- ============ SEARCH / FILTER ============ -->
        <div class="card" style="padding: 20px; margin-bottom: 20px;">
            <form method="GET" action="packages.php" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                <div style="flex: 2; min-width: 200px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Title or location..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 5px;">Type</label>
                    <select name="filter_type" class="form-control">
                        <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="tour" <?php echo $filter_type === 'tour' ? 'selected' : ''; ?>>Tour</option>
                        <option value="hotel" <?php echo $filter_type === 'hotel' ? 'selected' : ''; ?>>Hotel</option>
                    </select>
                </div>
                <div style="flex: 1; min-width: 180px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 5px;">Agency</label>
                    <select name="filter_agency" class="form-control">
                        <option value="all">All Agencies</option>
                        <?php foreach ($all_agencies_for_filter as $ag): ?>
                            <option value="<?php echo $ag['id']; ?>" <?php echo ($filter_agency == $ag['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ag['company_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn">Filter</button>
                    <a href="packages.php" class="btn btn-outline">Reset</a>
                </div>
            </form>
        </div>

        <!-- ============ TABLE ============ -->
        <div class="card" style="padding: 30px;">
            <h2 style="margin-bottom: 20px;">All Packages (<?php echo count($all_packages); ?>)</h2>

            <?php if (count($all_packages) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Agency</th>
                                <th>Location</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_packages as $pkg): ?>
                                <tr>
                                    <td>
                                        <div style="width: 60px; height: 45px; border-radius: 6px; background-color: #cbd5e1; background-size: cover; background-position: center; background-image: url('<?php echo htmlspecialchars($pkg['image_url'] ?: ''); ?>');"></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($pkg['title']); ?></td>
                                    <td><span class="badge badge-pending"><?php echo ucfirst($pkg['type'] ?? 'Tour'); ?></span></td>
                                    <td><?php echo htmlspecialchars($pkg['company_name']); ?></td>
                                    <td><?php echo htmlspecialchars($pkg['location']); ?></td>
                                    <td>$<?php echo number_format($pkg['price'], 2); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <a href="packages.php?edit=<?php echo $pkg['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem;">Edit</a>
                                            <a href="packages.php?delete_package=<?php echo $pkg['id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;" onclick="return confirm('Delete this package? This cannot be undone.');">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted);">No packages found matching your filters.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>