<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

// Validate menu item ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?err=Invalid menu item ID.");
    exit();
}

$id = (int)$_GET['id'];

// Check if menu item is referenced in any order_items
$checkSql = "SELECT COUNT(*) AS total FROM order_items WHERE menu_item_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$orderCount = $checkResult ? (int)$checkResult->fetch_assoc()['total'] : 0;
$checkStmt->close();

if ($orderCount > 0) {
    header("Location: index.php?err=Cannot delete this item because it is part of existing orders. Edit it and uncheck Available instead.");
    exit();
}

// Fetch image_path before deleting record
$imgSql = "SELECT image_path FROM menu_items WHERE id = ? LIMIT 1";
$imgStmt = $conn->prepare($imgSql);
$imgStmt->bind_param("i", $id);
$imgStmt->execute();
$imgResult = $imgStmt->get_result();

if (!$imgResult || $imgResult->num_rows === 0) {
    $imgStmt->close();
    header("Location: index.php?err=Menu item not found.");
    exit();
}

$row = $imgResult->fetch_assoc();
$imagePath = $row['image_path'] ?? '';
$imgStmt->close();

// Delete menu item
$sql = "DELETE FROM menu_items WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $stmt->close();

        // Delete image file if present
        $fullPath = "../../assets/images/menu/" . $imagePath;
        if (!empty($imagePath) && file_exists($fullPath)) {
            unlink($fullPath);
        }

        header("Location: index.php?msg=Menu item deleted successfully.");
        exit();
    }

    $stmt->close();
}

header("Location: index.php?err=Failed to delete menu item.");
exit();