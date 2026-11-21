<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php?err=Invalid category ID.");
    exit();
}


$check = $conn->prepare("SELECT id FROM menu_items WHERE category_id = ? LIMIT 1");
$check->bind_param("i", $id);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    header("Location: index.php?err=Cannot delete category because menu items are assigned to it.");
    exit();
}

$stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: index.php?msg=Category deleted successfully.");
} else {
    header("Location: index.php?err=Failed to delete category.");
}
exit();