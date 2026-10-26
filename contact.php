 <?php
session_start();
require_once "config/db.php";

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$name = '';
$email = '';
$message = '';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {

        $error = 'Please fill in all required fields.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } elseif (
        strlen($name) > 100 ||
        strlen($email) > 100 ||
        strlen($message) > 2000
    ) {

        $error = 'Please check the length of your information.';

    } else {

        // Values for the existing contact inquiries system.
        $phone = '';
        $subject = 'General Inquiry';

        /*
         * This query assumes these columns exist in
         * contact_inquiries:
         * name, email, phone, subject, message,
         * inquiry_status, created_at.
         */
        $sql = "INSERT INTO contact_inquiries
                (name, email, phone, subject, message,
                 inquiry_status, created_at)
                VALUES (?, ?, ?, ?, ?, 'New', NOW())";

        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "sssss",
                $name,
                $email,
                $phone,
                $subject,
                $message
            );

            if (mysqli_stmt_execute($stmt)) {

                $success = 'Your message has been sent successfully!';

                $name = '';
                $email = '';
                $message = '';

            } else {

                $error = 'Unable to send your message. Please check the contact inquiries table.';
            }

            mysqli_stmt_close($stmt);

        } else {

            $error = 'Unable to process your message. Please check the database table structure.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contact Us | SwiftDrive Rentals</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="contact.css">
</head>
<body>

<header class="header">
    <div class="container navbar">

        <a href="index.php" class="logo">
            Swift<span>Drive</span>
        </a>

        <nav>
            <a href="index.php">Home</a>
            <a href="vehicles.php">Vehicles</a>
            <a href="about.php">About</a>
            <a href="blog.php">Blog</a>
            <a href="contact.php" class="active">Contact</a>
        </nav>

        <div class="nav-buttons">
            <a href="login.php" class="btn btn-outline">Login</a>
            <a href="register.php" class="btn btn-primary">Register</a>
        </div>

    </div>
</header>

<main>

    <!-- Hero Section -->
    <section class="contact-hero">
        <div class="container">
            <h1>Get in Touch</h1>
            <p>We're here to help. Contact us anytime.</p>
        </div>
    </section>

    <!-- Contact Information and Form -->
    <section class="contact-main">
        <div class="container contact-layout">

            <!-- Contact Information -->
            <div class="contact-information">

                <h2>Contact Information</h2>

                <div class="information-item">
                    <div class="information-icon">&#128205;</div>
                    <div>
                        <h3>Address</h3>
                        <p>Negombo, Sri Lanka</p>
                    </div>
                </div>

                <div class="information-item">
                    <div class="information-icon">&#128222;</div>
                    <div>
                        <h3>Phone</h3>
                        <p>Contact us for rental enquiries</p>
                    </div>
                </div>

                <div class="information-item">
                    <div class="information-icon">&#9993;</div>
                    <div>
                        <h3>Email</h3>
                        <p>info@swiftdrive.lk</p>
                    </div>
                </div>

                <div class="information-item">
                    <div class="information-icon">&#128336;</div>
                    <div>
                        <h3>Business Hours</h3>
                        <p>Monday - Saturday</p>
                        <p>8:00 AM - 6:00 PM</p>
                    </div>
                </div>

            </div>

            <!-- Contact Form -->
            <div class="contact-form-area">

                <h2>Send Us a Message</h2>

                <?php if ($success !== ''): ?>
                    <div class="form-message success-message">
                        <?php echo h($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <div class="form-message error-message">
                        <?php echo h($error); ?>
                    </div>
                <?php endif; ?>

                <form action="contact.php" method="POST">

                    <div class="contact-field">
                        <label for="name">Name</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            maxlength="100"
                            autocomplete="name"
                            placeholder="Enter your name"
                            value="<?php echo h($name); ?>"
                            required
                        >
                    </div>

                    <div class="contact-field">
                        <label for="email">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            maxlength="100"
                            autocomplete="email"
                            placeholder="Enter your email"
                            value="<?php echo h($email); ?>"
                            required
                        >
                    </div>

                    <div class="contact-field">
                        <label for="message">Message</label>
                        <textarea
                            id="message"
                            name="message"
                            rows="4"
                            maxlength="2000"
                            placeholder="Enter your message"
                            required
                        ><?php echo h($message); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary send-button">
                        Send Message
                    </button>

                </form>

            </div>
        </div>
    </section>

    <!-- Location Map -->
    <section class="contact-map">
        <iframe
            title="Map showing Negombo, Sri Lanka"
            src="https://maps.google.com/maps?q=Negombo%2C%20Sri%20Lanka&t=&z=13&ie=UTF8&iwloc=&output=embed"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            allowfullscreen>
        </iframe>
    </section>

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

</body>
</html>