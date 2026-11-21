<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: index.php?err=Invalid table ID.");
    exit();
}

// Check if any orders are linked to this table
$check = $conn->prepare("SELECT id FROM orders WHERE table_id = ? LIMIT 1");
$check->bind_param("i", $id);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    header("Location: index.php?err=Cannot delete this table because orders exist for it.");
    exit();
}

// Delete dining table
$stmt = $conn->prepare("DELETE FROM restaurant_tables WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: index.php?msg=Table deleted successfully.");
} else {
    header("Location: index.php?err=Failed to delete table.");
}
exit();