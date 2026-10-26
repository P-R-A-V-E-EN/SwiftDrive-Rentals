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

$message_notice = "";
$search = trim($_GET['search'] ?? "");
$view_id = filter_input(INPUT_GET, 'view', FILTER_VALIDATE_INT);

function message_h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Delete a message after confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_message'])) {

    $posted_token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $posted_token)) {
        $message_notice = "Security validation failed. Please try again.";
    } else {
        $inquiry_id = filter_input(
            INPUT_POST,
            'inquiry_id',
            FILTER_VALIDATE_INT
        );

        if (!$inquiry_id || $inquiry_id < 1) {
            $message_notice = "Invalid message ID.";
        } else {
            $delete_stmt = mysqli_prepare(
                $conn,
                "DELETE FROM contact_inquiries WHERE inquiry_id = ?"
            );

            if ($delete_stmt) {
                mysqli_stmt_bind_param($delete_stmt, "i", $inquiry_id);

                if (mysqli_stmt_execute($delete_stmt)) {
                    if (mysqli_stmt_affected_rows($delete_stmt) > 0) {
                        mysqli_stmt_close($delete_stmt);
                        header("Location: admin_messages.php?deleted=1");
                        exit();
                    } else {
                        $message_notice = "Message not found.";
                    }
                } else {
                    $message_notice = "Message could not be deleted.";
                }

                mysqli_stmt_close($delete_stmt);
            } else {
                $message_notice = "Unable to prepare the delete query.";
            }
        }
    }
}

if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
    $message_notice = "Message deleted successfully.";
}

// Load one message for the View action
$selected_message = null;

if ($view_id && $view_id > 0) {
    $view_stmt = mysqli_prepare(
        $conn,
        "SELECT * FROM contact_inquiries WHERE inquiry_id = ?"
    );

    if ($view_stmt) {
        mysqli_stmt_bind_param($view_stmt, "i", $view_id);
        mysqli_stmt_execute($view_stmt);

        $view_result = mysqli_stmt_get_result($view_stmt);
        $selected_message = mysqli_fetch_assoc($view_result);

        mysqli_stmt_close($view_stmt);
    }
}

// Load messages, with optional search
$sql = "SELECT *
        FROM contact_inquiries";

if ($search !== "") {
    $sql .= " WHERE name LIKE ?
              OR email LIKE ?
              OR phone LIKE ?
              OR subject LIKE ?
              OR message LIKE ?
              OR inquiry_status LIKE ?
              OR CAST(inquiry_id AS CHAR) LIKE ?";
}

$sql .= " ORDER BY inquiry_id DESC";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Unable to load contact messages.");
}

if ($search !== "") {
    $search_term = "%" . $search . "%";

    mysqli_stmt_bind_param(
        $stmt,
        "sssssss",
        $search_term,
        $search_term,
        $search_term,
        $search_term,
        $search_term,
        $search_term,
        $search_term
    );
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$messages = [];

while ($row = mysqli_fetch_assoc($result)) {
    $messages[] = $row;
}

mysqli_stmt_close($stmt);

// Total message count
$count_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM contact_inquiries"
);

$total_messages = $count_result
    ? (int)mysqli_fetch_assoc($count_result)['total']
    : count($messages);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contact Messages | SwiftDrive Admin</title>

    <link rel="stylesheet" href="admin_messages.css">
</head>

<body>

