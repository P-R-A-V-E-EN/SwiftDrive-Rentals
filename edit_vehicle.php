 <?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$vehicle_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$vehicle_id || $vehicle_id < 1) {
    header("Location: admin_vehicles.php");
    exit();
}

$message = "";
$categories = [];

// Load categories from the database
$category_sql = "SELECT category_id, category_name
                 FROM vehicle_categories
                 ORDER BY category_name";
$category_result = mysqli_query($conn, $category_sql);

if ($category_result) {
    while ($row = mysqli_fetch_assoc($category_result)) {
        $categories[] = $row;
    }
}

// Load the selected vehicle
$select_stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM vehicles WHERE vehicle_id = ?"
);

if (!$select_stmt) {
    die("Unable to prepare vehicle query.");
}

mysqli_stmt_bind_param($select_stmt, "i", $vehicle_id);
mysqli_stmt_execute($select_stmt);

$vehicle_result = mysqli_stmt_get_result($select_stmt);
$vehicle = mysqli_fetch_assoc($vehicle_result);

mysqli_stmt_close($select_stmt);

if (!$vehicle) {
    die("Vehicle not found. Please return to the vehicles page.");
}

// Update vehicle details
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_vehicle'])) {

    $vehicle_name = trim($_POST['vehicle_name'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $transmission = $_POST['transmission'] ?? '';
    $seats = filter_input(INPUT_POST, 'seats', FILTER_VALIDATE_INT);
    $daily_rate = filter_input(INPUT_POST, 'daily_rate', FILTER_VALIDATE_FLOAT);
    $availability_status = $_POST['availability_status'] ?? '';

    $valid_category = false;

    if ($category_id) {
        foreach ($categories as $category) {
            if ((int)$category['category_id'] === (int)$category_id) {
                $valid_category = true;
                break;
            }
        }
    }

    if (
        $vehicle_name === '' ||
        $brand === '' ||
        $model === '' ||
        !$valid_category ||
        !in_array($transmission, ['Automatic', 'Manual'], true) ||
        $seats === false || $seats === null || $seats < 1 ||
        $daily_rate === false || $daily_rate === null || $daily_rate < 0 ||
        !in_array($availability_status, ['Available', 'Unavailable'], true)
    ) {
        $message = "Please enter valid details in all fields.";
    } else {

        $update_sql = "UPDATE vehicles SET
                       vehicle_name = ?,
                       brand = ?,
                       model = ?,
                       category_id = ?,
                       transmission = ?,
                       seats = ?,
                       daily_rate = ?,
                       availability_status = ?
                       WHERE vehicle_id = ?";

        $update_stmt = mysqli_prepare($conn, $update_sql);

        if ($update_stmt) {

            mysqli_stmt_bind_param(
                $update_stmt,
                "sssisidsi",
                $vehicle_name,
                $brand,
                $model,
                $category_id,
                $transmission,
                $seats,
                $daily_rate,
                $availability_status,
                $vehicle_id
            );

            if (mysqli_stmt_execute($update_stmt)) {
                mysqli_stmt_close($update_stmt);

                header("Location: admin_vehicles.php");
                exit();
            } else {
                $message = "Vehicle could not be updated. Please try again.";
            }

            mysqli_stmt_close($update_stmt);

        } else {
            $message = "Unable to prepare the update query.";
        }
    }

    // Keep submitted values in the form if validation fails
    $vehicle['vehicle_name'] = $vehicle_name;
    $vehicle['brand'] = $brand;
    $vehicle['model'] = $model;
    $vehicle['category_id'] = $category_id ?: '';
    $vehicle['transmission'] = $transmission;
    $vehicle['seats'] = $seats !== false && $seats !== null ? $seats : '';
    $vehicle['daily_rate'] = $daily_rate !== false && $daily_rate !== null ? $daily_rate : '';
    $vehicle['availability_status'] = $availability_status;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Vehicle | SwiftDrive Admin</title>

    <link rel="stylesheet" href="edit_vehicle.css">
</head>

<body>

<div class="admin-layout">

    <aside class="sidebar">

        <a href="admin_dashboard.php" class="brand">
            <span class="brand-icon">S</span>

            <span>
                Swift<span class="orange">Drive</span>
                <small>ADMIN PANEL</small>
            </span>
        </a>

        <div class="menu-label">MAIN MENU</div>

        <nav class="sidebar-nav">

            <a href="admin_dashboard.php">
                <span>▦</span> Dashboard
            </a>

            <a href="admin_vehicles.php" class="active">
                <span>🚘</span> Vehicles
            </a>

            <a href="admin_bookings.php">
                <span>▤</span> Bookings
            </a>

            <a href="admin_customers.php">
                <span>♙</span> Customers
            </a>

            <a href="admin_messages.php">
                <span>✉</span> Messages
            </a>

        </nav>

        <div class="sidebar-bottom">

            <div class="admin-mini">
                <div class="avatar">A</div>

                <div>
                    <strong>Administrator</strong>
                    <small>System Admin</small>
                </div>
            </div>

            <a href="admin_logout.php" class="logout-link">
                ↪ Log Out
            </a>

        </div>

    </aside>

    <main class="main-content">

        <header class="topbar">

            <div>
                <p class="breadcrumb">
                    Dashboard / Vehicles / Edit Vehicle
                </p>

                <h1>Edit Vehicle</h1>

                <p class="page-subtitle">
                    Update the details of your selected vehicle.
                </p>
            </div>

            <a href="admin_vehicles.php" class="back-button">
                ← Back to Vehicles
            </a>

        </header>

        <section class="form-card">

            <div class="card-heading">

                <div class="heading-icon">✎</div>

                <div>
                    <h2>Vehicle Information</h2>
                    <p>Make changes to the vehicle details below.</p>
                </div>

            </div>

            <?php if ($message !== ""): ?>
                <div class="error-message">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if (count($categories) === 0): ?>

                <div class="error-message">
                    No vehicle categories found. Please check your
                    vehicle_categories database table.
                </div>

            <?php else: ?>

            <form method="POST"
                  action="edit_vehicle.php?id=<?= (int)$vehicle_id ?>">

                <h3 class="form-section-title">Basic Details</h3>

                <div class="form-grid">

                    <div class="form-group">
                        <label for="vehicle_name">Vehicle Name *</label>

                        <input
                            type="text"
                            id="vehicle_name"
                            name="vehicle_name"
                            maxlength="100"
                            value="<?= htmlspecialchars($vehicle['vehicle_name'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="brand">Brand *</label>

                        <input
                            type="text"
                            id="brand"
                            name="brand"
                            maxlength="100"
                            value="<?= htmlspecialchars($vehicle['brand'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="model">Model *</label>

                        <input
                            type="text"
                            id="model"
                            name="model"
                            maxlength="100"
                            value="<?= htmlspecialchars($vehicle['model'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="category_id">Vehicle Category *</label>

                        <select id="category_id" name="category_id" required>

                            <option value="">Select Category</option>

                            <?php foreach ($categories as $category): ?>

                                <option
                                    value="<?= (int)$category['category_id'] ?>"
                                    <?= (string)$vehicle['category_id'] ===
                                        (string)$category['category_id'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars(
                                        $category['category_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>

                </div>

                <h3 class="form-section-title">
                    Specifications &amp; Pricing
                </h3>

                <div class="form-grid">

                    <div class="form-group">
                        <label for="transmission">Transmission *</label>

                        <select id="transmission" name="transmission" required>

                            <option value="">Select Transmission</option>

                            <option
                                value="Automatic"
                                <?= $vehicle['transmission'] === 'Automatic' ? 'selected' : '' ?>
                            >
                                Automatic
                            </option>

                            <option
                                value="Manual"
                                <?= $vehicle['transmission'] === 'Manual' ? 'selected' : '' ?>
                            >
                                Manual
                            </option>

                        </select>
                    </div>

                    <div class="form-group">
                        <label for="seats">Number of Seats *</label>

                        <input
                            type="number"
                            id="seats"
                            name="seats"
                            min="1"
                            max="100"
                            value="<?= htmlspecialchars((string)$vehicle['seats'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="daily_rate">Daily Rate (LKR) *</label>

                        <div class="rate-input">
                            <span>Rs.</span>

                            <input
                                type="number"
                                id="daily_rate"
                                name="daily_rate"
                                min="0"
                                step="0.01"
                                value="<?= htmlspecialchars((string)$vehicle['daily_rate'], ENT_QUOTES, 'UTF-8') ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="availability_status">
                            Availability Status *
                        </label>

                        <select
                            id="availability_status"
                            name="availability_status"
                            required
                        >

                            <option
                                value="Available"
                                <?= $vehicle['availability_status'] === 'Available' ? 'selected' : '' ?>
                            >
                                Available
                            </option>

                            <option
                                value="Unavailable"
                                <?= $vehicle['availability_status'] === 'Unavailable' ? 'selected' : '' ?>
                            >
                                Unavailable
                            </option>

                        </select>
                    </div>

                </div>

                <div class="form-actions">

                    <a href="admin_vehicles.php" class="cancel-button">
                        Cancel
                    </a>

                    <button
                        type="submit"
                        name="update_vehicle"
                        class="submit-button"
                    >
                        Save Changes
                    </button>

                </div>

            </form>

            <?php endif; ?>

        </section>

        <footer class="footer-note">
            &copy; <?= date('Y') ?> SwiftDrive Rentals. All rights reserved.
        </footer>

    </main>

</div>

</body>
</html>