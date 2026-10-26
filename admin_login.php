 <?php
session_start();
require_once "config/db.php";

$message = "";
$email = "";

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $message = "Please enter a valid email and password.";
    } else {

        $sql = "SELECT admin_id, full_name, email, password, role
                FROM admins
                WHERE email = ?
                LIMIT 1";

        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $admin = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            if ($admin && password_verify($password, $admin['password'])) {

                session_regenerate_id(true);

                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_role'] = $admin['role'];

                header("Location: admin_dashboard.php");
                exit;

            } else {
                $message = "Incorrect email or password.";
            }

        } else {
            $message = "Unable to process login. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | SwiftDrive Rentals</title>
    <link rel="stylesheet" href="admin_login.css">
</head>
<body>

<div class="login-page">

    <!-- LEFT: LOGIN FORM -->
    <section class="login-section">

        <a href="index.php" class="brand">
            <span class="brand-icon">S</span>
            <span>Swift<span class="brand-orange">Drive</span></span>
        </a>

        <div class="login-content">

            <div class="admin-label">
                <span class="label-dot"></span>
                ADMINISTRATOR PORTAL
            </div>

            <h1>Welcome Back!</h1>

            <p class="login-description">
                Sign in to manage bookings, vehicles and customers.
            </p>

            <?php if ($message !== ''): ?>
                <div class="error-message" role="alert">
                    <span>!</span>
                    <?php echo h($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="admin_login.php" class="login-form">

                <div class="form-group">
                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your admin email"
                        value="<?php echo h($email); ?>"
                        autocomplete="username"
                        required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>

                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required>

                        <button type="button"
                                class="show-password"
                                id="togglePassword"
                                aria-label="Show password">
                            Show
                        </button>
                    </div>
                </div>

                <button type="submit" name="login" class="login-button">
                    Sign In to Admin Panel
                    <span>→</span>
                </button>

            </form>

            <div class="login-divider">
                <span></span>
                <p>SECURE ADMIN ACCESS</p>
                <span></span>
            </div>

            <p class="security-text">
                🔒 Your administrator account is protected.
            </p>

        </div>

        <div class="login-footer">
            © 2026 SwiftDrive Rentals. All rights reserved.
        </div>

    </section>

    <!-- RIGHT: WELCOME PANEL -->
    <aside class="welcome-section">

        <div class="welcome-overlay"></div>

        <div class="welcome-content">

            <div class="car-icon">
                <svg viewBox="0 0 64 64" fill="none"
                     xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 35L17 21C18 18 21 16 24 16H40C43 16 46 18 47 21L52 35"
                          stroke="currentColor" stroke-width="3"
                          stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M8 36C8 33 10 31 13 31H51C54 31 56 33 56 36V47H8V36Z"
                          stroke="currentColor" stroke-width="3"
                          stroke-linejoin="round"/>
                    <path d="M17 31L21 21H43L47 31"
                          stroke="currentColor" stroke-width="3"
                          stroke-linejoin="round"/>
                    <circle cx="18" cy="46" r="5"
                            fill="#FF6B35" stroke="currentColor"
                            stroke-width="2"/>
                    <circle cx="46" cy="46" r="5"
                            fill="#FF6B35" stroke="currentColor"
                            stroke-width="2"/>
                    <path d="M8 38H15M49 38H56"
                          stroke="currentColor" stroke-width="3"
                          stroke-linecap="round"/>
                </svg>
            </div>

            <p class="welcome-tag">SWIFTDRIVE RENTALS</p>

            <h2>Manage Your Fleet.<br>
                <span>Drive Your Success.</span>
            </h2>

            <p class="welcome-description">
                Your central workspace for managing vehicle rentals,
                customer reservations and your business operations.
            </p>

            <div class="feature-list">
                <div>
                    <span class="feature-check">✓</span>
                    Manage vehicle listings
                </div>
                <div>
                    <span class="feature-check">✓</span>
                    Review customer bookings
                </div>
                <div>
                    <span class="feature-check">✓</span>
                    Monitor rental activities
                </div>
            </div>

            <div class="welcome-bottom">
                <span class="welcome-line"></span>
                <span>YOUR JOURNEY, OUR PRIORITY.</span>
            </div>

        </div>

    </aside>

</div>

<script>
const passwordInput = document.getElementById('password');
const togglePassword = document.getElementById('togglePassword');

togglePassword.addEventListener('click', function () {
    const isPassword = passwordInput.type === 'password';

    passwordInput.type = isPassword ? 'text' : 'password';
    togglePassword.textContent = isPassword ? 'Hide' : 'Show';
    togglePassword.setAttribute(
        'aria-label',
        isPassword ? 'Hide password' : 'Show password'
    );
});
</script>

</body>
</html>