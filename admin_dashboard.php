 <?php
session_start();
require_once "config/db.php";

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Admin login protection
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$adminName = $_SESSION['admin_name'] ?? 'Administrator';

// Get dashboard statistics
$stats = [
    'bookings' => 0,
    'vehicles' => 0,
    'customers' => 0,
    'pending' => 0
];

$queries = [
    'bookings' => "SELECT COUNT(*) AS total FROM bookings",
    'vehicles' => "SELECT COUNT(*) AS total FROM vehicles",
    'customers' => "SELECT COUNT(*) AS total FROM customers",
    'pending' => "SELECT COUNT(*) AS total FROM bookings
                  WHERE LOWER(booking_status) = 'pending'"
];

foreach ($queries as $key => $sql) {
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats[$key] = (int)($row['total'] ?? 0);
        mysqli_free_result($result);
    }
}

// Get latest bookings
$recentBookings = [];

$recentSql = "SELECT b.booking_id,
                     b.pickup_date,
                     b.return_date,
                     b.booking_status,
                     b.total_amount,
                     c.full_name,
                     v.vehicle_name
              FROM bookings b
              INNER JOIN customers c
                  ON b.customer_id = c.customer_id
              INNER JOIN vehicles v
                  ON b.vehicle_id = v.vehicle_id
              ORDER BY b.booking_id DESC
              LIMIT 5";

$recentResult = mysqli_query($conn, $recentSql);

if ($recentResult) {
    while ($row = mysqli_fetch_assoc($recentResult)) {
        $recentBookings[] = $row;
    }
    mysqli_free_result($recentResult);
}

