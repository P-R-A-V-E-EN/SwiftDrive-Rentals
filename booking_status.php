 <?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'config/db.php';
$customerId = (int) $_SESSION['customer_id'];
$bookings = [];
$error = false;
try {
    $sql = "SELECT b.booking_id,
                   b.pickup_date,
                   b.return_date,
                   b.booking_status,
                   v.vehicle_name
            FROM bookings b
            LEFT JOIN vehicles v
                ON b.vehicle_id = v.vehicle_id
            WHERE b.customer_id = ?
            ORDER BY b.booking_id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    $stmt->close();
} catch (Throwable $e) {
    $error = true;
}
function e($value) {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}
function statusClass($status) {
    $status = strtolower(trim((string) $status));
    if (in_array($status, ['confirmed', 'approved', 'completed'])) {
        return 'success';
    }
    if ($status === 'pending') {
        return 'pending';
    }
    if (in_array($status, ['cancelled', 'canceled', 'rejected'])) {
        return 'cancelled';
    }
    return 'default';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Status | SwiftDrive Rentals</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .status-page {
            max-width: 1400px;
            margin: auto;
            padding: 35px 5% 50px;
            min-height: 450px;
        }
        .status-page h1 {
            color: #0F172A;
            margin-bottom: 10px;
        }
        .status-intro {
            color: #64748B;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        .status-panel {
            background: #FFFFFF;
            padding: 24px;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
        }
        .status-table-wrap {
            overflow-x: auto;
        }
        .status-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        .status-table th,
        .status-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #E2E8F0;
            font-size: 13px;
            white-space: nowrap;
        }
        .status-table th {
            background: #F8FAFC;
            color: #64748B;
        }
        .status-pill {
            display: inline-block;
            padding: 7px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: capitalize;
        }
        .status-pill.success {
            color: #166534;
            background: #DCFCE7;
        }
        .status-pill.pending {
            color: #92400E;
            background: #FEF3C7;
        }
        .status-pill.cancelled {
            color: #991B1B;
            background: #FEE2E2;
        }
        .status-pill.default {
            color: #475569;
            background: #E2E8F0;
        }
        .status-empty {
            padding: 35px 10px;
            text-align: center;
            color: #64748B;
            line-height: 1.8;
        }
        .status-back {
            display: inline-block;
            margin-top: 20px;
            color: #FF6B35;
            font-weight: bold;
        }
        @media (max-width: 600px) {
            .status-page {
                padding: 25px 15px 35px;
            }
            .status-panel {
                padding: 14px;
            }
        }
    </style>
</head>
<body>
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
            <a href="dashboard.php" class="header-login">My Account</a>
            <a href="register.php" class="header-register">Register</a>
        </div>
        <button class="menu-toggle" id="menuToggle"
                type="button" aria-label="Toggle navigation">☰</button>
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
<main class="status-page">
    <h1>Booking Status</h1>
    <p class="status-intro">
        View your reservations, pickup dates, return dates and current status.
    </p>
    <section class="status-panel">
        <?php if ($error): ?>
            <p class="status-empty">
                Booking details could not be loaded.
                Please check the database table and column names.
            </p>
        <?php elseif (empty($bookings)): ?>
            <div class="status-empty">
                <h3>No bookings found</h3>
                <p>You have not made any reservations yet.</p>
                <a href="vehicles.php" class="book-car-btn">Explore Vehicles</a>
            </div>
        <?php else: ?>
            <div class="status-table-wrap">
                <table class="status-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Vehicle</th>
                            <th>Pickup Date</th>
                            <th>Return Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td>#<?php echo e($booking['booking_id']); ?></td>
                            <td><?php echo e($booking['vehicle_name'] ?? 'Vehicle'); ?></td>
                            <td>
                                <?php
                                echo !empty($booking['pickup_date'])
                                    ? e(date('M d, Y', strtotime($booking['pickup_date'])))
                                    : '-';
                                ?>
                            </td>
                            <td>
                                <?php
                                echo !empty($booking['return_date'])
                                    ? e(date('M d, Y', strtotime($booking['return_date'])))
                                    : '-';
                                ?>
                            </td>
                            <td>
                                <span class="status-pill <?php
                                    echo statusClass($booking['booking_status'] ?? '');
                                ?>">
                                    <?php echo e($booking['booking_status'] ?? 'Unknown'); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
    <a href="dashboard.php" class="status-back">← Back to Dashboard</a>
</main>
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-brand">
            <a href="index.php" class="footer-logo">Swift<span>Drive</span></a>
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
        <p>&copy; <?php echo date('Y'); ?> SwiftDrive Rentals. All rights reserved.</p>
    </div>
</footer>
<script>
const menuToggle = document.getElementById('menuToggle');
const mobileNav = document.getElementById('mobileNav');
menuToggle.addEventListener('click', function () {
    mobileNav.classList.toggle('show');
});
</script>
</body>
</html>