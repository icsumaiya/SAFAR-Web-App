<?php

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../admin/includes/TravelerBookingSearchQueryBuilder.php';
require_once '../admin/includes/TravelerDashboardService.php';
require_once '../admin/includes/BookingDetailsService.php';
require_once '../admin/includes/CancellationService.php';
require_once '../admin/includes/CancellationValidator.php';
require_once '../admin/includes/ReviewService.php';
require_once '../admin/includes/ReviewValidator.php';
require_once '../admin/includes/WishlistService.php';
require_once '../admin/includes/WishlistValidator.php';

requireRole('traveler');

$user_id = $_SESSION['user_id'];
$search = $_GET['search'] ?? '';

/*
 * Dashboard tab + My Bookings category filter — both whitelisted against
 * a fixed set of values so an unexpected query string can't reach the
 * query builder switch statements below.
 */
$valid_tabs = ['overview', 'upcoming', 'bookings', 'payments', 'reviews', 'wishlist'];
$tab = $_GET['tab'] ?? 'overview';
if (!in_array($tab, $valid_tabs, true)) {
    $tab = 'overview';
}

$valid_categories = ['all', 'pending', 'active', 'completed', 'cancelled'];
$category = $_GET['category'] ?? 'all';
if (!in_array($category, $valid_categories, true)) {
    $category = 'all';
}

$cancel_message = '';
$review_message = '';
$wishlist_message = '';

/**
 * Handle cancellation request
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_cancellation'])) {
    $postData = [
        'booking_id' => (int) ($_POST['booking_id'] ?? 0),
        'reason' => $_POST['reason'] ?? ''
    ];

    $error = CancellationValidator::validateRequest($postData);

    if ($error === '') {
        $service = new CancellationService($pdo);

        $error = $service->guardRequest(
            $postData['booking_id'],
            (int) $user_id
        );

        if ($error === '') {
            $service->requestCancellation(
                $postData['booking_id'],
                trim($postData['reason'])
            );

            $cancel_message = 'Cancellation request submitted.';
        }
    }

    if ($error !== '') {
        $cancel_message = $error;
    }
}

/**
 * Handle review submission
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $postData = [
        'booking_id' => (int) ($_POST['booking_id'] ?? 0),
        'rating' => $_POST['rating'] ?? '',
        'comment' => $_POST['comment'] ?? '',
    ];

    $error = ReviewValidator::validateSubmission($postData);

    if ($error === '') {
        $reviewService = new ReviewService($pdo);

        $guard = $reviewService->guardSubmission(
            $postData['booking_id'],
            (int) $user_id
        );

        if ($guard['error'] === '') {
            $reviewService->submitReview(
                $postData['booking_id'],
                (int) $user_id,
                $guard['package_id'],
                (int) $postData['rating'],
                trim($postData['comment'])
            );

            $review_message = 'Review submitted. Thank you!';
        } else {
            $error = $guard['error'];
        }
    }

    if ($error !== '') {
        $review_message = $error;
    }
}

/**
 * Handle "remove from wishlist" (the "add" side happens via
 * ../wishlist-toggle.php AJAX from explore.php / package-details.php;
 * this form-post path mirrors how cancellations/reviews on this page
 * work for travelers without JS).
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_wishlist'])) {
    $postData = ['package_id' => (int) ($_POST['package_id'] ?? 0)];

    $error = WishlistValidator::validate($postData);

    if ($error === '') {
        (new WishlistService($pdo))->remove((int) $user_id, $postData['package_id']);
        $wishlist_message = 'Removed from wishlist.';
    } else {
        $wishlist_message = $error;
    }
}

/**
 * Overview stats — one cheap aggregate query, used for the stat cards on
 * every tab (so the tab bar can show counts) regardless of which tab is
 * active.
 */
$dashboardService = new TravelerDashboardService($pdo);
$overview = $dashboardService->getOverview((int) $user_id);

/**
 * Per-tab data. Only the active tab's queries run.
 */
$upcoming_preview = [];
$upcoming_trips = [];
$bookings = [];
$reviewed_booking_ids = [];
$payments = [];
$my_reviews = [];
$wishlist_items = [];

