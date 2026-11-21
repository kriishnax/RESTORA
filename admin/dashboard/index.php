<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

// Helper function to fetch single count
function getCount($conn, $sql) {
    $result = $conn->query($sql);

    if ($result) {
        return $result->fetch_assoc()['total'] ?? 0;
    }

    return 0;
}

// Total categories
$totalCategories = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM categories"
);

// Total menu items
$totalMenuItems = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM menu_items"
);

// Total restaurant tables
$totalTables = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM restaurant_tables"
);

// Available tables
$availableTables = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM restaurant_tables
     WHERE status = 'Available'"
);

// Today's orders (excluding cancelled orders)
$todayOrders = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE DATE(created_at) = CURDATE()
     AND order_status != 'Cancelled'"
);

// Today's sales (paid, non-cancelled orders)
$salesResult = $conn->query(
    "SELECT COALESCE(SUM(total_amount), 0) AS total
     FROM orders
     WHERE DATE(created_at) = CURDATE()
     AND payment_status = 'Paid'
     AND order_status != 'Cancelled'"
);

$todaySales = $salesResult
    ? $salesResult->fetch_assoc()['total']
    : 0;

// Recent orders
$recentOrders = $conn->query(
    "SELECT
        o.id,
        o.order_number,
        o.total_amount,
        o.order_status,
        o.payment_status,
        o.created_at,
        rt.table_number
     FROM orders o
     INNER JOIN restaurant_tables rt
        ON o.table_id = rt.id
     ORDER BY o.id DESC
     LIMIT 5"
);

$userName = $_SESSION['user_name'] ?? 'Admin';

// Page layout variables
$pageTitle = "Restora | Dashboard";
$pageHeading = "Dashboard";
$pageSubtitle = "Restaurant overview";

include_once "../../includes/header.php";
include_once "../../includes/sidebar.php";
include_once "../../includes/navbar.php";
?>

<!-- Welcome -->
<div class="mb-4">
    <h3 class="fw-bold">
        Welcome back, <?= $userName ?>!
    </h3>
    <p class="text-muted">
        Here's what's happening in your restaurant today.
    </p>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="bi bi-egg-fried"></i>
            </div>
            <h3><?= $totalMenuItems ?></h3>
            <p>Total Menu Items</p>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-tags"></i>
            </div>
            <h3><?= $totalCategories ?></h3>
            <p>Total Categories</p>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-grid-3x3-gap"></i>
            </div>
            <h3><?= $availableTables ?> / <?= $totalTables ?></h3>
            <p>Available Tables</p>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="bi bi-receipt"></i>
            </div>
            <h3><?= $todayOrders ?></h3>
            <p>Today's Orders</p>
        </div>
    </div>

</div>

<!-- Today's Sales -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-currency-rupee"></i>
            </div>
            <h3>₹<?= $todaySales ?></h3>
            <p>Today's Paid Sales</p>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="content-card">

    <h5>
        <i class="bi bi-clock-history me-2"></i>
        Recent Orders
    </h5>

    <div class="table-responsive">
        <table class="table table-hover">

            <thead>
                <tr>
                    <th>Order Number</th>
                    <th>Table</th>
                    <th>Amount</th>
                    <th>Order Status</th>
                    <th>Payment</th>
                    <th>Date</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($recentOrders && $recentOrders->num_rows > 0): ?>

                <?php while ($order = $recentOrders->fetch_assoc()): ?>

                    <?php
                    $statusClass = match ($order['order_status']) {
                        'Pending' => 'warning',
                        'Accepted' => 'info',
                        'Preparing' => 'primary',
                        'Ready' => 'success',
                        'Completed' => 'success',
                        'Cancelled' => 'danger',
                        default => 'secondary'
                    };

                    $paymentClass = $order['payment_status'] === 'Paid'
                        ? 'success'
                        : 'danger';
                    ?>

                    <tr>
                        <td><?= $order['order_number'] ?></td>
                        <td><?= $order['table_number'] ?></td>
                        <td>₹<?= $order['total_amount'] ?></td>
                        <td>
                            <span class="badge text-bg-<?= $statusClass ?>">
                                <?= $order['order_status'] ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge text-bg-<?= $paymentClass ?>">
                                <?= $order['payment_status'] ?>
                            </span>
                        </td>
                        <td>
                            <?= date("d M Y, h:i A", strtotime($order['created_at'])) ?>
                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        No orders available yet.
                    </td>
                </tr>

            <?php endif; ?>
            </tbody>

        </table>
    </div>

</div>

<?php include_once "../../includes/footer.php"; ?>