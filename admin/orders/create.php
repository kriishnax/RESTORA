<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

$error = "";
$tables = $conn->query("SELECT * FROM restaurant_tables ORDER BY table_number ASC");

$menuRes = $conn->query("SELECT m.*, c.name AS category_name
                         FROM menu_items m
                         INNER JOIN categories c ON m.category_id = c.id
                         WHERE m.is_available = 1
                         ORDER BY c.name ASC, m.name ASC");

$menuItems = [];
while ($row = $menuRes->fetch_assoc()) {
    $menuItems[$row['id']] = $row;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tableId = (int)($_POST['table_id'] ?? 0);
    $quantities = $_POST['qty'] ?? [];
    $selected = [];
    $subtotal = 0;

    foreach ($quantities as $id => $qty) {
        $qty = (int)$qty;
        if ($qty > 0 && isset($menuItems[$id])) {
            $price = (float)$menuItems[$id]['price'];
            $lineTotal = round($price * $qty, 2);
            $subtotal += $lineTotal;

            $selected[] = [
                'id'    => (int)$id,
                'name'  => $menuItems[$id]['name'],
                'qty'   => $qty,
                'price' => $price,
                'total' => $lineTotal
            ];
        }
    }

    if ($tableId <= 0) {
        $error = "Please select a dining table.";
    } elseif (empty($selected)) {
        $error = "Please select at least one menu item.";
    } else {
        $conn->begin_transaction();

        try {
            $orderNo = generateOrderNumber($conn);
            $tax = round($subtotal * 0.05, 2);
            $total = round($subtotal + $tax, 2);
            $userId = $_SESSION['user_id'] ?? 1;

            $stmt = $conn->prepare("INSERT INTO orders (order_number, table_id, user_id, subtotal, tax_amount, total_amount, order_status, payment_status) VALUES (?, ?, ?, ?, ?, ?, 'Pending', 'Unpaid')");
            $stmt->bind_param("siiddd", $orderNo, $tableId, $userId, $subtotal, $tax, $total);
            $stmt->execute();
            $orderId = $conn->insert_id;

            $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, menu_item_id, item_name, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($selected as $item) {
                $itemStmt->bind_param("iisidd", $orderId, $item['id'], $item['name'], $item['qty'], $item['price'], $item['total']);
                $itemStmt->execute();
            }

            $tblStmt = $conn->prepare("UPDATE restaurant_tables SET status = 'Occupied' WHERE id = ?");
            $tblStmt->bind_param("i", $tableId);
            $tblStmt->execute();

            $conn->commit();
            header("Location: view.php?id=$orderId&msg=Dine-in order placed successfully.");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to place order. Please try again.";
        }
    }
}

$pageTitle = "Restora | New Dine-In Order";
$pageHeading = "New Dine-In Order";
$pageSubtitle = "Select a dining table and add food items";

include_once "../../includes/header.php";
include_once "../../includes/sidebar.php";
include_once "../../includes/navbar.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Create Customer Order</h5>
    <a href="index.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Orders
    </a>
</div>

<?php if ($error != ""): ?>
    <div class="alert alert-danger py-2"><?= $error ?></div>
<?php endif; ?>

<form method="POST">
    <div class="row g-4">

        <!-- Left Column: Table & Order Total -->
        <div class="col-lg-4">
            <div class="content-card">
                <h5><i class="bi bi-grid-3x3-gap me-2"></i>Table Selection</h5>

                <div class="mb-4">
                    <label class="form-label">Select Dining Table <span class="text-danger">*</span></label>
                    <select name="table_id" class="form-select" required>
                        <option value="">-- Choose Table --</option>
                        <?php while ($tbl = $tables->fetch_assoc()): ?>
                            <option value="<?= $tbl['id'] ?>" <?= (($_POST['table_id'] ?? '') == $tbl['id']) ? 'selected' : '' ?>>
                                <?= $tbl['table_number'] ?> (<?= $tbl['capacity'] ?> Seats - <?= $tbl['status'] ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="p-3 bg-light rounded border mb-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Subtotal:</span>
                        <span>₹<span id="liveSubtotal">0.00</span></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">GST (5%):</span>
                        <span>₹<span id="liveTax">0.00</span></span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Grand Total:</span>
                        <h4 class="mb-0 fw-bold">₹<span id="liveTotal">0.00</span></h4>
                    </div>
                </div>

                <button type="submit" class="btn btn-restora w-100 py-2">
                    <i class="bi bi-check-circle me-1"></i> Place Dine-In Order
                </button>
            </div>
        </div>

        <!-- Right Column: Menu Items Selection -->
        <div class="col-lg-8">
            <div class="content-card">
                <h5><i class="bi bi-egg-fried me-2"></i>Select Menu Items</h5>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th style="width: 110px;">Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($menuItems): ?>
                            <?php foreach ($menuItems as $item): ?>
                                <tr>
                                    <td class="fw-semibold"><?= $item['name'] ?></td>
                                    <td><span class="badge text-bg-light border"><?= $item['category_name'] ?></span></td>
                                    <td>₹<?= $item['price'] ?></td>
                                    <td>
                                        <input type="number" name="qty[<?= $item['id'] ?>]" min="0" max="50" value="<?= $_POST['qty'][$item['id']] ?? 0 ?>" data-price="<?= $item['price'] ?>" class="form-control form-control-sm qty-input">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No available menu items found.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</form>

<script>
    const inputs = document.querySelectorAll('.qty-input');

    function updateTotal() {
        let sub = 0;
        inputs.forEach(el => sub += (parseInt(el.value) || 0) * parseFloat(el.dataset.price));
        const tax = sub * 0.05;

        document.getElementById('liveSubtotal').textContent = sub.toFixed(2);
        document.getElementById('liveTax').textContent = tax.toFixed(2);
        document.getElementById('liveTotal').textContent = (sub + tax).toFixed(2);
    }

    inputs.forEach(el => el.addEventListener('input', updateTotal));
    updateTotal();
</script>

<?php include_once "../../includes/footer.php"; ?>