 <?php
session_start();
require_once "config/db.php";
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    if (
        $full_name === "" ||
        $email === "" ||
        $phone === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must contain at least 8 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check whether the email already exists
        $check = $conn->prepare(
            "SELECT customer_id FROM customers WHERE email = ?"
        );
        if (!$check) {
            $error = "Unable to process registration. Please try again.";
        } else {
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $error = "This email is already registered.";
            } else {
                // Securely hash the password
                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );
                // Insert the new customer
                $stmt = $conn->prepare(
                    "INSERT INTO customers
                    (full_name, email, phone, password, created_at)
                    VALUES (?, ?, ?, ?, NOW())"
                );
                if ($stmt) {
                    $stmt->bind_param(
                        "ssss",
                        $full_name,
                        $email,
                        $phone,
                        $hashed_password
                    );
                    if ($stmt->execute()) {
                        $stmt->close();
                        $check->close();
                        header("Location: login.php?registered=1");
                        exit();
                    } else {
                        $error = "Registration failed. Please try again.";
                    }
                    $stmt->close();
                } else {
                    $error = "Unable to create your account. Please try again.";
                }
            }
            $check->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | SwiftDrive Rentals</title>
    <link rel="stylesheet" href="register.css">
</head>
<body>
<!-- ================= NAVIGATION BAR ================= -->
<header class="site-header">
    <a href="index.php" class="brand">
        Swift<span>Drive</span>
    </a>
    <nav class="main-nav">
        <a href="index.php">Home</a>
        <a href="vehicles.php">Vehicles</a>
        <a href="about.php">About</a>
        <a href="blog.php">Blog</a>
        <a href="contact.php">Contact</a>
    </nav>
    <div class="nav-actions">
        <a href="login.php" class="nav-login">Login</a>
        <a href="register.php" class="nav-register active">Register</a>
    </div>
</header>
<!-- ================= REGISTER CONTENT ================= -->
<main class="register-layout">
    <!-- LEFT WELCOME PANEL -->
    <section class="welcome-panel">
        <div class="welcome-content">
            <p class="welcome-label">SWIFTDRIVE RENTALS</p>
            <h1>Join SwiftDrive Today!</h1>
            <p class="welcome-description">
                Create your account and enjoy a faster
                and easier booking experience.
            </p>
            <div class="benefits">
                <div class="benefit-item">
                    <span class="benefit-icon">▣</span>
                    <span>Secure Booking</span>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">◷</span>
                    <span>24/7 Support</span>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">★</span>
                    <span>Best Rates</span>
                </div>
            </div>
        </div>
        <div class="welcome-bottom">
            <strong>SwiftDrive Rentals</strong>
            <small>Your journey, our priority.</small>
        </div>
    </section>
    <!-- RIGHT REGISTER FORM -->
    <section class="register-panel">
        <div class="register-card">
            <div class="form-heading">
                <h2>Create Your Account</h2>
                <p>
                    Enter your details to get started.
                </p>
            </div>
            <?php if ($error !== ""): ?>
                <div class="alert error-alert">
                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </div>
            <?php endif; ?>
            <form action="register.php" method="POST">
                <!-- Full Name -->
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        placeholder="Enter your full name"
                        value="<?= htmlspecialchars(
                            $_POST["full_name"] ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        maxlength="100"
                        autocomplete="name"
                        required
                    >
                </div>
                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        value="<?= htmlspecialchars(
                            $_POST["email"] ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        maxlength="100"
                        autocomplete="email"
                        required
                    >
                </div>
                <!-- Phone Number -->
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        placeholder="Enter your phone number"
                        value="<?= htmlspecialchars(
                            $_POST["phone"] ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        maxlength="20"
                        autocomplete="tel"
                        required
                    >
                </div>
                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >
                        <button
                            type="button"
                            class="password-toggle"
                            data-target="password"
                            aria-label="Show password"
                        >Show</button>
                    </div>
                </div>
                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Confirm your password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >
                        <button
                            type="button"
                            class="password-toggle"
                            data-target="confirm_password"
                            aria-label="Show confirm password"
                        >Show</button>
                    </div>
                </div>
                <!-- Create Account -->
                <button type="submit" class="register-button">
                    Create Account
                </button>
            </form>
            <p class="login-prompt">
                Already have an account?
                <a href="login.php">Login</a>
            </p>
        </div>
    </section>
</main>
<!-- ================= FOOTER ================= -->
<footer class="site-footer">
    <div class="footer-brand">
        <a href="index.php" class="footer-logo">
            Swift<span>Drive</span>
        </a>
        <p>Your journey, our priority.</p>
    </div>
    <div class="footer-column">
        <h3>Quick Links</h3>
        <a href="index.php">Home</a>
        <a href="vehicles.php">Vehicles</a>
        <a href="about.php">About</a>
        <a href="blog.php">Blog</a>
        <a href="contact.php">Contact</a>
    </div>
    <div class="footer-column">
        <h3>Contact Us</h3>
        <p>Negombo, Sri Lanka</p>
        <p>
            Email:
            <a href="mailto:info@swiftdrive.lk">
                info@swiftdrive.lk
            </a>
        </p>
    </div>
</footer>
<div class="copyright">
    &copy; <?= date("Y") ?> SwiftDrive Rentals. All rights reserved.
</div>
<!-- Show / Hide Password -->
<script>
document.querySelectorAll(".password-toggle").forEach(function (button) {
    button.addEventListener("click", function () {
        const input = document.getElementById(
            button.getAttribute("data-target")
        );
        const showPassword = input.type === "password";
        input.type = showPassword ? "text" : "password";
        button.textContent = showPassword ? "Hide" : "Show";
        button.setAttribute(
            "aria-label",
            showPassword ? "Hide password" : "Show password"
        );
    });
});
</script>
</body>
</html>