 <?php
session_start();
require_once "config/db.php";

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Read search values
$keyword = trim($_GET['keyword'] ?? '');
$location = trim($_GET['location'] ?? '');
$pickup_date = $_GET['pickup_date'] ?? '';
$return_date = $_GET['return_date'] ?? '';

$category = $_GET['category'] ?? '';
$selected_categories = $_GET['categories'] ?? [];
$price = $_GET['price'] ?? 'all';
$transmission = $_GET['transmission'] ?? 'All';

if (!is_array($selected_categories)) {
    $selected_categories = [];
}

// Support category links coming from the Home Page
if ($category !== '' && ctype_digit((string)$category)) {
    $selected_categories[] = (string)$category;
}

$selected_categories = array_values(array_unique(array_filter(
    $selected_categories,
    function ($id) {
        return in_array((string)$id, ['1', '2', '3', '4'], true);
    }
)));

$allowed_prices = ['all', 'under10000', '10000to20000', 'over20000'];

if (!in_array($price, $allowed_prices, true)) {
    $price = 'all';
}

if (!in_array($transmission, ['All', 'Automatic', 'Manual'], true)) {
    $transmission = 'All';
}

// Validate dates
$date_error = '';

if (($pickup_date !== '' && $return_date === '') ||
    ($pickup_date === '' && $return_date !== '')) {
    $date_error = 'Please select both pickup and return dates.';
}

if ($pickup_date !== '' && $return_date !== '') {
    $pickup_obj = DateTime::createFromFormat('!Y-m-d', $pickup_date);
    $return_obj = DateTime::createFromFormat('!Y-m-d', $return_date);

    if (
        !$pickup_obj ||
        !$return_obj ||
        $pickup_obj->format('Y-m-d') !== $pickup_date ||
        $return_obj->format('Y-m-d') !== $return_date
    ) {
        $date_error = 'Please enter valid dates.';
    } elseif ($return_date <= $pickup_date) {
        $date_error = 'Return date must be after the pickup date.';
    } elseif ($pickup_date < date('Y-m-d')) {
        $date_error = 'Pickup date cannot be in the past.';
    }
}

// Build database query
$conditions = ["v.availability_status = 'Available'"];
$params = [];
$types = '';

if ($keyword !== '') {
    $conditions[] = "(v.vehicle_name LIKE ? OR v.brand LIKE ? OR v.model LIKE ?)";
    $search_term = '%' . $keyword . '%';

    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

if (!empty($selected_categories)) {
    $placeholders = implode(',', array_fill(0, count($selected_categories), '?'));
    $conditions[] = "v.category_id IN ($placeholders)";

    foreach ($selected_categories as $id) {
        $params[] = (int)$id;
        $types .= 'i';
    }
}

if ($transmission !== 'All') {
    $conditions[] = "v.transmission = ?";
    $params[] = $transmission;
    $types .= 's';
}

if ($price === 'under10000') {
    $conditions[] = "v.daily_rate < 10000";
} elseif ($price === '10000to20000') {
    $conditions[] = "v.daily_rate >= 10000 AND v.daily_rate <= 20000";
} elseif ($price === 'over20000') {
    $conditions[] = "v.daily_rate > 20000";
}

// Check overlapping bookings only when valid dates are provided
if ($date_error === '' && $pickup_date !== '' && $return_date !== '') {
    $conditions[] = "NOT EXISTS (
        SELECT 1
        FROM bookings b
        WHERE b.vehicle_id = v.vehicle_id
          AND b.booking_status IN ('Pending', 'Confirmed')
          AND b.pickup_date < ?
          AND b.return_date > ?
    )";

    $params[] = $return_date;
    $params[] = $pickup_date;
    $types .= 'ss';
}

$sql = "SELECT v.*, c.category_name
        FROM vehicles v
        INNER JOIN vehicle_categories c
            ON v.category_id = c.category_id
        WHERE " . implode(' AND ', $conditions) . "
        ORDER BY v.vehicle_id DESC";

$stmt = mysqli_prepare($conn, $sql);
$result = false;
$query_error = '';

if ($stmt) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $query_error = 'Unable to load vehicles. Please try again.';
    }
} else {
    $query_error = 'Unable to load vehicles. Please try again.';
}

