<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../admin/includes/TravelerBookingSearchQueryBuilder.php';
require_once '../admin/includes/CancellationService.php';
require_once '../admin/includes/CancellationValidator.php';

requireRole('traveler');

$user_id = $_SESSION['user_id'];
$search = $_GET['search'] ?? '';

$cancel_message = '';

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
 * Fetch stats
 */
$total_bookings = $pdo->prepare(
    "SELECT COUNT(*) FROM bookings WHERE traveler_id = ?"
);
$total_bookings->execute([$user_id]);
$total_bookings = $total_bookings->fetchColumn();

$upcoming_trips = $pdo->prepare(
    "SELECT COUNT(*) 
     FROM bookings 
     WHERE traveler_id = ? 
     AND status IN ('pending', 'approved')"
);
$upcoming_trips->execute([$user_id]);
$upcoming_trips = $upcoming_trips->fetchColumn();

/**
 * Fetch traveler's bookings with optional search
 */
$built = TravelerBookingSearchQueryBuilder::build(
    (int) $user_id,
    $search
);

$stmt = $pdo->prepare($built['query']);
$stmt->execute($built['params']);
$bookings = $stmt->fetchAll();

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


        <!-- Stats Cards -->
        <div
            class="grid"
            style="grid-template-columns: repeat(2, 1fr); margin-bottom: 40px; text-align: center;"
        >

            <div class="card" style="padding: 30px;">
                <h3
                    style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 10px;"
                >
                    Total Bookings
                </h3>

                <p
                    style="font-size: 2.5rem; color: var(--primary); font-weight: 800;"
                >
                    <?php echo $total_bookings; ?>
                </p>
            </div>


            <div class="card" style="padding: 30px;">
                <h3
                    style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 10px;"
                >
                    Upcoming Trips
                </h3>

                <p
                    style="font-size: 2.5rem; color: var(--secondary); font-weight: 800;"
                >
                    <?php echo $upcoming_trips; ?>
                </p>
            </div>

        </div>


        <!-- My Bookings -->
        <div class="card" style="padding: 30px;">

            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;"
            >

                <h2 style="margin: 0;">
                    My Bookings
                </h2>

                <form
                    method="GET"
                    action="traveler.php"
                    style="display: flex; gap: 10px;"
                >

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

    </div>

</div>


<?php require_once '../includes/footer.php'; ?>