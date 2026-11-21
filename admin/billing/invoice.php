<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

$orderId = (int)($_GET['order_id'] ?? $_GET['id'] ?? 0);
if ($orderId <= 0) {
    header("Location: index.php?err=Invalid order ID.");
    exit();
}

$err = "";

// Handle payment collection
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['collect_payment'])) {
    $method = $_POST['payment_method'] ?? '';

    if (!in_array($method, ['Cash', 'UPI', 'Card'])) {
        $err = "Select a valid payment method.";
    } else {
        $ord = $conn->query("SELECT table_id, payment_status FROM orders WHERE id = $orderId")->fetch_assoc();

        if (!$ord) {
            header("Location: index.php?err=Order not found.");
            exit();
        }
        if ($ord['payment_status'] === 'Paid') {
            header("Location: invoice.php?order_id=$orderId&msg=Already marked as Paid.");
            exit();
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE orders SET payment_status = 'Paid', payment_method = ?, order_status = 'Completed' WHERE id = ?");
            $stmt->bind_param("si", $method, $orderId);
            $stmt->execute();

            $tableId = (int)$ord['table_id'];
            $active = $conn->query("SELECT id FROM orders WHERE table_id = $tableId AND id != $orderId AND order_status NOT IN ('Completed', 'Cancelled') LIMIT 1");
            if ($tableId > 0 && $active->num_rows === 0) {
                $conn->query("UPDATE restaurant_tables SET status = 'Available' WHERE id = $tableId");
            }

            $conn->commit();
            header("Location: invoice.php?order_id=$orderId&msg=Payment received via $method.");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $err = "Failed to record payment.";
        }
    }
}

// Fetch order details
$stmt = $conn->prepare("SELECT o.*, rt.table_number, u.name AS staff_name
                        FROM orders o
                        INNER JOIN restaurant_tables rt ON o.table_id = rt.id
                        LEFT JOIN users u ON o.user_id = u.id
                        WHERE o.id = ? LIMIT 1");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: index.php?err=Order not found.");
    exit();
}

// Fetch line items
$itemsStmt = $conn->prepare("SELECT oi.*, (oi.quantity * oi.unit_price) AS line_total, m.name AS item_name
                             FROM order_items oi
                             INNER JOIN menu_items m ON oi.menu_item_id = m.id
                             WHERE oi.order_id = ?
                             ORDER BY oi.id ASC");
$itemsStmt->bind_param("i", $orderId);
$itemsStmt->execute();
$items = $itemsStmt->get_result();

$pageTitle = "Restora | Bill " . $order['order_number'];
$pageHeading = "Customer Bill";
$pageSubtitle = "Order summary and payment settlement";

include_once "../../includes/header.php";
include_once "../../includes/sidebar.php";
include_once "../../includes/navbar.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Bill #<?= $order['order_number'] ?></h5>
    <a href="index.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Billing
    </a>
</div>

<?php if (!empty($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $_GET['msg'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($err != ""): ?>
    <div class="alert alert-danger py-2"><?= $err ?></div>
<?php endif; ?>

<div class="row g-4">

    <!-- Bill Details -->
    <div class="col-lg-7">
        <div class="content-card">
            <div class="d-flex justify-content-between border-bottom pb-3 mb-3">
                <div>
                    <h5 class="mb-1">RESTORA</h5>
                    <small class="text-muted">Table: <?= $order['table_number'] ?> | Date: <?= date("d M Y, h:i A", strtotime($order['created_at'])) ?></small>
                </div>
                <div class="text-end">
                    <span class="badge text-bg-<?= getPaymentStatusBadge($order['payment_status']) ?>">
                        <?= $order['payment_status'] ?>
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Rate</th>
                            <th>Qty</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1; while ($item = $items->fetch_assoc()): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= $item['item_name'] ?></td>
                            <td>₹<?= $item['unit_price'] ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td class="text-end">₹<?= $item['line_total'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="4" class="text-end">Total</th>
                            <th class="text-end">₹<?= $order['subtotal'] ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Payment Settlement -->
    <div class="col-lg-5">
        <div class="content-card">
            <h5><i class="bi bi-wallet2 me-2"></i>Payment Settlement</h5>

            <?php if ($order['payment_status'] === 'Unpaid'): ?>

                <div class="p-3 bg-light rounded border mb-3">
                    <div class="small text-muted">Total Payable</div>
                    <h3 class="fw-bold mb-0">₹<?= $order['subtotal'] ?></h3>
                </div>

                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Payment Method</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="Cash">Cash</option>
                            <option value="UPI">UPI</option>
                            <option value="Card">Card</option>
                        </select>
                    </div>

                    <button type="submit" name="collect_payment" class="btn btn-restora w-100">
                        Confirm Payment & Free Table
                    </button>
                </form>

            <?php else: ?>

                <div class="alert alert-success mb-0">
                    Paid via <strong><?= $order['payment_method'] ?></strong>
                </div>

            <?php endif; ?>
        </div>
    </div>

</div>

<?php include_once "../../includes/footer.php"; ?>