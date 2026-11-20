<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

// Validate order ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?err=Invalid order ID.");
    exit();
}

$id = (int)$_GET['id'];
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

// Handle order status update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['order_status'])) {

    $newStatus = $_POST['order_status'];
    $allowedStatuses = ['Pending', 'Accepted', 'Preparing', 'Ready', 'Completed', 'Cancelled'];

    if (in_array($newStatus, $allowedStatuses)) {

        // Fetch table_id before updating
        $tblQuery = $conn->prepare("SELECT table_id FROM orders WHERE id = ? LIMIT 1");
        $tblQuery->bind_param("i", $id);
        $tblQuery->execute();
        $tblRes = $tblQuery->get_result();
        $tableId = ($tblRes && $tblRes->num_rows > 0) ? (int)$tblRes->fetch_assoc()['table_id'] : 0;
        $tblQuery->close();

        $updateSql = "UPDATE orders SET order_status = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $newStatus, $id);

        if ($updateStmt->execute()) {
            $updateStmt->close();

            // Free table if order is cancelled and no other active orders exist for this table
            if ($newStatus === 'Cancelled' && $tableId > 0) {
                $activeCheck = $conn->prepare(
                    "SELECT COUNT(*) AS total
                     FROM orders
                     WHERE table_id = ?
                     AND id != ?
                     AND order_status NOT IN ('Completed', 'Cancelled')"
                );
                $activeCheck->bind_param("ii", $tableId, $id);
                $activeCheck->execute();
                $activeCount = (int)$activeCheck->get_result()->fetch_assoc()['total'];
                $activeCheck->close();

                if ($activeCount === 0) {
                    $freeTable = $conn->prepare("UPDATE restaurant_tables SET status = 'Available' WHERE id = ?");
                    $freeTable->bind_param("i", $tableId);
                    $freeTable->execute();
                    $freeTable->close();
                }
            }

            header("Location: view.php?id=" . $id . "&msg=Order status updated to " . $newStatus . ".");
            exit();
        } else {
            $err = "Failed to update order status.";
            $updateStmt->close();
        }
    }
}

// Fetch main order details
$sql = "SELECT
            o.id,
            o.order_number,
            o.table_id,
            o.subtotal,
            o.tax_amount,
            o.total_amount,
            o.order_status,
            o.payment_status,
            o.payment_method,
            o.created_at,
            rt.table_number,
            rt.capacity,
            u.name AS created_by_name
        FROM orders o
        INNER JOIN restaurant_tables rt
            ON o.table_id = rt.id
        LEFT JOIN users u
            ON o.user_id = u.id
        WHERE o.id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $stmt->close();
    header("Location: index.php?err=Order not found.");
    exit();
}

$order = $result->fetch_assoc();
$stmt->close();

// Fetch ordered food items
$itemsSql = "SELECT
                oi.id,
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
$itemsStmt->bind_param("i", $id);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();

$statusClass = getOrderStatusBadge($order['order_status']);
$paymentClass = getPaymentStatusBadge($order['payment_status']);

// Page layout variables
$pageTitle = "Restora | Order " . $order['order_number'];
$pageHeading = "Order Details";
$pageSubtitle = "Track kitchen progress and view ordered items";

include_once "../../includes/header.php";
include_once "../../includes/sidebar.php";
include_once "../../includes/navbar.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-bold"><?= $order['order_number'] ?></h5>
    <a href="index.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Orders
    </a>
</div>

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

<div class="row g-4">

    <!-- Left Column: Order Summary & Status Control -->
    <div class="col-lg-5">
        <div class="content-card">
            <h5>
                <i class="bi bi-info-circle me-2"></i>
                Order Summary
            </h5>

            <table class="table table-borderless mb-3">
                <tr>
                    <th class="text-muted ps-0">Order Number:</th>
                    <td class="fw-semibold"><?= $order['order_number'] ?></td>
                </tr>
                <tr>
                    <th class="text-muted ps-0">Dining Table:</th>
                    <td><?= $order['table_number'] ?> (<?= $order['capacity'] ?> Seats)</td>
                </tr>
                <tr>
                    <th class="text-muted ps-0">Order Status:</th>
                    <td>
                        <span class="badge text-bg-<?= $statusClass ?>">
                            <?= $order['order_status'] ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th class="text-muted ps-0">Payment Status:</th>
                    <td>
                        <span class="badge text-bg-<?= $paymentClass ?>">
                            <?= $order['payment_status'] ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th class="text-muted ps-0">Placed By:</th>
                    <td><?= $order['created_by_name'] ?? 'Admin' ?></td>
                </tr>
                <tr>
                    <th class="text-muted ps-0">Date & Time:</th>
                    <td><?= date("d M Y, h:i A", strtotime($order['created_at'])) ?></td>
                </tr>
            </table>

            <hr>

            <!-- Update Kitchen / Order Status -->
            <form method="POST" action="" class="mb-3">
                <label class="form-label fw-semibold">Update Order Status</label>
                <div class="input-group">
                    <select name="order_status" class="form-select">
                        <?php
                        $statuses = ['Pending', 'Accepted', 'Preparing', 'Ready', 'Completed', 'Cancelled'];
                        foreach ($statuses as $st):
                        ?>
                            <option value="<?= $st ?>" <?= ($order['order_status'] === $st) ? 'selected' : '' ?>>
                                <?= $st ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-dark">
                        Update
                    </button>
                </div>
            </form>

            <!-- Billing Link -->
            <?php if ($order['order_status'] !== 'Cancelled'): ?>
                <a href="../billing/invoice.php?order_id=<?= $order['id'] ?>" class="btn btn-restora w-100">
                    <i class="bi bi-receipt-cutoff me-1"></i>
                    <?= ($order['payment_status'] === 'Paid') ? 'View Paid Invoice' : 'Proceed to Billing & Payment' ?>
                </a>
            <?php endif; ?>

        </div>
    </div>

    <!-- Right Column: Ordered Food Items -->
    <div class="col-lg-7">
        <div class="content-card">
            <h5>
                <i class="bi bi-list-ul me-2"></i>
                Ordered Items
            </h5>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item Name</th>
                            <th>Unit Price</th>
                            <th>Qty</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($itemsResult && $itemsResult->num_rows > 0): ?>
                        <?php $i = 1; ?>
                        <?php while ($item = $itemsResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td class="fw-semibold"><?= $item['item_name'] ?></td>
                                <td>₹<?= $item['unit_price'] ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td class="text-end fw-semibold">₹<?= $item['line_total'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end text-muted">Subtotal:</th>
                            <th class="text-end">₹<?= $order['subtotal'] ?></th>
                        </tr>
                        <tr>
                            <th colspan="4" class="text-end text-muted">GST (5%):</th>
                            <th class="text-end">₹<?= $order['tax_amount'] ?></th>
                        </tr>
                        <tr class="table-light">
                            <th colspan="4" class="text-end fs-6">Grand Total:</th>
                            <th class="text-end fs-5 text-dark">₹<?= $order['total_amount'] ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div>

<?php
$itemsStmt->close();
include_once "../../includes/footer.php";
?>