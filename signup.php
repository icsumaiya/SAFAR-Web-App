<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $company_name = trim($_POST['company_name'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif ($role === 'agency' && empty($company_name)) {
        $error = "Company name is required for agencies.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email is already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hashed_password, $role]);
                $user_id = $pdo->lastInsertId();
                
                if ($role === 'agency') {
                    $stmt = $pdo->prepare("INSERT INTO agencies (user_id, company_name) VALUES (?, ?)");
                    $stmt->execute([$user_id, $company_name]);
                }
                
                $pdo->commit();
                $success = "Registration successful! You can now <a href='" . BASE_URL . "/login.php'>log in</a>.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "An error occurred during registration. Please try again.";
            }
        }
    }
}
?>

<div class="container my-4">
    <div class="auth-form">
        <h2>Join SAFAR</h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php else: ?>
        <form method="POST" action="" id="signup-form">
            <div class="form-group">
                <label>I want to register as a:</label>
                <select name="role" id="role-select" class="form-control" onchange="toggleAgencyFields()">
                    <option value="traveler">Traveler (Looking to book tours)</option>
                    <option value="agency">Travel Agency (Looking to post tours)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="form-control" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>
            <div class="form-group" id="agency-fields" style="display: none;">
                <label>Company Name</label>
                <input type="text" name="company_name" class="form-control" value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required minlength="6">
            </div>
            <button type="submit" class="btn" style="width: 100%;">Create Account</button>
        </form>
        <?php endif; ?>
        <p class="text-center mt-2">Already have an account? <a href="<?php echo BASE_URL; ?>/login.php" style="color: var(--primary);">Log in here</a></p>
    </div>
</div>

<script>
function toggleAgencyFields() {
    const roleSelect = document.getElementById('role-select');
    const agencyFields = document.getElementById('agency-fields');
    if (roleSelect.value === 'agency') {
        agencyFields.style.display = 'block';
        agencyFields.querySelector('input').setAttribute('required', 'required');
    } else {
        agencyFields.style.display = 'none';
        agencyFields.querySelector('input').removeAttribute('required');
    }
}
// Run on load in case of form validation errors keeping state
document.addEventListener('DOMContentLoaded', toggleAgencyFields);
</script>

<?php require_once 'includes/footer.php'; ?>
