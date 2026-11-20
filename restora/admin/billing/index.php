<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

$paymentFilter = $_GET['payment'] ?? 'All';
$allowedFilters = ['All', 'Unpaid', 'Paid'];

if (!in_array($paymentFilter, $allowedFilters)) {
    $paymentFilter = 'All';
}

// Fetch billing records (excluding cancelled orders)
if ($paymentFilter === 'All') {
    $sql = "SELECT
                o.id,
                o.order_number,
                o.subtotal,
                o.tax_amount,
                o.total_amount,
                o.order_status,
                o.payment_status,
                o.payment_method,
                o.created_at,
                rt.table_number
            FROM orders o
            INNER JOIN restaurant_tables rt
                ON o.table_id = rt.id
            WHERE o.order_status != 'Cancelled'
            ORDER BY o.payment_status ASC, o.id DESC";

    $result = $conn->query($sql);
} else {
    $sql = "SELECT
                o.id,
                o.order_number,
                o.subtotal,
                o.tax_amount,
                o.total_amount,
                o.order_status,
                o.payment_status,
                o.payment_method,
                o.created_at,
                rt.table_number
            FROM orders o
            INNER JOIN restaurant_tables rt
                ON o.table_id = rt.id
            WHERE o.order_status != 'Cancelled'
              AND o.payment_status = ?
            ORDER BY o.id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $paymentFilter);
    $stmt->execute();
    $result = $stmt->get_result();
}

// Quick billing summary
$unpaidCount = getCount($conn, "SELECT COUNT(*) AS total FROM orders WHERE payment_status = 'Unpaid' AND order_status != 'Cancelled'");
$paidCount = getCount($conn, "SELECT COUNT(*) AS total FROM orders WHERE payment_status = 'Paid'");

$revRes = $conn->query("SELECT COALESCE(SUM(total_amount), 0) AS total FROM orders WHERE payment_status = 'Paid'");
$totalCollected = $revRes ? $revRes->fetch_assoc()['total'] : '0.00';

$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

// Page layout variables
$pageTitle = "Restora | Billing & Payments";
$pageHeading = "Billing & Payments";
$pageSubtitle = "Generate customer bills, collect payments, and print invoices";

include_once "../../includes/header.php";
include_once "../../includes/sidebar.php";
include_once "../../includes/navbar.php";
?>

<?php if (!empty($msg)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $msg ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($err)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $err ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Billing Summary Cards -->
<div class="row g-4 mb-4">

    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <h3><?= $unpaidCount ?></h3>
            <p>Pending Bills</p>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-check2-circle"></i>
            </div>
            <h3><?= $paidCount ?></h3>
            <p>Paid Invoices</p>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-currency-rupee"></i>
            </div>
            <h3>₹<?= $totalCollected ?></h3>
            <p>Total Revenue Collected</p>
        </div>
    </div>

</div>

<!-- Bills Table -->
<div class="content-card">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h5 class="mb-0">
            <i class="bi bi-credit-card me-2"></i>
            Order Bills & Invoices
        </h5>

        <!-- Payment Status Filter -->
        <div class="d-flex gap-2">
            <?php foreach ($allowedFilters as $filter): ?>
                <a
                    href="index.php?payment=<?= $filter ?>"
                    class="btn btn-sm <?= ($paymentFilter === $filter) ? 'btn-dark' : 'btn-outline-secondary' ?>"
                >
                    <?= $filter ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">

            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Table</th>
                    <th>Subtotal</th>
                    <th>Tax (GST)</th>
                    <th>Grand Total</th>
                    <th>Payment Status</th>
                    <th>Method</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($bill = $result->fetch_assoc()): ?>

                    <?php $payBadge = getPaymentStatusBadge($bill['payment_status']); ?>

                    <tr>
                        <td class="fw-semibold"><?= $bill['order_number'] ?></td>
                        <td>
                            <span class="badge text-bg-light border">
                                <?= $bill['table_number'] ?>
                            </span>
                        </td>
                        <td>₹<?= $bill['subtotal'] ?></td>
                        <td>₹<?= $bill['tax_amount'] ?></td>
                        <td class="fw-bold">₹<?= $bill['total_amount'] ?></td>
                        <td>
                            <span class="badge text-bg-<?= $payBadge ?>">
                                <?= $bill['payment_status'] ?>
                            </span>
                        </td>
                        <td>
                            <?= !empty($bill['payment_method']) ? $bill['payment_method'] : '-' ?>
                        </td>
                        <td><?= date("d M Y, h:i A", strtotime($bill['created_at'])) ?></td>
                        <td>
                            <?php if ($bill['payment_status'] === 'Unpaid'): ?>
                                <a href="invoice.php?order_id=<?= $bill['id'] ?>" class="btn btn-sm btn-restora">
                                    <i class="bi bi-cash-coin me-1"></i> Collect Bill
                                </a>
                            <?php else: ?>
                                <a href="invoice.php?order_id=<?= $bill['id'] ?>" class="btn btn-sm btn-outline-dark">
                                    <i class="bi bi-printer me-1"></i> Invoice
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>

                <?php endwhile; ?>
            <?php else: ?>

                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        No billing records found.
                    </td>
                </tr>

            <?php endif; ?>
            </tbody>

        </table>
    </div>

</div>

<?php include_once "../../includes/footer.php"; ?>