<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

// Validate category ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?err=Invalid category ID.");
    exit();
}

$id = (int)$_GET['id'];
$error = "";

// Fetch existing category
$sql = "SELECT id, name, description FROM categories WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $stmt->close();
    header("Location: index.php?err=Category not found.");
    exit();
}

$category = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {

        $error = "Category name is required.";

    } else {

        // Check if another category already uses this name
        $checkSql = "SELECT id FROM categories WHERE name = ? AND id != ? LIMIT 1";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("si", $name, $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult && $checkResult->num_rows > 0) {

            $error = "Another category with this name already exists.";
            $checkStmt->close();

        } else {

            $checkStmt->close();

            // Update category
            $updateSql = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);

            if ($updateStmt) {
                $updateStmt->bind_param("ssi", $name, $description, $id);

                if ($updateStmt->execute()) {
                    $updateStmt->close();
                    header("Location: index.php?msg=Category updated successfully.");
                    exit();
                } else {
                    $error = "Failed to update category. Please try again.";
                }

                $updateStmt->close();
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }

    // Keep entered values on validation error
    $category['name'] = $name;
    $category['description'] = $description;
}

// Page layout variables
$pageTitle = "Restora | Edit Category";
$pageHeading = "Edit Category";
$pageSubtitle = "Update menu category details";

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
                    Edit Category
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
                        value="<?= $category['name'] ?>"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label class="form-label">Description</label>
                    <textarea
                        name="description"
                        rows="3"
                        class="form-control"
                    ><?= $category['description'] ?></textarea>
                </div>

                <button type="submit" class="btn btn-restora">
                    <i class="bi bi-check-lg me-1"></i>
                    Update Category
                </button>

            </form>

        </div>

    </div>
</div>

<?php include_once "../../includes/footer.php"; ?>