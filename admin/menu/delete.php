<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: index.php?err=Invalid menu item ID.");
    exit();
}

// Check if menu item is used in any orders
$check = $conn->prepare("SELECT id FROM order_items WHERE menu_item_id = ? LIMIT 1");
$check->bind_param("i", $id);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    header("Location: index.php?err=Cannot delete this item because it is part of existing orders. Uncheck Available instead.");
    exit();
}

// Fetch image path before deleting
$img = $conn->prepare("SELECT image_path FROM menu_items WHERE id = ? LIMIT 1");
$img->bind_param("i", $id);
$img->execute();
$item = $img->get_result()->fetch_assoc();

if (!$item) {
    header("Location: index.php?err=Menu item not found.");
    exit();
}

// Delete menu item and its image file
$stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $file = "../../assets/images/menu/" . $item['image_path'];
    if ($item['image_path'] && file_exists($file)) {
        unlink($file);
    }
    header("Location: index.php?msg=Menu item deleted successfully.");
} else {
    header("Location: index.php?err=Failed to delete menu item.");
}
exit();