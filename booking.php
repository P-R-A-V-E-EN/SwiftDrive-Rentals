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
$customer_id = (int)$_SESSION['customer_id'];
$vehicle_id = filter_input(INPUT_GET, 'vehicle_id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT);
}
if (!$vehicle_id || $vehicle_id < 1) {
    header("Location: vehicles.php");
    exit;
}
// Customer details
$stmt = mysqli_prepare(
    $conn,
    "SELECT full_name, email, phone
     FROM customers WHERE customer_id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$customer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$customer) {
    header("Location: login.php");
    exit;
}
// Vehicle details
$sql = "SELECT v.*, c.category_name
        FROM vehicles v
        INNER JOIN vehicle_categories c
        ON v.category_id = c.category_id
        WHERE v.vehicle_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $vehicle_id);
mysqli_stmt_execute($stmt);
$vehicle = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$vehicle) {
    exit("Vehicle not found. <a href='vehicles.php'>Back to Vehicles</a>");
}
$error = "";
$pickup_location = "";
$pickup_date = "";
$return_date = "";
$total_days = 0;
$total_amount = 0;
$daily_rate = (float)$vehicle['daily_rate'];
$today = date('Y-m-d');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pickup_location = trim($_POST['pickup_location'] ?? '');
    $pickup_date = trim($_POST['pickup_date'] ?? '');
    $return_date = trim($_POST['return_date'] ?? '');
    $pickup = DateTime::createFromFormat('!Y-m-d', $pickup_date);
    $return = DateTime::createFromFormat('!Y-m-d', $return_date);
    if ($pickup_location === '' || !$pickup || !$return ||
        $pickup->format('Y-m-d') !== $pickup_date ||
        $return->format('Y-m-d') !== $return_date) {
        $error = "Please complete all fields correctly.";
    } elseif ($pickup_date < $today) {
        $error = "Pickup date cannot be in the past.";
    } elseif ($return <= $pickup) {
        $error = "Return date must be after pickup date.";
    } elseif ($vehicle['availability_status'] !== 'Available') {
        $error = "This vehicle is currently unavailable.";
    } else {
        $total_days = (int)$pickup->diff($return)->days;
        $total_amount = $total_days * $daily_rate;
        // Check date conflicts
        $check_sql = "SELECT booking_id
                      FROM bookings
                      WHERE vehicle_id = ?
                      AND booking_status NOT IN ('Cancelled', 'Rejected')
                      AND pickup_date < ?
                      AND return_date > ?
                      LIMIT 1";
        $check = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param(
            $check, "iss", $vehicle_id, $return_date, $pickup_date
        );
        mysqli_stmt_execute($check);
        $conflict = mysqli_num_rows(mysqli_stmt_get_result($check)) > 0;
        mysqli_stmt_close($check);
        if ($conflict) {
            $error = "This vehicle is already booked for these dates.";
        } else {
            /*
             * This INSERT uses the core columns from the existing
             * bookings table. See the note below about saving
             * pickup_location and total_amount.
             */
            $status = "Pending";
            $insert_sql = "INSERT INTO bookings
                (customer_id, vehicle_id, pickup_date, return_date, booking_status)
                VALUES (?, ?, ?, ?, ?)";
            $insert = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param(
                $insert, "iisss",
                $customer_id, $vehicle_id, $pickup_date,
                $return_date, $status
            );
            if (mysqli_stmt_execute($insert)) {
                $booking_id = mysqli_insert_id($conn);
                mysqli_stmt_close($insert);
                header("Location: payment.php?booking_id=" . $booking_id);
                exit;
            } else {
                $error = "Unable to save your booking. Please try again.";
                mysqli_stmt_close($insert);
            }
        }
    }
    if ($pickup_date && $return_date && isset($pickup, $return)
        && $pickup && $return && $return > $pickup) {
        $total_days = (int)$pickup->diff($return)->days;
        $total_amount = $total_days * $daily_rate;
    }
}
$vehicle_image = "images/vehicles/" . (int)$vehicle['vehicle_id'] . ".png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details | SwiftDrive Rentals</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="booking.css">
</head>

