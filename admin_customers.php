 <?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";
$search = trim($_GET['search'] ?? "");

// Delete a customer only after confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_customer'])) {

    $posted_token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $posted_token)) {
        $message = "Security validation failed. Please try again.";
    } else {
        $customer_id = filter_input(
            INPUT_POST,
            'customer_id',
            FILTER_VALIDATE_INT
        );

        if (!$customer_id || $customer_id < 1) {
            $message = "Invalid customer ID.";
        } else {
            $delete_stmt = mysqli_prepare(
                $conn,
                "DELETE FROM customers WHERE customer_id = ?"
            );

            if ($delete_stmt) {
                mysqli_stmt_bind_param($delete_stmt, "i", $customer_id);

                if (mysqli_stmt_execute($delete_stmt)) {
                    if (mysqli_stmt_affected_rows($delete_stmt) > 0) {
                        mysqli_stmt_close($delete_stmt);
                        header("Location: admin_customers.php?deleted=1");
                        exit();
                    } else {
                        $message = "Customer not found.";
                    }
                } else {
                    $message = "Customer could not be deleted. This customer may have existing bookings.";
                }

                mysqli_stmt_close($delete_stmt);
            } else {
                $message = "Unable to prepare the delete query.";
            }
        }
    }
}

if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
    $message = "Customer deleted successfully.";
}

// Get customers, with optional search
$sql = "SELECT customer_id, full_name, email, phone, address, created_at
        FROM customers";

if ($search !== "") {
    $sql .= " WHERE full_name LIKE ?
              OR email LIKE ?
              OR phone LIKE ?
              OR CAST(customer_id AS CHAR) LIKE ?";
}

$sql .= " ORDER BY customer_id DESC";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Unable to load customers.");
}

if ($search !== "") {
    $search_term = "%" . $search . "%";

    mysqli_stmt_bind_param(
        $stmt,
        "ssss",
        $search_term,
        $search_term,
        $search_term,
        $search_term
    );
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$customers = [];

while ($customer = mysqli_fetch_assoc($result)) {
    $customers[] = $customer;
}

mysqli_stmt_close($stmt);

// Customer count
$count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM customers");
$total_customers = $count_result
    ? (int)mysqli_fetch_assoc($count_result)['total']
    : count($customers);

function customer_h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Customers | SwiftDrive Admin</title>

    <link rel="stylesheet" href="admin_customers.css">
</head>

<body>

<div class="admin-layout">

    <aside class="sidebar">

        <a href="admin_dashboard.php" class="brand">
            <span class="brand-icon">S</span>
            <span>
                Swift<span class="orange">Drive</span>
                <small>ADMIN PANEL</small>
            </span>
        </a>

        <div class="menu-label">MAIN MENU</div>

        <nav class="sidebar-nav">
            <a href="admin_dashboard.php"><span>▦</span> Dashboard</a>
            <a href="admin_vehicles.php"><span>🚘</span> Vehicles</a>
            <a href="admin_bookings.php"><span>▤</span> Bookings</a>
            <a href="admin_customers.php" class="active"><span>♙</span> Customers</a>
            <a href="admin_messages.php"><span>✉</span> Messages</a>
        </nav>

        <div class="sidebar-bottom">
            <div class="admin-mini">
                <div class="avatar">A</div>
                <div>
                    <strong>Administrator</strong>
                    <small>System Admin</small>
                </div>
            </div>

            <a href="admin_logout.php" class="logout-link">↪ Log Out</a>
        </div>

    </aside>

    <main class="main-content">

        <header class="topbar">
            <div>
                <p class="breadcrumb">Dashboard / Customers</p>
                <h1>Manage Customers</h1>
                <p class="page-subtitle">
                    View and manage registered SwiftDrive customers.
                </p>
            </div>

            <a href="admin_dashboard.php" class="back-button">
                ← Back to Dashboard
            </a>
        </header>

        <section class="stats-grid">

            <div class="stat-card">
                <div class="stat-icon customers-icon">♙</div>
                <div>
                    <p>Total Customers</p>
                    <h2><?= $total_customers ?></h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon search-icon">⌕</div>
                <div>
                    <p>Search Results</p>
                    <h2><?= count($customers) ?></h2>
                </div>
            </div>

        </section>

        <section class="table-card">

            <div class="table-heading">
                <div>
                    <h2>Customer List</h2>
                    <p>Customer contact and registration details.</p>
                </div>
            </div>

            <?php if ($message !== ""): ?>
                <div class="<?= isset($_GET['deleted']) ? 'success-message' : 'notice-message' ?>">
                    <?= customer_h($message) ?>
                </div>
            <?php endif; ?>

            <form method="GET" action="admin_customers.php" class="search-form">
                <div class="search-box">
                    <span>⌕</span>
                    <input
                        type="search"
                        name="search"
                        placeholder="Search name, email, phone or customer ID..."
                        value="<?= customer_h($search) ?>"
                    >
                </div>

                <button type="submit" class="search-button">Search</button>

                <?php if ($search !== ""): ?>
                    <a href="admin_customers.php" class="clear-button">Clear</a>
                <?php endif; ?>
            </form>

            <div class="table-wrapper">

                <table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Registered Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (count($customers) > 0): ?>

                        <?php foreach ($customers as $customer): ?>

                            <tr>
                                <td>
                                    <div class="customer-cell">
                                        <div class="customer-avatar">
                                            <?= customer_h(
                                                strtoupper(substr(
                                                    trim($customer['full_name']),
                                                    0,
                                                    1
                                                ))
                                            ) ?>
                                        </div>

                                        <div>
                                            <strong><?= customer_h($customer['full_name']) ?></strong>
                                            <small>ID: #<?= (int)$customer['customer_id'] ?></small>
                                        </div>
                                    </div>
                                </td>

                                <td><?= customer_h($customer['email']) ?></td>

                                <td>
                                    <?= customer_h($customer['phone'] ?: 'Not provided') ?>
                                </td>

                                <td class="address-cell">
                                    <?= customer_h($customer['address'] ?: 'Not provided') ?>
                                </td>

                                <td>
                                    <?= customer_h(
                                        $customer['created_at']
                                            ? date('Y-m-d', strtotime($customer['created_at']))
                                            : 'N/A'
                                    ) ?>
                                </td>

                                <td>
                                    <form
                                        method="POST"
                                        action="admin_customers.php<?= $search !== '' ? '?search=' . urlencode($search) : '' ?>"
                                        onsubmit="return confirm('Are you sure you want to delete this customer? This action cannot be undone.');"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= customer_h($_SESSION['csrf_token']) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="customer_id"
                                            value="<?= (int)$customer['customer_id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="delete_customer"
                                            class="delete-button"
                                            aria-label="Delete customer"
                                            title="Delete customer"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="empty-state">
                                <div class="empty-icon">♙</div>
                                <strong>No customers found</strong>
                                <p>
                                    <?= $search !== ''
                                        ? 'Try another search term.'
                                        : 'Registered customers will appear here.' ?>
                                </p>
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>

            </div>

            <div class="table-footer">
                Showing <?= count($customers) ?> customer(s)
            </div>

        </section>

        <footer class="footer-note">
            &copy; <?= date('Y') ?> SwiftDrive Rentals. All rights reserved.
        </footer>

    </main>

</div>

</body>
</html>