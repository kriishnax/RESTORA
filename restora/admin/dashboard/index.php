
<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

// Fetch dashboard statistics
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
$userRole = $_SESSION['user_role'] ?? 'Staff';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Restora | Dashboard</title>

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <style>
        body {
            background: #f5f6fa;
            font-family: Arial, sans-serif;
            margin: 0;
        }

        /* Sidebar */

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #212529;
            color: white;
            padding: 25px 15px;
            transition: 0.3s;
            z-index: 1000;
        }

        .sidebar-brand {
            font-size: 25px;
            font-weight: 800;
            padding: 0 15px 30px;
            letter-spacing: 1px;
        }

        .sidebar a {
            display: block;
            color: #c5c7ca;
            text-decoration: none;
            padding: 12px 15px;
            margin-bottom: 6px;
            border-radius: 7px;
            transition: 0.2s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #dc6b35;
            color: white;
        }

        .sidebar a i {
            margin-right: 10px;
        }

        .sidebar-section {
            font-size: 11px;
            color: #8d9297;
            padding: 18px 15px 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Main Content */

        .main-content {
            margin-left: 250px;
            padding: 25px;
        }

        /* Topbar */

        .topbar {
            background: white;
            padding: 16px 22px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .topbar h5 {
            margin: 0;
            font-weight: 700;
        }

        /* Statistics */

        .stat-card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            height: 100%;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 23px;
            margin-bottom: 15px;
        }

        .stat-card h3 {
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: #777;
            margin: 0;
        }

        .orange {
            background: #fff0e8;
            color: #dc6b35;
        }

        .blue {
            background: #e8f1ff;
            color: #2878d0;
        }

        .green {
            background: #e7f8ee;
            color: #249b59;
        }

        .purple {
            background: #f1eaff;
            color: #7952c8;
        }

        /* Recent Orders */

        .content-card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }

        .content-card h5 {
            font-weight: 700;
            margin-bottom: 20px;
        }

        .table th {
            color: #777;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
            font-size: 14px;
        }

        /* Responsive */

        .menu-toggle {
            display: none;
            border: none;
            background: transparent;
            font-size: 24px;
        }

        @media (max-width: 991px) {

            .sidebar {
                left: -250px;
            }

            .sidebar.show {
                left: 0;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .menu-toggle {
                display: inline-block;
            }
        }
    </style>

</head>

<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        RESTORA
    </div>

    <div class="sidebar-section">Main</div>

    <a href="index.php" class="active">
        <i class="bi bi-grid"></i>
        Dashboard
    </a>

    <div class="sidebar-section">Restaurant Management</div>

    <a href="../categories/index.php">
        <i class="bi bi-tags"></i>
        Categories
    </a>

    <a href="../menu/index.php">
        <i class="bi bi-egg-fried"></i>
        Menu Management
    </a>

    <a href="../tables/index.php">
        <i class="bi bi-grid-3x3-gap"></i>
        Tables
    </a>

    <a href="../orders/index.php">
        <i class="bi bi-receipt"></i>
        Orders
    </a>

    <div class="sidebar-section">Finance</div>

    <a href="../billing/index.php">
        <i class="bi bi-credit-card"></i>
        Billing
    </a>

    <a href="../reports/index.php">
        <i class="bi bi-bar-chart"></i>
        Reports
    </a>

    <div class="sidebar-section">Account</div>

    <?php if ($userRole === 'Admin'): ?>
        <a href="../auth/register.php">
            <i class="bi bi-person-plus"></i>
            Add Staff
        </a>
    <?php endif; ?>

    <a href="../auth/logout.php">
        <i class="bi bi-box-arrow-right"></i>
        Logout
    </a>

</aside>


<!-- Main Content -->
<main class="main-content">

    <!-- Topbar -->
    <div class="topbar">

        <div class="d-flex align-items-center gap-3">

            <button class="menu-toggle" id="menuToggle">
                <i class="bi bi-list"></i>
            </button>

            <div>
                <h5>Dashboard</h5>
                <small class="text-muted">
                    Restaurant overview
                </small>
            </div>

        </div>

        <div class="text-end">
            <strong>
                <?= htmlspecialchars($userName) ?>
            </strong>

            <div>
                <small class="text-muted">
                    <?= htmlspecialchars($userRole) ?>
                </small>
            </div>
        </div>

    </div>


    <!-- Welcome -->
    <div class="mb-4">

        <h3 class="fw-bold">
            Welcome back, <?= htmlspecialchars($userName) ?>!
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

                <h3>
                    <?= $availableTables ?> / <?= $totalTables ?>
                </h3>

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

                <h3>
                    ₹<?= number_format((float)$todaySales, 2) ?>
                </h3>

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

                            <td>
                                <?= htmlspecialchars($order['order_number']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($order['table_number']) ?>
                            </td>

                            <td>
                                ₹<?= number_format((float)$order['total_amount'], 2) ?>
                            </td>

                            <td>
                                <span class="badge text-bg-<?= $statusClass ?>">
                                    <?= htmlspecialchars($order['order_status']) ?>
                                </span>
                            </td>

                            <td>
                                <span class="badge text-bg-<?= $paymentClass ?>">
                                    <?= htmlspecialchars($order['payment_status']) ?>
                                </span>
                            </td>

                            <td>
                                <?= date(
                                    "d M Y, h:i A",
                                    strtotime($order['created_at'])
                                ) ?>
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

</main>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const menuToggle = document.getElementById("menuToggle");
    const sidebar = document.getElementById("sidebar");

    menuToggle.addEventListener("click", function () {
        sidebar.classList.toggle("show");
    });
</script>

</body>
</html>
