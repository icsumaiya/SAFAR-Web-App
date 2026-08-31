<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Current page path detect korar jonno (active nav link highlight korte)
// Full path use kora hocche jate 'index.php' o 'admin/index.php' mix na hoy
$current_page = str_replace('\\', '/', $_SERVER['PHP_SELF']);
$current_type = isset($_GET['type']) ? $_GET['type'] : '';

// Navbar-e chuto avatar dekhanor jonno logged-in user er profile image niye asha
$nav_avatar = null;
if (isset($_SESSION['user_id']) && isset($pdo)) {
    $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $nav_avatar = $stmt->fetchColumn();
}

// NavHelper.php require করা হলো (যেখানে nav_active ফাংশনটি সংজ্ঞায়িত আছে)
require_once __DIR__ . '/NavHelper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - SAFAR' : 'SAFAR - Travel Booking & Management'; ?></title>
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
    <script>
        // Apply saved theme before paint (avoids flash of wrong theme)
        (function () {
            if (localStorage.getItem('safar_theme') === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/theme.js"></script>
</head>
<body>
    <nav class="navbar glass" id="main-nav">
        <div class="container nav-container">
            <a href="<?php echo BASE_URL; ?>/" class="brand" style="display: flex; align-items: center;">
                <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="SAFAR Logo" style="height: 100px; width: auto; transition: transform 0.3s ease;">
            </a>
            <ul class="nav-links">
                <li><a href="<?php echo BASE_URL; ?>/explore.php" class="<?php echo trim(nav_active('explore.php', $current_page, null, $current_type)); ?>">Explore</a></li>
                <li><a href="<?php echo BASE_URL; ?>/explore.php?type=tour" class="<?php echo trim(nav_active('explore.php', $current_page, 'tour', $current_type)); ?>">Tours</a></li>
                <li><a href="<?php echo BASE_URL; ?>/explore.php?type=hotel" class="<?php echo trim(nav_active('explore.php', $current_page, 'hotel', $current_type)); ?>">Hotels</a></li>

                <li>
                    <button onclick="toggleSafarTheme()" style="background: none; border: none; cursor: pointer; font-size: 1.15rem; color: var(--text-main); display: flex; align-items: center;" title="Toggle dark mode">
                        <i class="fas fa-moon" id="theme-toggle-icon"></i>
                    </button>
                </li>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['user_role'] === 'traveler'): ?>
                        <li><a href="<?php echo BASE_URL; ?>/dashboard/traveler.php" class="<?php echo trim(nav_active('traveler.php', $current_page)); ?>">My Bookings</a></li>
                    <?php elseif ($_SESSION['user_role'] === 'agency'): ?>
                        <li><a href="<?php echo BASE_URL; ?>/dashboard/agency.php" class="<?php echo trim(nav_active('agency.php', $current_page)); ?>">Agency Dashboard</a></li>
                    <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
                        <li><a href="<?php echo BASE_URL; ?>/admin/index.php" class="<?php echo trim(nav_active('admin/index.php', $current_page)); ?>">Admin Panel</a></li>
                    <?php endif; ?>

                    <li>
                        <a href="<?php echo BASE_URL; ?>/profile.php" class="nav-avatar <?php echo trim(nav_active('profile.php', $current_page)) ? 'active' : ''; ?>" title="My Profile">
                            <?php if (!empty($nav_avatar)): ?>
                                <img src="<?php echo BASE_URL . '/' . htmlspecialchars($nav_avatar); ?>" alt="Profile">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </a>
                    </li>

                    <li><a href="<?php echo BASE_URL; ?>/logout.php" class="nav-btn btn-outline-nav">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?php echo BASE_URL; ?>/login.php" class="nav-btn btn-outline-nav <?php echo trim(nav_active('login.php', $current_page)); ?>">Log In</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/signup.php" class="nav-btn btn-gradient-nav <?php echo trim(nav_active('signup.php', $current_page)); ?>">Sign Up</a></li>
                <?php endif; ?>
            </ul>

            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <main class="main-content">
    
    <script>
        // Sticky Navbar Scroll Effect
        window.addEventListener('scroll', () => {
            const nav = document.getElementById('main-nav');
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });
    </script>
