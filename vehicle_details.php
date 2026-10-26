 <?php
session_start();
require_once "config/db.php";

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$vehicle_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$vehicle_id || $vehicle_id < 1) {
    header("Location: vehicles.php");
    exit;
}

$sql = "SELECT v.*, c.category_name
        FROM vehicles v
        INNER JOIN vehicle_categories c
            ON v.category_id = c.category_id
        WHERE v.vehicle_id = ?";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Unable to load vehicle details.");
}

mysqli_stmt_bind_param($stmt, "i", $vehicle_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$vehicle = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$vehicle) {
    http_response_code(404);
    die("Vehicle not found. <a href='vehicles.php'>Back to Vehicles</a>");
}

$vehicle_name = $vehicle['vehicle_name'];
$vehicle_image = "images/vehicles/" . (int)$vehicle['vehicle_id'] . ".png";

$description = "The " . $vehicle_name .
    " is a comfortable " . $vehicle['category_name'] .
    " suitable for everyday travel, family trips and longer journeys. " .
    "It provides " . (int)$vehicle['seats'] .
    " seats and " . $vehicle['transmission'] .
    " transmission for a convenient driving experience.";

$is_available = ($vehicle['availability_status'] === 'Available');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo h($vehicle_name); ?> | SwiftDrive Rentals</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="vehicle_details.css">
</head>
<body>

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

<main class="details-page">
    <div class="container">

        <a href="vehicles.php" class="back-link">
            &larr; Back to Vehicles
        </a>

        <section class="details-card">

            <!-- LEFT: Vehicle image gallery -->
            <div class="vehicle-gallery">

                <div class="details-image">
                    <img
                        id="mainVehicleImage"
                        src="<?php echo h($vehicle_image); ?>"
                        alt="<?php echo h($vehicle_name); ?>"
                        onerror="this.style.display='none'; document.getElementById('image-fallback').style.display='grid';"
                    >

                    <div id="image-fallback" class="image-fallback">
                        &#128663;
                    </div>
                </div>

                <div class="vehicle-thumbnails">

                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <button
                            type="button"
                            class="thumbnail <?php echo $i === 1 ? 'selected' : ''; ?>"
                            onclick="changeVehicleImage(this)"
                            aria-label="View vehicle image <?php echo $i; ?>"
                        >
                            <img
                                src="<?php echo h($vehicle_image); ?>"
                                alt="<?php echo h($vehicle_name); ?>"
                            >
                        </button>
                    <?php endfor; ?>

                </div>
            </div>

            <!-- RIGHT: Vehicle information -->
            <div class="details-info">

                <span class="category-badge">
                    <?php echo h($vehicle['category_name']); ?>
                </span>

                <h1><?php echo h($vehicle_name); ?></h1>

                <p class="brand-model">
                    <?php echo h($vehicle['brand']); ?>
                    &middot;
                    <?php echo h($vehicle['model']); ?>
                </p>

                <div class="vehicle-rating">
                    <span class="rating-star">&#9733;</span>
                    <span>Vehicle information and specifications</span>
                </div>

                <div class="vehicle-spec-list">

                    <div class="vehicle-spec-row">
                        <span class="spec-icon">&#128101;</span>
                        <span><?php echo (int)$vehicle['seats']; ?> Seats</span>
                    </div>

                    <div class="vehicle-spec-row">
                        <span class="spec-icon">&#9881;</span>
                        <span><?php echo h($vehicle['transmission']); ?> Transmission</span>
                    </div>

                    <div class="vehicle-spec-row">
                        <span class="spec-icon">&#9981;</span>
                        <span>Fuel type: Please confirm with staff</span>
                    </div>

                    <div class="vehicle-spec-row">
                        <span class="spec-icon">&#10003;</span>
                        <span>
                            <?php echo $is_available
                                ? 'Currently Available'
                                : 'Currently Unavailable'; ?>
                        </span>
                    </div>

                </div>

                <div class="rental-conditions">
                    <h2>Rental Conditions</h2>

                    <ul>
                        <li>Minimum rental period: 1 day</li>
                        <li>Valid driving licence required</li>
                        <li>Vehicle must be returned on time</li>
                        <li>Security deposit may apply</li>
                    </ul>

                    <div class="price-section">
                        <span>Rental Price</span>

                        <div class="price-line">
                            <strong>
                                Rs. <?php echo number_format(
                                    (float)$vehicle['daily_rate'], 2
                                ); ?>
                            </strong>
                            <span>/ day</span>
                        </div>
                    </div>

                    <?php if ($is_available): ?>
                        <a
                            href="booking.php?vehicle_id=<?php echo (int)$vehicle['vehicle_id']; ?>"
                            class="btn btn-primary book-button"
                        >
                            Book Now
                        </a>
                    <?php else: ?>
                        <button class="btn btn-primary book-button" disabled>
                            Currently Unavailable
                        </button>
                    <?php endif; ?>

                </div>
            </div>
        </section>

        <!-- Description and Features -->
        <section class="vehicle-extra">

            <div class="detail-tabs">
                <button
                    type="button"
                    class="detail-tab active"
                    onclick="showDetailTab('description', this)"
                >
                    Description
                </button>

                <button
                    type="button"
                    class="detail-tab"
                    onclick="showDetailTab('features', this)"
                >
                    Features
                </button>
            </div>

            <div id="description" class="tab-content">
                <p><?php echo h($description); ?></p>
            </div>

            <div id="features" class="tab-content" hidden>
                <ul class="features-list">
                    <li><?php echo (int)$vehicle['seats']; ?> passenger seats</li>
                    <li><?php echo h($vehicle['transmission']); ?> transmission</li>
                    <li>Comfortable interior</li>
                    <li>Suitable for everyday travel</li>
                    <li>Rental support from SwiftDrive Rentals</li>
                </ul>
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

<script>
function changeVehicleImage(button) {
    const thumbnailImage = button.querySelector('img');
    const mainImage = document.getElementById('mainVehicleImage');
    const fallback = document.getElementById('image-fallback');

    mainImage.src = thumbnailImage.src;
    mainImage.style.display = 'block';
    fallback.style.display = 'none';

    document.querySelectorAll('.thumbnail').forEach(function (item) {
        item.classList.remove('selected');
    });

    button.classList.add('selected');
}

function showDetailTab(tabId, button) {
    document.querySelectorAll('.tab-content').forEach(function (tab) {
        tab.hidden = true;
    });

    document.getElementById(tabId).hidden = false;

    document.querySelectorAll('.detail-tab').forEach(function (tabButton) {
        tabButton.classList.remove('active');
    });

    button.classList.add('active');
}
</script>

</body>
</html>