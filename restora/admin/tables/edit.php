<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

// Validate table ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?err=Invalid table ID.");
    exit();
}

$id = (int)$_GET['id'];
$error = "";

// Fetch existing table details
$sql = "SELECT id, table_number, capacity, status
        FROM restaurant_tables
        WHERE id = ?
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $stmt->close();
    header("Location: index.php?err=Dining table not found.");
    exit();
}

$table = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $tableNumber = trim($_POST['table_number'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 0);
    $status = $_POST['status'] ?? 'Available';
    $allowedStatuses = ['Available', 'Occupied', 'Reserved'];

    if (empty($tableNumber) || $capacity <= 0 || !in_array($status, $allowedStatuses)) {

        $error = "Please enter a valid table number, seating capacity, and status.";

    } else {

        // Check if another table already uses this table number
        $checkSql = "SELECT id FROM restaurant_tables WHERE table_number = ? AND id != ? LIMIT 1";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("si", $tableNumber, $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult && $checkResult->num_rows > 0) {

            $error = "Another table with this number already exists.";
            $checkStmt->close();

        } else {

            $checkStmt->close();

            // Update dining table
            $updateSql = "UPDATE restaurant_tables
                          SET table_number = ?, capacity = ?, status = ?
                          WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);

            if ($updateStmt) {
                $updateStmt->bind_param("sisi", $tableNumber, $capacity, $status, $id);

                if ($updateStmt->execute()) {
                    $updateStmt->close();
                    header("Location: index.php?msg=Table updated successfully.");
                    exit();
                } else {
                    $error = "Failed to update table. Please try again.";
                }

                $updateStmt->close();
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }

    // Keep entered values on validation error
    $table['table_number'] = $tableNumber;
    $table['capacity'] = $capacity;
    $table['status'] = $status;
}

// Page layout variables
$pageTitle = "Restora | Edit Table";
$pageHeading = "Edit Dining Table";
$pageSubtitle = "Update table number, seating capacity, or status";

include_once "../../includes/header.php";
include_once "../../includes/sidebar.php";
include_once "../../includes/navbar.php";
?>

<div class="row">
    <div class="col-md-7 col-lg-6">

        <div class="content-card">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">
                    <i class="bi bi-pencil-square me-2"></i>
                    Edit Dining Table
                </h5>

                <a href="index.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="mb-3">
                    <label class="form-label">Table Number <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="table_number"
                        class="form-control"
                        value="<?= $table['table_number'] ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Seating Capacity <span class="text-danger">*</span></label>
                    <input
                        type="number"
                        name="capacity"
                        min="1"
                        max="30"
                        class="form-control"
                        value="<?= $table['capacity'] ?>"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="Available" <?= ($table['status'] === 'Available') ? 'selected' : '' ?>>Available</option>
                        <option value="Occupied" <?= ($table['status'] === 'Occupied') ? 'selected' : '' ?>>Occupied</option>
                        <option value="Reserved" <?= ($table['status'] === 'Reserved') ? 'selected' : '' ?>>Reserved</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-restora">
                    <i class="bi bi-check-lg me-1"></i>
                    Update Table
                </button>

            </form>

        </div>

    </div>
</div>

<?php include_once "../../includes/footer.php"; ?>