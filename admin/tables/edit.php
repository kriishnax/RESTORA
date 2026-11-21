<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: index.php?err=Invalid table ID.");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM restaurant_tables WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$table = $stmt->get_result()->fetch_assoc();

if (!$table) {
    header("Location: index.php?err=Dining table not found.");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tableNumber = trim($_POST['table_number'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 0);
    $status = $_POST['status'] ?? 'Available';

    if ($tableNumber == "" || $capacity <= 0) {
        $error = "Please enter a valid table number and seating capacity.";
    } else {
        $check = $conn->prepare("SELECT id FROM restaurant_tables WHERE table_number = ? AND id != ? LIMIT 1");
        $check->bind_param("si", $tableNumber, $id);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = "Another table with this number already exists.";
        } else {
            $update = $conn->prepare("UPDATE restaurant_tables SET table_number = ?, capacity = ?, status = ? WHERE id = ?");
            $update->bind_param("sisi", $tableNumber, $capacity, $status, $id);

            if ($update->execute()) {
                header("Location: index.php?msg=Table updated successfully.");
                exit();
            }
            $error = "Failed to update table.";
        }
    }

    $table['table_number'] = $tableNumber;
    $table['capacity'] = $capacity;
    $table['status'] = $status;
}

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
                <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Dining Table</h5>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>

            <?php if ($error != ""): ?>
                <div class="alert alert-danger py-2"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Table Number <span class="text-danger">*</span></label>
                    <input type="text" name="table_number" class="form-control" value="<?= $table['table_number'] ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Seating Capacity <span class="text-danger">*</span></label>
                    <input type="number" name="capacity" min="1" max="30" class="form-control" value="<?= $table['capacity'] ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['Available', 'Occupied', 'Reserved'] as $opt): ?>
                            <option value="<?= $opt ?>" <?= ($table['status'] === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-restora">
                    <i class="bi bi-check-lg me-1"></i> Update Table
                </button>
            </form>

        </div>
    </div>
</div>

<?php include_once "../../includes/footer.php"; ?>