switch ($tab) {
    case 'overview':
        $upcoming_preview = array_slice(
            $dashboardService->getUpcomingTrips((int) $user_id),
            0,
            3
        );
        break;

    case 'upcoming':
        $upcoming_trips = $dashboardService->getUpcomingTrips((int) $user_id);
        break;

    case 'bookings':
        if ($search !== '') {
            /*
             * A search term overrides the category filter — it searches
             * across all of the traveler's bookings, not just one status
             * bucket.
             */
            $built = TravelerBookingSearchQueryBuilder::build(
                (int) $user_id,
                $search
            );

            $stmt = $pdo->prepare($built['query']);
            $stmt->execute($built['params']);
            $bookings = $stmt->fetchAll();
        } else {
            $bookings = $dashboardService->getBookingsByCategory(
                (int) $user_id,
                $category
            );
        }

        /*
         * Booking IDs already reviewed, so the table can show "Reviewed"
         * instead of the review form. Convert to integers because PDO may
         * return them as strings.
         */
        $reviewedStmt = $pdo->prepare(
            "SELECT booking_id FROM reviews WHERE traveler_id = ?"
        );
        $reviewedStmt->execute([$user_id]);

        $reviewed_booking_ids = array_map(
            'intval',
            $reviewedStmt->fetchAll(PDO::FETCH_COLUMN)
        );
        break;

    case 'payments':
        $payments = $dashboardService->getPaymentHistory((int) $user_id);
        break;

    case 'reviews':
        $my_reviews = $dashboardService->getMyReviews((int) $user_id);
        break;

    case 'wishlist':
        $wishlist_items = (new WishlistService($pdo))->getForTraveler((int) $user_id);
        break;
}

require_once '../includes/header.php';
?>

