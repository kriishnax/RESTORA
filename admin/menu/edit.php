<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: index.php?err=Invalid menu item ID.");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM menu_items WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    header("Location: index.php?err=Menu item not found.");
    exit();
}

$error = "";
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? -1);
    $isAvailable = isset($_POST['is_available']) ? 1 : 0;
    $imagePath = $item['image_path'];

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

                $newFile = "menu_" . time() . "_" . rand(100, 999) . "." . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $newFile)) {
                    if ($imagePath && file_exists($dir . $imagePath)) unlink($dir . $imagePath);
                    $imagePath = $newFile;
                } else {
                    $error = "Failed to upload image.";
                }
            }
        }

        if ($error == "") {
            $update = $conn->prepare("UPDATE menu_items SET category_id = ?, name = ?, description = ?, price = ?, image_path = ?, is_available = ? WHERE id = ?");
            $update->bind_param("issdsii", $categoryId, $name, $description, $price, $imagePath, $isAvailable, $id);

            if ($update->execute()) {
                header("Location: index.php?msg=Menu item updated successfully.");
                exit();
            }
            $error = "Failed to update menu item.";
        }
    }

    $item = array_merge($item, [
        'category_id' => $categoryId,
        'name' => $name,
        'description' => $description,
        'price' => $_POST['price'] ?? '',
        'is_available' => $isAvailable
    ]);
}

$pageTitle = "Restora | Edit Menu Item";
$pageHeading = "Edit Menu Item";
$pageSubtitle = "Update food item details, pricing, or availability";

include_once "../../includes/header.php";
include_once "../../includes/sidebar.php";
include_once "../../includes/navbar.php";
?>

<div class="row">
    <div class="col-md-8 col-lg-7">
        <div class="content-card">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Menu Item</h5>
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
                            <option value="<?= $cat['id'] ?>" <?= ($item['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= $cat['name'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Food Item Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= $item['name'] ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Price (₹) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control" value="<?= $item['price'] ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-control"><?= $item['description'] ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Food Image</label>
                    <?php if ($item['image_path']): ?>
                        <div class="mb-2">
                            <img src="../../assets/images/menu/<?= $item['image_path'] ?>" width="75" height="75" class="rounded object-fit-cover border">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    <small class="text-muted">Leave empty to keep current image</small>
                </div>

                <div class="mb-4 form-check">
                    <input type="checkbox" name="is_available" id="is_available" class="form-check-input" <?= $item['is_available'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_available">Available for ordering</label>
                </div>

                <button type="submit" class="btn btn-restora">
                    <i class="bi bi-check-lg me-1"></i> Update Menu Item
                </button>
            </form>

        </div>
    </div>
</div>

<?php include_once "../../includes/footer.php"; ?>