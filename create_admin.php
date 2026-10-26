 <?php

include "config/db.php";

$full_name = "SwiftDrive Admin";
$email = "admin@swiftdrive.com";
$password = password_hash("Admin@123", PASSWORD_DEFAULT);
$role = "Admin";

$sql = "INSERT INTO admins
        (full_name, email, password, role)
        VALUES
        ('$full_name', '$email', '$password', '$role')";

if (mysqli_query($conn, $sql)) {
    echo "Admin account created successfully!";
} else {
    echo "Error: " . mysqli_error($conn);
}

?>