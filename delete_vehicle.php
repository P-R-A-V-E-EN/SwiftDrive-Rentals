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

// Get vehicle details
$select_stmt = mysqli_prepare(
    $conn,
    "SELECT vehicle_id, vehicle_name, brand, model
     FROM vehicles
     WHERE vehicle_id = ?"
);

if (!$select_stmt) {
    die("Unable to prepare vehicle query.");
}

mysqli_stmt_bind_param($select_stmt, "i", $vehicle_id);
mysqli_stmt_execute($select_stmt);

$result = mysqli_stmt_get_result($select_stmt);
$vehicle = mysqli_fetch_assoc($result);

mysqli_stmt_close($select_stmt);

if (!$vehicle) {
    header("Location: admin_vehicles.php");
    exit();
}

// Delete vehicle after confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['confirm_delete'])) {

    $delete_stmt = mysqli_prepare(
        $conn,
        "DELETE FROM vehicles WHERE vehicle_id = ?"
    );

    if (!$delete_stmt) {
        $message = "Unable to prepare delete query.";
    } else {
        mysqli_stmt_bind_param($delete_stmt, "i", $vehicle_id);

        if (mysqli_stmt_execute($delete_stmt)) {
            mysqli_stmt_close($delete_stmt);

            header("Location: admin_vehicles.php");
            exit();
        } else {
            $message = "Vehicle could not be deleted. It may be linked to existing bookings.";
            mysqli_stmt_close($delete_stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Delete Vehicle | SwiftDrive Admin</title>

    <link rel="stylesheet" href="delete_vehicle.css">
</head>

<body>

<div class="delete-card">

    <div class="warning-icon">!</div>

    <h1>Delete Vehicle?</h1>

    <p class="description">
        Are you sure you want to delete this vehicle?
        Please check the details below before continuing.
    </p>

    <div class="vehicle-info">

        <strong>
            <?= htmlspecialchars(
                $vehicle['vehicle_name'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </strong>

        <span>
            <?= htmlspecialchars($vehicle['brand'], ENT_QUOTES, 'UTF-8') ?>
            ·
            <?= htmlspecialchars($vehicle['model'], ENT_QUOTES, 'UTF-8') ?>
            · ID: <?= (int)$vehicle['vehicle_id'] ?>
        </span>

    </div>

    <?php if ($message !== ""): ?>
        <div class="error-message">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST"
          action="delete_vehicle.php?id=<?= (int)$vehicle_id ?>">

        <div class="actions">

            <a href="admin_vehicles.php" class="button cancel-button">
                Cancel
            </a>

            <button
                type="submit"
                name="confirm_delete"
                class="button delete-button"
                onclick="return confirm('Are you sure you want to permanently delete this vehicle?');"
            >
                Yes, Delete Vehicle
            </button>

        </div>

    </form>

</div>

</body>
</html>