 <?php
session_start();
require_once "config/db.php";
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}
$customer_id = (int)$_SESSION['customer_id'];
$booking_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$booking_id || $booking_id < 1) {
    header("Location: dashboard.php");
    exit;
}
$sql = "SELECT b.booking_id,
               b.customer_id,
               b.pickup_date,
               b.return_date,
               b.booking_status,
               v.vehicle_id,
               v.vehicle_name,
               v.brand,
               v.model,
               v.daily_rate,
               v.transmission,
               c.category_name,
               cu.full_name,
               cu.email
        FROM bookings b
        INNER JOIN vehicles v ON b.vehicle_id = v.vehicle_id
        INNER JOIN vehicle_categories c ON v.category_id = c.category_id
        INNER JOIN customers cu ON b.customer_id = cu.customer_id
        WHERE b.booking_id = ?
          AND b.customer_id = ?
        LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $booking_id, $customer_id);
mysqli_stmt_execute($stmt);
$booking = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$booking) {
    http_response_code(404);
    exit("Booking not found.");
}
$pickup = new DateTime($booking['pickup_date']);
$return = new DateTime($booking['return_date']);
$total_days = (int)$pickup->diff($return)->days;
$total_amount = $total_days * (float)$booking['daily_rate'];
$payment_methods = [
    'card' => 'Credit / Debit Card',
    'bank_transfer' => 'Bank Transfer',
    'cash' => 'Cash at Pickup'
];
$selected_method = $_SESSION['booking_payment_method'][$booking_id] ?? '';
$method_name = $payment_methods[$selected_method] ?? 'Not selected';
$vehicle_image = "images/vehicles/" .
                 (int)$booking['vehicle_id'] . ".jpg";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation | SwiftDrive Rentals</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="booking_confirmation.css">
</head>
<body>
<header class="header">
    <div class="container navbar">
        <a href="index.php" class="logo">Swift<span>Drive</span></a>
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
<main class="confirmation-page">
    <div class="container">
        <section class="confirmation-card">
            <div class="confirmation-icon">
                &#10003;
            </div>
            <h1>Booking Request Received!</h1>
            <p class="confirmation-intro">
                Thank you, <?php echo h($booking['full_name']); ?>.
                Your booking request has been recorded.
            </p>
            <div class="status-badge">
                <?php echo h($booking['booking_status']); ?>
            </div>
            <p class="status-message">
                Your booking is awaiting confirmation from SwiftDrive Rentals.
                No successful payment is being claimed by this page.
            </p>
            <div class="booking-reference">
                <span>Booking Reference</span>
                <strong>#<?php echo (int)$booking['booking_id']; ?></strong>
            </div>
            <div class="confirmation-vehicle">
                <img
                    src="<?php echo h($vehicle_image); ?>"
                    alt="<?php echo h($booking['vehicle_name']); ?>"
                    onerror="this.onerror=null;this.src='images/vehicles/<?php echo (int)$booking['vehicle_id']; ?>.png';">
                <div>
                    <h2><?php echo h($booking['vehicle_name']); ?></h2>
                    <p>
                        <?php echo h($booking['brand'] . ' ' . $booking['model']); ?>
                    </p>
                    <p>
                        <?php echo h($booking['category_name']); ?>
                        | <?php echo h($booking['transmission']); ?>
                    </p>
                </div>
            </div>
            <div class="confirmation-details">
                <div class="detail-row">
                    <span>Customer</span>
                    <strong><?php echo h($booking['full_name']); ?></strong>
                </div>
                <div class="detail-row">
                    <span>Email</span>
                    <strong><?php echo h($booking['email']); ?></strong>
                </div>
                <div class="detail-row">
                    <span>Pickup Date</span>
                    <strong><?php echo h($booking['pickup_date']); ?></strong>
                </div>
                <div class="detail-row">
                    <span>Return Date</span>
                    <strong><?php echo h($booking['return_date']); ?></strong>
                </div>
                <div class="detail-row">
                    <span>Rental Period</span>
                    <strong>
                        <?php echo $total_days; ?>
                        <?php echo $total_days === 1 ? 'day' : 'days'; ?>
                    </strong>
                </div>
                <div class="detail-row">
                    <span>Daily Rental Rate</span>
                    <strong>
                        Rs. <?php echo number_format(
                            (float)$booking['daily_rate'], 2
                        ); ?>
                    </strong>
                </div>
                <div class="detail-row">
                    <span>Selected Payment Method</span>
                    <strong><?php echo h($method_name); ?></strong>
                </div>
                <div class="detail-row total-row">
                    <span>Estimated Total</span>
                    <strong>
                        Rs. <?php echo number_format($total_amount, 2); ?>
                    </strong>
                </div>
            </div>
            <div class="confirmation-notice">
                <strong>Payment notice</strong>
                <p>
                    This website currently uses a demonstration payment flow.
                    No card has been charged and no payment has been verified.
                </p>
            </div>
            <div class="confirmation-actions">
                <a href="dashboard.php" class="confirm-primary">
                    Go to Dashboard
                </a>
                <a href="vehicles.php" class="confirm-secondary">
                    Browse More Vehicles
                </a>
            </div>
        </section>
    </div>
</main>
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