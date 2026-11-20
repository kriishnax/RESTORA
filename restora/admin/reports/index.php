<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

// Default date range: first day of current month to today
$fromDate = $_GET['from_date'] ?? date('Y-m-01');
$toDate = $_GET['to_date'] ?? date('Y-m-d');

// 1. Overall Paid Sales Summary for the selected date range
$summarySql = "SELECT
                    COUNT(*) AS total_orders,
                    COALESCE(SUM(subtotal), 0) AS total_subtotal,
                    COALESCE(SUM(tax_amount), 0) AS total_tax,
                    COALESCE(SUM(total_amount), 0) AS total_revenue,
                    COALESCE(AVG(total_amount), 0) AS avg_order_value
               FROM orders
               WHERE payment_status = 'Paid'
                 AND DATE(created_at) BETWEEN ? AND ?";

$summaryStmt = $conn->prepare($summarySql);
$summaryStmt->bind_param("ss", $fromDate, $toDate);
$summaryStmt->execute();
$summary = $summaryStmt->get_result()->fetch_assoc();
$summaryStmt->close();

// 2. Revenue Breakdown by Payment Method (Cash, UPI, Card)
$methodSql = "SELECT
                    COALESCE(payment_method, 'Cash') AS method,
                    COUNT(*) AS order_count,
                    COALESCE(SUM(total_amount), 0) AS method_total
              FROM orders
              WHERE payment_status = 'Paid'
                AND DATE(created_at) BETWEEN ? AND ?
              GROUP BY payment_method
              ORDER BY method_total DESC";

$methodStmt = $conn->prepare($methodSql);
$methodStmt->bind_param("ss", $fromDate, $toDate);
$methodStmt->execute();
$methodResult = $methodStmt->get_result();

// 3. Top Selling Menu Items in the selected period
$topItemsSql = "SELECT
                    m.name AS item_name,
                    c.name AS category_name,
                    SUM(oi.quantity) AS total_qty,
                    SUM(oi.quantity * oi.unit_price) AS item_revenue
                FROM order_items oi
                INNER JOIN orders o
                    ON oi.order_id = o.id
                INNER JOIN menu_items m
                    ON oi.menu_item_id = m.id
                INNER JOIN categories c
                    ON m.category_id = c.id
                WHERE o.payment_status = 'Paid'
                  AND DATE(o.created_at) BETWEEN ? AND ?
                GROUP BY m.id, m.name, c.name
                ORDER BY total_qty DESC
                LIMIT 10";

$topItemsStmt = $conn->prepare($topItemsSql);
$topItemsStmt->bind_param("ss", $fromDate, $toDate);
$topItemsStmt->execute();
$topItemsResult = $topItemsStmt->get_result();

// 4. Detailed Paid Orders Log
$ordersSql = "SELECT
                    o.id,
                    o.order_number,
                    o.subtotal,
                    o.tax_amount,
                    o.total_amount,
                    o.payment_method,
                    o.created_at,
                    rt.table_number
              FROM orders o
              INNER JOIN restaurant_tables rt
                  ON o.table_id = rt.id
              WHERE o.payment_status = 'Paid'
                AND DATE(o.created_at) BETWEEN ? AND ?
              ORDER BY o.id DESC";

$ordersStmt = $conn->prepare($ordersSql);
$ordersStmt->bind_param("ss", $fromDate, $toDate);
$ordersStmt->execute();
$ordersResult = $ordersStmt->get_result();

// Page layout variables
$pageTitle = "Restora | Sales Reports";
$pageHeading = "Sales Reports";
$pageSubtitle = "Analyze revenue, payment methods, and top-selling menu items";

include_once "../../includes/header.php";
include_once "../../includes/sidebar.php";
include_once "../../includes/navbar.php";
?>

<style>
@media print {
    .sidebar,
    .topbar,
    .no-print {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
    }
}
</style>

<!-- Date Range Filter Card -->
<div class="content-card mb-4 no-print">
    <form method="GET" action="" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label fw-semibold">From Date</label>
            <input type="date" name="from_date" class="form-control" value="<?= $fromDate ?>" required>
        </div>

        <div class="col-md-3">
            <label class="form-label fw-semibold">To Date</label>
            <input type="date" name="to_date" class="form-control" value="<?= $toDate ?>" required>
        </div>

        <div class="col-md-6 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-restora">
                <i class="bi bi-funnel me-1"></i> Filter Report
            </button>
            <a href="index.php" class="btn btn-outline-secondary">
                Reset
            </a>
            <button type="button" onclick="window.print()" class="btn btn-dark ms-md-auto">
                <i class="bi bi-printer me-1"></i> Print Report
            </button>
        </div>
    </form>
