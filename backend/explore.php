<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
$type = $_GET['type'] ?? '';
$page_title = $type === 'tour' ? 'Tours' : ($type === 'hotel' ? 'Hotels' : 'Explore');
require_once 'includes/header.php';
?>

<div class="container my-4 dashboard-layout">
    <aside class="dashboard-sidebar glass" style="align-self: flex-start; position: sticky; top: 100px;">
        <h3>Filter & Search</h3>
        <form id="explore-filter-form">
            <div class="form-group">
                <label>Type</label>
                <select name="type" class="form-control" id="filter-type">
                    <option value="all">All (Tours & Hotels)</option>
                    <option value="tour">Tours Only</option>
                    <option value="hotel">Hotels Only</option>
                </select>
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" class="form-control" id="filter-location" placeholder="e.g., Dubai, Paris...">
            </div>
            <div class="form-group">
                <label>Max Price ($)</label>
                <input type="range" name="price" class="form-control" id="filter-price" min="0" max="5000" step="50" value="5000" style="padding: 0;">
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: var(--text-muted); margin-top: 5px;">
                    <span>$0</span>
                    <span id="price-display">$5000</span>
                </div>
            </div>
        </form>
    </aside>

    <main class="dashboard-main" style="background: transparent; padding-top: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <h1 style="color: var(--primary);">Explore Listings</h1>
            <div style="display: flex; gap: 10px; align-items: center;">
                <button type="button" class="btn btn-accent" id="btn-near-hotels" style="padding: 8px 15px; font-size: 0.9rem;">
                    <i class="fas fa-location-arrow"></i> Near Hotels
                </button>
                <span id="results-count" class="badge badge-approved" style="font-size: 1rem;">Loading...</span>
            </div>
        </div>
        
        <div class="grid" id="explore-grid">
            <!-- Results injected via AJAX -->
        </div>
    </main>
</div>

<script>
    window.SAFAR_BASE_URL = <?php echo json_encode(BASE_URL); ?>;
    window.SAFAR_IS_TRAVELER = <?php echo (isLoggedIn() && $_SESSION['user_role'] === 'traveler') ? 'true' : 'false'; ?>;
</script>
<script src="<?php echo BASE_URL; ?>/assets/js/wishlist.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/explore.js"></script>
<?php require_once 'includes/footer.php'; ?>