<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($name) && !empty($email)) {
        $profile_image = null;

        // ---- Profile image upload handling ----
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $max_size = 2 * 1024 * 1024; // 2MB

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $_FILES['profile_image']['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mime_type, $allowed_types)) {
                    $error = "Only JPG, PNG, GIF, or WEBP images are allowed.";
                } elseif ($_FILES['profile_image']['size'] > $max_size) {
                    $error = "Image size must be under 2MB.";
                } else {
                    $upload_dir = __DIR__ . '/uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                    $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
                    $destination = $upload_dir . $filename;

                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $destination)) {
                        // Delete old profile image if it exists
                        $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
                        $stmt->execute([$user_id]);
                        $old = $stmt->fetchColumn();
                        if (!empty($old) && file_exists(__DIR__ . '/' . $old)) {
                            unlink(__DIR__ . '/' . $old);
                        }
                        $profile_image = 'uploads/' . $filename;
                    } else {
                        $error = "Failed to upload image. Check folder permissions.";
                    }
                }
            } else {
                $error = "Image upload error (code: " . $_FILES['profile_image']['error'] . ").";
            }
        }

        // ---- Save to database only if there was no image error ----
        if ($error === null) {
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                if ($profile_image) {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ?, profile_image = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $hashed, $profile_image, $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $hashed, $user_id]);
                }
            } else {
                if ($profile_image) {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, profile_image = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $profile_image, $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $user_id]);
                }
            }
            $_SESSION['user_name'] = $name;
            $success = "Profile updated successfully.";
        }
    } else {
        $error = "Name and email cannot be empty.";
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$page_title = 'My Profile';
require_once 'includes/header.php';
?>

<div style="display: flex; justify-content: center; align-items: center; min-height: calc(100vh - 220px); padding: 40px 20px;">
    <div style="width: 100%; max-width: 500px; background: white; box-shadow: var(--shadow-lg); padding: 50px 40px; border-radius: var(--radius); border-top: 5px solid var(--primary);">
        <h2 style="color: var(--primary); text-align: center; margin-bottom: 30px; font-weight: 800;">My Profile</h2>

        <?php if ($success): ?>
            <div class="alert alert-success" style="text-align: center;"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger" style="text-align: center; background:#fee2e2; color:#b91c1c; padding:12px; border-radius:8px; margin-bottom:20px;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div style="text-align: center; margin-bottom: 40px;">
                <div style="position: relative; width: 140px; height: 140px; margin: 0 auto 20px;">
                    <div style="width: 100%; height: 100%; border-radius: 50%; background-color: var(--bg-light); overflow: hidden; border: 4px solid var(--primary); box-shadow: var(--shadow-md);">
                        <?php if (!empty($user['profile_image'])): ?>
                            <img src="<?php echo BASE_URL . '/' . htmlspecialchars($user['profile_image']); ?>?v=<?php echo time(); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; font-size: 4rem; color: var(--primary);">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <label for="profile-upload" style="position: absolute; bottom: 0; right: 10px; background: var(--primary); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; justify-content: center; align-items: center; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.2); transition: var(--transition); font-size: 1.2rem; font-weight: bold;">
                        <i class="fas fa-plus"></i>
                    </label>
                    <input id="profile-upload" type="file" name="profile_image" accept="image/*" style="display: none;" onchange="document.getElementById('upload-status').innerText = 'Image selected for upload.';">
                </div>
                <div id="upload-status" style="font-size: 0.9rem; color: var(--secondary); font-weight: 600;"></div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">Account Role</label>
                <input type="text" disabled value="<?php echo ucfirst($user['role']); ?>" style="width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 1rem; background-color: var(--bg-light); cursor: not-allowed; border-color: transparent; font-weight: 600; color: var(--primary-dark);">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Full Name</label>
                <input type="text" name="name" required value="<?php echo htmlspecialchars($user['name']); ?>" style="width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 1rem; background: #f8fafc;">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Email Address</label>
                <input type="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>" style="width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 1rem; background: #f8fafc;">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Update Password <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 400;">(Leave blank to keep current)</span></label>
                <input type="password" name="password" minlength="6" placeholder="••••••••" style="width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 1rem; background: #f8fafc;">
            </div>
            <button type="submit" class="btn" style="width: 100%; margin-top: 20px; font-size: 1.1rem; padding: 15px;">Save Changes</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>