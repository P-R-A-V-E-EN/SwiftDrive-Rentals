 <?php
session_start();
require_once "config/db.php";

function h($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Admin login protection
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$adminName = $_SESSION['admin_name'] ?? 'Administrator';

// Load vehicles and their categories
$sql = "SELECT v.*, c.category_name
        FROM vehicles v
        INNER JOIN vehicle_categories c
            ON v.category_id = c.category_id
        ORDER BY v.vehicle_id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    exit("Unable to load vehicles. Please check the database.");
}

$vehicles = [];
$categories = [];

while ($vehicle = mysqli_fetch_assoc($result)) {
    $vehicles[] = $vehicle;

    $category = $vehicle['category_name'] ?? '';

    if ($category !== '' && !in_array($category, $categories, true)) {
        $categories[] = $category;
    }
}

// Statistics
$totalVehicles = count($vehicles);
$availableVehicles = 0;
$unavailableVehicles = 0;

foreach ($vehicles as $vehicle) {
    if (strcasecmp(
        trim((string)($vehicle['availability_status'] ?? '')),
        'Available'
    ) === 0) {
        $availableVehicles++;
    } else {
        $unavailableVehicles++;
    }
}

function vehicleStatusClass($status) {
    return strcasecmp(trim((string)$status), 'Available') === 0
        ? 'available'
        : 'unavailable';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Vehicles | SwiftDrive Admin</title>

    <link rel="stylesheet" href="admin_vehicles.css">
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

            <a href="admin_dashboard.php">
                <span class="nav-icon">▦</span>
                Dashboard
            </a>

            <a href="admin_vehicles.php" class="active">
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
                    aria-label="Toggle navigation">
                ☰
            </button>

            <div class="breadcrumb">
                Admin Panel <span>/</span>
                <strong>Manage Vehicles</strong>
            </div>

            <div class="topbar-user">
                <span class="topbar-avatar">
                    <?php echo h(strtoupper(substr($adminName, 0, 1))); ?>
                </span>
                <span><?php echo h($adminName); ?></span>
            </div>

        </header>

        <section class="page-content">

            <!-- PAGE HEADING -->
            <div class="page-heading">

                <div>
                    <span class="eyebrow">FLEET MANAGEMENT</span>
                    <h1>Manage Vehicles</h1>
                    <p>
                        Add, update and manage the vehicles available
                        for rental.
                    </p>
                </div>

                <a href="add_vehicle.php" class="add-vehicle-btn">
                    <span>+</span> Add New Vehicle
                </a>

            </div>

            <!-- STATISTICS -->
            <section class="vehicle-stats">

                <div class="stat-card">
                    <div class="stat-icon navy">🚗</div>
                    <div>
                        <p>Total Vehicles</p>
                        <h2><?php echo $totalVehicles; ?></h2>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">✓</div>
                    <div>
                        <p>Available Vehicles</p>
                        <h2><?php echo $availableVehicles; ?></h2>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">◷</div>
                    <div>
                        <p>Unavailable Vehicles</p>
                        <h2><?php echo $unavailableVehicles; ?></h2>
                    </div>
                </div>

            </section>

            <!-- VEHICLES TABLE -->
            <section class="vehicles-panel">

                <div class="panel-heading">
                    <div>
                        <h2>Vehicle Inventory</h2>
                        <p>All vehicles registered in the system.</p>
                    </div>

                    <span class="total-pill">
                        <?php echo $totalVehicles; ?> Vehicles
                    </span>
                </div>

                <!-- SEARCH AND FILTER -->
                <div class="vehicle-controls">

                    <div class="search-box">
                        <span>⌕</span>
                        <input
                            type="search"
                            id="vehicleSearch"
                            placeholder="Search name, brand or model..."
                            aria-label="Search vehicles">
                    </div>

                    <select id="categoryFilter" aria-label="Filter by category">
                        <option value="all">All Categories</option>

                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo h(strtolower($category)); ?>">
                                <?php echo h($category); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select id="availabilityFilter" aria-label="Filter by availability">
                        <option value="all">All Availability</option>
                        <option value="available">Available</option>
                        <option value="unavailable">Unavailable</option>
                    </select>

                </div>

                <div class="table-responsive">

                    <table>
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Category</th>
                                <th>Transmission</th>
                                <th>Seats</th>
                                <th>Daily Rate</th>
                                <th>Availability</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody id="vehicleTable">

                        <?php foreach ($vehicles as $vehicle): ?>

                            <?php
                            $status = $vehicle['availability_status'] ?? 'Unavailable';
                            $statusClass = vehicleStatusClass($status);

                            $searchText = strtolower(
                                ($vehicle['vehicle_name'] ?? '') . ' ' .
                                ($vehicle['brand'] ?? '') . ' ' .
                                ($vehicle['model'] ?? '') . ' ' .
                                ($vehicle['category_name'] ?? '')
                            );
                            ?>

                            <tr
                                data-search="<?php echo h($searchText); ?>"
                                data-category="<?php echo h(strtolower($vehicle['category_name'] ?? '')); ?>"
                                data-availability="<?php echo h($statusClass); ?>">

                                <td>
                                    <div class="vehicle-info">

                                        <div class="vehicle-icon">🚘</div>

                                        <div>
                                            <strong>
                                                <?php echo h($vehicle['vehicle_name']); ?>
                                            </strong>

                                            <small>
                                                <?php echo h($vehicle['brand']); ?>
                                                <?php if (!empty($vehicle['model'])): ?>
                                                    · <?php echo h($vehicle['model']); ?>
                                                <?php endif; ?>
                                            </small>

                                            <small class="vehicle-id">
                                                ID: #<?php echo (int)$vehicle['vehicle_id']; ?>
                                            </small>
                                        </div>

                                    </div>
                                </td>

                                <td>
                                    <span class="category-badge">
                                        <?php echo h($vehicle['category_name']); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php echo h($vehicle['transmission']); ?>
                                </td>

                                <td>
                                    <?php echo (int)$vehicle['seats']; ?>
                                    seats
                                </td>

                                <td>
                                    <strong class="vehicle-price">
                                        Rs. <?php echo number_format((float)$vehicle['daily_rate'], 2); ?>
                                    </strong>
                                    <small class="per-day">per day</small>
                                </td>

                                <td>
                                    <span class="availability-badge status-<?php echo h($statusClass); ?>">
                                        <span class="status-dot"></span>
                                        <?php echo h($status); ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="action-buttons">

                                        <a
                                            href="edit_vehicle.php?id=<?php echo (int)$vehicle['vehicle_id']; ?>"
                                            class="edit-btn">
                                            Edit
                                        </a>

                                        <a
                                            href="delete_vehicle.php?id=<?php echo (int)$vehicle['vehicle_id']; ?>"
                                            class="delete-btn"
                                            onclick="return confirm('Are you sure you want to delete this vehicle?');">
                                            Delete
                                        </a>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>
                    </table>

                </div>

                <div id="noVehicles" class="empty-state" hidden>
                    <div class="empty-icon">🚗</div>
                    <h3>No vehicles found</h3>
                    <p>Try changing your search or category filter.</p>
                </div>

                <div class="table-footer">
                    Showing <strong id="visibleCount"><?php echo $totalVehicles; ?></strong>
                    of <?php echo $totalVehicles; ?> vehicles
                </div>

            </section>

            <footer class="page-footer">
                © 2026 SwiftDrive Rentals. All rights reserved.
            </footer>

        </section>

    </main>

</div>

<script>
const vehicleSearch = document.getElementById('vehicleSearch');
const categoryFilter = document.getElementById('categoryFilter');
const availabilityFilter = document.getElementById('availabilityFilter');

const vehicleRows = document.querySelectorAll('#vehicleTable tr');
const noVehicles = document.getElementById('noVehicles');
const visibleCount = document.getElementById('visibleCount');

function filterVehicles() {

    const query = vehicleSearch.value.trim().toLowerCase();
    const category = categoryFilter.value;
    const availability = availabilityFilter.value;

    let visible = 0;

    vehicleRows.forEach(function(row) {

        const text = row.dataset.search || '';
        const rowCategory = row.dataset.category || '';
        const rowAvailability = row.dataset.availability || '';

        const matchesSearch = text.includes(query);
        const matchesCategory =
            category === 'all' || rowCategory === category;
        const matchesAvailability =
            availability === 'all' || rowAvailability === availability;

        const show =
            matchesSearch && matchesCategory && matchesAvailability;

        row.hidden = !show;

        if (show) {
            visible++;
        }
    });

    visibleCount.textContent = visible;
    noVehicles.hidden = visible !== 0;
}

vehicleSearch.addEventListener('input', filterVehicles);
categoryFilter.addEventListener('change', filterVehicles);
availabilityFilter.addEventListener('change', filterVehicles);

document.getElementById('menuToggle').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('sidebar-open');
});
</script>

</body>
</html>