<div class="admin-layout">

    <!-- SIDEBAR -->
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
            <a href="admin_customers.php"><span>♙</span> Customers</a>
            <a href="admin_messages.php" class="active"><span>✉</span> Messages</a>
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

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <header class="topbar">
            <div>
                <p class="breadcrumb">Dashboard / Contact Messages</p>
                <h1>Contact Messages</h1>
                <p class="page-subtitle">
                    View and manage messages submitted through the website.
                </p>
            </div>

            <a href="admin_dashboard.php" class="back-button">
                ← Back to Dashboard
            </a>
        </header>

        <!-- STATISTICS -->
        <section class="stats-grid">

            <div class="stat-card">
                <div class="stat-icon messages-icon">✉</div>
                <div>
                    <p>Total Messages</p>
                    <h2><?= $total_messages ?></h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon search-icon">⌕</div>
                <div>
                    <p>Search Results</p>
                    <h2><?= count($messages) ?></h2>
                </div>
            </div>

        </section>

        <!-- MESSAGE DETAILS -->
        <?php if ($view_id): ?>

            <section class="detail-card">

                <?php if ($selected_message): ?>

                    <div class="detail-heading">
                        <div>
                            <h2>Message Details</h2>
                            <p>
                                Message reference #<?= (int)$selected_message['inquiry_id'] ?>
                            </p>
                        </div>

                        <a href="admin_messages.php" class="back-button">
                            ← Back to Messages
                        </a>
                    </div>

                    <div class="detail-grid">

                        <div class="detail-item">
                            <span>Sender Name</span>
                            <strong><?= message_h($selected_message['name']) ?></strong>
                        </div>

                        <div class="detail-item">
                            <span>Email Address</span>
                            <strong><?= message_h($selected_message['email']) ?></strong>
                        </div>

                        <div class="detail-item">
                            <span>Phone Number</span>
                            <strong><?= message_h($selected_message['phone'] ?: 'Not provided') ?></strong>
                        </div>

                        <div class="detail-item">
                            <span>Subject</span>
                            <strong><?= message_h($selected_message['subject']) ?></strong>
                        </div>

                        <div class="detail-item">
                            <span>Status</span>
                            <strong><?= message_h($selected_message['inquiry_status']) ?></strong>
                        </div>

                        <div class="detail-item">
                            <span>Date Received</span>
                            <strong><?= message_h($selected_message['created_at']) ?></strong>
                        </div>

                    </div>

                    <div class="full-message">
                        <h3>Message</h3>
                        <p><?= message_h($selected_message['message']) ?></p>
                    </div>

                    <div class="detail-actions">

                        <a href="mailto:<?= rawurlencode($selected_message['email']) ?>?subject=<?= rawurlencode('Re: ' . $selected_message['subject']) ?>"
                           class="reply-button">
                            ✉ Reply by Email
                        </a>

                        <form method="POST"
                              action="admin_messages.php"
                              onsubmit="return confirm('Are you sure you want to permanently delete this message?');">

                            <input type="hidden" name="csrf_token"
                                   value="<?= message_h($_SESSION['csrf_token']) ?>">

                            <input type="hidden" name="inquiry_id"
                                   value="<?= (int)$selected_message['inquiry_id'] ?>">

                            <button type="submit" name="delete_message"
                                    class="delete-button">
                                Delete Message
                            </button>

                        </form>

                    </div>

                <?php else: ?>

                    <h2>Message not found</h2>
                    <p>This message may have been deleted.</p>

                    <a href="admin_messages.php" class="back-button">
                        Back to Messages
                    </a>

                <?php endif; ?>

            </section>

        <?php endif; ?>

        <!-- MESSAGE TABLE -->
        <section class="table-card">

            <div class="table-heading">
                <div>
                    <h2>All Contact Messages</h2>
                    <p>Messages received from website visitors.</p>
                </div>
            </div>

            <?php if ($message_notice !== ""): ?>
                <div class="<?= isset($_GET['deleted']) ? 'success-message' : 'notice-message' ?>">
                    <?= message_h($message_notice) ?>
                </div>
            <?php endif; ?>

            <form method="GET" action="admin_messages.php" class="search-form">

                <div class="search-box">
                    <span>⌕</span>

                    <input
                        type="search"
                        name="search"
                        placeholder="Search name, email, subject or message..."
                        value="<?= message_h($search) ?>"
                    >
                </div>

                <button type="submit" class="search-button">Search</button>

                <?php if ($search !== ""): ?>
                    <a href="admin_messages.php" class="clear-button">Clear</a>
                <?php endif; ?>

            </form>

            <div class="table-wrapper">

                <table>

                    <thead>
                        <tr>
                            <th>Sender</th>
                            <th>Subject</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Date Received</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (count($messages) > 0): ?>

                        <?php foreach ($messages as $item): ?>

                            <tr>

                                <td>
                                    <div class="sender-cell">
                                        <div class="sender-avatar">
                                            <?= message_h(
                                                strtoupper(substr(
                                                    trim($item['name']),
                                                    0,
                                                    1
                                                ))
                                            ) ?>
                                        </div>

                                        <div>
                                            <strong><?= message_h($item['name']) ?></strong>
                                            <small><?= message_h($item['email']) ?></small>
                                        </div>
                                    </div>
                                </td>

                                <td class="subject-cell">
                                    <?= message_h($item['subject']) ?>
                                </td>

                                <td>
                                    <?= message_h($item['phone'] ?: 'N/A') ?>
                                </td>

                                <td>
                                    <span class="status-badge">
                                        <?= message_h($item['inquiry_status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= message_h(
                                        $item['created_at']
                                            ? date('Y-m-d', strtotime($item['created_at']))
                                            : 'N/A'
                                    ) ?>
                                </td>

                                <td>
                                    <div class="action-buttons">

                                        <a href="admin_messages.php?view=<?= (int)$item['inquiry_id'] ?>"
                                           class="view-button">
                                            View
                                        </a>

                                        <form method="POST"
                                              action="admin_messages.php<?= $search !== '' ? '?search=' . urlencode($search) : '' ?>"
                                              onsubmit="return confirm('Are you sure you want to permanently delete this message?');">

                                            <input type="hidden" name="csrf_token"
                                                   value="<?= message_h($_SESSION['csrf_token']) ?>">

                                            <input type="hidden" name="inquiry_id"
                                                   value="<?= (int)$item['inquiry_id'] ?>">

                                            <button type="submit"
                                                    name="delete_message"
                                                    class="delete-button">
                                                Delete
                                            </button>

                                        </form>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="empty-state">
                                <div class="empty-icon">✉</div>
                                <strong>No messages found</strong>
                                <p>
                                    <?= $search !== ''
                                        ? 'Try another search term.'
                                        : 'Website contact messages will appear here.' ?>
                                </p>
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <div class="table-footer">
                Showing <?= count($messages) ?> message(s)
            </div>

        </section>

        <footer class="footer-note">
            &copy; <?= date('Y') ?> SwiftDrive Rentals. All rights reserved.
        </footer>

    </main>

</div>

</body>
</html>