</div>

<!-- Revenue Summary Cards -->
<div class="row g-4 mb-4">

    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-currency-rupee"></i>
            </div>
            <h3>₹<?= number_format((float)$summary['total_revenue'], 2) ?></h3>
            <p>Total Revenue (Paid)</p>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-receipt"></i>
            </div>
            <h3><?= (int)$summary['total_orders'] ?></h3>
            <p>Completed Paid Orders</p>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="bi bi-percent"></i>
            </div>
            <h3>₹<?= number_format((float)$summary['total_tax'], 2) ?></h3>
            <p>Total GST Collected</p>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <h3>₹<?= number_format((float)$summary['avg_order_value'], 2) ?></h3>
            <p>Average Bill Value</p>
        </div>
    </div>

</div>

<div class="row g-4 mb-4">

    <!-- Payment Method Breakdown -->
    <div class="col-lg-5">
        <div class="content-card h-100">
            <h5>
                <i class="bi bi-wallet2 me-2"></i>
                Collection by Payment Mode
            </h5>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Payment Method</th>
                            <th class="text-center">Bills</th>
                            <th class="text-end">Total Collected</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($methodResult && $methodResult->num_rows > 0): ?>
                        <?php while ($m = $methodResult->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-semibold"><?= $m['method'] ?></td>
                                <td class="text-center"><?= $m['order_count'] ?></td>
                                <td class="text-end fw-semibold">₹<?= number_format((float)$m['method_total'], 2) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                No paid transactions in this date range.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Selling Menu Items -->
    <div class="col-lg-7">
        <div class="content-card h-100">
            <h5>
                <i class="bi bi-star me-2"></i>
                Top Selling Menu Items
            </h5>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th class="text-center">Qty Sold</th>
                            <th class="text-end">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($topItemsResult && $topItemsResult->num_rows > 0): ?>
                        <?php while ($item = $topItemsResult->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-semibold"><?= $item['item_name'] ?></td>
                                <td>
                                    <span class="badge text-bg-light border">
                                        <?= $item['category_name'] ?>
                                    </span>
                                </td>
                                <td class="text-center fw-semibold"><?= $item['total_qty'] ?></td>
                                <td class="text-end fw-semibold">₹<?= number_format((float)$item['item_revenue'], 2) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                No item sales recorded in this date range.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Detailed Paid Invoices Table -->
<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            <i class="bi bi-file-earmark-spreadsheet me-2"></i>
            Paid Invoices (<?= date("d M Y", strtotime($fromDate)) ?> – <?= date("d M Y", strtotime($toDate)) ?>)
        </h5>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Table</th>
                    <th>Payment Mode</th>
                    <th class="text-end">Subtotal</th>
                    <th class="text-end">GST</th>
                    <th class="text-end">Total Paid</th>
                    <th>Date & Time</th>
                    <th class="no-print">Receipt</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($ordersResult && $ordersResult->num_rows > 0): ?>
                <?php while ($ord = $ordersResult->fetch_assoc()): ?>
                    <tr>
                        <td class="fw-semibold"><?= $ord['order_number'] ?></td>
                        <td><?= $ord['table_number'] ?></td>
                        <td>
                            <span class="badge text-bg-light border">
                                <?= $ord['payment_method'] ?>
                            </span>
                        </td>
                        <td class="text-end">₹<?= $ord['subtotal'] ?></td>
                        <td class="text-end">₹<?= $ord['tax_amount'] ?></td>
                        <td class="text-end fw-bold">₹<?= $ord['total_amount'] ?></td>
                        <td><?= date("d M Y, h:i A", strtotime($ord['created_at'])) ?></td>
                        <td class="no-print">
                            <a href="../billing/invoice.php?order_id=<?= $ord['id'] ?>" class="btn btn-sm btn-outline-dark">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        No paid invoices found for the selected date range.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$methodStmt->close();
$topItemsStmt->close();
$ordersStmt->close();
include_once "../../includes/footer.php";
?>