
<?php
session_start();

require_once "../../config/database.php";

// Escape output
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Allow POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

// Verify CSRF token
if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    die("Invalid request. Please refresh the page and try again.");
}

// Validate food item ID
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: index.php?error=invalid");
    exit;
}

// Fetch image path before deleting the record
$stmt = $conn->prepare(
    "SELECT image_path FROM menu_items WHERE id = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();

if (!$item) {
    header("Location: index.php?error=notfound");
    exit;
}

$imagePath = $item['image_path'];

// Delete food item using prepared statement
$stmt = $conn->prepare(
    "DELETE FROM menu_items WHERE id = ?"
);

$stmt->bind_param("i", $id);

if ($stmt->execute()) {

   
    if (
        !empty($imagePath) &&
        preg_match(
            '#^assets/images/menu/[a-f0-9]{32}\.(jpg|png|webp)$#',
            $imagePath
        )
    ) {
        $fullPath = "../../" . $imagePath;

        if (is_file($fullPath)) {
            unlink($fullPath);
        }
    }

    $stmt->close();
    header("Location: index.php?deleted=1");
    exit;

} else {
    $stmt->close();
    header("Location: index.php?error=deletefailed");
    exit;
}
?>