$vehicle_count = $result ? mysqli_num_rows($result) : 0;

$categories = [
    '1' => 'Economy Cars',
    '2' => 'SUVs',
    '3' => 'Vans',
    '4' => 'Luxury Cars'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Our Vehicles | SwiftDrive Rentals</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="vehicles.css">
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
            <a href="vehicles.php" class="active">Vehicles</a>
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

<main class="vehicles-page">

    <!-- Page Heading -->
    <section class="vehicles-heading">
        <div class="container">
            <p class="eyebrow">YOUR JOURNEY STARTS HERE</p>
            <h1>Our Vehicles</h1>
            <p>Choose the right vehicle for your next journey.</p>

        </div>
    </section>

    <!-- Search Form -->
    <section class="container vehicles-search">

        <form action="vehicles.php" method="GET" class="vehicles-filter">

            <div class="form-group">
                <label for="keyword">Search Vehicle</label>
                <input
                    type="text"
                    id="keyword"
                    name="keyword"
                    placeholder="Vehicle name or brand"
                    value="<?php echo h($keyword); ?>">
            </div>

            <div class="form-group">
                <label for="search_category">Vehicle Type</label>

                <select name="category" id="search_category">
                    <option value="">All Types</option>

                    <?php foreach ($categories as $id => $name): ?>
                        <option
                            value="<?php echo h($id); ?>"
                            <?php echo ($category === (string)$id) ? 'selected' : ''; ?>>
                            <?php echo h($name); ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div class="form-group">
                <label for="pickup_date">Pickup Date</label>
                <input
                    type="date"
                    id="pickup_date"
                    name="pickup_date"
                    min="<?php echo date('Y-m-d'); ?>"
                    value="<?php echo h($pickup_date); ?>">
            </div>

            <div class="form-group">
                <label for="return_date">Return Date</label>
                <input
                    type="date"
                    id="return_date"
                    name="return_date"
                    min="<?php echo $pickup_date !== '' ? h($pickup_date) : date('Y-m-d'); ?>"
                    value="<?php echo h($return_date); ?>">
            </div>

            <button type="submit" class="btn btn-primary">
                Search
            </button>

        </form>

    </section>

    <!-- Vehicle Results -->
    <section class="container vehicle-results">

        <div class="results-heading">
            <div>
                <h2>Available Vehicles</h2>
                <p>Choose a vehicle to view its details.</p>
            </div>

            <span class="results-label">
                <?php echo $vehicle_count; ?> vehicle(s) found
            </span>
        </div>

        <?php if ($date_error !== ''): ?>

            <div class="notice-message">
                <?php echo h($date_error); ?>
            </div>

        <?php elseif ($query_error !== ''): ?>

            <div class="notice-message">
                <?php echo h($query_error); ?>
            </div>

        <?php else: ?>

            <div class="vehicles-layout">

                <!-- Left Filter Panel -->
                <aside class="filter-panel">

                    <h3>Filter by</h3>

                    <form action="vehicles.php" method="GET">

                        <input type="hidden" name="keyword"
                               value="<?php echo h($keyword); ?>">

                        <input type="hidden" name="pickup_date"
                               value="<?php echo h($pickup_date); ?>">

                        <input type="hidden" name="return_date"
                               value="<?php echo h($return_date); ?>">

                        <input type="hidden" name="location"
                               value="<?php echo h($location); ?>">

                        <h4>Vehicle Type</h4>

                        <?php foreach ($categories as $id => $name): ?>
                            <label class="filter-option">
                                <input
                                    type="checkbox"
                                    name="categories[]"
                                    value="<?php echo h($id); ?>"
                                    <?php echo in_array((string)$id, $selected_categories, true) ? 'checked' : ''; ?>>

                                <span><?php echo h($name); ?></span>
                            </label>
                        <?php endforeach; ?>

                        <hr>

                        <h4>Price Range (Rs./day)</h4>

                        <label class="filter-option">
                            <input type="radio" name="price" value="all"
                                <?php echo $price === 'all' ? 'checked' : ''; ?>>
                            <span>Any Price</span>
                        </label>

                        <label class="filter-option">
                            <input type="radio" name="price" value="under10000"
                                <?php echo $price === 'under10000' ? 'checked' : ''; ?>>
                            <span>Under Rs. 10,000</span>
                        </label>

                        <label class="filter-option">
                            <input type="radio" name="price" value="10000to20000"
                                <?php echo $price === '10000to20000' ? 'checked' : ''; ?>>
                            <span>Rs. 10,000 – 20,000</span>
                        </label>

                        <label class="filter-option">
                            <input type="radio" name="price" value="over20000"
                                <?php echo $price === 'over20000' ? 'checked' : ''; ?>>
                            <span>Over Rs. 20,000</span>
                        </label>

                        <hr>

                        <h4>Transmission</h4>

                        <label class="filter-option">
                            <input type="radio" name="transmission" value="All"
                                <?php echo $transmission === 'All' ? 'checked' : ''; ?>>
                            <span>All</span>
                        </label>

                        <label class="filter-option">
                            <input type="radio" name="transmission" value="Automatic"
                                <?php echo $transmission === 'Automatic' ? 'checked' : ''; ?>>
                            <span>Automatic</span>
                        </label>

                        <label class="filter-option">
                            <input type="radio" name="transmission" value="Manual"
                                <?php echo $transmission === 'Manual' ? 'checked' : ''; ?>>
                            <span>Manual</span>
                        </label>

                        <button type="submit"
                                class="btn btn-primary filter-button">
                            Apply Filters
                        </button>

                        <a href="vehicles.php" class="clear-filters">
                            Clear Filters
                        </a>

                    </form>

                </aside>

                <!-- Vehicle Cards -->
                <div class="vehicle-grid">

                    <?php if ($vehicle_count > 0): ?>

                        <?php while ($vehicle = mysqli_fetch_assoc($result)): ?>

                            <article class="vehicle-card">

                                <div class="vehicle-image">

                                    <img
                                        src="images/vehicles/<?php echo (int)$vehicle['vehicle_id']; ?>.png"
                                        alt="<?php echo h($vehicle['vehicle_name']); ?>"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">

                                    <div class="vehicle-placeholder">
                                        🚗
                                    </div>

                                    <span class="vehicle-category">
                                        <?php echo h($vehicle['category_name']); ?>
                                    </span>

                                </div>

                                <div class="vehicle-info">

                                    <h3><?php echo h($vehicle['vehicle_name']); ?></h3>

                                    <p class="vehicle-brand">
                                        <?php echo h($vehicle['brand']); ?>
                                        ·
                                        <?php echo h($vehicle['model']); ?>
                                    </p>

                                    <div class="vehicle-specs">
                                        <span>
                                            <?php echo (int)$vehicle['seats']; ?> Seats
                                        </span>

                                        <span>
                                            <?php echo h($vehicle['transmission']); ?>
                                        </span>
                                    </div>

                                    <div class="vehicle-card-bottom">

                                        <div class="vehicle-price">
                                            <strong>
                                                Rs. <?php echo number_format((float)$vehicle['daily_rate'], 2); ?>
                                            </strong>
                                            <span>per day</span>
                                        </div>

                                        <a
                                            class="btn btn-primary"
                                            href="vehicle_details.php?id=<?php echo (int)$vehicle['vehicle_id']; ?>">
                                            View Details
                                        </a>

                                    </div>

                                </div>

                            </article>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <div class="no-vehicles">
                            <h3>No vehicles found</h3>
                            <p>Try changing your filters or search dates.</p>

                            <a href="vehicles.php" class="btn btn-primary">
                                View All Vehicles
                            </a>
                        </div>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>

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

<script>
const pickupInput = document.getElementById('pickup_date');
const returnInput = document.getElementById('return_date');

pickupInput.addEventListener('change', function () {
    returnInput.min = this.value || '<?php echo date('Y-m-d'); ?>';

    if (returnInput.value && returnInput.value <= this.value) {
        returnInput.value = '';
    }
});

returnInput.addEventListener('change', function () {
    if (pickupInput.value && this.value <= pickupInput.value) {
        alert('Return date must be after the pickup date.');
        this.value = '';
    }
});
</script>

</body>
</html>

<?php
if ($stmt) {
    mysqli_stmt_close($stmt);
}
?>