 <?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | SwiftDrive Rentals</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="about.css">
</head>
<body>

<header class="header">
    <div class="container navbar">
        <a href="index.php" class="logo">Swift<span>Drive</span></a>

        <nav>
            <a href="index.php">Home</a>
            <a href="vehicles.php">Vehicles</a>
            <a href="about.php" class="active">About</a>
            <a href="blog.php">Blog</a>
            <a href="contact.php">Contact</a>
        </nav>

        <div class="nav-buttons">
            <a href="login.php" class="btn btn-outline">Login</a>
            <a href="register.php" class="btn btn-primary">Register</a>
        </div>
    </div>
</header>

<main>

    <!-- Hero -->
    <section class="about-hero">
        <div class="container">
            <h1>About SwiftDrive Rentals</h1>
            <p>Your trusted partner in car rental services in Sri Lanka.</p>
        </div>
    </section>

    <!-- Mission and Values -->
    <section class="about-main">
        <div class="container about-layout">

            <div class="about-text">

                <article class="about-item">
                    <div class="about-icon">&#9733;</div>
                    <div>
                        <h2>Our Mission</h2>
                        <p>
                            To provide safe, reliable and affordable car
                            rental services that make every journey
                            more enjoyable.
                        </p>
                    </div>
                </article>

                <article class="about-item">
                    <div class="about-icon">&#10003;</div>
                    <div>
                        <h2>Our Values</h2>
                        <ul class="values-list">
                            <li>Customer Satisfaction</li>
                            <li>Safety and Reliability</li>
                            <li>Transparency</li>
                            <li>Innovation</li>
                        </ul>
                    </div>
                </article>

            </div>

            <div class="about-image">
                <img
                    src="images/about-car.jpg"
                    alt="Cars ready for a journey"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';"
                >
                <div class="about-image-fallback">&#128663;</div>
            </div>

        </div>
    </section>

    <!-- Services -->
    <section class="about-services">
        <div class="container">

            <h2 class="services-title">Our Services</h2>

            <div class="services-grid">

                <article class="service-card">
                    <div class="service-icon">&#128663;</div>
                    <h3>Wide Range<br>of Vehicles</h3>
                </article>

                <article class="service-card">
                    <div class="service-icon">&#128222;</div>
                    <h3>24/7<br>Support</h3>
                </article>

                <article class="service-card">
                    <div class="service-icon">&#128197;</div>
                    <h3>Easy Online<br>Booking</h3>
                </article>

                <article class="service-card">
                    <div class="service-icon">&#128176;</div>
                    <h3>Affordable<br>Rates</h3>
                </article>

            </div>
        </div>
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