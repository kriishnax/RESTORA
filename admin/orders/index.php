<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

$sql = "SELECT o.*, rt.table_number, u.name AS staff_name
        FROM orders o
        INNER JOIN restaurant_tables rt ON o.table_id = rt.id
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.id DESC";

$result = $conn->query($sql);

$pageTitle = "Restora | Orders";
$pageHeading = "Dine-In Orders";
$pageSubtitle = "Create, track, and manage customer dine-in orders";

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

<div class="content-card">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>All Dine-In Orders</h5>
        <a href="create.php" class="btn btn-restora">
            <i class="bi bi-plus-lg me-1"></i> New Dine-In Order
        </a>
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
            <?php if ($result->num_rows > 0): ?>
                <?php while ($order = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="fw-semibold"><?= $order['order_number'] ?></td>
                        <td><span class="badge text-bg-light border"><?= $order['table_number'] ?></span></td>
                        <td class="fw-semibold">₹<?= $order['total_amount'] ?></td>
                        <td><span class="badge text-bg-<?= getOrderStatusBadge($order['order_status']) ?>"><?= $order['order_status'] ?></span></td>
                        <td><span class="badge text-bg-<?= getPaymentStatusBadge($order['payment_status']) ?>"><?= $order['payment_status'] ?></span></td>
                        <td><?= $order['staff_name'] ?? 'Admin' ?></td>
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
                    <td colspan="8" class="text-center text-muted py-4">No dine-in orders found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php include_once "../../includes/footer.php"; ?>