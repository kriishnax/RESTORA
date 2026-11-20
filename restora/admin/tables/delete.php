<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

// Validate table ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?err=Invalid table ID.");
    exit();
}

$id = (int)$_GET['id'];

// Check if any orders are linked to this table
$checkSql = "SELECT COUNT(*) AS total FROM orders WHERE table_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$orderCount = $checkResult ? (int)$checkResult->fetch_assoc()['total'] : 0;
$checkStmt->close();

if ($orderCount > 0) {
    header("Location: index.php?err=Cannot delete this table because orders exist for it.");
    exit();
}

// Delete dining table
$sql = "DELETE FROM restaurant_tables WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: index.php?msg=Table deleted successfully.");
        exit();
    }

    $stmt->close();
}

header("Location: index.php?err=Failed to delete table.");
exit();