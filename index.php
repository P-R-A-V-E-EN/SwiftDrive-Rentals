 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SwiftDrive Rentals | Home</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

    <!-- Header -->
    <header class="header">
        <div class="container navbar">

            <a href="index.php" class="logo">
                Swift<span>Drive</span>
            </a>

            <nav>
                <a href="index.php" class="active">Home</a>
                <a href="vehicles.php">Vehicles</a>
                <a href="about.php">About</a>
                <a href="blog.php">Blog</a>
                <a href="contact.php">Contact</a>
            </nav>

            <div class="nav-buttons">
                <a href="login.php" class="btn btn-outline">Login</a>
                <a href="register.php" class="btn btn-primary">Register</a>
            </div>

        </div>
    </header>


    <!-- Hero Section -->
    <section class="hero">
        <div class="container hero-content">

            <div class="hero-text">
           

                <h1>Drive Your Journey<br>
                    With Ease
                </h1>

                <p>
                    Discover the perfect vehicle for your next journey.
                    Comfortable rides, affordable rates and easy booking.
                </p>

                <a href="vehicles.php" class="btn btn-primary">
                    Book Now
                </a>
            </div>

        </div>
    </section>


    <!-- Vehicle Search -->
    <section class="search-section">
        <div class="container search-box">

            <h2>Find Your Perfect Car</h2>

            <form action="vehicles.php" method="GET" class="search-form">

                <div class="form-group">
                    <label for="location">Pickup Location</label>
                    <input type="text" id="location"
                           name="location"
                           placeholder="Enter location"
                           required>
                </div>

                <div class="form-group">
                    <label for="pickup_date">Pickup Date</label>
                    <input type="date" id="pickup_date"
                           name="pickup_date" required>
                </div>

                <div class="form-group">
                    <label for="return_date">Return Date</label>
                    <input type="date" id="return_date"
                           name="return_date" required>
                </div>

                <div class="form-group">
                    <label for="category">Vehicle Type</label>
                    <select id="category" name="category">
                        <option value="">All Types</option>
                        <option value="1">Economy Cars</option>
                        <option value="2">SUVs</option>
                        <option value="3">Vans</option>
                        <option value="4">Luxury Cars</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary search-btn">
                    Search
                </button>

            </form>

        </div>
    </section>


    <!-- Vehicle Categories -->
    <section class="categories section-padding">
        <div class="container">

            <div class="section-heading">
                <h2>Vehicle Categories</h2>
                <p>Choose the right vehicle for your journey.</p>
            </div>

            <div class="category-grid">

                <a href="vehicles.php?category=1" class="category-card">
                    <div class="category-icon">🚗</div>
                    <h3>Economy Cars</h3>
                    <p>Affordable and fuel-efficient</p>
                    <span>View Cars →</span>
                </a>

                <a href="vehicles.php?category=2" class="category-card">
                    <div class="category-icon">🚙</div>
                    <h3>SUVs</h3>
                    <p>Comfort for every journey</p>
                    <span>View SUVs →</span>
                </a>

                <a href="vehicles.php?category=3" class="category-card">
                    <div class="category-icon">🚐</div>
                    <h3>Vans</h3>
                    <p>Space for groups and families</p>
                    <span>View Vans →</span>
                </a>

                <a href="vehicles.php?category=4" class="category-card">
                    <div class="category-icon">🏎️</div>
                    <h3>Luxury Cars</h3>
                    <p>Premium travel experience</p>
                    <span>View Cars →</span>
                </a>

            </div>

        </div>
    </section>


    <!-- Why Choose SwiftDrive -->
    <section class="why-section section-padding">
        <div class="container">

            <div class="section-heading">
                <h2>Why Choose SwiftDrive?</h2>
                <p>Making every journey simple and comfortable.</p>
            </div>

            <div class="features-grid">

                <div class="feature-card">
                    <div class="feature-icon">✓</div>
                    <h3>Reliable Vehicles</h3>
                    <p>Well-maintained vehicles for your safety.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">₨</div>
                    <h3>Affordable Prices</h3>
                    <p>Rental rates to suit your budget.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">◷</div>
                    <h3>24/7 Support</h3>
                    <p>Support when you need assistance.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">✓</div>
                    <h3>Easy Booking</h3>
                    <p>Book your preferred vehicle with ease.</p>
                </div>

            </div>

        </div>
    </section>


    <!-- Customer Reviews -->
    <section class="reviews section-padding">
        <div class="container">

            <div class="section-heading">
                <h2>Customer Reviews</h2>
                <p>Experiences shared by our customers.</p>
            </div>

            <div class="review-grid">

                <div class="review-card">
                    <div class="stars">★★★★★</div>
                    <p>"Reliable service and a comfortable vehicle."</p>
                    <h4>Customer Review</h4>
                </div>

                <div class="review-card">
                    <div class="stars">★★★★★</div>
                    <p>"The booking process was simple and convenient."</p>
                    <h4>Customer Review</h4>
                </div>

                <div class="review-card">
                    <div class="stars">★★★★★</div>
                    <p>"Good vehicle selection and helpful service."</p>
                    <h4>Customer Review</h4>
                </div>

            </div>

        </div>
    </section>


    <!-- Footer -->
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