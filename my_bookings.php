 <?php
session_start();
require_once "config/db.php";

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Customer login check
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$customer_id = (int) $_SESSION['customer_id'];

// Get all bookings for this customer
$sql = "SELECT
            b.booking_id,
            b.pickup_location,
            b.pickup_date,
            b.return_date,
            b.total_amount,
            b.booking_status,
            b.payment_method,
            v.vehicle_id,
            v.vehicle_name,
            v.brand,
            v.model
        FROM bookings b
        INNER JOIN vehicles v
            ON b.vehicle_id = v.vehicle_id
        WHERE b.customer_id = ?
        ORDER BY b.booking_id DESC";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    exit("Unable to load bookings. Please check your database.");
}

mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$bookings = [];

while ($row = mysqli_fetch_assoc($result)) {
    $bookings[] = $row;
}

mysqli_stmt_close($stmt);

// Booking statistics
$total_bookings = count($bookings);
$pending_count = 0;
$confirmed_count = 0;
$completed_count = 0;

foreach ($bookings as $booking) {
    $status = strtolower(trim($booking['booking_status'] ?? ''));

    if ($status === 'pending') {
        $pending_count++;
    } elseif (in_array($status, ['confirmed', 'approved'], true)) {
        $confirmed_count++;
    } elseif ($status === 'completed') {
        $completed_count++;
    }
}

// Status display helper
function statusClass($status) {
    $status = strtolower(trim((string)$status));

    if (in_array($status, ['confirmed', 'approved'], true)) {
        return 'confirmed';
    }

    if (in_array($status, ['completed'], true)) {
        return 'completed';
    }

    if (in_array($status, ['cancelled', 'canceled', 'rejected'], true)) {
        return 'cancelled';
    }

    if (in_array($status, ['active', 'in_progress', 'on trip'], true)) {
        return 'active';
    }

    return 'pending';
}

