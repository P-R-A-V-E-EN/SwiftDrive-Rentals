 <?php
session_start();
require_once "config/db.php";

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Check customer login
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$customer_id = (int) $_SESSION['customer_id'];

$booking_id = filter_input(
    INPUT_GET,
    'booking_id',
    FILTER_VALIDATE_INT
);

if (!$booking_id || $booking_id < 1) {
    header("Location: dashboard.php");
    exit;
}

// Load booking belonging to the logged-in customer
$sql = "SELECT
            b.booking_id,
            b.customer_id,
            b.vehicle_id,
            b.pickup_date,
            b.return_date,
            b.booking_status,
            b.payment_method,
            b.total_amount AS booking_total,
            v.vehicle_name,
            v.brand,
            v.model,
            v.daily_rate,
            v.transmission,
            c.category_name
        FROM bookings b
        INNER JOIN vehicles v
            ON b.vehicle_id = v.vehicle_id
        INNER JOIN vehicle_categories c
            ON v.category_id = c.category_id
        WHERE b.booking_id = ?
          AND b.customer_id = ?
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    exit("Unable to load booking. Please check your database.");
}

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $booking_id,
    $customer_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$booking) {
    http_response_code(404);
    exit("Booking not found.");
}

// Prevent payment selection for cancelled bookings
$status = strtolower(trim($booking['booking_status']));

if (in_array($status, ['cancelled', 'canceled', 'rejected'], true)) {
    exit("This booking cannot proceed to payment.");
}

// Calculate rental duration
try {
    $pickup = new DateTime($booking['pickup_date']);
    $return = new DateTime($booking['return_date']);
} catch (Exception $e) {
    exit("Invalid booking dates.");
}

$total_days = (int) $pickup->diff($return)->days;

if ($return <= $pickup || $total_days < 1) {
    exit("Invalid rental period. Please check your booking dates.");
}

// Use the booking total when it is valid; otherwise calculate it
$booking_total = (float) $booking['booking_total'];

if ($booking_total > 0) {
    $total_amount = $booking_total;
} else {
    $total_amount = $total_days * (float) $booking['daily_rate'];
}

// Demonstration deposit policy
$deposit = min(50, $total_amount);

$error = "";

// Save payment method to the database
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $payment_method = $_POST['payment_method'] ?? '';

    $allowed_methods = [
        'card',
        'bank_transfer',
        'cash'
    ];

    if (!in_array($payment_method, $allowed_methods, true)) {

        $error = "Please select a valid payment method.";

    } else {

        $update_sql = "UPDATE bookings
                       SET payment_method = ?
                       WHERE booking_id = ?
                         AND customer_id = ?";

        $update_stmt = mysqli_prepare($conn, $update_sql);

        if (!$update_stmt) {

            $error = "Unable to save payment method. Please try again.";

        } else {

            mysqli_stmt_bind_param(
                $update_stmt,
                "sii",
                $payment_method,
                $booking_id,
                $customer_id
            );

            if (mysqli_stmt_execute($update_stmt)) {

                mysqli_stmt_close($update_stmt);

                // Keep compatibility with existing confirmation page
                $_SESSION['booking_payment_method'][$booking_id] =
                    $payment_method;

                // Redirect to booking confirmation
                header(
                    "Location: booking_confirmation.php?id=" .
                    (int) $booking_id
                );
                exit;

            } else {

                $error = "Unable to save payment method. Please try again.";
                mysqli_stmt_close($update_stmt);
            }
        }
    }
}

// Get the saved payment method from the database
$selected_method = $booking['payment_method'] ?? '';

if (!in_array($selected_method, ['card', 'bank_transfer', 'cash'], true)) {
    $selected_method = 'card';
}

// Vehicle image
$vehicle_image = "images/vehicles/" .
                 (int) $booking['vehicle_id'] . ".jpg";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Payment | SwiftDrive Rentals</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="payment.css">
</head>
<body>

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
            <a href="login.php" class="btn btn-outline">Login</a>
            <a href="register.php" class="btn btn-primary">Register</a>
        </div>

    </div>
</header>

