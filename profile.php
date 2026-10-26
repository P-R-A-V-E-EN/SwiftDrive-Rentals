 <?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'config/db.php';
$customerId = (int) $_SESSION['customer_id'];
$message = '';
$messageType = 'success';
// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    if ($fullName === '' || $email === '') {
        $message = 'Name and email are required.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        try {
            // Check whether another customer uses this email
            $checkSql = "
                SELECT customer_id
                FROM customers
                WHERE email = ? AND customer_id != ?
                LIMIT 1
            ";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("si", $email, $customerId);
            $checkStmt->execute();
            $existingEmail = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();
            if ($existingEmail) {
                $message = 'This email address is already in use.';
                $messageType = 'error';
            } else {
                $updateSql = "
                    UPDATE customers
                    SET full_name = ?,
                        email = ?,
                        phone = ?,
                        address = ?
                    WHERE customer_id = ?
                ";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param(
                    "ssssi",
                    $fullName,
                    $email,
                    $phone,
                    $address,
                    $customerId
                );
                $updateStmt->execute();
                $updateStmt->close();
                // Keep the displayed name in sync with the session
                $_SESSION['full_name'] = $fullName;
                $message = 'Your profile has been updated successfully.';
            }
        } catch (Throwable $e) {
            $message = 'Unable to update your profile. Please check your database structure.';
            $messageType = 'error';
        }
    }
}
// Load current customer details
$customer = [
    'full_name' => $_SESSION['full_name'] ?? '',
    'email' => '',
    'phone' => '',
    'address' => ''
];
try {
    $sql = "
        SELECT full_name, email, phone, address
        FROM customers
        WHERE customer_id = ?
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $dbCustomer = $result->fetch_assoc();
    if ($dbCustomer) {
        $customer = $dbCustomer;
    }
    $stmt->close();
} catch (Throwable $e) {
    if ($message === '') {
        $message = 'Unable to load your profile details.';
        $messageType = 'error';
    }
}
function profileEscape($value) {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | SwiftDrive Rentals</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .profile-page {
            max-width: 1400px;
            min-height: 450px;
            margin: auto;
            padding: 35px 5% 50px;
        }
        .profile-page h1 {
            color: #0F172A;
            margin-bottom: 10px;
        }
        .profile-description {
            color: #64748B;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 25px;
        }
        .profile-card {
            max-width: 750px;
            background: #FFFFFF;
            padding: 30px;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
        }
        .profile-heading {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 28px;
            padding-bottom: 22px;
            border-bottom: 1px solid #E2E8F0;
        }
        .profile-avatar {
            width: 58px;
            height: 58px;
            min-width: 58px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #FFF0E9;
            color: #FF6B35;
            font-size: 24px;
            font-weight: bold;
        }
        .profile-heading h2 {
            color: #0F172A;
            font-size: 19px;
            margin-bottom: 6px;
            overflow-wrap: anywhere;
        }
        .profile-heading p {
            color: #64748B;
            font-size: 13px;
        }
        .profile-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .profile-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .profile-field.full-width {
            grid-column: 1 / -1;
        }
        .profile-field label {
            color: #334155;
            font-size: 13px;
            font-weight: bold;
        }
        .profile-field input,
        .profile-field textarea {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #CBD5E1;
            border-radius: 7px;
            outline: none;
            background: #FFFFFF;
            color: #0F172A;
            font: inherit;
            font-size: 14px;
        }
        .profile-field input:focus,
        .profile-field textarea:focus {
            border-color: #FF6B35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.12);
        }
        .profile-field textarea {
            min-height: 95px;
            resize: vertical;
        }
        .profile-actions {
            grid-column: 1 / -1;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 15px;
            margin-top: 5px;
        }
        .profile-save {
            border: none;
            border-radius: 7px;
            padding: 13px 20px;
            background: #FF6B35;
            color: #FFFFFF;
            font-weight: bold;
            cursor: pointer;
        }
        .profile-save:hover {
            background: #E55A2B;
        }
        .profile-cancel {
            color: #64748B;
            font-size: 13px;
            font-weight: bold;
        }
        .profile-message {
            padding: 13px 15px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 13px;
            line-height: 1.6;
        }
        .profile-message.success {
            background: #DCFCE7;
            color: #166534;
        }
        .profile-message.error {
            background: #FEE2E2;
            color: #991B1B;
        }
        @media (max-width: 600px) {
            .profile-page {
                padding: 25px 15px 35px;
            }
            .profile-card {
                padding: 20px 15px;
            }
            .profile-form {
                grid-template-columns: 1fr;
            }
            .profile-field.full-width,
            .profile-actions {
                grid-column: 1;
            }
        }
    </style>
</head>
<body>
<header class="site-header">
    <div class="header-container">
        <a href="index.php" class="site-logo">
            Swift<span>Drive</span>
        </a>
        <nav class="main-nav">
            <a href="index.php">Home</a>
            <a href="vehicles.php">Vehicles</a>
            <a href="about.php">About</a>
            <a href="blog.php">Blog</a>
            <a href="contact.php">Contact</a>
        </nav>
        <div class="header-actions">
            <a href="dashboard.php" class="header-login">My Account</a>
            <a href="register.php" class="header-register">Register</a>
        </div>
        <button class="menu-toggle" id="menuToggle"
                type="button" aria-label="Toggle navigation">☰</button>
    </div>
    <nav class="mobile-nav" id="mobileNav">
        <a href="index.php">Home</a>
        <a href="vehicles.php">Vehicles</a>
        <a href="about.php">About</a>
        <a href="blog.php">Blog</a>
        <a href="contact.php">Contact</a>
        <a href="dashboard.php">My Account</a>
        <a href="register.php">Register</a>
    </nav>
</header>
<main class="profile-page">
    <h1>My Profile</h1>
    <p class="profile-description">
        View and update your personal information.
    </p>
    <section class="profile-card">
        <div class="profile-heading">
            <div class="profile-avatar">
                <?php
                echo strtoupper(
                    substr(
                        $customer['full_name'] ?: 'C',
                        0,
                        1
                    )
                );
                ?>
            </div>
            <div>
                <h2><?php echo profileEscape($customer['full_name']); ?></h2>
                <p>Customer Account</p>
            </div>
        </div>
        <?php if ($message !== ''): ?>
            <div class="profile-message <?php echo $messageType; ?>">
                <?php echo profileEscape($message); ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="profile.php" class="profile-form">
            <div class="profile-field">
                <label for="full_name">Full Name *</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    maxlength="100"
                    value="<?php echo profileEscape($customer['full_name']); ?>"
                    required
                >
            </div>
            <div class="profile-field">
                <label for="email">Email Address *</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="100"
                    value="<?php echo profileEscape($customer['email']); ?>"
                    required
                >
            </div>
            <div class="profile-field">
                <label for="phone">Phone Number</label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    maxlength="20"
                    value="<?php echo profileEscape($customer['phone']); ?>"
                >
            </div>
            <div class="profile-field full-width">
                <label for="address">Address</label>
                <textarea
                    id="address"
                    name="address"
                    maxlength="255"
                ><?php echo profileEscape($customer['address']); ?></textarea>
            </div>
            <div class="profile-actions">
                <button type="submit" class="profile-save">
                    Save Changes
                </button>
                <a href="dashboard.php" class="profile-cancel">
                    Back to Dashboard
                </a>
            </div>
        </form>
    </section>
</main>
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-brand">
            <a href="index.php" class="footer-logo">Swift<span>Drive</span></a>
            <p>Your journey, our priority.</p>
        </div>
        <div class="footer-links">
            <h3>Quick Links</h3>
            <a href="index.php">Home</a>
            <a href="vehicles.php">Vehicles</a>
            <a href="about.php">About</a>
            <a href="blog.php">Blog</a>
            <a href="contact.php">Contact</a>
        </div>
        <div class="footer-contact">
            <h3>Contact Us</h3>
            <p>Negombo, Sri Lanka</p>
            <p>Email: info@swiftdrive.lk</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> SwiftDrive Rentals. All rights reserved.</p>
    </div>
</footer>
<script>
const menuToggle = document.getElementById('menuToggle');
const mobileNav = document.getElementById('mobileNav');
menuToggle.addEventListener('click', function () {
    mobileNav.classList.toggle('show');
});
</script>
</body>
</html>