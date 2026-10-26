  <?php
session_start();

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$articles = [
    1 => [
        'image' => 'images/blog/1.jpg',
        'category' => 'Travel Tips',
        'date' => 'May 10, 2026',
        'title' => 'Top 5 Places to Visit in Sri Lanka',
        'description' => 'Discover the best travel destinations for your next adventure.',
        'content' => [
            'Sri Lanka is a beautiful island with beaches, mountains, historic cities and scenic countryside. A rental car can help you explore these destinations at your own pace.',
            'Visit the cultural attractions of Kandy, enjoy the cool climate of Nuwara Eliya, explore the beaches of the southern coast, discover the history of Galle Fort, or enjoy the natural beauty around Ella.',
            'Before travelling, plan your route, check road conditions and choose a vehicle that suits your journey. Remember to take breaks and follow local traffic rules.'
        ]
    ],
    2 => [
        'image' => 'images/blog/2.jpg',
        'category' => 'Travel Tips',
        'date' => 'May 08, 2026',
        'title' => 'Tips for a Safe Road Trip',
        'description' => 'Learn how to prepare for a safe and comfortable journey.',
        'content' => [
            'A good road trip starts with proper planning. Choose your route, check the weather and allow enough time to reach your destination without rushing.',
            'Before leaving, check the tyres, lights, fuel and other important vehicle features. Keep your driving licence and necessary documents with you.',
            'Always wear your seat belt, follow speed limits, avoid using your phone while driving and take breaks when tired.'
        ]
    ],
    3 => [
        'image' => 'images/blog/3.jpg',
        'category' => 'Car Rental',
        'date' => 'May 05, 2026',
        'title' => 'Why Rent a Car in Sri Lanka?',
        'description' => 'Explore the benefits of renting a car for your next trip.',
        'content' => [
            'Renting a car can make travelling more flexible. You can plan your own schedule and visit places that may not be convenient to reach using public transport.',
            'Choose a vehicle based on your passenger count, luggage, travel distance and budget. Check the rental price and conditions before confirming your booking.',
            'SwiftDrive Rentals helps customers explore available vehicles and select a suitable option for their journey.'
        ]
    ],
    4 => [
        'image' => 'images/blog/4.jpg',
        'category' => 'Family Travel',
        'date' => 'April 28, 2026',
        'title' => 'Best Cars for Family Trips',
        'description' => 'Find suitable vehicles for your next family adventure.',
        'content' => [
            'When planning a family trip, comfort and space are important. Consider the number of passengers and the amount of luggage you need to carry.',
            'An economy car may suit a small family, while an SUV or van can provide additional space for larger groups and longer journeys.',
            'Compare seating capacity, transmission, rental rates and vehicle availability before choosing your family travel vehicle.'
        ]
    ],
    5 => [
        'image' => 'images/blog/5.jpg',
        'category' => 'Budget Travel',
        'date' => 'April 25, 2026',
        'title' => 'Travel on a Budget',
        'description' => 'Learn how to save money on car rentals and travel expenses.',
        'content' => [
            'Planning ahead can help you manage travel expenses. Set a budget for transport, accommodation, food and activities before starting your journey.',
            'Compare vehicle rental prices and check what is included in the rental agreement. Remember to consider fuel costs, deposits and any additional charges.',
            'Choose a vehicle that matches your actual needs rather than paying for features or space you do not require.'
        ]
    ],
    6 => [
        'image' => 'images/blog/6.jpg',
        'category' => 'Destinations',
        'date' => 'April 20, 2026',
        'title' => 'Top 10 Scenic Routes',
        'description' => 'Explore beautiful routes for your next road trip.',
        'content' => [
            'Sri Lanka offers many beautiful routes through green hills, coastal roads, tea plantations and charming towns.',
            'You could plan a journey through the hill country, travel along parts of the southern coast or explore scenic roads around Ella and Nuwara Eliya.',
            'Check your route before departure, plan suitable stops and allow enough time to enjoy the journey safely.'
        ]
    ]
];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || !isset($articles[$id])) {
    http_response_code(404);
    $article = null;
} else {
    $article = $articles[$id];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $article ? h($article['title']) : 'Article Not Found'; ?>
        | SwiftDrive Rentals
    </title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="blog_details.css">
</head>
<body>

<header class="header">
    <div class="container navbar">
        <a href="index.php" class="logo">Swift<span>Drive</span></a>

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

<?php if ($article): ?>

    <section class="article-hero">
        <div class="container">
            <p class="article-eyebrow">SWIFTDRIVE TRAVEL BLOG</p>
            <h1><?php echo h($article['title']); ?></h1>
            <p>
                <?php echo h($article['category']); ?>
                &nbsp; | &nbsp;
                <?php echo h($article['date']); ?>
            </p>
        </div>
    </section>

    <section class="article-section">
        <article class="container article-container">

            <a href="blog.php" class="back-link">
                &larr; Back to Our Blog
            </a>

            <div class="article-image">
                <img
                    src="<?php echo h($article['image']); ?>"
                    alt="<?php echo h($article['title']); ?>"
                >
            </div>

            <div class="article-content">
                <span class="article-category">
                    <?php echo h($article['category']); ?>
                </span>

                <h2><?php echo h($article['title']); ?></h2>

                <p class="article-intro">
                    <?php echo h($article['description']); ?>
                </p>

                <?php foreach ($article['content'] as $paragraph): ?>
                    <p><?php echo h($paragraph); ?></p>
                <?php endforeach; ?>

                <div class="article-cta">
                    <h3>Ready for Your Next Journey?</h3>
                    <p>Explore our vehicles and find the right option for your trip.</p>
                    <a href="vehicles.php" class="btn btn-primary">
                        Explore Vehicles
                    </a>
                </div>
            </div>

            <a href="blog.php" class="back-link bottom-back-link">
                &larr; Back to Our Blog
            </a>

        </article>
    </section>

<?php else: ?>

    <section class="article-section">
        <div class="container article-not-found">
            <h1>Article Not Found</h1>
            <p>Sorry, the article you are looking for is unavailable.</p>
            <a href="blog.php" class="btn btn-primary">Back to Our Blog</a>
        </div>
    </section>

<?php endif; ?>

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