<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

$error = "";
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? -1);
    $isAvailable = isset($_POST['is_available']) ? 1 : 0;
    $imagePath = "";

    if ($categoryId <= 0 || $name == "" || $price < 0) {
        $error = "Please select a category, enter a name, and provide a valid price.";
    } else {
        if (!empty($_FILES['image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $error = "Only JPG, JPEG, PNG, and WEBP images are allowed.";
            } else {
                $dir = "../../assets/images/menu/";
                if (!is_dir($dir)) mkdir($dir, 0777, true);

                $imagePath = "menu_" . time() . "_" . rand(100, 999) . "." . $ext;
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $dir . $imagePath)) {
                    $error = "Failed to upload image.";
                }
            }
        }

        if ($error == "") {
            $stmt = $conn->prepare("INSERT INTO menu_items (category_id, name, description, price, image_path, is_available) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issdsi", $categoryId, $name, $description, $price, $imagePath, $isAvailable);

            if ($stmt->execute()) {
                header("Location: index.php?msg=Menu item added successfully.");
                exit();
            }
            $error = "Failed to save menu item.";
        }
    }
}

$pageTitle = "Restora | Add Menu Item";
$pageHeading = "Add Menu Item";
$pageSubtitle = "Create a new food item for the menu";

include_once "../../includes/header.php";
include_once "../../includes/sidebar.php";
include_once "../../includes/navbar.php";
?>

<div class="row">
    <div class="col-md-8 col-lg-7">
        <div class="content-card">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>New Menu Item</h5>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>

            <?php if ($error != ""): ?>
                <div class="alert alert-danger py-2"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">-- Select Category --</option>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?= $cat['id'] ?>" <?= (($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                                <?= $cat['name'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Food Item Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g., Paneer Tikka, Masala Dosa" value="<?= $_POST['name'] ?? '' ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Price (₹) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control" placeholder="e.g., 180.00" value="<?= $_POST['price'] ?? '' ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-control" placeholder="Short description (optional)"><?= $_POST['description'] ?? '' ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Food Image</label>
                    <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    <small class="text-muted">JPG, JPEG, PNG, or WEBP (optional)</small>
                </div>

                <div class="mb-4 form-check">
                    <input type="checkbox" name="is_available" id="is_available" class="form-check-input" <?= ($_SERVER["REQUEST_METHOD"] !== "POST" || isset($_POST['is_available'])) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_available">Available for ordering</label>
                </div>

                <button type="submit" class="btn btn-restora">
                    <i class="bi bi-check-lg me-1"></i> Save Menu Item
                </button>
            </form>

        </div>
    </div>
</div>

<?php include_once "../../includes/footer.php"; ?>