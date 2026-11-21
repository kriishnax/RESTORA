<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: index.php?err=Invalid category ID.");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$category = $stmt->get_result()->fetch_assoc();

if (!$category) {
    header("Location: index.php?err=Category not found.");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    if ($name == "") {
        $error = "Category name is required.";
    } else {
        $check = $conn->prepare("SELECT id FROM categories WHERE name = ? AND id != ? LIMIT 1");
        $check->bind_param("si", $name, $id);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = "Another category with this name already exists.";
        } else {
            $update = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $update->bind_param("ssi", $name, $description, $id);

            if ($update->execute()) {
                header("Location: index.php?msg=Category updated successfully.");
                exit();
            }
            $error = "Failed to update category.";
        }
    }

    $category['name'] = $name;
    $category['description'] = $description;
}

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
                <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Category</h5>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>

            <?php if ($error != ""): ?>
                <div class="alert alert-danger py-2"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Category Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= $category['name'] ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-control"><?= $category['description'] ?></textarea>
                </div>

                <button type="submit" class="btn btn-restora">
                    <i class="bi bi-check-lg me-1"></i> Update Category
                </button>
            </form>

        </div>
    </div>
</div>

<?php include_once "../../includes/footer.php"; ?>