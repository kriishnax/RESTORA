<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

// Validate category ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?err=Invalid category ID.");
    exit();
}

$id = (int)$_GET['id'];

// Check if any menu items belong to this category
$checkSql = "SELECT COUNT(*) AS total FROM menu_items WHERE category_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$itemCount = $checkResult ? (int)$checkResult->fetch_assoc()['total'] : 0;
$checkStmt->close();

if ($itemCount > 0) {
    header("Location: index.php?err=Cannot delete category because menu items are assigned to it.");
    exit();
}

// Delete category
$sql = "DELETE FROM categories WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: index.php?msg=Category deleted successfully.");
        exit();
    }

    $stmt->close();
}

header("Location: index.php?err=Failed to delete category.");
exit();