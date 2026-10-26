 <?php
session_start();
require_once "config/db.php";

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$sql = "SELECT b.*, c.full_name, c.email, v.vehicle_name
        FROM bookings b
        INNER JOIN customers c ON b.customer_id = c.customer_id
        INNER JOIN vehicles v ON b.vehicle_id = v.vehicle_id
        ORDER BY b.booking_id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    exit("Unable to load bookings. Please check your database.");
}

$bookings = [];
$counts = [
    'total' => 0,
    'pending' => 0,
    'confirmed' => 0,
    'completed' => 0,
    'cancelled' => 0
];

while ($booking = mysqli_fetch_assoc($result)) {
    $bookings[] = $booking;
    $counts['total']++;

    $status = strtolower(trim($booking['booking_status'] ?? ''));

    if ($status === 'pending') {
        $counts['pending']++;
    } elseif (in_array($status, ['confirmed', 'approved'], true)) {
        $counts['confirmed']++;
    } elseif ($status === 'completed') {
        $counts['completed']++;
    } elseif (in_array($status, ['cancelled', 'canceled', 'rejected'], true)) {
        $counts['cancelled']++;
    }
}

function statusClass($status) {
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

function paymentLabel($method) {
    switch ($method) {
        case 'card': return 'Card';
        case 'bank_transfer': return 'Bank Transfer';
        case 'cash': return 'Cash';
        default: return 'Not selected';
    }
}

$adminName = $_SESSION['admin_name'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings | SwiftDrive Admin</title>
    <link rel="stylesheet" href="admin_bookings.css">
</head>
<body>

<div class="admin-layout">

    <!-- LEFT SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <a href="admin_dashboard.php" class="sidebar-logo">
            Swift<span>Drive</span>
            <small>ADMIN</small>
        </a>

        <p class="sidebar-label">MAIN MENU</p>

        <nav class="sidebar-nav">
            <a href="admin_dashboard.php">
                <span class="nav-icon">▦</span> Dashboard
            </a>
            <a href="admin_vehicles.php">
                <span class="nav-icon">🚗</span> Manage Vehicles
            </a>
            <a href="admin_bookings.php" class="active">
                <span class="nav-icon">▤</span> Manage Bookings
            </a>
            <a href="admin_customers.php">
                <span class="nav-icon">♙</span> Manage Customers
            </a>
            <a href="admin_messages.php">
                <span class="nav-icon">✉</span> Messages
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="admin-profile">
                <div class="admin-avatar">
                    <?php echo h(strtoupper(substr($adminName, 0, 1))); ?>
                </div>
                <div>
                    <strong><?php echo h($adminName); ?></strong>
                    <span>Administrator</span>
                </div>
            </div>

            <a href="admin_logout.php" class="sidebar-logout">
                ↪ Logout
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <header class="topbar">
            <button type="button" class="menu-toggle" id="menuToggle"
                    aria-label="Toggle navigation">☰</button>

            <div class="breadcrumb">
                Admin Panel <span>/</span> <strong>Manage Bookings</strong>
            </div>

            <div class="topbar-user">
                <span class="topbar-avatar">
                    <?php echo h(strtoupper(substr($adminName, 0, 1))); ?>
                </span>
                <span><?php echo h($adminName); ?></span>
            </div>
        </header>

        <section class="page-content">

            <div class="page-heading">
                <div>
                    <span class="eyebrow">RESERVATION MANAGEMENT</span>
                    <h1>Manage Bookings</h1>
                    <p>View customer reservations and manage their status.</p>
                </div>
                <div class="total-pill">
                    <?php echo $counts['total']; ?> Total Bookings
                </div>
            </div>

            <!-- SUMMARY CARDS -->
            <section class="stats-grid">
                <div class="stat-card">
                    <span class="stat-icon navy">▤</span>
                    <div>
                        <p>Total Bookings</p>
                        <h2><?php echo $counts['total']; ?></h2>
                    </div>
                </div>

                <div class="stat-card">
                    <span class="stat-icon orange">◷</span>
                    <div>
                        <p>Pending</p>
                        <h2><?php echo $counts['pending']; ?></h2>
                    </div>
                </div>

                <div class="stat-card">
                    <span class="stat-icon green">✓</span>
                    <div>
                        <p>Confirmed</p>
                        <h2><?php echo $counts['confirmed']; ?></h2>
                    </div>
                </div>

                <div class="stat-card">
                    <span class="stat-icon blue">✔</span>
                    <div>
                        <p>Completed</p>
                        <h2><?php echo $counts['completed']; ?></h2>
                    </div>
                </div>
            </section>

            <!-- BOOKINGS TABLE -->
            <section class="table-panel">

                <div class="table-heading">
                    <div>
                        <h2>All Bookings</h2>
                        <p>Review and update customer bookings below.</p>
                    </div>
                </div>

                <div class="table-controls">
                    <div class="search-field">
                        <span>⌕</span>
                        <input type="search" id="bookingSearch"
                               placeholder="Search customer, vehicle or booking ID...">
                    </div>

                    <select id="statusFilter" aria-label="Filter by status">
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Booking</th>
                                <th>Customer</th>
                                <th>Vehicle</th>
                                <th>Pickup Date</th>
                                <th>Return Date</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody id="bookingTable">
                        <?php foreach ($bookings as $booking): ?>
                            <?php
                            $status = $booking['booking_status'] ?? 'Pending';
                            $class = statusClass($status);
                            ?>
                            <tr
                                data-status="<?php echo h($class); ?>"
                                data-search="<?php echo h(strtolower(
                                    '#' . $booking['booking_id'] . ' ' .
                                    $booking['full_name'] . ' ' .
                                    $booking['email'] . ' ' .
                                    $booking['vehicle_name'] . ' ' .
                                    ($booking['pickup_location'] ?? '')
                                )); ?>">

                                <td>
                                    <strong class="booking-number">
                                        #<?php echo (int)$booking['booking_id']; ?>
                                    </strong>
                                </td>

                                <td>
                                    <strong><?php echo h($booking['full_name']); ?></strong>
                                    <small class="sub-text">
                                        <?php echo h($booking['email']); ?>
                                    </small>
                                </td>

                                <td>
                                    <strong><?php echo h($booking['vehicle_name']); ?></strong>
                                    <small class="sub-text">
                                        <?php echo h($booking['pickup_location'] ?: 'Location not set'); ?>
                                    </small>
                                </td>

                                <td><?php echo h($booking['pickup_date']); ?></td>

                                <td><?php echo h($booking['return_date']); ?></td>

                                <td>
                                    <strong class="amount">
                                        Rs. <?php echo number_format((float)$booking['total_amount'], 2); ?>
                                    </strong>
                                    <small class="sub-text">
                                        <?php echo h(paymentLabel($booking['payment_method'] ?? '')); ?>
                                    </small>
                                </td>

                                <td>
                                    <span class="status-badge status-<?php echo h($class); ?>">
                                        <?php echo h(ucwords(str_replace('_', ' ', $status))); ?>
                                    </span>
                                </td>

                                <td>
                                    <form class="update-form" method="POST"
                                          action="update_booking_status.php?id=<?php echo (int)$booking['booking_id']; ?>">

                                        <select name="booking_status"
                                                aria-label="New status for booking <?php echo (int)$booking['booking_id']; ?>"
                                                required>
                                            <?php foreach (['Pending', 'Confirmed', 'Completed', 'Cancelled'] as $option): ?>
                                                <option value="<?php echo h($option); ?>"
                                                    <?php echo strcasecmp($status, $option) === 0 ? 'selected' : ''; ?>>
                                                    <?php echo h($option); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <button type="submit" name="update_status">
                                            Save
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <p class="no-results" id="noResults" hidden>
                    No bookings match your search.
                </p>

                <div class="table-footer">
                    Showing <strong id="visibleCount"><?php echo count($bookings); ?></strong>
                    of <?php echo count($bookings); ?> bookings
                </div>

            </section>

            <footer class="content-footer">
                © 2026 SwiftDrive Rentals. Admin Panel.
            </footer>

        </section>
    </main>
</div>

<script>
const searchInput = document.getElementById('bookingSearch');
const statusFilter = document.getElementById('statusFilter');
const rows = document.querySelectorAll('#bookingTable tr');
const noResults = document.getElementById('noResults');
const visibleCount = document.getElementById('visibleCount');

function filterBookings() {
    const query = searchInput.value.trim().toLowerCase();
    const selected = statusFilter.value;
    let count = 0;

    rows.forEach(function(row) {
        const text = row.dataset.search || '';
        const status = row.dataset.status || '';

        const matchesSearch = text.includes(query);
        const matchesStatus = selected === 'all' || status === selected;
        const visible = matchesSearch && matchesStatus;

        row.hidden = !visible;

        if (visible) count++;
    });

    visibleCount.textContent = count;
    noResults.hidden = count !== 0;
}

searchInput.addEventListener('input', filterBookings);
statusFilter.addEventListener('change', filterBookings);

document.getElementById('menuToggle').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('sidebar-open');
});
</script>

</body>
</html>