<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

$orderId = 0;
if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
    $orderId = (int)$_GET['order_id'];
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $orderId = (int)$_GET['id'];
}

if ($orderId <= 0) {
    header("Location: index.php?err=Invalid order ID.");
    exit();
}

$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

// Handle payment collection
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['collect_payment'])) {

    $paymentMethod = $_POST['payment_method'] ?? '';
    $allowedMethods = ['Cash', 'UPI', 'Card'];

    if (!in_array($paymentMethod, $allowedMethods)) {
        $err = "Please select a valid payment method (Cash, UPI, or Card).";
    } else {

        // Fetch table_id and current payment_status
        $checkStmt = $conn->prepare("SELECT table_id, payment_status FROM orders WHERE id = ? LIMIT 1");
        $checkStmt->bind_param("i", $orderId);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();

        if (!$checkRes || $checkRes->num_rows === 0) {
            $checkStmt->close();
            header("Location: index.php?err=Order not found.");
            exit();
        }

        $orderRow = $checkRes->fetch_assoc();
        $tableId = (int)$orderRow['table_id'];
        $checkStmt->close();

        if ($orderRow['payment_status'] === 'Paid') {
            header("Location: invoice.php?order_id=" . $orderId . "&msg=This invoice is already marked as Paid.");
            exit();
        }

        $conn->begin_transaction();

        try {
            // 1. Mark order as Paid & Completed
            $paySql = "UPDATE orders
                       SET payment_status = 'Paid',
                           payment_method = ?,
                           order_status = 'Completed'
                       WHERE id = ?";
            $payStmt = $conn->prepare($paySql);
            $payStmt->bind_param("si", $paymentMethod, $orderId);
            $payStmt->execute();
            $payStmt->close();

            // 2. Free the dining table if no other active orders remain on it
            if ($tableId > 0) {
                $activeCheck = $conn->prepare(
                    "SELECT COUNT(*) AS total
                     FROM orders
                     WHERE table_id = ?
                       AND id != ?
                       AND order_status NOT IN ('Completed', 'Cancelled')"
                );
                $activeCheck->bind_param("ii", $tableId, $orderId);
                $activeCheck->execute();
                $activeCount = (int)$activeCheck->get_result()->fetch_assoc()['total'];
                $activeCheck->close();

                if ($activeCount === 0) {
                    $freeStmt = $conn->prepare("UPDATE restaurant_tables SET status = 'Available' WHERE id = ?");
                    $freeStmt->bind_param("i", $tableId);
                    $freeStmt->execute();
                    $freeStmt->close();
                }
            }

            $conn->commit();

            header("Location: invoice.php?order_id=" . $orderId . "&msg=Payment received via " . $paymentMethod . ". Table is now available.");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $err = "Failed to record payment. Please try again.";
        }
    }
}

// Fetch order details
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
            rt.table_number,
            u.name AS created_by_name
        FROM orders o
        INNER JOIN restaurant_tables rt
            ON o.table_id = rt.id
        LEFT JOIN users u
            ON o.user_id = u.id
        WHERE o.id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $stmt->close();
    header("Location: index.php?err=Order not found.");
    exit();
}

$order = $result->fetch_assoc();
$stmt->close();

// Fetch ordered line items
$itemsSql = "SELECT
                oi.quantity,
                oi.unit_price,
                (oi.quantity * oi.unit_price) AS line_total,
                m.name AS item_name
             FROM order_items oi
             INNER JOIN menu_items m
                ON oi.menu_item_id = m.id
             WHERE oi.order_id = ?
             ORDER BY oi.id ASC";

$itemsStmt = $conn->prepare($itemsSql);
$itemsStmt->bind_param("i", $orderId);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();

$paymentClass = getPaymentStatusBadge($order['payment_status']);

// Page layout variables
$pageTitle = "Restora | Invoice " . $order['order_number'];
$pageHeading = "Customer Invoice";
$pageSubtitle = "Collect payment and print customer tax receipt";

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
    .invoice-box {
        box-shadow: none !important;
        border: 1px solid #dee2e6 !important;
    }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h5 class="mb-0 fw-bold">Invoice #<?= $order['order_number'] ?></h5>
    <div class="d-flex gap-2">
        <button type="button" onclick="window.print()" class="btn btn-sm btn-dark">
            <i class="bi bi-printer me-1"></i> Print Receipt
        </button>
        <a href="index.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Billing
        </a>
    </div>
