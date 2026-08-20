<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once 'includes/PackageFactory.php';
require_once 'includes/adapter/HotelDataAdapter.php';

requireRole('admin');

// ধরে নেওয়া হচ্ছে এজেন্সি আইডি সেশন থেকে আসছে
$agency_id = $_SESSION['agency_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {

    // raw hotel data (external/legacy format ধরে নেওয়া হয়েছে)
    $rawHotelRows = [
        [
            'hotel_name'   => 'Grand Sylhet Hotel',
            'city'         => 'Sylhet',
            'nightly_rate' => 5000.00,
            'details'      => 'A luxury hotel in the heart of Sylhet.',
            'image_url'    => 'https://example.com/hotel1.jpg',
        ],
        [
            'hotel_name'   => 'Hotel Star Pacific',
            'city'         => 'Sylhet',
            'nightly_rate' => 3000.00,
            'details'      => 'Comfortable stay for business travelers.',
            'image_url'    => 'https://example.com/hotel2.jpg',
        ],
    ];

    foreach ($rawHotelRows as $row) {
        // Adapter raw row → standard Package object এ রূপান্তর করে
        $pkg = HotelDataAdapter::adapt($row);

        $stmt = $pdo->prepare("INSERT INTO packages (agency_id, title, location, price, description, image_url, type) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $agency_id,
            $pkg->title,
            $pkg->location,
            $pkg->price,
            $pkg->description,
            $pkg->image_url,
            $pkg->type
        ]);
    }

    header("Location: import-hotels.php?msg=success");
    exit();
}

$page_title = 'Import Hotels';
require_once '../includes/header.php';
?>
<div class="container my-4" style="display: flex; gap: 30px; align-items: flex-start;">
    <?php require_once 'includes/sidebar.php'; ?>
    <div style="flex: 1; min-width: 0;">
        <h1 style="margin-bottom: 25px;">Import Hotels</h1>
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
            <div class="alert alert-success">Hotels imported successfully!</div>
        <?php endif; ?>
        <div class="card" style="padding: 30px;">
            <form method="POST" action="import-hotels.php">
                <p>Click the button below to process and import hotel data.</p>
                <button type="submit" name="import" class="btn" style="background: var(--primary); padding: 10px 20px; color: #fff; border: none; border-radius: 5px; cursor: pointer;">
                    Start Import
                </button>
            </form>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>