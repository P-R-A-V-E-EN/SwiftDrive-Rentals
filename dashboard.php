 <?php
session_start();
require_once 'config/db.php';
// Customer login check
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}
$customerId = (int) $_SESSION['customer_id'];
$customerName = htmlspecialchars(
    $_SESSION['full_name'] ?? 'Customer',
    ENT_QUOTES,
    'UTF-8'
);
// Booking statistics
$totalBookings = 0;
$activeBookings = 0;
$completedBookings = 0;
$recentBookings = [];
$bookingError = false;
try {
    /*
       Assumed booking table:
       bookings
       - booking_id
       - customer_id
       - vehicle_id
       - pickup_date
       - return_date
       - booking_status
       Assumed vehicles table:
       vehicles
       - vehicle_id
       - vehicle_name
    */
    $statsSql = "
        SELECT
            COUNT(*) AS total_bookings,
            COALESCE(SUM(
                CASE
                    WHEN LOWER(booking_status) IN
                    ('pending', 'confirmed', 'approved', 'active')
                    THEN 1 ELSE 0
                END
            ), 0) AS active_bookings,
            COALESCE(SUM(
                CASE
                    WHEN LOWER(booking_status) = 'completed'
                    THEN 1 ELSE 0
                END
            ), 0) AS completed_bookings
        FROM bookings
        WHERE customer_id = ?
    ";
    $statsStmt = $conn->prepare($statsSql);
    $statsStmt->bind_param("i", $customerId);
    $statsStmt->execute();
    $statsResult = $statsStmt->get_result();
    $stats = $statsResult->fetch_assoc();
    $totalBookings = (int) $stats['total_bookings'];
    $activeBookings = (int) $stats['active_bookings'];
    $completedBookings = (int) $stats['completed_bookings'];
    $statsStmt->close();
    // Recent bookings
    $recentSql = "
        SELECT
            b.booking_id,
            b.pickup_date,
            b.return_date,
            b.booking_status,
            v.vehicle_name
        FROM bookings b
        LEFT JOIN vehicles v
            ON b.vehicle_id = v.vehicle_id
        WHERE b.customer_id = ?
        ORDER BY b.booking_id DESC
        LIMIT 5
    ";
    $recentStmt = $conn->prepare($recentSql);
    $recentStmt->bind_param("i", $customerId);
    $recentStmt->execute();
    $recentResult = $recentStmt->get_result();
    while ($row = $recentResult->fetch_assoc()) {
        $recentBookings[] = $row;
    }
    $recentStmt->close();
} catch (Throwable $e) {
    // Keep the dashboard available if the schema differs.
    $bookingError = true;
}
function safeText($value) {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}
function bookingStatusClass($status) {
    $status = strtolower(trim((string) $status));
    if (in_array($status, ['confirmed', 'approved', 'completed'])) {
        return 'status-success';
    }
    if (in_array($status, ['pending'])) {
        return 'status-pending';
    }
    if (in_array($status, ['cancelled', 'canceled', 'rejected'])) {
        return 'status-cancelled';
    }
    return 'status-default';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard | SwiftDrive Rentals</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
<!-- WEBSITE HEADER -->
<header class="site-header">
    <div class="header-container">
        <a href="index.php" class="site-logo">
            Swift<span>Drive</span>
        </a>
        <nav class="main-nav">
            <a href="index.php">Home</a>
            <a href="vehicles.php">Vehicles</a>
            <a href="about.php">About</a>
            <a href="blog.php">Blog</a>
            <a href="contact.php">Contact</a>
        </nav>
        <div class="header-actions">
            <a href="dashboard.php" class="header-login">
                My Account
            </a>
            <a href="register.php" class="header-register">
                Register
            </a>
        </div>
        <button
            class="menu-toggle"
            id="menuToggle"
            type="button"
            aria-label="Toggle navigation"
            aria-expanded="false"
        >
            ☰
        </button>
    </div>
    <nav class="mobile-nav" id="mobileNav">
        <a href="index.php">Home</a>
        <a href="vehicles.php">Vehicles</a>
        <a href="about.php">About</a>
        <a href="blog.php">Blog</a>
        <a href="contact.php">Contact</a>
        <a href="dashboard.php">My Account</a>
        <a href="register.php">Register</a>
    </nav>
</header>
<!-- DASHBOARD -->
<div class="dashboard-layout">
    <!-- SIDEBAR -->
    <aside class="dashboard-sidebar">
        <div class="customer-profile">
            <div class="customer-avatar">
                <?php echo strtoupper(substr($customerName, 0, 1)); ?>
            </div>
            <h3><?php echo $customerName; ?></h3>
            <p>Customer Account</p>
        </div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="sidebar-link active">
                <span>▦</span>
                Dashboard
            </a>
            <a href="my_bookings.php" class="sidebar-link">
                <span>▤</span>
                My Bookings
            </a>
            <a href="booking_status.php" class="sidebar-link">
                <span>◷</span>
                Booking Status
            </a>
            <a href="profile.php" class="sidebar-link">
                <span>♙</span>
                My Profile
            </a>
        </nav>
        <div class="sidebar-logout">
            <a href="logout.php" class="sidebar-link">
                <span>↪</span>
                Logout
            </a>
        </div>
    </aside>
    <!-- DASHBOARD MAIN -->
    <main class="dashboard-main">
        <div class="dashboard-heading">
            <div>
                <p class="eyebrow">CUSTOMER AREA</p>
                <h1>Dashboard</h1>
            </div>
            <a href="vehicles.php" class="book-car-btn">
                + Book a Car
            </a>
        </div>
        <!-- WELCOME -->
        <section class="welcome-section">
            <div class="welcome-car-icon">🚘</div>
            <div>
                <h2>Welcome, <?php echo $customerName; ?>!</h2>
                <p>
                    Manage your bookings and enjoy your journey
                    with SwiftDrive Rentals.
                </p>
            </div>
        </section>
        <!-- BOOKING STATISTICS -->
        <section class="statistics-grid">
            <div class="stat-card">
                <div class="stat-top">
                    <span>Total Bookings</span>
                    <span class="stat-icon orange-icon">▤</span>
                </div>
                <h2><?php echo $totalBookings; ?></h2>
                <p>All your reservations</p>
            </div>
            <div class="stat-card">
                <div class="stat-top">
                    <span>Active Bookings</span>
                    <span class="stat-icon blue-icon">◷</span>
                </div>
                <h2><?php echo $activeBookings; ?></h2>
                <p>Pending and confirmed</p>
            </div>
            <div class="stat-card">
                <div class="stat-top">
                    <span>Completed</span>
                    <span class="stat-icon green-icon">✓</span>
                </div>
                <h2><?php echo $completedBookings; ?></h2>
                <p>Completed reservations</p>
            </div>
        </section>
        <!-- RECENT BOOKINGS -->
        <section class="content-card">
            <div class="section-heading">
                <div>
                    <h2>Recent Bookings</h2>
                    <p>Your latest vehicle reservations</p>
                </div>
                <a href="my_bookings.php" class="view-all">
                    View All →
                </a>
            </div>
            <?php if ($bookingError): ?>
                <div class="notice-box">
                    Booking information could not be loaded.
                    Please check the bookings table and its column names
                    in phpMyAdmin.
                </div>
            <?php elseif (empty($recentBookings)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🚘</div>
                    <h3>No bookings yet</h3>
                    <p>
                        You have not made any reservations.
                        Find your next car and start your journey.
                    </p>
                    <a href="vehicles.php" class="book-car-btn">
                        Explore Vehicles
                    </a>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="booking-table">
                        <thead>
                            <tr>
                                <th>Car</th>
                                <th>Pickup Date</th>
                                <th>Return Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentBookings as $booking): ?>
                            <tr>
                                <td>
                                    <div class="car-name">
                                        <span class="car-symbol">🚘</span>
                                        <span>
                                            <?php
                                            echo safeText(
                                                $booking['vehicle_name']
                                                    ?? 'Vehicle'
                                            );
                                            ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    echo !empty($booking['pickup_date'])
                                        ? date(
                                            'M d, Y',
                                            strtotime($booking['pickup_date'])
                                        )
                                        : '-';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    echo !empty($booking['return_date'])
                                        ? date(
                                            'M d, Y',
                                            strtotime($booking['return_date'])
                                        )
                                        : '-';
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php
                                        echo bookingStatusClass(
                                            $booking['booking_status'] ?? ''
                                        );
                                    ?>">
                                        <?php
                                        echo safeText(
                                            $booking['booking_status']
                                                ?? 'Unknown'
                                        );
                                        ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
        <!-- BOOKING STATUS PROGRESS -->
        <section class="content-card progress-card">
            <div class="section-heading">
                <div>
                    <h2>Booking Status</h2>
                    <p>Understand the steps of your reservation</p>
                </div>
            </div>
            <div class="booking-progress">
                <div class="progress-step completed-step">
                    <div class="progress-dot">✓</div>
                    <span>Booked</span>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step">
                    <div class="progress-dot">2</div>
                    <span>Payment</span>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step">
                    <div class="progress-dot">3</div>
                    <span>Confirmed</span>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step">
                    <div class="progress-dot">4</div>
                    <span>On Trip</span>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step">
                    <div class="progress-dot">5</div>
                    <span>Completed</span>
                </div>
            </div>
        </section>
    </main>
</div>
<!-- WEBSITE FOOTER -->
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-brand">
            <a href="index.php" class="footer-logo">
                Swift<span>Drive</span>
            </a>
            <p>Your journey, our priority.</p>
        </div>
        <div class="footer-links">
            <h3>Quick Links</h3>
            <a href="index.php">Home</a>
            <a href="vehicles.php">Vehicles</a>
            <a href="about.php">About</a>
            <a href="blog.php">Blog</a>
            <a href="contact.php">Contact</a>
        </div>
        <div class="footer-contact">
            <h3>Contact Us</h3>
            <p>Negombo, Sri Lanka</p>
            <p>Email: info@swiftdrive.lk</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>
            &copy; <?php echo date('Y'); ?>
            SwiftDrive Rentals. All rights reserved.
        </p>
    </div>
</footer>
<script>
const menuToggle = document.getElementById('menuToggle');
const mobileNav = document.getElementById('mobileNav');
menuToggle.addEventListener('click', function () {
    const isOpen = mobileNav.classList.toggle('show');
    menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
});
</script>
</body>
</html>