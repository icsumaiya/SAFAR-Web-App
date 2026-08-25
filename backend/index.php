<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Handle search and filtering
$search = $_GET['search'] ?? '';
$price_max = $_GET['price_max'] ?? '';

$query = "SELECT p.*, a.company_name FROM packages p JOIN agencies a ON p.agency_id = a.id WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (p.location LIKE ? OR p.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($price_max) {
    $query .= " AND p.price <= ?";
    $params[] = $price_max;
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$packages = $stmt->fetchAll();
?>

<section class="hero" style="margin-top: -110px; padding-top: 180px;">
    <div class="hero-slider">
        <div class="hero-slide active" style="background-image: url('https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=1280&q=50');"></div>
        <div class="hero-slide" style="background-image: url('https://images.unsplash.com/photo-1501785888041-af3ef285b470?w=1280&q=50');"></div>
        <div class="hero-slide" style="background-image: url('https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=1280&q=50');"></div>
        <div class="hero-slide" style="background-image: url('https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1280&q=50');"></div>
        <div class="hero-slide" style="background-image: url('https://images.unsplash.com/photo-1524492412937-b28074a5d7da?w=1280&q=50');"></div>
    </div>
    
    <div class="container hero-content">
        <h1>Discover Your Next Adventure</h1>
        <p>Find the best tour packages from verified agencies worldwide. Book easily, travel safely.</p>
        
        <div class="hero-search-box" style="background: rgba(255, 255, 255, 0.95); padding: 25px; border-radius: var(--radius); box-shadow: var(--shadow-lg); max-width: 900px; margin: 0 auto; margin-top: 30px;">
            <form class="search-form" method="GET" action="explore.php" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                <div style="flex: 2; min-width: 200px; text-align: left;">
                    <label style="font-weight: 600; color: var(--text-main); font-size: 0.9rem; margin-bottom: 5px; display: block;"><i class="fas fa-map-marker-alt" style="color: var(--primary);"></i> Destination</label>
                    <input type="text" name="search" class="form-control" placeholder="Where are you going?" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div style="flex: 1; min-width: 130px; text-align: left;">
                    <label style="font-weight: 600; color: var(--text-main); font-size: 0.9rem; margin-bottom: 5px; display: block;"><i class="far fa-calendar-alt" style="color: var(--primary);"></i> Check In</label>
                    <input type="date" name="check_in" class="form-control" value="<?php echo htmlspecialchars($_GET['check_in'] ?? ''); ?>">
                </div>
                <div style="flex: 1; min-width: 130px; text-align: left;">
                    <label style="font-weight: 600; color: var(--text-main); font-size: 0.9rem; margin-bottom: 5px; display: block;"><i class="far fa-calendar-alt" style="color: var(--primary);"></i> Check Out</label>
                    <input type="date" name="check_out" class="form-control" value="<?php echo htmlspecialchars($_GET['check_out'] ?? ''); ?>">
                </div>
                <div style="flex: 1; min-width: 100px; text-align: left;">
                    <label style="font-weight: 600; color: var(--text-main); font-size: 0.9rem; margin-bottom: 5px; display: block;"><i class="fas fa-user" style="color: var(--primary);"></i> Guests</label>
                    <select name="guests" class="form-control">
                        <option value="1" <?php echo (isset($_GET['guests']) && $_GET['guests'] == '1') ? 'selected' : ''; ?>>1 Guest</option>
                        <option value="2" <?php echo (isset($_GET['guests']) && $_GET['guests'] == '2') ? 'selected' : ''; ?>>2 Guests</option>
                        <option value="3" <?php echo (isset($_GET['guests']) && $_GET['guests'] == '3') ? 'selected' : ''; ?>>3 Guests</option>
                        <option value="4" <?php echo (isset($_GET['guests']) && $_GET['guests'] == '4') ? 'selected' : ''; ?>>4+ Guests</option>
                    </select>
                </div>
                <div style="flex: 1; min-width: 120px;">
                    <button type="submit" class="btn" style="width: 100%; height: 48px; font-size: 1.1rem;">Search</button>
                </div>
            </form>
            <div class="hero-quick-btns" style="margin-top: 15px; display: flex; justify-content: center; gap: 10px;">
                <form method="GET" action="explore.php">
                    <input type="hidden" name="type" value="hotel">
                    <input type="hidden" name="sort" value="near">
                    <button type="submit" class="btn btn-outline" style="padding: 8px 15px; font-size: 0.9rem;"><i class="fas fa-bed"></i> Search Near Hotels</button>
                </form>
                <form method="GET" action="explore.php">
                    <input type="hidden" name="search" value="Sylhet">
                    <button type="submit" class="btn btn-outline" style="padding: 8px 15px; font-size: 0.9rem;"><i class="fas fa-leaf"></i> Explore Sylhet Tours</button>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const slides = document.querySelectorAll('.hero-slide');
        let currentSlide = 0;
        
        setInterval(() => {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.add('active');
        }, 5000); // Change image every 5 seconds
    });
</script>

<section style="max-width: 1200px; margin: 2rem auto; padding: 0 40px;">
    <h2 class="text-center mb-2">Featured Tour Packages</h2>
    <?php if (count($packages) > 0): ?>
        <?php
        $fallback_images = [
            'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=500&q=60',
            'https://images.unsplash.com/photo-1501785888041-af3ef285b470?w=500&q=60',
            'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=500&q=60',
            'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=500&q=60',
            'https://images.unsplash.com/photo-1524492412937-b28074a5d7da?w=500&q=60',
            'https://images.unsplash.com/photo-1493558103817-58b2924bce98?w=500&q=60',
            'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=500&q=60',
            'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=500&q=60',
            'https://images.unsplash.com/photo-1473448912268-2022ce9509d8?w=500&q=60',
            'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=500&q=60',
            'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=500&q=60',
        ];
        $fb_index = 0;
        ?>
        <div class="packages-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-top: 1rem;">
            <?php foreach ($packages as $pkg): ?>
                <?php
                $card_image = $pkg['image_url'] ?: $fallback_images[$fb_index % count($fallback_images)];
                $fb_index++;
                ?>
                <div style="background: var(--card-bg); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-sm); border: 1px solid rgba(0,0,0,0.05); display: flex; flex-direction: column; transition: var(--transition);">
                    <div style="height: 200px; background-color: #cbd5e1; background-size: cover; background-position: center; background-image: url('<?php echo htmlspecialchars($card_image); ?>');"></div>
                    <div style="padding: 20px; display: flex; flex-direction: column; flex-grow: 1;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.9rem; color: var(--text-muted);">
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($pkg['location']); ?></span>
                            <span>By SAFAR</span>
                        </div>
                        <h3 style="font-size: 1.25rem; margin-bottom: 10px; color: var(--text-main);"><?php echo htmlspecialchars($pkg['title']); ?></h3>
                        <p style="font-size: 1.5rem; font-weight: 700; color: var(--primary); margin-bottom: 15px;">$<?php echo number_format($pkg['price'], 2); ?></p>
                        
                        <div style="display: flex; gap: 10px; margin-top: auto;">
                            <a href="package-details.php?id=<?php echo $pkg['id']; ?>" class="btn btn-outline" style="flex: 1;">View Details</a>
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'traveler'): ?>
                                <a href="package-details.php?id=<?php echo $pkg['id']; ?>" class="btn" style="flex: 1;">Book Now</a>
                            <?php else: ?>
                                <a href="login.php" class="btn" style="flex: 1;">Login to Book</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center my-4">
            <p>No packages found matching your criteria. Try adjusting your search.</p>
        </div>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>
