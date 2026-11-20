<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

$error = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {

        $error = "Category name is required.";

    } else {

        // Check if category already exists
        $checkSql = "SELECT id FROM categories WHERE name = ? LIMIT 1";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $name);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult && $checkResult->num_rows > 0) {

            $error = "This category already exists.";
            $checkStmt->close();

        } else {

            $checkStmt->close();

            // Insert new category
            $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("ss", $name, $description);

                if ($stmt->execute()) {
                    $stmt->close();
                    header("Location: index.php?msg=Category added successfully.");
                    exit();
                } else {
                    $error = "Failed to add category. Please try again.";
                }

                $stmt->close();
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}

// Page layout variables
$pageTitle = "Restora | Add Category";
$pageHeading = "Add Category";
$pageSubtitle = "Create a new menu category";

include_once "../../includes/header.php";
include_once "../../includes/sidebar.php";
include_once "../../includes/navbar.php";
?>

<div class="row">
    <div class="col-md-7 col-lg-6">

        <div class="content-card">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">
                    <i class="bi bi-plus-circle me-2"></i>
                    New Category
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
                    <label class="form-label">Category Name <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        placeholder="e.g., Starters, Main Course, Beverages"
                        value="<?= $_POST['name'] ?? '' ?>"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label class="form-label">Description</label>
                    <textarea
                        name="description"
                        rows="3"
                        class="form-control"
                        placeholder="Short description (optional)"
                    ><?= $_POST['description'] ?? '' ?></textarea>
                </div>

                <button type="submit" class="btn btn-restora">
                    <i class="bi bi-check-lg me-1"></i>
                    Save Category
                </button>

            </form>

        </div>

    </div>
</div>

<?php include_once "../../includes/footer.php"; ?>