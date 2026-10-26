 <?php
session_start();
require_once "config/db.php";
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    if ($email === "" || $password === "") {
        $error = "Please enter your email and password.";
    } else {
        $sql = "SELECT customer_id, full_name, email, password
                FROM customers
                WHERE email = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $customer = $result->fetch_assoc();
                if (password_verify($password, $customer["password"])) {
                    session_regenerate_id(true);
                    $_SESSION["customer_id"] = $customer["customer_id"];
                    $_SESSION["full_name"] = $customer["full_name"];
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "Incorrect email or password.";
                }
            } else {
                $error = "Incorrect email or password.";
            }
            $stmt->close();
        } else {
            $error = "Unable to process your login. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SwiftDrive Rentals</title>
    <link rel="stylesheet" href="login.css">
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
        <a href="login.php" class="nav-login active">Login</a>
        <a href="register.php" class="nav-register">Register</a>
    </div>
</header>
<!-- ================= LOGIN CONTENT ================= -->
<main class="login-layout">
    <!-- LEFT WELCOME PANEL -->
    <section class="welcome-panel">
        <div class="welcome-content">
            <p class="welcome-label">SWIFTDRIVE RENTALS</p>
            <h1>Welcome Back!</h1>
            <p class="welcome-description">
                Log in to your account to manage bookings,
                view your history and more.
            </p>
            <div class="benefits">
                <div class="benefit-item">
                    <span class="benefit-icon">▣</span>
                    <span>Manage Bookings</span>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">◷</span>
                    <span>View History</span>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">♙</span>
                    <span>Get Support</span>
                </div>
            </div>
        </div>
        <div class="welcome-bottom">
            <strong>SwiftDrive Rentals</strong>
            <small>Your journey, our priority.</small>
        </div>
    </section>
    <!-- RIGHT LOGIN FORM -->
    <section class="login-panel">
        <div class="login-card">
            <div class="form-heading">
                <h2>Login to Your Account</h2>
                <p>
                    Welcome back! Enter your details to continue.
                </p>
            </div>
            <?php if (
                isset($_GET["registered"]) &&
                $_GET["registered"] === "1"
            ): ?>
                <div class="alert success-alert">
                    Account created successfully! Please log in.
                </div>
            <?php endif; ?>
            <?php if ($error !== ""): ?>
                <div class="alert error-alert">
                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </div>
            <?php endif; ?>
            <form action="login.php" method="POST">
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
                        autocomplete="email"
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
                            autocomplete="current-password"
                            required
                        >
                        <button
                            type="button"
                            class="password-toggle"
                            id="togglePassword"
                            aria-label="Show password"
                        >Show</button>
                    </div>
                </div>
                <!-- Remember Me and Forgot Password -->
                <div class="form-options">
                    <label class="remember-option">
                        <input
                            type="checkbox"
                            name="remember_me"
                            value="1"
                        >
                        <span>Remember me</span>
                    </label>
                    <a
                        href="forgot_password.php"
                        class="forgot-link"
                    >
                        Forgot Password?
                    </a>
                </div>
                <!-- Login Button -->
                <button type="submit" class="login-button">
                    Login
                </button>
            </form>
            <p class="register-prompt">
                Don't have an account?
                <a href="register.php">Register</a>
            </p>
        </div>
    </section>
</main>
<!-- ================= FOOTER ================= -->
<footer class="site-footer">
    <!-- Brand -->
    <div class="footer-brand">
        <a href="index.php" class="footer-logo">
            Swift<span>Drive</span>
        </a>
        <p>Your journey, our priority.</p>
    </div>
    <!-- Quick Links -->
    <div class="footer-column">
        <h3>Quick Links</h3>
        <a href="index.php">Home</a>
        <a href="vehicles.php">Vehicles</a>
        <a href="about.php">About</a>
        <a href="blog.php">Blog</a>
        <a href="contact.php">Contact</a>
    </div>
    <!-- Contact Us -->
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
<!-- Copyright -->
<div class="copyright">
    &copy; <?= date("Y") ?> SwiftDrive Rentals. All rights reserved.
</div>
<!-- Show / Hide Password -->
<script>
const passwordInput = document.getElementById("password");
const togglePassword = document.getElementById("togglePassword");
togglePassword.addEventListener("click", function () {
    const showPassword = passwordInput.type === "password";
    passwordInput.type = showPassword ? "text" : "password";
    togglePassword.textContent = showPassword ? "Hide" : "Show";
    togglePassword.setAttribute(
        "aria-label",
        showPassword ? "Hide password" : "Show password"
    );
});
</script>
</body>
</html>