<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: " . BASE_URL . "/404.php");
    exit();
}

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT p.*, a.company_name, a.phone, u.email FROM packages p JOIN agencies a ON p.agency_id = a.id JOIN users u ON a.user_id = u.id WHERE p.id = ?");
$stmt->execute([$id]);
$pkg = $stmt->fetch();

if (!$pkg) {
    header("Location: " . BASE_URL . "/404.php");
    exit();
}
?>

<div class="package-hero" style="position: relative; height: 500px; width: 100%; margin-bottom: 40px;">
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-image: url('<?php echo htmlspecialchars($pkg['image_url'] ?: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1920&q=80'); ?>'); background-size: cover; background-position: center; border-bottom-left-radius: 40px; border-bottom-right-radius: 40px; box-shadow: var(--shadow-sm);"></div>
    <div style="position: absolute; bottom: 0; left: 0; width: 100%; height: 50%; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent); border-bottom-left-radius: 40px; border-bottom-right-radius: 40px;"></div>
    <div class="container" style="position: relative; height: 100%; display: flex; align-items: flex-end; padding-bottom: 40px; z-index: 2;">
        <div style="color: white;">
            <div style="display: flex; gap: 15px; margin-bottom: 10px;">
                <span class="badge badge-approved" style="background: var(--primary); color: white; font-size: 1rem; padding: 6px 15px;"><i class="fas fa-tag"></i> <?php echo ucfirst($pkg['type'] ?? 'Tour'); ?></span>
                <span style="font-size: 1.1rem; text-shadow: 0 2px 4px rgba(0,0,0,0.5);"><i class="fas fa-map-marker-alt" style="color: var(--primary);"></i> <?php echo htmlspecialchars($pkg['location']); ?></span>
            </div>
            <h1 style="font-size: 3.5rem; font-weight: 800; text-shadow: 0 2px 8px rgba(0,0,0,0.6); margin: 0;"><?php echo htmlspecialchars($pkg['title']); ?></h1>
        </div>
    </div>
</div>

<div class="container my-4" style="margin-bottom: 80px;">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 50px; align-items: start;">
        
        <!-- Left Column: Details -->
        <div class="package-details-content">
            <h2 style="font-size: 2rem; margin-bottom: 25px; color: var(--text-main); border-bottom: 2px solid var(--bg-light); padding-bottom: 15px;">Overview</h2>
            <p style="font-size: 1.1rem; color: #475569; margin-bottom: 40px; line-height: 1.8;">
                <?php echo nl2br(htmlspecialchars($pkg['description'])); ?>
            </p>
            
            <h2 style="font-size: 2rem; margin-bottom: 25px; color: var(--text-main); border-bottom: 2px solid var(--bg-light); padding-bottom: 15px;">Hosted by SAFAR</h2>
            <div style="display: flex; gap: 20px; align-items: center; background: #fff5f0; padding: 30px; border-radius: var(--radius); border: 1px solid rgba(255,125,75,0.2);">
                <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--primary); display: flex; justify-content: center; align-items: center; color: white; font-size: 2rem;">
                    <i class="fas fa-building"></i>
                </div>
                <div>
                    <p style="margin-bottom: 5px;"><strong>Contact Email:</strong> <a href="mailto:<?php echo htmlspecialchars($pkg['email']); ?>" style="color: var(--primary);"><?php echo htmlspecialchars($pkg['email']); ?></a></p>
                    <?php if ($pkg['phone']): ?>
                        <p style="margin-bottom: 0;"><strong>Phone:</strong> <?php echo htmlspecialchars($pkg['phone']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Sticky Booking Card -->
        <div class="package-booking-card" style="position: sticky; top: 120px; background: white; padding: 35px; border-radius: var(--radius); box-shadow: var(--shadow-lg); border: 1px solid rgba(0,0,0,0.05);">
            <div style="margin-bottom: 25px;">
                <span style="font-size: 2.5rem; font-weight: 800; color: var(--text-main);">$<?php echo number_format($pkg['price'], 2); ?></span>
                <span style="font-size: 1.1rem; color: var(--text-muted);">/ person</span>
            </div>
            
            <div style="border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 25px; overflow: hidden;">
                <div style="display: flex; border-bottom: 1px solid #cbd5e1;">
                    <div style="flex: 1; padding: 12px 15px; border-right: 1px solid #cbd5e1;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 5px;">Check-in</label>
                        <input type="date" style="border: none; outline: none; width: 100%; font-family: 'Inter', sans-serif;">
                    </div>
                    <div style="flex: 1; padding: 12px 15px;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 5px;">Check-out</label>
                        <input type="date" style="border: none; outline: none; width: 100%; font-family: 'Inter', sans-serif;">
                    </div>
                </div>
                <div style="padding: 12px 15px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 5px;">Guests</label>
                    <select style="border: none; outline: none; width: 100%; font-family: 'Inter', sans-serif;">
                        <option>1 Guest</option>
                        <option>2 Guests</option>
                        <option>3 Guests</option>
                        <option>4+ Guests</option>
                    </select>
                </div>
            </div>

            <div style="text-align: center;">
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'traveler'): ?>
                    <button onclick="document.getElementById('payment-modal').style.display='flex'" class="btn" style="width: 100%; font-size: 1.2rem; padding: 16px; font-weight: 700;">Reserve Now</button>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/login.php" class="btn" style="display: block; width: 100%; font-size: 1.2rem; padding: 16px; font-weight: 700;">Login to Reserve</a>
                <?php endif; ?>
            </div>
            <p style="text-align: center; color: var(--text-muted); font-size: 0.9rem; margin-top: 15px; margin-bottom: 0;">You won't be charged yet</p>
        </div>
        
    </div>
