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

function handleImageUpload(): ?string {
    if (empty($_FILES['image']['name']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $uploadDir = __DIR__ . '/../assets/images/menu/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $allowed, true)) {
        http_response_code(400);
        echo json_encode([
            "status"  => "error",
            "message" => "Only JPG, PNG, and WEBP images are allowed."
        ]);
        exit;
    }

    $fileName = 'menu_' . time() . '_' . rand(100, 999) . '.' . $ext;
    $targetFile = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
        return 'assets/images/menu/' . $fileName;
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    // 1. Delete Menu Item
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Invalid menu item ID."]);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Menu item deleted!"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Delete failed: " . $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    $categoryId  = (int)($_POST['category_id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $isAvailable = isset($_POST['is_available']) ? (int)$_POST['is_available'] : 1;

    if ($categoryId <= 0 || $name === '' || $price <= 0) {
        http_response_code(400);
        echo json_encode([
            "status"  => "error",
            "message" => "Category, item name, and a valid price are required."
        ]);
        exit;
    }

    $newImagePath = handleImageUpload();

    // 2. Update Existing Menu Item
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Invalid menu item ID."]);
            exit;
        }

        if ($newImagePath !== null) {
            $sql = "UPDATE menu_items
                    SET category_id = ?, name = ?, description = ?, price = ?, image_path = ?, is_available = ?
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issdsii", $categoryId, $name, $description, $price, $newImagePath, $isAvailable, $id);
        } else {
            $sql = "UPDATE menu_items
                    SET category_id = ?, name = ?, description = ?, price = ?, is_available = ?
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issdii", $categoryId, $name, $description, $price, $isAvailable, $id);
        }

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Menu item updated!"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Update failed: " . $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    // 3. Create New Menu Item
    $sql = "INSERT INTO menu_items (category_id, name, description, price, image_path, is_available)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issdsi", $categoryId, $name, $description, $price, $newImagePath, $isAvailable);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Menu item added successfully!"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Database error: " . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// 4. GET: Fetch categories and all menu items
$categories = [];
$catResult = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

$sql = "SELECT m.id, m.category_id, m.name, m.description, m.price, m.image_path, m.is_available,
               c.name AS category_name
        FROM menu_items m
        JOIN categories c ON m.category_id = c.id
        ORDER BY m.id DESC";

$result = $conn->query($sql);
$items = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['image_path'])) {
            $cleanPath = str_replace('\\', '/', $row['image_path']);
            $cleanPath = preg_replace('#^(\.\./|\./|/RESTORA/restora/|/restora/|/)#i', '', $cleanPath);
            if (strpos($cleanPath, '/') === false) {
                $cleanPath = 'assets/images/menu/' . $cleanPath;
            }
            $row['image_path'] = $cleanPath;
        }
        $items[] = $row;
    }
}

echo json_encode([
    "status"     => "success",
    "categories" => $categories,
    "data"       => $items
]);