<script src="js/script.js"></script>
<body>
<header class="header">
    <div class="container navbar">
        <a href="index.php" class="logo">Swift<span>Drive</span></a>
        <nav>
            <a href="index.php">Home</a>
            <a href="vehicles.php" class="active">Vehicles</a>
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
<main class="booking-page">
    <div class="container">
        <a class="back-link"
           href="vehicle_details.php?id=<?php echo (int)$vehicle_id; ?>">
            &larr; Back to Vehicle Details
        </a>
        <h1 class="booking-title">Booking Details</h1>
        <?php if ($error !== ''): ?>
            <div class="booking-error"><?php echo h($error); ?></div>
        <?php endif; ?>
        <div class="booking-layout">
            <!-- LEFT SIDE -->
            <section class="booking-card">
                <div class="vehicle-mini">
                    <img src="<?php echo h($vehicle_image); ?>"
                         alt="<?php echo h($vehicle['vehicle_name']); ?>"
                         onerror="this.onerror=null;this.src='images/vehicles/<?php echo (int)$vehicle['vehicle_id']; ?>.png';">
                    <div>
                        <h2><?php echo h($vehicle['vehicle_name']); ?></h2>
                        <p>
                            <?php echo h($vehicle['category_name']); ?>
                            | <?php echo h($vehicle['transmission']); ?>
                        </p>
                        <p class="vehicle-price">
                            Rs. <?php echo number_format($daily_rate, 2); ?> / day
                        </p>
                    </div>
                </div>
                <form method="POST"
                      action="booking.php?vehicle_id=<?php echo (int)$vehicle_id; ?>"
                      id="bookingForm">
                    <input type="hidden" name="vehicle_id"
                           value="<?php echo (int)$vehicle_id; ?>">
                    <h2 class="section-title">1. Customer Information</h2>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" value="<?php echo h($customer['full_name']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" value="<?php echo h($customer['email']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" value="<?php echo h($customer['phone']); ?>" readonly>
                    </div>
                    <h2 class="section-title">2. Rental Information</h2>
                    <div class="form-group">
                        <label for="pickup_location">Pickup Location</label>
                        <select name="pickup_location" id="pickup_location" required>
                            <option value="">Select location</option>
                            <?php
                            $locations = [
                                'Negombo',
                                'Colombo',
                                'Katunayake Airport',
                                'Gampaha',
                                'Kalutara'
                            ];
                            foreach ($locations as $location):
                            ?>
                                <option value="<?php echo h($location); ?>"
                                    <?php echo $pickup_location === $location ? 'selected' : ''; ?>>
                                    <?php echo h($location); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="pickup_date">Pickup Date</label>
                        <input type="date" name="pickup_date" id="pickup_date"
                               min="<?php echo h($today); ?>"
                               value="<?php echo h($pickup_date); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="return_date">Return Date</label>
                        <input type="date" name="return_date" id="return_date"
                               min="<?php echo h($today); ?>"
                               value="<?php echo h($return_date); ?>" required>
                    </div>
                    <button type="submit" class="continue-btn">
                        Continue to Payment
                    </button>
                </form>
            </section>
            <!-- RIGHT SIDE -->
            <aside class="summary-card">
                <h2>Booking Summary</h2>
                <div class="vehicle-mini">
                    <img src="<?php echo h($vehicle_image); ?>"
                         alt="<?php echo h($vehicle['vehicle_name']); ?>"
                         onerror="this.onerror=null;this.src='images/vehicles/<?php echo (int)$vehicle['vehicle_id']; ?>.png';">
                    <div>
                        <h2><?php echo h($vehicle['vehicle_name']); ?></h2>
                        <p>
                            <?php echo h($vehicle['category_name']); ?>
                            | <?php echo h($vehicle['transmission']); ?>
                        </p>
                    </div>
                </div>
                <div class="summary-row">
                    <span>Pickup Date</span>
                    <strong id="summaryPickup">
                        <?php echo $pickup_date ? h($pickup_date) : 'Select date'; ?>
                    </strong>
                </div>
                <div class="summary-row">
                    <span>Return Date</span>
                    <strong id="summaryReturn">
                        <?php echo $return_date ? h($return_date) : 'Select date'; ?>
                    </strong>
                </div>
                <div class="summary-row">
                    <span>Rental Period</span>
                    <strong id="summaryDays"><?php echo $total_days; ?> days</strong>
                </div>
                <div class="summary-row summary-total">
                    <span>Total Amount</span>
                    <strong id="summaryTotal">
                        Rs. <?php echo number_format($total_amount, 2); ?>
                    </strong>
                </div>
                <div class="payment-section">
                    <h3>Payment / Deposit</h3>
                    <p>Deposit amount required</p>
                    <input class="deposit-input" type="text"
                           value="Rs. 50.00" readonly>
                    <p class="remaining">
                        Remaining amount and final payment details will be
                        confirmed before payment.
                    </p>
                </div>
                <p class="payment-note">
                    The button submits your booking request. Actual online
                    payment requires a separate payment page and integration.
                </p>
            </aside>
        </div>
    </div>
</main>
<footer class="footer">
    <div class="container footer-content">
        <div>
            <a href="index.php" class="logo footer-logo">Swift<span>Drive</span></a>
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
const pickup = document.getElementById('pickup_date');
const returnDate = document.getElementById('return_date');
const dailyRate = <?php echo json_encode($daily_rate); ?>;
function updateSummary() {
    const p = pickup.value;
    const r = returnDate.value;
    document.getElementById('summaryPickup').textContent = p || 'Select date';
    document.getElementById('summaryReturn').textContent = r || 'Select date';
    let days = 0;
    if (p && r) {
        days = Math.round(
            (new Date(r + 'T00:00:00') - new Date(p + 'T00:00:00')) / 86400000
        );
    }
    document.getElementById('summaryDays').textContent =
        Math.max(0, days) + (days === 1 ? ' day' : ' days');
    const total = Math.max(0, days) * dailyRate;
    document.getElementById('summaryTotal').textContent =
        'Rs. ' + total.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
}
pickup.addEventListener('change', function () {
    if (pickup.value) {
        const nextDay = new Date(pickup.value + 'T00:00:00');
        nextDay.setDate(nextDay.getDate() + 1);
        const minReturn = nextDay.getFullYear() + '-' +
            String(nextDay.getMonth() + 1).padStart(2, '0') + '-' +
            String(nextDay.getDate()).padStart(2, '0');
        returnDate.min = minReturn;
        if (returnDate.value && returnDate.value < minReturn) {
            returnDate.value = '';
        }
    }
    updateSummary();
});
returnDate.addEventListener('change', updateSummary);
updateSummary();
</script>
</body>
</html>