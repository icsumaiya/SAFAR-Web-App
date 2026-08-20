<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /404.php");
    exit();
}

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT p.*, a.company_name, a.phone, u.email FROM packages p JOIN agencies a ON p.agency_id = a.id JOIN users u ON a.user_id = u.id WHERE p.id = ?");
$stmt->execute([$id]);
$pkg = $stmt->fetch();

if (!$pkg) {
    header("Location: /404.php");
    exit();
}

$check_in = $_GET['check_in'] ?? '';
$check_out = $_GET['check_out'] ?? '';
$guests = $_GET['guests'] ?? 1;
?>

<div class="container my-4 fade-in">
    <div class="card" style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column;">
        <div style="height: 400px; background-image: url('<?php echo htmlspecialchars($pkg['image_url'] ?: 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=1200&auto=format&fit=crop&q=80'); ?>'); background-size: cover; background-position: center; position: relative;">
            <span class="card-badge"><?php echo htmlspecialchars(ucfirst($pkg['type'] ?? 'Tour')); ?></span>
        </div>
        <div class="card-body" style="padding: 40px;">
            <div class="card-meta" style="font-size: 1.1rem; margin-bottom: 20px;">
                <span><i class="fas fa-map-marker-alt"></i> Location: <?php echo htmlspecialchars($pkg['location']); ?></span>
                <span style="font-weight: 600; color: var(--primary);">Price: $<?php echo number_format($pkg['price'], 2); ?></span>
            </div>
            <h1 style="margin-bottom: 20px; font-weight: 800; color: var(--text-main);"><?php echo htmlspecialchars($pkg['title']); ?></h1>
            <p style="font-size: 1.1rem; color: var(--text-muted); margin-bottom: 30px; line-height: 1.8;">
                <?php echo nl2br(htmlspecialchars($pkg['description'])); ?>
            </p>
            
            <div class="glass" style="padding: 20px; border-radius: var(--radius); margin-bottom: 30px;">
                <h3 style="color: var(--primary); margin-bottom: 15px;">Agency Information</h3>
                <p><strong>Company:</strong> SAFAR</p>
                <p><strong>Contact Email:</strong> <?php echo htmlspecialchars($pkg['email']); ?></p>
                <?php if ($pkg['phone']): ?>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($pkg['phone']); ?></p>
                <?php endif; ?>
            </div>

            <div class="glass" style="padding: 30px; border-radius: var(--radius);">
                <h3 style="margin-bottom: 20px; color: var(--primary);">Book This <?php echo htmlspecialchars(ucfirst($pkg['type'] ?? 'Tour')); ?></h3>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'traveler'): ?>
                    <form id="booking-form">
                        <input type="hidden" name="package_id" value="<?php echo $pkg['id']; ?>">
                        <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 150px;">
                                <label style="display: block; font-weight: 600; margin-bottom: 8px;">Check-in Date</label>
                                <input type="date" name="check_in" class="form-control" required value="<?php echo htmlspecialchars($check_in); ?>">
                            </div>
                            <div style="flex: 1; min-width: 150px;">
                                <label style="display: block; font-weight: 600; margin-bottom: 8px;">Check-out Date</label>
                                <input type="date" name="check_out" class="form-control" required value="<?php echo htmlspecialchars($check_out); ?>">
                            </div>
                            <div style="flex: 1; min-width: 100px;">
                                <label style="display: block; font-weight: 600; margin-bottom: 8px;">Guests</label>
                                <input type="number" name="guests" class="form-control" min="1" required value="<?php echo htmlspecialchars($guests); ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-book-submit" style="width: 100%; font-size: 1.2rem; padding: 15px;">Confirm Booking</button>
                    </form>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px;">
                        <p style="margin-bottom: 15px;">Please log in to book this <?php echo htmlspecialchars($pkg['type'] ?? 'tour'); ?>.</p>
                        <a href="/safar_web/safar_web/login.php" class="btn" style="font-size: 1.1rem; padding: 12px 30px;">Login to Book</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
