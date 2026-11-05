
<?php

session_start();

require_once "../../config/database.php";

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];

$name = "";
$category_id = "";
$description = "";
$price = "";
$is_available = "1";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verify CSRF token
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $errors[] = "Invalid form submission. Please try again.";
    }

    // Get form values
    $name = trim($_POST['name'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $is_available = $_POST['is_available'] ?? '';

    // Validate food name
    if ($name === '' || strlen($name) > 150) {
        $errors[] = "Food name is required and must be within 150 characters.";
    }

    // Validate category
    if (
        filter_var($category_id, FILTER_VALIDATE_INT) === false ||
        (int)$category_id <= 0
    ) {
        $errors[] = "Please select a valid category.";
    } else {
        $categoryCheck = $conn->prepare(
            "SELECT id FROM categories WHERE id = ?"
        );

        $categoryCheck->bind_param("i", $category_id);
        $categoryCheck->execute();

        $categoryResult = $categoryCheck->get_result();

        if ($categoryResult->num_rows === 0) {
            $errors[] = "Selected category does not exist.";
        }

        $categoryCheck->close();
    }

    // Validate description length
    if (strlen($description) > 65535) {
        $errors[] = "Description is too long.";
    }

    // Validate price
    if (
        $price === '' ||
        !is_numeric($price) ||
        !is_finite((float)$price) ||
        (float)$price < 0 ||
        (float)$price > 99999999.99
    ) {
        $errors[] = "Please enter a valid price.";
    }

    // Validate availability
    if (!in_array($is_available, ['0', '1'], true)) {
        $errors[] = "Please select a valid availability status.";
    }

    // Image upload
    $imagePath = null;
    $uploadedFile = null;

    if (
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        $file = $_FILES['image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {

            $errors[] = "Image upload failed.";

        } elseif ($file['size'] > 2 * 1024 * 1024) {

            $errors[] = "Image size must not exceed 2 MB.";

        } else {

            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            if (!isset($allowedTypes[$mimeType])) {

                $errors[] = "Only JPG, PNG and WEBP images are allowed.";

            } else {

                $imageInfo = getimagesize($file['tmp_name']);

                if ($imageInfo === false) {

                    $errors[] = "Uploaded file is not a valid image.";

                } else {

                    $uploadDirectory = __DIR__ . "/../../assets/images/menu/";

                    if (
                        !is_dir($uploadDirectory) &&
                        !mkdir($uploadDirectory, 0755, true)
                    ) {
                        $errors[] = "Unable to create image directory.";
                    } elseif (!is_writable($uploadDirectory)) {
                        $errors[] = "Image directory is not writable.";
                    } else {

                        $fileName = bin2hex(random_bytes(16))
                            . "." . $allowedTypes[$mimeType];

                        $uploadedFile = $uploadDirectory . $fileName;

                        $imagePath = "assets/images/menu/" . $fileName;
                    }
                }
            }
        }
    }

    // Insert into database
    if (empty($errors)) {

        // Move image to assets directory
        if (
            $uploadedFile !== null &&
            !move_uploaded_file($_FILES['image']['tmp_name'], $uploadedFile)
        ) {
            $errors[] = "Unable to save uploaded image.";
        }

        if (empty($errors)) {

            $sql = "INSERT INTO menu_items
                    (category_id, name, description, image_path, price, is_available)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {

                $errors[] = "Database query preparation failed.";

                if ($uploadedFile !== null && file_exists($uploadedFile)) {
                    unlink($uploadedFile);
                }

            } else {

                $category_id = (int)$category_id;
                $is_available = (int)$is_available;

                $stmt->bind_param(
                    "issssi",
                    $category_id,
                    $name,
                    $description,
                    $imagePath,
                    $price,
                    $is_available
                );

                if ($stmt->execute()) {

                    $stmt->close();

                    header("Location: index.php?added=1");
                    exit;

                } else {

                    $errors[] = "Unable to add food item. Please try again.";

                    if ($uploadedFile !== null && file_exists($uploadedFile)) {
                        unlink($uploadedFile);
                    }

                    $stmt->close();
                }
            }
        }
    }
}

// Fetch categories for dropdown
$categoryQuery = $conn->query(
    "SELECT id, name FROM categories ORDER BY name ASC"
);

if (!$categoryQuery) {
    die("Unable to fetch categories: " . $conn->error);
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Food Item | Restora</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f5f5;
            font-family: Arial, sans-serif;
        }

        .main-container {
            max-width: 850px;
            margin: 40px auto;
        }

        .form-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .form-label {
            font-weight: 600;
            font-size: 14px;
        }

        .form-control,
        .form-select {
            padding: 11px;
        }

        .btn-save {
            background: #29251f;
            color: white;
        }

        .btn-save:hover {
            background: #443d34;
            color: white;
        }

    </style>

</head>

<body>

<div class="container main-container">

    <!-- Page Header -->

    <div class="mb-4">

        <a href="index.php"
           class="text-decoration-none text-muted">

            ← Back to Menu

        </a>

        <h2 class="fw-bold mt-3 mb-1">
            Add Food Item
        </h2>

        <p class="text-muted">
            Add a new food item to your restaurant menu.
        </p>

    </div>

    <!-- Form Card -->

    <div class="form-card">

        <!-- Error Messages -->

        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger">

                <strong>Please fix the following errors:</strong>

                <ul class="mb-0 mt-2">

                    <?php foreach ($errors as $error): ?>

                        <li><?= e($error) ?></li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <form method="POST"
              enctype="multipart/form-data">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION['csrf_token']) ?>"
            >

            <!-- Food Name -->

            <div class="mb-3">

                <label class="form-label">
                    Food Name *
                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    placeholder="Enter food name"
                    maxlength="150"
                    value="<?= e($name) ?>"
                    required
                >

            </div>


            <!-- Category -->

            <div class="mb-3">

                <label class="form-label">
                    Category *
                </label>

                <select
                    name="category_id"
                    class="form-select"
                    required
                >

                    <option value="">
                        Select Category
                    </option>

                    <?php while ($category = $categoryQuery->fetch_assoc()): ?>

                        <option
                            value="<?= (int)$category['id'] ?>"
                            <?= (string)$category_id === (string)$category['id'] ? 'selected' : '' ?>
                        >

                            <?= e($category['name']) ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>


            <!-- Description -->

            <div class="mb-3">

                <label class="form-label">
                    Description
                </label>

                <textarea
                    name="description"
                    class="form-control"
                    rows="4"
                    placeholder="Describe the food item..."
                ><?= e($description) ?></textarea>

            </div>


            <!-- Food Image -->

            <div class="mb-3">

                <label class="form-label">
                    Food Image
                </label>

                <input
                    type="file"
                    name="image"
                    class="form-control"
                    accept=".jpg,.jpeg,.png,.webp"
                >

                <small class="text-muted">
                    Allowed formats: JPG, PNG, WEBP. Maximum size: 2 MB.
                </small>

            </div>


            <!-- Price -->

            <div class="mb-3">

                <label class="form-label">
                    Price (₹) *
                </label>

                <input
                    type="number"
                    name="price"
                    class="form-control"
                    placeholder="Enter food price"
                    min="0"
                    max="99999999.99"
                    step="0.01"
                    value="<?= e($price) ?>"
                    required
                >

            </div>


            <!-- Availability -->

            <div class="mb-4">

                <label class="form-label">
                    Availability
                </label>

                <select
                    name="is_available"
                    class="form-select"
                >

                    <option value="1"
                        <?= $is_available === '1' ? 'selected' : '' ?>>
                        Available
                    </option>

                    <option value="0"
                        <?= $is_available === '0' ? 'selected' : '' ?>>
                        Unavailable
                    </option>

                </select>

            </div>


            <!-- Buttons -->

            <div class="d-flex gap-2">

                <button
                    type="submit"
                    class="btn btn-save px-4"
                >

                    Add Food Item

                </button>

                <a
                    href="index.php"
                    class="btn btn-outline-secondary px-4"
                >

                    Cancel

                </a>

            </div>

        </form>

    </div>

</div>

</body>
</html>
