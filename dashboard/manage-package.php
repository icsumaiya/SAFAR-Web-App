<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireRole('agency');

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id, status FROM agencies WHERE user_id = ?");
$stmt->execute([$user_id]);
$agency = $stmt->fetch();

if (!$agency || $agency['status'] !== 'verified') {
    die("Unauthorized or unverified agency.");
}

$agency_id = $agency['id'];
$error = '';
$success = '';

$edit_id = $_GET['edit'] ?? null;
$package = null;

if ($edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ? AND agency_id = ?");
    $stmt->execute([$edit_id, $agency_id]);
    $package = $stmt->fetch();
    if (!$package) {
        die("Package not found.");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $location = trim($_POST['location']);
    $price = $_POST['price'];
    $description = trim($_POST['description']);
    $image_url = trim($_POST['image_url']);
    
    if (isset($_POST['delete']) && $edit_id) {
        $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ? AND agency_id = ?");
        $stmt->execute([$edit_id, $agency_id]);
        header("Location: agency.php");
        exit();
    }
    
    if (empty($title) || empty($location) || empty($price) || empty($description)) {
        $error = "Please fill in all required fields.";
    } else {
        if ($edit_id) {
            $stmt = $pdo->prepare("UPDATE packages SET title=?, location=?, price=?, description=?, image_url=? WHERE id=? AND agency_id=?");
            $stmt->execute([$title, $location, $price, $description, $image_url, $edit_id, $agency_id]);
            $success = "Package updated successfully.";
            // Update local $package variable
            $package = ['title'=>$title, 'location'=>$location, 'price'=>$price, 'description'=>$description, 'image_url'=>$image_url];
        } else {
            $stmt = $pdo->prepare("INSERT INTO packages (agency_id, title, location, price, description, image_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$agency_id, $title, $location, $price, $description, $image_url]);
            $success = "New package added successfully.";
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container my-4">
    <div class="auth-form" style="max-width: 600px;">
        <h2><?php echo $edit_id ? 'Edit Package' : 'Create New Package'; ?></h2>
        
        <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Package Title</label>
                <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($package['title'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" class="form-control" required value="<?php echo htmlspecialchars($package['location'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Price ($)</label>
                <input type="number" step="0.01" name="price" class="form-control" required value="<?php echo htmlspecialchars($package['price'] ?? ''); ?>">
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
                <?php if ($edit_id): ?>
                    <button type="submit" name="delete" value="1" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this package?');">Delete</button>
                <?php endif; ?>
                <a href="agency.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