<main class="payment-page">
    <div class="container">

        <h1 class="payment-title">Payment</h1>

        <?php if ($error !== ''): ?>
            <div class="payment-alert error">
                <?php echo h($error); ?>
            </div>
        <?php endif; ?>

        <div class="payment-layout">

            <!-- LEFT COLUMN -->
            <div class="payment-sidebar">

                <!-- Booking Summary -->
                <section class="payment-card">

                    <h2>Booking Summary</h2>

                    <div class="vehicle-summary">

                        <img
                            src="<?php echo h($vehicle_image); ?>"
                            alt="<?php echo h($booking['vehicle_name']); ?>"
                            onerror="this.onerror=null;this.src='images/vehicles/<?php echo (int)$booking['vehicle_id']; ?>.png';">

                        <div>
                            <h3><?php echo h($booking['vehicle_name']); ?></h3>

                            <p>
                                <?php echo h($booking['category_name']); ?>
                                |
                                <?php echo h($booking['transmission']); ?>
                            </p>
                        </div>

                    </div>

                    <div class="summary-row">
                        <span>Pickup Date</span>
                        <strong><?php echo h($booking['pickup_date']); ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>Return Date</span>
                        <strong><?php echo h($booking['return_date']); ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>Rental Period</span>
                        <strong>
                            <?php echo $total_days; ?>
                            <?php echo $total_days === 1 ? 'day' : 'days'; ?>
                        </strong>
                    </div>

                    <div class="summary-row summary-total">
                        <span>Total Amount</span>
                        <strong>
                            Rs. <?php echo number_format($total_amount, 2); ?>
                        </strong>
                    </div>

                </section>

                <!-- Deposit Summary -->
                <section class="payment-card pay-summary">

                    <h2>2. Pay Now</h2>

                    <div class="summary-row">
                        <span>Rental Amount</span>
                        <strong>
                            Rs. <?php echo number_format($total_amount, 2); ?>
                        </strong>
                    </div>

                    <div class="summary-row">
                        <span>Deposit (Demo)</span>
                        <strong>
                            Rs. <?php echo number_format($deposit, 2); ?>
                        </strong>
                    </div>

                    <div class="summary-row summary-total">
                        <span>Deposit Amount</span>
                        <strong>
                            Rs. <?php echo number_format($deposit, 2); ?>
                        </strong>
                    </div>

                    <div class="payment-progress">
                        <span></span>
                    </div>

                    <p class="small-note">
                        This is a demonstration deposit only.
                        No real payment is collected on this page.
                    </p>

                </section>

            </div>

            <!-- RIGHT COLUMN -->
            <section class="payment-card payment-method-card">

                <h2>1. Payment Method</h2>

                <form
                    method="POST"
                    action="payment.php?booking_id=<?php echo (int)$booking_id; ?>"
                    id="paymentForm">

                    <!-- Card -->
                    <label class="method-option">

                        <input
                            type="radio"
                            name="payment_method"
                            value="card"
                            <?php echo $selected_method === 'card' ? 'checked' : ''; ?>
                            required>

                        <span class="method-content">

                            <span class="method-heading">
                                <strong>Credit / Debit Card</strong>

                                <span class="card-brands">
                                    <span class="visa">VISA</span>

                                    <span class="mastercard">
                                        <i></i><i></i>
                                    </span>
                                </span>
                            </span>

                        </span>

                    </label>

                    <div class="card-fields" id="cardFields">

                        <div class="form-group">

                            <label for="card_number">Card Number</label>

                            <input
                                type="text"
                                id="card_number"
                                inputmode="numeric"
                                autocomplete="off"
                                placeholder="1234 5678 9012 3456"
                                maxlength="19">

                        </div>

                        <div class="card-field-row">

                            <div class="form-group">

                                <label for="expiry">Expiry Date</label>

                                <input
                                    type="text"
                                    id="expiry"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    placeholder="MM / YY"
                                    maxlength="7">

                            </div>

                            <div class="form-group">

                                <label for="cvv">CVV</label>

                                <input
                                    type="password"
                                    id="cvv"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    placeholder="123"
                                    maxlength="4">

                            </div>

                        </div>

                        <p class="small-note">
                            Demo only. Do not enter real card details.
                            These fields are not submitted or stored.
                        </p>

                    </div>

                    <!-- Bank Transfer -->
                    <label class="method-option">

                        <input
                            type="radio"
                            name="payment_method"
                            value="bank_transfer"
                            <?php echo $selected_method === 'bank_transfer' ? 'checked' : ''; ?>>

                        <span class="method-content">
                            <strong>Bank Transfer</strong>
                        </span>

                    </label>

                    <div class="method-info" id="bankInfo" hidden>
                        Bank transfer instructions will be provided
                        when business bank details are configured.
                    </div>

                    <!-- Cash -->
                    <label class="method-option">

                        <input
                            type="radio"
                            name="payment_method"
                            value="cash"
                            <?php echo $selected_method === 'cash' ? 'checked' : ''; ?>>

                        <span class="method-content">
                            <strong>Cash (at pickup)</strong>
                        </span>

                    </label>

                    <div class="method-info" id="cashInfo" hidden>
                        This records your preferred payment method only.
                        No payment is collected on this page.
                    </div>

                    <button type="submit" class="pay-button">
                        Continue
                    </button>

                    <p class="security-note">
                        Demonstration checkout. No real payment will be made.
                    </p>

                </form>

            </section>

        </div>
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

<script>
const paymentRadios = document.querySelectorAll(
    'input[name="payment_method"]'
);

const cardFields = document.getElementById('cardFields');
const bankInfo = document.getElementById('bankInfo');
const cashInfo = document.getElementById('cashInfo');

function updatePaymentMethod() {

    const selected = document.querySelector(
        'input[name="payment_method"]:checked'
    );

    const method = selected ? selected.value : 'card';

    cardFields.hidden = method !== 'card';
    bankInfo.hidden = method !== 'bank_transfer';
    cashInfo.hidden = method !== 'cash';
}

paymentRadios.forEach(function (radio) {
    radio.addEventListener('change', updatePaymentMethod);
});

updatePaymentMethod();

// Format demonstration card number
document.getElementById('card_number').addEventListener('input', function () {

    const digits = this.value.replace(/\D/g, '').slice(0, 16);

    this.value = digits.replace(/(.{4})/g, '$1 ').trim();
});

// Format demonstration expiry date
document.getElementById('expiry').addEventListener('input', function () {

    const digits = this.value.replace(/\D/g, '').slice(0, 4);

    this.value = digits.length > 2
        ? digits.slice(0, 2) + ' / ' + digits.slice(2)
        : digits;
});

// Allow numbers only in demonstration CVV field
document.getElementById('cvv').addEventListener('input', function () {

    this.value = this.value.replace(/\D/g, '').slice(0, 4);
});
</script>

</body>
</html>