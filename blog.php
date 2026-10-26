 <?php
session_start();

$articles = [
    [
        'image' => 'images/blog/1.jpg',
        'date' => 'May 10, 2026',
        'title' => 'Top 5 Places to Visit in Sri Lanka',
        'description' => 'Discover the best travel destinations for your next adventure.'
    ],
    [
        'image' => 'images/blog/2.jpg',
        'date' => 'May 08, 2026',
        'title' => 'Tips for a Safe Road Trip',
        'description' => 'Learn how to prepare for a safe and comfortable journey.'
    ],
    [
        'image' => 'images/blog/3.jpg',
        'date' => 'May 05, 2026',
        'title' => 'Why Rent a Car in Sri Lanka?',
        'description' => 'Explore the benefits of renting a car for your next trip.'
    ],
    [
        'image' => 'images/blog/4.jpg',
        'date' => 'April 28, 2026',
        'title' => 'Best Cars for Family Trips',
        'description' => 'Find suitable vehicles for your next family adventure.'
    ],
    [
        'image' => 'images/blog/5.jpg',
        'date' => 'April 25, 2026',
        'title' => 'Travel on a Budget',
        'description' => 'Learn how to save money on car rentals and travel expenses.'
    ],
    [
        'image' => 'images/blog/6.jpg',
        'date' => 'April 20, 2026',
        'title' => 'Top 10 Scenic Routes',
        'description' => 'Explore beautiful routes for your next road trip.'
    ]
];

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="blog.css">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Our Blog | SwiftDrive Rentals</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="blog.css">
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
            <a href="blog.php" class="active">Blog</a>
            <a href="contact.php">Contact</a>
        </nav>

        <div class="nav-buttons">
            <a href="login.php" class="btn btn-outline">Login</a>
            <a href="register.php" class="btn btn-primary">Register</a>
        </div>

    </div>
</header>

<main>

    <!-- Blog Hero -->
    <section class="blog-hero">
        <div class="container blog-hero-content">
            <h1>Our Blog</h1>
            <p>Travel tips, guides and news from SwiftDrive.</p>
        </div>
    </section>

    <!-- Blog Cards -->
    <section class="blog-section">
        <div class="container">

            <div class="blog-grid">

                <?php foreach ($articles as $index => $article): ?>

                    <article class="blog-card">

                        <div class="blog-image">

                            <img
                                src="<?php echo h($article['image']); ?>"
                                alt="<?php echo h($article['title']); ?>"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';"
                            >

                            <div class="blog-image-fallback">
                                <span>&#128663;</span>
                            </div>

                        </div>

                        <div class="blog-content">

                            <h2>
                                <?php echo h($article['title']); ?>
                            </h2>

                            <p class="blog-date">
                                <?php echo h($article['date']); ?>
                            </p>

                            <p class="blog-description">
                                <?php echo h($article['description']); ?>
                            </p>

                            <a
                                class="read-more"
                                href="blog_details.php?id=<?php echo $index + 1; ?>"
                            >
                                Read More &rarr;
                            </a>

                        </div>

                    </article>

                <?php endforeach; ?>

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