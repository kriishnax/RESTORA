<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

// Validate menu item ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?err=Invalid menu item ID.");
    exit();
}

$id = (int)$_GET['id'];
$error = "";

// Fetch existing menu item
$sql = "SELECT id, category_id, name, description, price, image_path, is_available
        FROM menu_items
        WHERE id = ?
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $stmt->close();
    header("Location: index.php?err=Menu item not found.");
    exit();
}

$item = $result->fetch_assoc();
$stmt->close();

// Fetch categories for dropdown
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $categoryId = (int)($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $isAvailable = isset($_POST['is_available']) ? 1 : 0;
    $imagePath = $item['image_path'];

    if ($categoryId <= 0 || empty($name) || $price === '' || !is_numeric($price) || (float)$price < 0) {

        $error = "Please select a category, enter a valid item name, and provide a valid price.";

    } else {

        // Handle new image upload if provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

            $fileTmpPath = $_FILES['image']['tmp_name'];
            $fileName = $_FILES['image']['name'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($fileExt, $allowedExt)) {

                $error = "Only JPG, JPEG, PNG, and WEBP image formats are allowed.";

            } else {

                $uploadDir = "../../assets/images/menu/";

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $newFileName = "menu_" . rand(100000000, 999999999) . "_" . rand(100, 999) . "." . $fileExt;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {

                    // Remove old image file if it exists
                    if (!empty($item['image_path']) && file_exists($uploadDir . $item['image_path'])) {
                        unlink($uploadDir . $item['image_path']);
                    }

                    $imagePath = $newFileName;

                } else {
                    $error = "Failed to upload the new food image.";
                }
            }
        }

        // Update database if no upload error occurred
        if (empty($error)) {

            $priceVal = (float)$price;

            $updateSql = "UPDATE menu_items
                          SET category_id = ?, name = ?, description = ?, price = ?, image_path = ?, is_available = ?
                          WHERE id = ?";

            $updateStmt = $conn->prepare($updateSql);

            if ($updateStmt) {
                $updateStmt->bind_param("issdsii", $categoryId, $name, $description, $priceVal, $imagePath, $isAvailable, $id);

                if ($updateStmt->execute()) {
                    $updateStmt->close();
                    header("Location: index.php?msg=Menu item updated successfully.");
                    exit();
                } else {
                    $error = "Failed to update menu item. Please try again.";
                }

                $updateStmt->close();
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }

    // Keep entered values on validation error
    $item['category_id'] = $categoryId;
    $item['name'] = $name;
    $item['description'] = $description;
    $item['price'] = $price;
    $item['is_available'] = $isAvailable;
}

// Page layout variables
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
                <h5 class="mb-0">
                    <i class="bi bi-pencil-square me-2"></i>
                    Edit Menu Item
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

            <form method="POST" action="" enctype="multipart/form-data">

                <div class="mb-3">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">-- Select Category --</option>
                        <?php if ($categories && $categories->num_rows > 0): ?>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option
                                    value="<?= $cat['id'] ?>"
                                    <?= ($item['category_id'] == $cat['id']) ? 'selected' : '' ?>
                                >
                                    <?= $cat['name'] ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Food Item Name <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="<?= $item['name'] ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Price (₹) <span class="text-danger">*</span></label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="price"
                        class="form-control"
                        value="<?= $item['price'] ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea
                        name="description"
                        rows="3"
                        class="form-control"
                    ><?= $item['description'] ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Food Image</label>

                    <?php if (!empty($item['image_path'])): ?>
                        <div class="mb-2">
                            <img
                                src="../../assets/images/menu/<?= $item['image_path'] ?>"
                                alt="<?= $item['name'] ?>"
                                width="80"
                                height="80"
                                class="rounded object-fit-cover border"
                            >
                        </div>
                    <?php endif; ?>

                    <input
                        type="file"
                        name="image"
                        class="form-control"
                        accept=".jpg,.jpeg,.png,.webp"
                    >
                    <small class="text-muted">Leave empty to keep the current image (JPG, JPEG, PNG, WEBP)</small>
                </div>

                <div class="mb-4 form-check">
                    <input
                        type="checkbox"
                        name="is_available"
                        id="is_available"
                        class="form-check-input"
                        <?= ($item['is_available'] == 1) ? 'checked' : '' ?>
                    >
                    <label class="form-check-label" for="is_available">
                        Available for ordering
                    </label>
                </div>

                <button type="submit" class="btn btn-restora">
                    <i class="bi bi-check-lg me-1"></i>
                    Update Menu Item
                </button>

            </form>

        </div>

    </div>
</div>

<?php include_once "../../includes/footer.php"; ?>