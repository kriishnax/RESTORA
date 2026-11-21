<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

$filters = ['All', 'Unpaid', 'Paid'];
$filter = in_array($_GET['payment'] ?? '', $filters) ? $_GET['payment'] : 'All';

$sql = "SELECT o.*, rt.table_number
        FROM orders o
        INNER JOIN restaurant_tables rt ON o.table_id = rt.id
        WHERE o.order_status != 'Cancelled'";

if ($filter === 'All') {
    $result = $conn->query("$sql ORDER BY o.payment_status ASC, o.id DESC");
} else {
    $stmt = $conn->prepare("$sql AND o.payment_status = ? ORDER BY o.id DESC");
    $stmt->bind_param("s", $filter);
    $stmt->execute();
    $result = $stmt->get_result();
}

// Billing summary
$unpaidCount = getCount($conn, "SELECT COUNT(*) AS total FROM orders WHERE payment_status = 'Unpaid' AND order_status != 'Cancelled'");
$paidCount = getCount($conn, "SELECT COUNT(*) AS total FROM orders WHERE payment_status = 'Paid'");
$totalCollected = $conn->query("SELECT COALESCE(SUM(total_amount), 0) AS total FROM orders WHERE payment_status = 'Paid'")->fetch_assoc()['total'];

$pageTitle = "Restora | Billing & Payments";
$pageHeading = "Billing & Payments";
$pageSubtitle = "Generate customer bills, collect payments, and print invoices";

include_once "../../includes/header.php";
include_once "../../includes/sidebar.php";
include_once "../../includes/navbar.php";
?>

<?php if (!empty($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $_GET['msg'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($_GET['err'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $_GET['err'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Billing Summary Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="bi bi-hourglass-split"></i></div>
            <h3><?= $unpaidCount ?></h3>
            <p>Pending Bills</p>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-check2-circle"></i></div>
            <h3><?= $paidCount ?></h3>
            <p>Paid Invoices</p>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-currency-rupee"></i></div>
            <h3>₹<?= $totalCollected ?></h3>
            <p>Total Revenue Collected</p>
        </div>
    </div>
</div>

<!-- Bills Table -->
<div class="content-card">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <h5 class="mb-0"><i class="bi bi-credit-card me-2"></i>Order Bills & Invoices</h5>

        <div class="d-flex gap-2">
            <?php foreach ($filters as $f): ?>
                <a href="index.php?payment=<?= $f ?>" class="btn btn-sm <?= ($filter === $f) ? 'btn-restora' : 'btn-outline-secondary' ?>">
                    <?= $f ?>
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
                    <th>Payment</th>
                    <th>Method</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($bill = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="fw-semibold"><?= $bill['order_number'] ?></td>
                        <td><span class="badge text-bg-light border"><?= $bill['table_number'] ?></span></td>
                        <td>₹<?= $bill['subtotal'] ?></td>
                        <td>₹<?= $bill['tax_amount'] ?></td>
                        <td class="fw-bold">₹<?= $bill['total_amount'] ?></td>
                        <td><span class="badge text-bg-<?= getPaymentStatusBadge($bill['payment_status']) ?>"><?= $bill['payment_status'] ?></span></td>
                        <td><?= $bill['payment_method'] ?: '-' ?></td>
                        <td><?= date("d M Y, h:i A", strtotime($bill['created_at'])) ?></td>
                        <td>
                            <?php if ($bill['payment_status'] === 'Unpaid'): ?>
                                <a href="invoice.php?order_id=<?= $bill['id'] ?>" class="btn btn-sm btn-restora">
                                    <i class="bi bi-cash-coin me-1"></i> Collect Bill
                                </a>
                            <?php else: ?>
                                <a href="invoice.php?order_id=<?= $bill['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-printer me-1"></i> Invoice
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">No billing records found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php include_once "../../includes/footer.php"; ?>