<div class="container my-4" style="display: flex; gap: 30px; align-items: flex-start;">

    <!-- Sidebar -->
    <aside style="width: 250px; background: var(--white); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow-sm); border: 1px solid var(--glass-border);">

        <h3 style="margin-bottom: 20px; color: var(--primary);">
            Traveler Menu
        </h3>

        <ul style="list-style: none; padding: 0;">

            <li style="margin-bottom: 10px;">
                <a
                    href="traveler.php"
                    style="display: block; padding: 10px; border-radius: 8px; background: var(--bg-light); color: var(--primary); font-weight: 600;"
                >
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>

            <li style="margin-bottom: 10px;">
                <a
                    href="../explore.php"
                    style="display: block; padding: 10px; border-radius: 8px; color: var(--text-main); transition: var(--transition);"
                >
                    <i class="fas fa-search"></i> Explore Tours
                </a>
            </li>

            <li>
                <a
                    href="../profile.php"
                    style="display: block; padding: 10px; border-radius: 8px; color: var(--text-main); transition: var(--transition);"
                >
                    <i class="fas fa-user"></i> Update Profile
                </a>
            </li>

        </ul>
    </aside>


    <!-- Main Content -->
    <div style="flex: 1;">

        <h1 style="margin-bottom: 10px;">
            Traveler Dashboard
        </h1>

        <p style="margin-bottom: 30px; color: var(--text-muted);">
            Welcome back,
            <strong>
                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </strong>!
        </p>


        <!-- Cancellation Message -->
        <?php if ($cancel_message !== ''): ?>

            <div
                class="alert"
                style="margin-bottom: 20px; padding: 12px 16px; border-radius: 8px; background: #fef3c7; color: #92400e;"
            >
                <?php echo htmlspecialchars($cancel_message); ?>
            </div>

        <?php endif; ?>


        <!-- Review Message -->
        <?php if ($review_message !== ''): ?>

            <div
                class="alert"
                style="margin-bottom: 20px; padding: 12px 16px; border-radius: 8px; background: var(--bg-light); color: var(--primary);"
            >
                <?php echo htmlspecialchars($review_message); ?>
            </div>

        <?php endif; ?>


        <!-- Wishlist Message -->
        <?php if ($wishlist_message !== ''): ?>

            <div
                class="alert"
                style="margin-bottom: 20px; padding: 12px 16px; border-radius: 8px; background: var(--bg-light); color: var(--primary);"
            >
                <?php echo htmlspecialchars($wishlist_message); ?>
            </div>

        <?php endif; ?>


        <!-- Stats Cards -->
        <div
            class="grid"
            style="grid-template-columns: repeat(5, 1fr); margin-bottom: 30px; text-align: center;"
        >

            <div class="card" style="padding: 20px;">
                <h3 style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 8px;">
                    Total
                </h3>
                <p style="font-size: 2rem; color: var(--primary); font-weight: 800;">
                    <?php echo $overview['total']; ?>
                </p>
            </div>

            <div class="card" style="padding: 20px;">
                <h3 style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 8px;">
                    Upcoming
                </h3>
                <p style="font-size: 2rem; color: var(--secondary); font-weight: 800;">
                    <?php echo $overview['upcoming']; ?>
                </p>
            </div>

            <div class="card" style="padding: 20px;">
                <h3 style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 8px;">
                    Pending
                </h3>
                <p style="font-size: 2rem; color: #92400e; font-weight: 800;">
                    <?php echo $overview['pending']; ?>
                </p>
            </div>

            <div class="card" style="padding: 20px;">
                <h3 style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 8px;">
                    Completed
                </h3>
                <p style="font-size: 2rem; color: #166534; font-weight: 800;">
                    <?php echo $overview['completed']; ?>
                </p>
            </div>

            <div class="card" style="padding: 20px;">
                <h3 style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 8px;">
                    Cancelled
                </h3>
                <p style="font-size: 2rem; color: #9f1239; font-weight: 800;">
                    <?php echo $overview['cancelled']; ?>
                </p>
            </div>

        </div>


        <!-- Dashboard Tabs -->
        <div
            style="display: flex; gap: 6px; margin-bottom: 25px; border-bottom: 1px solid var(--glass-border); flex-wrap: wrap;"
        >

            <?php
            $tab_links = [
                'overview' => ['label' => 'Overview', 'icon' => 'fa-gauge'],
                'upcoming' => ['label' => 'Upcoming', 'icon' => 'fa-plane-departure'],
                'bookings' => ['label' => 'My Bookings', 'icon' => 'fa-suitcase'],
                'payments' => ['label' => 'Payment History', 'icon' => 'fa-receipt'],
                'reviews' => ['label' => 'Reviews', 'icon' => 'fa-star'],
                'wishlist' => ['label' => 'Wishlist', 'icon' => 'fa-heart'],
            ];
            ?>

            <?php foreach ($tab_links as $tab_key => $tab_meta): ?>

                <a
                    href="traveler.php?tab=<?php echo $tab_key; ?>"
                    style="padding: 10px 16px; font-size: 0.9rem; font-weight: 600; border-bottom: 3px solid <?php echo $tab === $tab_key ? 'var(--primary)' : 'transparent'; ?>; color: <?php echo $tab === $tab_key ? 'var(--primary)' : 'var(--text-muted)'; ?>;"
                >
                    <i class="fas <?php echo $tab_meta['icon']; ?>"></i>
                    <?php echo $tab_meta['label']; ?>
                </a>

            <?php endforeach; ?>

        </div>


        <?php if ($tab === 'overview'): ?>

            <!-- Overview -->
            <div class="card" style="padding: 30px;">

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="margin: 0;">Upcoming Trips</h2>
                    <a href="traveler.php?tab=upcoming" style="color: var(--primary); font-size: 0.9rem;">
                        View all <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <?php if (count($upcoming_preview) > 0): ?>

                    <div style="display: flex; flex-direction: column; gap: 12px;">

                        <?php foreach ($upcoming_preview as $trip): ?>

                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 14px; border: 1px solid var(--glass-border); border-radius: 10px;">

                                <div>
                                    <strong><?php echo htmlspecialchars($trip['title']); ?></strong>
                                    <br>
                                    <small style="color: var(--text-muted);">
                                        <?php echo htmlspecialchars($trip['location']); ?>
                                        &middot;
                                        <?php echo htmlspecialchars($trip['company_name']); ?>
                                        &middot;
                                        <?php echo BookingDetailsService::formatReference((int) $trip['booking_id']); ?>
                                    </small>
                                </div>

                                <div style="text-align: right; font-size: 0.85rem; color: var(--text-muted);">
                                    <?php if (!empty($trip['check_in'])): ?>
                                        <?php echo date('M j, Y', strtotime($trip['check_in'])); ?>
                                    <?php else: ?>
                                        Date TBD
                                    <?php endif; ?>
                                    <br>
                                    <?php if (!empty($trip['payment_status'])): ?>
                                        <span class="badge badge-<?php echo strtolower($trip['payment_status']); ?>" style="margin-top: 4px; display: inline-block;">
                                            <?php echo ucfirst($trip['payment_status']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-pending" style="margin-top: 4px; display: inline-block;">
                                            No payment
                                        </span>
                                    <?php endif; ?>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <p style="color: var(--text-muted);">
                        No upcoming trips yet.
                        <a href="../explore.php" style="color: var(--primary);">Explore packages</a>
                    </p>

                <?php endif; ?>

            </div>

        <?php elseif ($tab === 'upcoming'): ?>

            <!-- Upcoming Trips (full list) -->
            <div class="card" style="padding: 30px;">

                <h2 style="margin: 0 0 20px 0;">Upcoming Trips</h2>

                <?php if (count($upcoming_trips) > 0): ?>

                    <div style="display: flex; flex-direction: column; gap: 12px;">

                        <?php foreach ($upcoming_trips as $trip): ?>

                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 14px; border: 1px solid var(--glass-border); border-radius: 10px;">

                                <div>
                                    <strong><?php echo htmlspecialchars($trip['title']); ?></strong>
                                    <br>
                                    <small style="color: var(--text-muted);">
                                        <?php echo htmlspecialchars($trip['location']); ?>
                                        &middot;
                                        <?php echo htmlspecialchars($trip['company_name']); ?>
                                        &middot;
                                        <?php echo htmlspecialchars(ucfirst($trip['type'])); ?>
                                        &middot;
                                        <?php echo BookingDetailsService::formatReference((int) $trip['booking_id']); ?>
                                    </small>
                                </div>

                                <div style="text-align: right; font-size: 0.85rem; color: var(--text-muted);">
                                    <?php if (!empty($trip['check_in'])): ?>
                                        Check-in: <?php echo date('M j, Y', strtotime($trip['check_in'])); ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($trip['check_out'])): ?>
                                        Check-out: <?php echo date('M j, Y', strtotime($trip['check_out'])); ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($trip['payment_status'])): ?>
                                        <span class="badge badge-<?php echo strtolower($trip['payment_status']); ?>" style="margin-top: 4px; display: inline-block;">
                                            <?php echo ucfirst($trip['payment_status']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-pending" style="margin-top: 4px; display: inline-block;">
                                            No payment
                                        </span>
                                    <?php endif; ?>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <p style="color: var(--text-muted);">
                        No upcoming trips yet.
                        <a href="../explore.php" style="color: var(--primary);">Explore packages</a>
                    </p>

                <?php endif; ?>

            </div>

        <?php elseif ($tab === 'bookings'): ?>

            <!-- My Bookings -->
            <div class="card" style="padding: 30px;">

                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 15px;"
                >

                    <h2 style="margin: 0;">
                        My Bookings
                    </h2>

                    <form
                        method="GET"
                        action="traveler.php"
                        style="display: flex; gap: 10px;"
                    >

                        <input type="hidden" name="tab" value="bookings">

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search bookings..."
                            value="<?php echo htmlspecialchars($search); ?>"
                            style="padding: 8px 12px; width: 200px;"
                        >

                        <button
                            type="submit"
                            class="btn"
                            style="padding: 8px 15px;"
                        >
                            Search
                        </button>

                    </form>

                </div>


                <!-- Category filter (ignored while a search term is active) -->
                <div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">

                    <?php
                    $category_links = [
                        'all' => 'All',
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ];
                    ?>

                    <?php foreach ($category_links as $cat_key => $cat_label): ?>

                        <a
                            href="traveler.php?tab=bookings&category=<?php echo $cat_key; ?>"
                            class="badge"
                            style="background: <?php echo $category === $cat_key && $search === '' ? 'var(--primary)' : 'var(--bg-light)'; ?>; color: <?php echo $category === $cat_key && $search === '' ? '#fff' : 'var(--text-main)'; ?>; padding: 6px 14px;"
                        >
                            <?php echo $cat_label; ?>
                        </a>

                    <?php endforeach; ?>

                </div>


                <?php if (count($bookings) > 0): ?>

                    <div class="table-responsive">

                        <table class="table">

                            <thead>

                                <tr>
                                    <th>Tour Package</th>
                                    <th>Agency</th>
                                    <th>Date Requested</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach ($bookings as $booking): ?>

                                    <?php
                                    /*
                                     * Support either "id" or "booking_id"
                                     * depending on the query builder alias.
                                     */
                                    $booking_id = (int) (
                                        $booking['id']
                                        ?? $booking['booking_id']
                                        ?? 0
                                    );
                                    ?>

                                    <tr>

                                        <!-- Tour Package -->
                                        <td>
                                            <?php echo htmlspecialchars($booking['title']); ?>

                                            <br>

                                            <small style="color: var(--text-muted);">
                                                <?php echo htmlspecialchars($booking['location']); ?>
                                            </small>
                                        </td>


                                        <!-- Agency -->
                                        <td>
                                            <?php echo htmlspecialchars($booking['company_name']); ?>
                                        </td>


                                        <!-- Date -->
                                        <td>
                                            <?php
                                            echo date(
                                                'M j, Y',
                                                strtotime($booking['booking_date'])
                                            );
                                            ?>
                                        </td>


                                        <!-- Price -->
                                        <td>
                                            $<?php echo number_format($booking['price'], 2); ?>
                                        </td>


                                        <!-- Status -->
                                        <td>
                                            <span
                                                class="badge badge-<?php echo strtolower($booking['status']); ?>"
                                            >
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>


                                        <!-- Action -->
                                        <td>

                                            <?php if (
                                                in_array(
                                                    $booking['status'],
                                                    ['pending', 'approved'],
                                                    true
                                                )
                                            ): ?>

                                                <!-- Cancellation -->
                                                <details>

                                                    <summary
                                                        style="cursor: pointer; color: var(--primary); font-size: 0.85rem;"
                                                    >
                                                        Request Cancellation
                                                    </summary>

                                                    <form
                                                        method="POST"
                                                        action="traveler.php"
                                                        style="margin-top: 10px; display: flex; flex-direction: column; gap: 6px; min-width: 220px;"
                                                    >

                                                        <input
                                                            type="hidden"
                                                            name="booking_id"
                                                            value="<?php echo $booking_id; ?>"
                                                        >

                                                        <textarea
                                                            name="reason"
                                                            required
                                                            maxlength="1000"
                                                            rows="2"
                                                            placeholder="Reason for cancellation..."
                                                            style="padding: 6px 8px; border: 1px solid var(--glass-border); border-radius: 6px; font-size: 0.85rem;"
                                                        ></textarea>

                                                        <button
                                                            type="submit"
                                                            name="request_cancellation"
                                                            value="1"
                                                            class="btn btn-danger"
                                                            style="padding: 5px 10px; font-size: 0.8rem; align-self: flex-start;"
                                                        >
                                                            Submit Request
                                                        </button>

                                                    </form>

                                                </details>


                                                <!-- Review -->
                                                <?php if ($booking['status'] === 'approved'): ?>

                                                    <?php if (
                                                        in_array(
                                                            $booking_id,
                                                            $reviewed_booking_ids,
                                                            true
                                                        )
                                                    ): ?>

                                                        <div
                                                            style="margin-top: 6px; font-size: 0.8rem; color: var(--secondary);"
                                                        >
                                                            <i class="fas fa-check-circle"></i>
                                                            Reviewed
                                                        </div>

                                                    <?php else: ?>

                                                        <details style="margin-top: 6px;">

                                                            <summary
                                                                style="cursor: pointer; color: var(--secondary); font-size: 0.85rem;"
                                                            >
                                                                Leave a Review
                                                            </summary>

                                                            <form
                                                                method="POST"
                                                                action="traveler.php"
                                                                style="margin-top: 10px; display: flex; flex-direction: column; gap: 6px; min-width: 220px;"
                                                            >

                                                                <input
                                                                    type="hidden"
                                                                    name="booking_id"
                                                                    value="<?php echo $booking_id; ?>"
                                                                >

                                                                <select
                                                                    name="rating"
                                                                    required
                                                                    style="padding: 6px 8px; border: 1px solid var(--glass-border); border-radius: 6px; font-size: 0.85rem;"
                                                                >
                                                                    <option value="">
                                                                        Rating...
                                                                    </option>

                                                                    <option value="5">
                                                                        ★★★★★ (5)
                                                                    </option>

                                                                    <option value="4">
                                                                        ★★★★ (4)
                                                                    </option>

                                                                    <option value="3">
                                                                        ★★★ (3)
                                                                    </option>

                                                                    <option value="2">
                                                                        ★★ (2)
                                                                    </option>

                                                                    <option value="1">
                                                                        ★ (1)
                                                                    </option>

                                                                </select>


                                                                <textarea
                                                                    name="comment"
                                                                    maxlength="1000"
                                                                    rows="2"
                                                                    placeholder="Share your experience (optional)..."
                                                                    style="padding: 6px 8px; border: 1px solid var(--glass-border); border-radius: 6px; font-size: 0.85rem;"
                                                                ></textarea>


                                                                <button
                                                                    type="submit"
                                                                    name="submit_review"
                                                                    value="1"
                                                                    class="btn btn-primary"
                                                                    style="padding: 5px 10px; font-size: 0.8rem; align-self: flex-start;"
                                                                >
                                                                    Submit Review
                                                                </button>

                                                            </form>

                                                        </details>

                                                    <?php endif; ?>

                                                <?php endif; ?>


                                            <?php else: ?>

                                                <span
                                                    style="color: var(--text-muted); font-size: 0.8rem;"
                                                >
                                                    —
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <p style="color: var(--text-muted);">
                        No bookings found matching your criteria.

                        <a
                            href="../explore.php"
                            style="color: var(--primary);"
                        >
                            Explore packages
                        </a>
                    </p>

                <?php endif; ?>

            </div>

        <?php elseif ($tab === 'payments'): ?>

            <!-- Payment History -->
            <div class="card" style="padding: 30px;">

                <h2 style="margin: 0 0 20px 0;">Payment History</h2>

                <?php if (count($payments) > 0): ?>

                    <div class="table-responsive">

                        <table class="table">

                            <thead>
                                <tr>
                                    <th>Tour Package</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Transaction ID</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($payments as $payment): ?>

                                    <tr>

                                        <td><?php echo htmlspecialchars($payment['title']); ?></td>

                                        <td>
                                            $<?php echo number_format((float) $payment['amount'], 2); ?>
                                            <?php if (!empty($payment['discount_amount']) && (float) $payment['discount_amount'] > 0): ?>
                                                <br>
                                                <small style="color: var(--secondary);">
                                                    −$<?php echo number_format((float) $payment['discount_amount'], 2); ?> discount
                                                </small>
                                            <?php endif; ?>
                                        </td>

                                        <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $payment['method']))); ?></td>

                                        <td><?php echo htmlspecialchars($payment['transaction_id'] ?? '—'); ?></td>

                                        <td>
                                            <span class="badge badge-<?php echo strtolower($payment['status']); ?>">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </td>

                                        <td><?php echo date('M j, Y', strtotime($payment['created_at'])); ?></td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <p style="color: var(--text-muted);">No payments yet.</p>

                <?php endif; ?>

            </div>

        <?php elseif ($tab === 'reviews'): ?>

            <!-- My Reviews -->
            <div class="card" style="padding: 30px;">

                <h2 style="margin: 0 0 20px 0;">My Reviews</h2>

                <?php if (count($my_reviews) > 0): ?>

                    <div style="display: flex; flex-direction: column; gap: 14px;">

                        <?php foreach ($my_reviews as $review): ?>

                            <div style="padding: 16px; border: 1px solid var(--glass-border); border-radius: 10px;">

                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; flex-wrap: wrap;">

                                    <div>
                                        <strong><?php echo htmlspecialchars($review['title']); ?></strong>
                                        <div style="color: #f59e0b; margin-top: 4px;">
                                            <?php echo str_repeat('★', (int) $review['rating']) . str_repeat('☆', 5 - (int) $review['rating']); ?>
                                        </div>
                                    </div>

                                    <span class="badge badge-<?php echo strtolower($review['status']); ?>">
                                        <?php echo ucfirst($review['status']); ?>
                                    </span>

                                </div>

                                <?php if (!empty($review['comment'])): ?>
                                    <p style="margin: 10px 0 0 0; color: var(--text-muted);">
                                        <?php echo htmlspecialchars($review['comment']); ?>
                                    </p>
                                <?php endif; ?>

                                <small style="color: var(--text-muted); display: block; margin-top: 8px;">
                                    <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                </small>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <p style="color: var(--text-muted);">
                        You haven't left any reviews yet. Reviews can be added from a completed booking in
                        <a href="traveler.php?tab=bookings&category=completed" style="color: var(--primary);">My Bookings</a>.
                    </p>

                <?php endif; ?>

            </div>

        <?php elseif ($tab === 'wishlist'): ?>

            <!-- Wishlist -->
            <div class="card" style="padding: 30px;">

                <h2 style="margin: 0 0 20px 0;">Wishlist</h2>

                <?php if (count($wishlist_items) > 0): ?>

                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px;">

                        <?php foreach ($wishlist_items as $item): ?>

                            <div style="border: 1px solid var(--glass-border); border-radius: 10px; overflow: hidden; display: flex; flex-direction: column;">

                                <div style="height: 150px; background-color: #cbd5e1; background-size: cover; background-position: center; background-image: url('<?php echo htmlspecialchars($item['image_url'] ?: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=500&q=60'); ?>');"></div>

                                <div style="padding: 16px; display: flex; flex-direction: column; flex-grow: 1;">

                                    <span style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 4px;">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['location']); ?>
                                    </span>

                                    <strong style="margin-bottom: 6px;"><?php echo htmlspecialchars($item['title']); ?></strong>

                                    <span style="color: var(--primary); font-weight: 700; margin-bottom: 4px;">
                                        $<?php echo number_format($item['price'], 2); ?>
                                    </span>

                                    <span style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 14px;">
                                        By <?php echo htmlspecialchars($item['company_name']); ?>
                                    </span>

                                    <div style="display: flex; gap: 8px; margin-top: auto;">

                                        <a
                                            href="../package-details.php?id=<?php echo (int) $item['package_id']; ?>"
                                            class="btn btn-outline"
                                            style="flex: 1; padding: 8px 10px; font-size: 0.85rem; text-align: center;"
                                        >
                                            View
                                        </a>

                                        <form method="POST" action="traveler.php?tab=wishlist" style="flex: 1;">
                                            <input type="hidden" name="package_id" value="<?php echo (int) $item['package_id']; ?>">
                                            <button
                                                type="submit"
                                                name="remove_wishlist"
                                                value="1"
                                                class="btn btn-danger"
                                                style="width: 100%; padding: 8px 10px; font-size: 0.85rem;"
                                            >
                                                Remove
                                            </button>
                                        </form>

                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div style="text-align: center; padding: 30px 0;">
                        <i class="fas fa-heart" style="font-size: 2.5rem; color: var(--glass-border); margin-bottom: 15px;"></i>
                        <p style="color: var(--text-muted); margin: 0;">
                            Your wishlist is empty. Save packages you like from
                            <a href="../explore.php" style="color: var(--primary);">Explore Tours</a>.
                        </p>
                    </div>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    </div>

</div>


<?php require_once '../includes/footer.php'; ?>