</div>

<!-- Demo Payment Modal -->
<div id="payment-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; justify-content: center; align-items: center; backdrop-filter: blur(4px);">
    <div style="background: white; border-radius: 16px; padding: 40px; max-width: 480px; width: 90%; box-shadow: 0 25px 50px rgba(0,0,0,0.3); position: relative;">
        <button onclick="document.getElementById('payment-modal').style.display='none'" style="position: absolute; top: 15px; right: 20px; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #666;">&times;</button>
        
        <h2 style="margin-bottom: 5px; color: var(--text-main);">Complete Payment</h2>
        <p style="color: var(--text-muted); margin-bottom: 25px; font-size: 0.9rem;">Demo payment — no real charges</p>
        
        <div style="background: var(--bg-light); padding: 15px; border-radius: 10px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-weight: 600;"><?php echo htmlspecialchars($pkg['title']); ?></span>
            <span style="font-size: 1.3rem; font-weight: 800; color: var(--primary);">$<?php echo number_format($pkg['price'], 2); ?></span>
        </div>

        <form id="demo-payment-form">
            <div style="margin-bottom: 18px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem;">Cardholder Name</label>
                <input type="text" id="card-name" placeholder="John Doe" required style="width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 1rem;">
            </div>
            <div style="margin-bottom: 18px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem;">Card Number</label>
                <div style="position: relative;">
                    <input type="text" id="card-number" placeholder="4242 4242 4242 4242" maxlength="19" required style="width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 1rem; padding-right: 60px;">
                    <div style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); display: flex; gap: 4px;">
                        <i class="fab fa-cc-visa" style="font-size: 1.3rem; color: #1a1f71;"></i>
                        <i class="fab fa-cc-mastercard" style="font-size: 1.3rem; color: #eb001b;"></i>
                    </div>
                </div>
            </div>
            <div style="display: flex; gap: 15px; margin-bottom: 18px;">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem;">Expiry</label>
                    <input type="text" id="card-expiry" placeholder="MM/YY" maxlength="5" required style="width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 1rem;">
                </div>
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem;">CVC</label>
                    <input type="text" id="card-cvc" placeholder="123" maxlength="4" required style="width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 1rem;">
                </div>
            </div>
            <button type="submit" id="pay-btn" class="btn" style="width: 100%; font-size: 1.1rem; padding: 16px; font-weight: 700;">
                <i class="fas fa-lock" style="margin-right: 8px;"></i> Pay $<?php echo number_format($pkg['price'], 2); ?>
            </button>
        </form>
        <p style="text-align: center; color: var(--text-muted); font-size: 0.8rem; margin-top: 15px;"><i class="fas fa-shield-alt"></i> Secure demo checkout — no real payment processed</p>
    </div>
</div>

<style>
@media (max-width: 992px) {
    .container > div[style*="display: grid"] {
        grid-template-columns: 1fr !important;
    }
    .package-hero h1 { font-size: 2.5rem !important; }
    .package-booking-card { position: static !important; margin-top: 40px; }
}
</style>

<script>
// Card number formatting
document.getElementById('card-number').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
    let matches = v.match(/\d{4,16}/g);
    let match = matches && matches[0] || '';
    let parts = [];
    for (let i = 0, len = match.length; i < len; i += 4) {
        parts.push(match.substring(i, i + 4));
    }
    e.target.value = parts.length ? parts.join(' ') : v;
});

// Expiry formatting
document.getElementById('card-expiry').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, '');
    if (v.length >= 2) v = v.substring(0, 2) + '/' + v.substring(2);
    e.target.value = v;
});

// Demo payment form submit
document.getElementById('demo-payment-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('pay-btn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;

    // Simulate payment delay
    setTimeout(function() {
        // Send booking request
        const formData = new FormData();
        formData.append('package_id', '<?php echo $pkg['id']; ?>');

        fetch('<?php echo BASE_URL; ?>/api/book.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                btn.innerHTML = '<i class="fas fa-check"></i> Payment Successful!';
                btn.style.background = '#10b981';
                setTimeout(() => {
                    document.getElementById('payment-modal').innerHTML = `
                        <div style="background: white; border-radius: 16px; padding: 50px; max-width: 480px; width: 90%; text-align: center; box-shadow: 0 25px 50px rgba(0,0,0,0.3);">
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: #10b981; margin: 0 auto 20px; display: flex; justify-content: center; align-items: center;">
                                <i class="fas fa-check" style="color: white; font-size: 2.5rem;"></i>
                            </div>
                            <h2 style="margin-bottom: 10px;">Booking Confirmed!</h2>
                            <p style="color: #666; margin-bottom: 25px;">Your booking is pending approval from the agency/admin.</p>
                            <a href="<?php echo BASE_URL; ?>/dashboard/traveler.php" class="btn" style="padding: 12px 30px;">View My Bookings</a>
                        </div>
                    `;
                }, 1000);
            } else {
                btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + data.message;
                btn.style.background = '#ef4444';
                setTimeout(() => {
                    btn.innerHTML = '<i class="fas fa-lock"></i> Pay $<?php echo number_format($pkg['price'], 2); ?>';
                    btn.style.background = '';
                    btn.disabled = false;
                }, 2500);
            }
        })
        .catch(() => {
            btn.innerHTML = 'Error - Try Again';
            btn.style.background = '#ef4444';
            btn.disabled = false;
        });
    }, 1500);
});
</script>

<?php require_once 'includes/footer.php'; ?>
