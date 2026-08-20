<?php
require_once 'includes/db.php';
$page_title = 'Log In';
require_once 'includes/header.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: " . BASE_URL . "/admin/index.php");
            } elseif ($user['role'] === 'agency') {
                header("Location: " . BASE_URL . "/dashboard/agency.php");
            } else {
                header("Location: " . BASE_URL . "/dashboard/traveler.php");
            }
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<div class="container my-4">
    <div class="auth-form">
        <h2>Welcome Back to SAFAR</h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn" style="width: 100%;">Log In</button>
        </form>
        <p class="text-center mt-2">Don't have an account? <a href="<?php echo BASE_URL; ?>/signup.php" style="color: var(--primary);">Sign up here</a></p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
