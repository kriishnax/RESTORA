<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tableNumber = trim($_POST['table_number'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 0);
    $status = $_POST['status'] ?? 'Available';

    if ($tableNumber == "" || $capacity <= 0) {
        $error = "Please enter a valid table number and seating capacity.";
    } else {
        $check = $conn->prepare("SELECT id FROM restaurant_tables WHERE table_number = ? LIMIT 1");
        $check->bind_param("s", $tableNumber);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = "This table number already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO restaurant_tables (table_number, capacity, status) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $tableNumber, $capacity, $status);

            if ($stmt->execute()) {
                header("Location: index.php?msg=Table added successfully.");
                exit();
            }
            $error = "Failed to add table.";
        }
    }
}

$pageTitle = "Restora | Add Table";
$pageHeading = "Add Dining Table";
$pageSubtitle = "Create a new restaurant dining table";

include_once "../../includes/header.php";
include_once "../../includes/sidebar.php";
include_once "../../includes/navbar.php";
?>

<div class="row">
    <div class="col-md-7 col-lg-6">
        <div class="content-card">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>New Dining Table</h5>
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
                    <input type="text" name="table_number" class="form-control" placeholder="e.g., T-01, Table 5" value="<?= $_POST['table_number'] ?? '' ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Seating Capacity <span class="text-danger">*</span></label>
                    <input type="number" name="capacity" min="1" max="30" class="form-control" value="<?= $_POST['capacity'] ?? '4' ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['Available', 'Occupied', 'Reserved'] as $opt): ?>
                            <option value="<?= $opt ?>" <?= (($_POST['status'] ?? 'Available') === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-restora">
                    <i class="bi bi-check-lg me-1"></i> Save Table
                </button>
            </form>

        </div>
    </div>
</div>

<?php include_once "../../includes/footer.php"; ?>