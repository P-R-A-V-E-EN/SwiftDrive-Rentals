 <?php

include "config/db.php";

$category = "";
$pickup_date = "";
$return_date = "";

if (isset($_GET['category'])) {
    $category = $_GET['category'];
}

if (isset($_GET['pickup_date'])) {
    $pickup_date = $_GET['pickup_date'];
}

if (isset($_GET['return_date'])) {
    $return_date = $_GET['return_date'];
}

$sql = "SELECT vehicles.*, vehicle_categories.category_name
        FROM vehicles
        INNER JOIN vehicle_categories
        ON vehicles.category_id = vehicle_categories.category_id
        WHERE vehicles.availability_status = 'Available'";

if ($category != "") {
    $sql .= " AND vehicles.category_id = '$category'";
}

if ($pickup_date != "" && $return_date != "") {

    $sql .= " AND vehicles.vehicle_id NOT IN (
                SELECT vehicle_id
                FROM bookings
                WHERE pickup_date <= '$return_date'
                AND return_date >= '$pickup_date'
                AND booking_status IN ('Pending', 'Confirmed')
              )";
}

$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html>

<head>

    <title>SwiftDrive - Search Vehicles</title>

</head>

<body>

    <h1>Search Vehicles</h1>

    <form method="GET" action="search.php">

        <label>Vehicle Category:</label>

        <select name="category">

            <option value="">All Vehicles</option>

            <option value="1"
                <?php if ($category == "1") echo "selected"; ?>>
                Economy
            </option>

            <option value="2"
                <?php if ($category == "2") echo "selected"; ?>>
                SUV
            </option>

            <option value="3"
                <?php if ($category == "3") echo "selected"; ?>>
                Van
            </option>

            <option value="4"
                <?php if ($category == "4") echo "selected"; ?>>
                Luxury
            </option>

        </select>

        <br><br>

        <label>Pick-up Date:</label>

        <input
            type="date"
            name="pickup_date"
            value="<?php echo htmlspecialchars($pickup_date); ?>"
            required
        >

        <br><br>

        <label>Return Date:</label>

        <input
            type="date"
            name="return_date"
            value="<?php echo htmlspecialchars($return_date); ?>"
            required
        >

        <br><br>

        <button type="submit">
            Search Vehicles
        </button>

    </form>

    <hr>

    <h2>Available Vehicles</h2>

    <?php

    if (mysqli_num_rows($result) > 0) {

        while ($vehicle = mysqli_fetch_assoc($result)) {

    ?>

            <div>

                <h3>
                    <?php echo htmlspecialchars($vehicle['vehicle_name']); ?>
                </h3>

                <p>
                    Category:
                    <?php echo htmlspecialchars($vehicle['category_name']); ?>
                </p>

                <p>
                    Brand:
                    <?php echo htmlspecialchars($vehicle['brand']); ?>
                </p>

                <p>
                    Model:
                    <?php echo htmlspecialchars($vehicle['model']); ?>
                </p>

                <p>
                    Transmission:
                    <?php echo htmlspecialchars($vehicle['transmission']); ?>
                </p>

                <p>
                    Seats:
                    <?php echo htmlspecialchars($vehicle['seats']); ?>
                </p>

                <p>
                    Daily Rate:
                    Rs.
                    <?php echo number_format($vehicle['daily_rate'], 2); ?>
                </p>

                <p>
                    Status:
                    <?php echo htmlspecialchars($vehicle['availability_status']); ?>
                </p>

                <!-- IMPORTANT: Vehicle ID is passed here -->

                <a href="vehicle_details.php?id=<?php echo $vehicle['vehicle_id']; ?>">

                    <button type="button">
                        View Details
                    </button>

                </a>

            </div>

            <hr>

    <?php

        }

    } else {

        echo "<p>No vehicles are available for the selected dates.</p>";

    }

    ?>

</body>

</html>