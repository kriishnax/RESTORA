<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

$statusFilter = $_GET['status'] ?? 'All';
$allowedStatuses = ['All', 'Pending', 'Accepted', 'Preparing', 'Ready', 'Completed', 'Cancelled'];

if (!in_array($statusFilter, $allowedStatuses)) {
    $statusFilter = 'All';
}

// Fetch orders with table number and staff name
if ($statusFilter === 'All') {
    $sql = "SELECT
                o.id,
                o.order_number,
                o.total_amount,
                o.order_status,
                o.payment_status,
                o.created_at,
                rt.table_number,
                u.name AS created_by_name
            FROM orders o
            INNER JOIN restaurant_tables rt
                ON o.table_id = rt.id
            LEFT JOIN users u
                ON o.user_id = u.id
            ORDER BY o.id DESC";

    $result = $conn->query($sql);
} else {
    $sql = "SELECT
                o.id,
                o.order_number,
                o.total_amount,
                o.order_status,
                o.payment_status,
                o.created_at,
                rt.table_number,
                u.name AS created_by_name
            FROM orders o
            INNER JOIN restaurant_tables rt
                ON o.table_id = rt.id
            LEFT JOIN users u
                ON o.user_id = u.id
            WHERE o.order_status = ?
            ORDER BY o.id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $statusFilter);
    $stmt->execute();
    $result = $stmt->get_result();
}

$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

// Page layout variables
$pageTitle = "Restora | Orders";
$pageHeading = "Dine-In Orders";
$pageSubtitle = "Create, track, and manage customer dine-in orders";

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

<div class="content-card">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h5 class="mb-0">
            <i class="bi bi-receipt me-2"></i>
            All Dine-In Orders
        </h5>

        <a href="create.php" class="btn btn-restora">
            <i class="bi bi-plus-lg me-1"></i>
            New Dine-In Order
        </a>
    </div>

    <!-- Status Filter Buttons -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <?php foreach ($allowedStatuses as $st): ?>
            <a
                href="index.php?status=<?= $st ?>"
                class="btn btn-sm <?= ($statusFilter === $st) ? 'btn-dark' : 'btn-outline-secondary' ?>"
            >
                <?= $st ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">

            <thead>
                <tr>
                    <th>Order Number</th>
                    <th>Table</th>
                    <th>Total Amount</th>
                    <th>Order Status</th>
                    <th>Payment</th>
                    <th>Staff</th>
                    <th>Date & Time</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($order = $result->fetch_assoc()): ?>

                    <?php
                    $statusClass = getOrderStatusBadge($order['order_status']);
                    $paymentClass = getPaymentStatusBadge($order['payment_status']);
                    ?>

                    <tr>
                        <td class="fw-semibold"><?= $order['order_number'] ?></td>
                        <td>
                            <span class="badge text-bg-light border">
                                <?= $order['table_number'] ?>
                            </span>
                        </td>
                        <td class="fw-semibold">₹<?= $order['total_amount'] ?></td>
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
                        <td><?= $order['created_by_name'] ?? 'Admin' ?></td>
                        <td><?= date("d M Y, h:i A", strtotime($order['created_at'])) ?></td>
                        <td>
                            <a href="view.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </td>
                    </tr>

                <?php endwhile; ?>
            <?php else: ?>

                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        No dine-in orders found. Click "New Dine-In Order" to place one.
                    </td>
                </tr>

            <?php endif; ?>
            </tbody>

        </table>
    </div>

</div>

<?php include_once "../../includes/footer.php"; ?>