// Payment method display helper
function paymentLabel($method) {
    switch ($method) {
        case 'card':
            return 'Credit / Debit Card';

        case 'bank_transfer':
            return 'Bank Transfer';

        case 'cash':
            return 'Cash at Pickup';

        default:
            return 'Not selected';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Bookings | SwiftDrive Rentals</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="my_bookings.css">
</head>

<body>

<!-- Header -->
<header class="header">
    <div class="container navbar">

        <a href="index.php" class="logo">
            Swift<span>Drive</span>
        </a>

        <nav>
            <a href="index.php">Home</a>
            <a href="vehicles.php">Vehicles</a>
            <a href="about.php">About</a>
            <a href="blog.php">Blog</a>
            <a href="contact.php">Contact</a>
        </nav>

        <div class="nav-buttons">
            <a href="dashboard.php" class="btn btn-outline">Dashboard</a>
            <a href="logout.php" class="btn btn-primary">Logout</a>
        </div>

    </div>
</header>

<main class="my-bookings-page">

    <section class="bookings-hero">
        <div class="container">
            <span class="bookings-eyebrow">YOUR ACCOUNT</span>
            <h1>My Bookings</h1>
            <p>View your rental bookings, payment preferences and booking status.</p>
        </div>
    </section>

    <section class="container bookings-content">

        <!-- Statistics -->
        <div class="booking-stats">

            <div class="stat-card">
                <span class="stat-label">Total Bookings</span>
                <strong><?php echo $total_bookings; ?></strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">Pending</span>
                <strong><?php echo $pending_count; ?></strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">Confirmed</span>
                <strong><?php echo $confirmed_count; ?></strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">Completed</span>
                <strong><?php echo $completed_count; ?></strong>
            </div>

        </div>

        <div class="bookings-heading">
            <div>
                <h2>Your Rental History</h2>
                <p>Bookings are shown from newest to oldest.</p>
            </div>

            <a href="vehicles.php" class="new-booking-btn">
                + Book a Vehicle
            </a>
        </div>

        <?php if ($total_bookings > 0): ?>

            <div class="booking-list">

                <?php foreach ($bookings as $booking): ?>

                    <?php
                    $status = $booking['booking_status'] ?? 'Pending';
                    $payment_method = $booking['payment_method'] ?? '';

                    $image_jpg = "images/vehicles/" .
                                 (int)$booking['vehicle_id'] . ".jpg";

                    $image_png = "images/vehicles/" .
                                 (int)$booking['vehicle_id'] . ".png";
                    ?>

                    <article class="booking-card">

                        <div class="booking-card-top">

                            <div class="booking-vehicle">

                                <div class="booking-vehicle-image">
                                    <img
                                        src="<?php echo h($image_jpg); ?>"
                                        alt="<?php echo h($booking['vehicle_name']); ?>"
                                        onerror="this.onerror=null;this.src='<?php echo h($image_png); ?>';">
                                </div>

                                <div class="booking-vehicle-info">

                                    <span class="booking-reference">
                                        BOOKING #<?php echo (int)$booking['booking_id']; ?>
                                    </span>

                                    <h3><?php echo h($booking['vehicle_name']); ?></h3>

                                    <p>
                                        <?php echo h($booking['brand']); ?>
                                        <?php if (!empty($booking['model'])): ?>
                                            · <?php echo h($booking['model']); ?>
                                        <?php endif; ?>
                                    </p>

                                </div>

                            </div>

                            <span class="status-badge status-<?php echo h(statusClass($status)); ?>">
                                <?php echo h(ucwords(str_replace('_', ' ', $status))); ?>
                            </span>

                        </div>

                        <div class="booking-details-grid">

                            <div class="booking-detail">
                                <span>Pickup Location</span>
                                <strong>
                                    <?php echo h($booking['pickup_location'] ?: 'Not specified'); ?>
                                </strong>
                            </div>

                            <div class="booking-detail">
                                <span>Pickup Date</span>
                                <strong><?php echo h($booking['pickup_date']); ?></strong>
                            </div>

                            <div class="booking-detail">
                                <span>Return Date</span>
                                <strong><?php echo h($booking['return_date']); ?></strong>
                            </div>

                            <div class="booking-detail">
                                <span>Payment Method</span>
                                <strong>
                                    <?php echo h(paymentLabel($payment_method)); ?>
                                </strong>
                            </div>

                        </div>

                        <div class="booking-card-bottom">

                            <div class="booking-total">
                                <span>Booking Total</span>
                                <strong>
                                    Rs. <?php echo number_format((float)$booking['total_amount'], 2); ?>
                                </strong>
                            </div>

                            <a
                                href="booking_confirmation.php?id=<?php echo (int)$booking['booking_id']; ?>"
                                class="view-booking-btn">
                                View Details
                            </a>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-bookings">

                <div class="empty-bookings-icon">&#128663;</div>

                <h2>No Bookings Yet</h2>

                <p>
                    You haven't booked a vehicle yet.
                    Explore our vehicles and plan your next journey.
                </p>

                <a href="vehicles.php" class="new-booking-btn">
                    Explore Vehicles
                </a>

            </div>

        <?php endif; ?>

        <div class="back-dashboard">
            <a href="dashboard.php">&larr; Back to Dashboard</a>
        </div>

    </section>

</main>

<!-- Footer -->
<footer class="footer">

    <div class="container footer-content">

        <div>
            <a href="index.php" class="logo footer-logo">
                Swift<span>Drive</span>
            </a>

            <p>Your journey, our priority.</p>
        </div>

        <div>
            <h3>Quick Links</h3>
            <a href="index.php">Home</a>
            <a href="vehicles.php">Vehicles</a>
            <a href="about.php">About</a>
            <a href="blog.php">Blog</a>
            <a href="contact.php">Contact</a>
        </div>

        <div>
            <h3>Contact Us</h3>
            <p>Negombo, Sri Lanka</p>
            <p>Email: info@swiftdrive.lk</p>
        </div>

    </div>

    <div class="footer-bottom">
        &copy; 2026 SwiftDrive Rentals. All rights reserved.
    </div>

</footer>

</body>
</html>