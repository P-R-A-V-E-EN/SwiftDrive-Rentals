 <?php

session_start();
include "config/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Booking ID not found.");
}

$booking_id = $_GET['id'];

if (isset($_POST['update_status'])) {

    $booking_status = $_POST['booking_status'];

    $sql = "UPDATE bookings
            SET booking_status = '$booking_status'
            WHERE booking_id = '$booking_id'";

    if (mysqli_query($conn, $sql)) {

        header("Location: admin_bookings.php");
        exit();

    } else {

        echo "Status update failed: "
           . mysqli_error($conn);

    }
}

?>