</div>

<?php if (!empty($msg)): ?>
    <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
        <?= $msg ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($err)): ?>
    <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
        <?= $err ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4">

    <!-- Printable Invoice Card -->
    <div class="col-lg-8">
        <div class="content-card invoice-box">

            <!-- Restaurant Header -->
            <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-4">
                <div>
                    <h3 class="fw-bold mb-1" style="color: #dc6b35; letter-spacing: 1px;">RESTORA</h3>
                    <p class="text-muted small mb-0">Dine-In Restaurant & Kitchen</p>
                    <p class="text-muted small mb-0">GSTIN: 21AABCR1234F1Z5</p>
                </div>

                <div class="text-end">
                    <h5 class="fw-bold mb-1">TAX INVOICE</h5>
                    <div class="small text-muted">Order: <strong class="text-dark"><?= $order['order_number'] ?></strong></div>
                    <div class="small text-muted">Date: <?= date("d M Y, h:i A", strtotime($order['created_at'])) ?></div>
                    <div class="mt-1">
                        <span class="badge text-bg-<?= $paymentClass ?>">
                            <?= $order['payment_status'] ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Table & Billing Meta -->
            <div class="row mb-4 small">
                <div class="col-6">
                    <span class="text-muted">Dining Table:</span>
                    <strong class="ms-1"><?= $order['table_number'] ?></strong>
                </div>
                <div class="col-6 text-end">
                    <span class="text-muted">Billed By:</span>
                    <strong class="ms-1"><?= $order['created_by_name'] ?? 'Admin' ?></strong>
                </div>
                <?php if (!empty($order['payment_method'])): ?>
                    <div class="col-12 mt-1">
                        <span class="text-muted">Payment Mode:</span>
                        <strong class="ms-1"><?= $order['payment_method'] ?></strong>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Itemized Table -->
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Item Description</th>
                            <th class="text-end">Rate</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($itemsResult && $itemsResult->num_rows > 0): ?>
                        <?php $i = 1; ?>
                        <?php while ($item = $itemsResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= $item['item_name'] ?></td>
                                <td class="text-end">₹<?= $item['unit_price'] ?></td>
                                <td class="text-center"><?= $item['quantity'] ?></td>
                                <td class="text-end">₹<?= $item['line_total'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end fw-normal text-muted">Subtotal</th>
                            <th class="text-end fw-normal">₹<?= $order['subtotal'] ?></th>
                        </tr>
                        <tr>
                            <th colspan="4" class="text-end fw-normal text-muted">GST (5%)</th>
                            <th class="text-end fw-normal">₹<?= $order['tax_amount'] ?></th>
                        </tr>
                        <tr class="table-light">
                            <th colspan="4" class="text-end fs-6">Total Payable</th>
                            <th class="text-end fs-5">₹<?= $order['total_amount'] ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="text-center text-muted small mt-4 pt-3 border-top">
                Thank you for dining with Restora! Please visit again.
            </div>

        </div>
    </div>

    <!-- Right Column: Payment Settlement Box (Hidden on Print) -->
    <div class="col-lg-4 no-print">
        <div class="content-card">
            <h5>
                <i class="bi bi-wallet2 me-2"></i>
                Payment Settlement
            </h5>

            <?php if ($order['payment_status'] === 'Unpaid'): ?>

                <div class="p-3 bg-light rounded border mb-3">
                    <div class="small text-muted">Amount Due</div>
                    <h3 class="fw-bold mb-0 text-dark">₹<?= $order['total_amount'] ?></h3>
                </div>

                <form method="POST" action="">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Select Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="Cash">Cash</option>
                            <option value="UPI">UPI</option>
                            <option value="Card">Card</option>
                        </select>
                    </div>

                    <button type="submit" name="collect_payment" class="btn btn-restora w-100 py-2">
                        <i class="bi bi-check-circle me-1"></i>
                        Confirm Payment & Free Table
                    </button>
                </form>

            <?php else: ?>

                <div class="alert alert-success mb-3">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    Paid via <strong><?= $order['payment_method'] ?></strong>
                </div>

                <button type="button" onclick="window.print()" class="btn btn-dark w-100 py-2">
                    <i class="bi bi-printer me-1"></i>
                    Print Customer Invoice
                </button>

            <?php endif; ?>
        </div>
    </div>

</div>

<?php
$itemsStmt->close();
include_once "../../includes/footer.php";
?>