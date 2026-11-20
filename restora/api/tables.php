<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    $action = $input['action'] ?? '';

    // 0. Add a new restaurant table
    if ($action === 'add_table') {
        $tableNumber = trim($input['table_number'] ?? '');
        $capacity    = (int)($input['capacity'] ?? 4);

        if ($tableNumber === '' || $capacity <= 0) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Table number and valid capacity are required."]);
            exit;
        }

        $chk = $conn->prepare("SELECT id FROM restaurant_tables WHERE table_number = ?");
        $chk->bind_param("s", $tableNumber);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Table '$tableNumber' already exists."]);
            exit;
        }
        $chk->close();

        $stmt = $conn->prepare(
            "INSERT INTO restaurant_tables (table_number, capacity, status) VALUES (?, ?, 'Available')"
        );
        $stmt->bind_param("si", $tableNumber, $capacity);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Table $tableNumber added!"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Database error: " . $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    // 1. Place a new dine-in order on a table (with Customer Name & Mobile Number)
    if ($action === 'create_order') {
        $tableId       = (int)($input['table_id'] ?? 0);
        $customerName  = trim($input['customer_name'] ?? 'Guest');
        $customerPhone = preg_replace('/\D/', '', $input['customer_phone'] ?? '');
        $items         = $input['items'] ?? [];

        if ($tableId <= 0 || empty($items)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Select a table and at least one item."]);
            exit;
        }

        if ($customerName === '') {
            $customerName = 'Guest';
        }

        $userRes = $conn->query("SELECT id FROM users ORDER BY id ASC LIMIT 1");
        $userId = ($userRes && $row = $userRes->fetch_assoc()) ? (int)$row['id'] : 1;

        $totalAmount = 0;
        foreach ($items as $it) {
            $totalAmount += ((float)$it['price']) * ((int)$it['quantity']);
        }

        $orderNumber = 'ORD-' . date('Ymd') . '-' . rand(100, 999);

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare(
                "INSERT INTO orders (order_number, table_id, user_id, customer_name, customer_phone, total_amount, order_status, payment_status)
                 VALUES (?, ?, ?, ?, ?, ?, 'Pending', 'Unpaid')"
            );
            $stmt->bind_param("siissd", $orderNumber, $tableId, $userId, $customerName, $customerPhone, $totalAmount);
            $stmt->execute();
            $orderId = $stmt->insert_id;
            $stmt->close();

            $itemStmt = $conn->prepare(
                "INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price)
                 VALUES (?, ?, ?, ?)"
            );
            foreach ($items as $it) {
                $mId  = (int)$it['menu_item_id'];
                $qty  = (int)$it['quantity'];
                $unit = (float)$it['price'];
                $itemStmt->bind_param("iiid", $orderId, $mId, $qty, $unit);
                $itemStmt->execute();
            }
            $itemStmt->close();

            $upd = $conn->prepare("UPDATE restaurant_tables SET status = 'Occupied' WHERE id = ?");
            $upd->bind_param("i", $tableId);
            $upd->execute();
            $upd->close();

            $conn->commit();
            echo json_encode([
                "status"       => "success",
                "message"      => "Order $orderNumber created for $customerName!",
                "order_number" => $orderNumber
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to place order: " . $e->getMessage()]);
        }
        exit;
    }

    // 2. Settle bill with Payment Method (Cash / UPI / Card) & generate bill data
    if ($action === 'pay_bill') {
        $orderId       = (int)($input['order_id'] ?? 0);
        $tableId       = (int)($input['table_id'] ?? 0);
        $paymentMethod = trim($input['payment_method'] ?? 'Cash');
        $allowedModes  = ['Cash', 'UPI', 'Card'];

        if (!in_array($paymentMethod, $allowedModes, true)) {
            $paymentMethod = 'Cash';
        }

        if ($orderId <= 0 || $tableId <= 0) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Invalid order or table."]);
            exit;
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare(
                "UPDATE orders
                 SET order_status = 'Completed', payment_status = 'Paid', payment_method = ?
                 WHERE id = ?"
            );
            $stmt->bind_param("si", $paymentMethod, $orderId);
            $stmt->execute();
            $stmt->close();

            $upd = $conn->prepare("UPDATE restaurant_tables SET status = 'Available' WHERE id = ?");
            $upd->bind_param("i", $tableId);
            $upd->execute();
            $upd->close();

            $conn->commit();
            echo json_encode([
                "status"         => "success",
                "message"        => "Bill paid via $paymentMethod!",
                "bill_no"        => "INV-" . date('Ymd') . "-" . str_pad((string)$orderId, 4, '0', STR_PAD_LEFT),
                "payment_method" => $paymentMethod,
                "paid_at"        => date('d M Y, h:i A')
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to settle bill: " . $e->getMessage()]);
        }
        exit;
    }
}

// GET: Fetch all tables + active unpaid order & items for each table
$tables = [];
$res = $conn->query("SELECT id, table_number, capacity, status FROM restaurant_tables ORDER BY id ASC");

if ($res) {
    while ($t = $res->fetch_assoc()) {
        $tableId = (int)$t['id'];
        $t['active_order'] = null;

        $ordStmt = $conn->prepare(
            "SELECT id, order_number, customer_name, customer_phone, total_amount, order_status AS status, payment_status, created_at
             FROM orders
             WHERE table_id = ? AND payment_status = 'Unpaid' AND order_status != 'Cancelled'
             ORDER BY id DESC LIMIT 1"
        );
        $ordStmt->bind_param("i", $tableId);
        $ordStmt->execute();
        $ordRes = $ordStmt->get_result();

        if ($order = $ordRes->fetch_assoc()) {
            $orderId = (int)$order['id'];
            $itemsStmt = $conn->prepare(
                "SELECT oi.quantity, oi.unit_price, (oi.quantity * oi.unit_price) AS subtotal, m.name
                 FROM order_items oi
                 JOIN menu_items m ON oi.menu_item_id = m.id
                 WHERE oi.order_id = ?"
            );
            $itemsStmt->bind_param("i", $orderId);
            $itemsStmt->execute();
            $itemsRes = $itemsStmt->get_result();

            $orderItems = [];
            while ($ir = $itemsRes->fetch_assoc()) {
                $orderItems[] = $ir;
            }
            $itemsStmt->close();

            $order['items'] = $orderItems;
            $t['active_order'] = $order;
        }
        $ordStmt->close();

        $tables[] = $t;
    }
}

echo json_encode([
    "status" => "success",
    "data" => $tables
]);