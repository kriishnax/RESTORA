<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";
require_once "../../includes/functions.php";

$error = "";

// Fetch dining tables
$tablesResult = $conn->query(
    "SELECT id, table_number, capacity, status
     FROM restaurant_tables
     ORDER BY table_number ASC"
);

// Fetch available menu items with category name
$menuResult = $conn->query(
    "SELECT m.id, m.name, m.price, c.name AS category_name
     FROM menu_items m
     INNER JOIN categories c ON m.category_id = c.id
     WHERE m.is_available = 1
     ORDER BY c.name ASC, m.name ASC"
);

$menuItems = [];
if ($menuResult && $menuResult->num_rows > 0) {
    while ($row = $menuResult->fetch_assoc()) {
        $menuItems[$row['id']] = $row;
    }
}

// Handle order submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $tableId = (int)($_POST['table_id'] ?? 0);
    $quantities = $_POST['qty'] ?? [];
    $userId = $_SESSION['user_id'] ?? 1;

    // Filter selected items with quantity > 0
    $selectedItems = [];
    $orderSubtotal = 0;

    foreach ($quantities as $itemId => $qty) {
        $itemId = (int)$itemId;
        $qty = (int)$qty;

        if ($qty > 0 && isset($menuItems[$itemId])) {
            $unitPrice = (float)$menuItems[$itemId]['price'];
            $lineTotal = round($unitPrice * $qty, 2);
            $orderSubtotal += $lineTotal;

            $selectedItems[] = [
                'menu_item_id' => $itemId,
                'item_name'    => $menuItems[$itemId]['name'],
                'quantity'     => $qty,
                'unit_price'   => $unitPrice,
                'line_total'   => $lineTotal
            ];
        }
    }

    if ($tableId <= 0) {
        $error = "Please select a dining table.";
    } elseif (count($selectedItems) === 0) {
        $error = "Please enter quantity (1 or more) for at least one menu item.";
    } else {

        $conn->begin_transaction();

        try {
            $orderNumber = generateOrderNumber($conn);
            $taxAmount = round($orderSubtotal * 0.05, 2); // 5% GST
            $totalAmount = round($orderSubtotal + $taxAmount, 2);
            $orderStatus = "Pending";
            $paymentStatus = "Unpaid";

            // 1. Insert into orders
            $orderSql = "INSERT INTO orders (order_number, table_id, user_id, subtotal, tax_amount, total_amount, order_status, payment_status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $orderStmt = $conn->prepare($orderSql);
            $orderStmt->bind_param("siidddss", $orderNumber, $tableId, $userId, $orderSubtotal, $taxAmount, $totalAmount, $orderStatus, $paymentStatus);
            $orderStmt->execute();
            $orderId = $conn->insert_id;
            $orderStmt->close();

            // 2. Insert each item into order_items
            $itemSql = "INSERT INTO order_items (order_id, menu_item_id, item_name, quantity, unit_price, line_total)
                        VALUES (?, ?, ?, ?, ?, ?)";
            $itemStmt = $conn->prepare($itemSql);

            foreach ($selectedItems as $item) {
                $itemStmt->bind_param(
                    "iisidd",
                    $orderId,
                    $item['menu_item_id'],
                    $item['item_name'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['line_total']
                );
                $itemStmt->execute();
            }
            $itemStmt->close();

            // 3. Mark dining table as Occupied
            $tableSql = "UPDATE restaurant_tables SET status = 'Occupied' WHERE id = ?";
            $tableStmt = $conn->prepare($tableSql);
            $tableStmt->bind_param("i", $tableId);
            $tableStmt->execute();
            $tableStmt->close();

            $conn->commit();

            header("Location: view.php?id=" . $orderId . "&msg=Dine-in order placed successfully.");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to place order. Please try again.";
        }
    }
}

// Page layout variables
$pageTitle = "Restora | New Dine-In Order";
$pageHeading = "New Dine-In Order";
$pageSubtitle = "Select a dining table and add food items";

include_once "../../includes/header.php";
include_once "../../includes/sidebar.php";
include_once "../../includes/navbar.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-bold">Create Customer Order</h5>
    <a href="index.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Orders
    </a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <?= $error ?>
    </div>
<?php endif; ?>

<form method="POST" action="">
    <div class="row g-4">

        <!-- Left Column: Table & Order Total -->
        <div class="col-lg-4">
            <div class="content-card">
                <h5>
                    <i class="bi bi-grid-3x3-gap me-2"></i>
                    Table Selection
                </h5>

                <div class="mb-4">
                    <label class="form-label">Select Dining Table <span class="text-danger">*</span></label>
                    <select name="table_id" class="form-select" required>
                        <option value="">-- Choose Table --</option>
                        <?php if ($tablesResult && $tablesResult->num_rows > 0): ?>
                            <?php while ($tbl = $tablesResult->fetch_assoc()): ?>
                                <option
                                    value="<?= $tbl['id'] ?>"
                                    <?= (($_POST['table_id'] ?? '') == $tbl['id']) ? 'selected' : '' ?>
                                >
                                    <?= $tbl['table_number'] ?> (<?= $tbl['capacity'] ?> Seats - <?= $tbl['status'] ?>)
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
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
                        <h4 class="mb-0 fw-bold text-dark">₹<span id="liveTotal">0.00</span></h4>
                    </div>
                </div>

                <button type="submit" class="btn btn-restora w-100 py-2">
                    <i class="bi bi-check-circle me-1"></i>
                    Place Dine-In Order
                </button>
            </div>
        </div>

        <!-- Right Column: Menu Items Selection -->
        <div class="col-lg-8">
            <div class="content-card">
                <h5>
                    <i class="bi bi-egg-fried me-2"></i>
                    Select Menu Items
                </h5>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th style="width: 120px;">Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($menuItems) > 0): ?>
                            <?php foreach ($menuItems as $item): ?>
                                <tr>
                                    <td class="fw-semibold"><?= $item['name'] ?></td>
                                    <td>
                                        <span class="badge text-bg-light border">
                                            <?= $item['category_name'] ?>
                                        </span>
                                    </td>
                                    <td>₹<?= $item['price'] ?></td>
                                    <td>
                                        <input
                                            type="number"
                                            name="qty[<?= $item['id'] ?>]"
                                            min="0"
                                            max="50"
                                            value="<?= $_POST['qty'][$item['id']] ?? 0 ?>"
                                            data-price="<?= $item['price'] ?>"
                                            class="form-control form-control-sm qty-input"
                                        >
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No available menu items found. Add items in Menu Management first.
                                </td>
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
    const qtyInputs = document.querySelectorAll('.qty-input');
    const liveSubtotalEl = document.getElementById('liveSubtotal');
    const liveTaxEl = document.getElementById('liveTax');
    const liveTotalEl = document.getElementById('liveTotal');

    function updateOrderTotal() {
        let subtotal = 0;
        qtyInputs.forEach(function (input) {
            const qty = parseInt(input.value) || 0;
            const price = parseFloat(input.getAttribute('data-price')) || 0;
            if (qty > 0) {
                subtotal += qty * price;
            }
        });
        const tax = subtotal * 0.05;
        const total = subtotal + tax;

        liveSubtotalEl.textContent = subtotal.toFixed(2);
        liveTaxEl.textContent = tax.toFixed(2);
        liveTotalEl.textContent = total.toFixed(2);
    }

    qtyInputs.forEach(function (input) {
        input.addEventListener('input', updateOrderTotal);
    });

    updateOrderTotal();
</script>

<?php include_once "../../includes/footer.php"; ?>