function dashboardStatusClass($status) {
    $status = strtolower(trim((string)$status));

    if (in_array($status, ['confirmed', 'approved'], true)) {
        return 'confirmed';
    }

    if ($status === 'completed') {
        return 'completed';
    }

    if (in_array($status, ['cancelled', 'canceled', 'rejected'], true)) {
        return 'cancelled';
    }

    return 'pending';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | SwiftDrive Rentals</title>

    <link rel="stylesheet" href="admin_dashboard.css">
</head>
<body>

<div class="admin-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">

        <a href="admin_dashboard.php" class="sidebar-logo">
            Swift<span>Drive</span>
            <small>ADMIN PANEL</small>
        </a>

        <p class="sidebar-label">MAIN MENU</p>

        <nav class="sidebar-nav">

            <a href="admin_dashboard.php" class="active">
                <span class="nav-icon">▦</span>
                Dashboard
            </a>

            <a href="admin_vehicles.php">
                <span class="nav-icon">🚗</span>
                Manage Vehicles
            </a>

            <a href="admin_bookings.php">
                <span class="nav-icon">▤</span>
                Manage Bookings
            </a>

            <a href="admin_customers.php">
                <span class="nav-icon">♙</span>
                Manage Customers
            </a>

            <a href="admin_messages.php">
                <span class="nav-icon">✉</span>
                Contact Messages
            </a>

        </nav>

        <div class="sidebar-bottom">

            <div class="admin-profile">
                <div class="admin-avatar">
                    <?php echo h(strtoupper(substr($adminName, 0, 1))); ?>
                </div>

                <div class="admin-profile-text">
                    <strong><?php echo h($adminName); ?></strong>
                    <span>Administrator</span>
                </div>
            </div>

            <a href="admin_logout.php" class="logout-link">
                <span>↪</span> Logout
            </a>

        </div>

    </aside>

    <!-- MAIN AREA -->
    <main class="main-content">

        <header class="topbar">

            <button type="button" class="menu-toggle" id="menuToggle"
                    aria-label="Toggle navigation">
                ☰
            </button>

            <div class="breadcrumb">
                Admin Panel <span>/</span>
                <strong>Dashboard</strong>
            </div>

            <div class="topbar-right">
                <span class="topbar-greeting">Welcome,</span>

                <div class="topbar-avatar">
                    <?php echo h(strtoupper(substr($adminName, 0, 1))); ?>
                </div>

                <strong><?php echo h($adminName); ?></strong>
            </div>

        </header>

        <div class="page-content">

            <!-- PAGE TITLE -->
            <section class="welcome-heading">
                <div>
                    <span class="eyebrow">OVERVIEW</span>
                    <h1>Admin Dashboard</h1>

                    <p>
                        Welcome back, <?php echo h($adminName); ?>!
                        Here's what's happening with SwiftDrive Rentals.
                    </p>
                </div>

                <a href="admin_bookings.php" class="primary-button">
                    View Bookings <span>→</span>
                </a>
            </section>

            <!-- STATISTICS -->
            <section class="stats-grid">

                <article class="stat-card">
                    <div class="stat-top">
                        <span class="stat-icon navy">▤</span>
                        <span class="stat-caption">All reservations</span>
                    </div>

                    <p>Total Bookings</p>
                    <h2><?php echo number_format($stats['bookings']); ?></h2>

                    <a href="admin_bookings.php">Manage bookings →</a>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span class="stat-icon orange">🚗</span>
                        <span class="stat-caption">Fleet inventory</span>
                    </div>

                    <p>Total Vehicles</p>
                    <h2><?php echo number_format($stats['vehicles']); ?></h2>

                    <a href="admin_vehicles.php">Manage vehicles →</a>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span class="stat-icon blue">♙</span>
                        <span class="stat-caption">Registered users</span>
                    </div>

                    <p>Total Customers</p>
                    <h2><?php echo number_format($stats['customers']); ?></h2>

                    <a href="admin_customers.php">View customers →</a>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span class="stat-icon green">◷</span>
                        <span class="stat-caption">Needs attention</span>
                    </div>

                    <p>Pending Bookings</p>
                    <h2><?php echo number_format($stats['pending']); ?></h2>

                    <a href="admin_bookings.php">Review pending →</a>
                </article>

            </section>

            <!-- RECENT BOOKINGS -->
            <section class="panel recent-panel">

                <div class="panel-heading">
                    <div>
                        <h2>Recent Bookings</h2>
                        <p>The latest customer reservations.</p>
                    </div>

                    <a href="admin_bookings.php" class="outline-button">
                        View All
                    </a>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Vehicle</th>
                                <th>Pickup Date</th>
                                <th>Return Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if (count($recentBookings) > 0): ?>

                            <?php foreach ($recentBookings as $booking): ?>

                                <?php
                                $status = $booking['booking_status'] ?? 'Pending';
                                $statusClass = dashboardStatusClass($status);
                                ?>

                                <tr>
                                    <td>
                                        <strong class="booking-id">
                                            #<?php echo (int)$booking['booking_id']; ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <strong><?php echo h($booking['full_name']); ?></strong>
                                    </td>

                                    <td><?php echo h($booking['vehicle_name']); ?></td>

                                    <td><?php echo h($booking['pickup_date']); ?></td>

                                    <td><?php echo h($booking['return_date']); ?></td>

                                    <td class="amount">
                                        Rs. <?php echo number_format((float)$booking['total_amount'], 2); ?>
                                    </td>

                                    <td>
                                        <span class="status-badge status-<?php echo h($statusClass); ?>">
                                            <?php echo h($status); ?>
                                        </span>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="7" class="empty-state">
                                    <div class="empty-icon">▤</div>
                                    <strong>No bookings found</strong>
                                    <p>Customer bookings will appear here.</p>
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>
                    </table>
                </div>

                <div class="panel-footer">
                    Showing the latest <?php echo count($recentBookings); ?> bookings.
                </div>

            </section>

            <!-- QUICK ACTIONS -->
            <section class="quick-section">

                <div class="section-title">
                    <h2>Quick Actions</h2>
                    <p>Access your main administration tasks.</p>
                </div>

                <div class="quick-grid">

                    <a href="admin_vehicles.php" class="quick-card">
                        <span class="quick-icon orange">🚗</span>
                        <div>
                            <strong>Manage Vehicles</strong>
                            <p>Review your rental fleet.</p>
                        </div>
                        <span class="quick-arrow">→</span>
                    </a>

                    <a href="admin_bookings.php" class="quick-card">
                        <span class="quick-icon navy">▤</span>
                        <div>
                            <strong>Manage Bookings</strong>
                            <p>Review and update reservations.</p>
                        </div>
                        <span class="quick-arrow">→</span>
                    </a>

                    <a href="admin_customers.php" class="quick-card">
                        <span class="quick-icon blue">♙</span>
                        <div>
                            <strong>Manage Customers</strong>
                            <p>View registered customers.</p>
                        </div>
                        <span class="quick-arrow">→</span>
                    </a>

                    <a href="admin_messages.php" class="quick-card">
                        <span class="quick-icon green">✉</span>
                        <div>
                            <strong>Contact Messages</strong>
                            <p>Review customer inquiries.</p>
                        </div>
                        <span class="quick-arrow">→</span>
                    </a>

                </div>

            </section>

            <footer class="page-footer">
                © 2026 SwiftDrive Rentals. All rights reserved.
            </footer>

        </div>

    </main>
</div>

<script>
document.getElementById('menuToggle').addEventListener('click', function () {
    document.getElementById('sidebar').classList.toggle('sidebar-open');
});
</script>

</body>
</html>