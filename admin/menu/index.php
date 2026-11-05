
<?php
session_start();

require_once "../../config/database.php";

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper function for safe HTML output
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Search functionality
$search = trim($_GET['search'] ?? '');

$sql = "SELECT
            m.id,
            m.name,
            m.description,
            m.image_path,
            m.price,
            m.is_available,
            c.name AS category_name
        FROM menu_items m
        INNER JOIN categories c
            ON m.category_id = c.id";

if ($search !== '') {
    $sql .= " WHERE m.name LIKE ? OR c.name LIKE ?";
}

$sql .= " ORDER BY m.created_at DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}

if ($search !== '') {
    $searchTerm = "%" . $search . "%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
}

$stmt->execute();

$result = $stmt->get_result();
$totalItems = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Menu Management | Restora</title>

    <!-- Bootstrap 5 CSS -->
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
            max-width: 1200px;
            margin: 40px auto;
        }

        .menu-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .food-image {
            width: 55px;
            height: 55px;
            object-fit: cover;
            border-radius: 8px;
        }

        .no-image {
            width: 55px;
            height: 55px;
            border-radius: 8px;
            background: #eeeeee;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            color: #777;
        }

        .table th {
            font-size: 13px;
            color: #777;
            font-weight: 600;
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
        }

        .food-name {
            font-weight: 600;
            color: #29251f;
        }

        .btn-add {
            background: #29251f;
            color: white;
        }

        .btn-add:hover {
            background: #443d34;
            color: white;
        }
    </style>
</head>

<body>

<div class="container main-container">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold mb-1">Menu Management</h2>
            <p class="text-muted mb-0">
                Manage your restaurant food items.
            </p>
        </div>

        <a href="add.php" class="btn btn-add px-4">
            + Add Food
        </a>

    </div>

    <!-- Menu Card -->
    <div class="menu-card">

        <!-- Success Messages -->
        <?php if (isset($_GET['added']) && $_GET['added'] == '1'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                Food item added successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                Food item updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                Food item deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Error Messages -->
        <?php if (isset($_GET['error'])): ?>

            <?php
            $errors = [
                'invalid' => 'Invalid food item ID.',
                'notfound' => 'Food item not found.',
                'deletefailed' => 'Unable to delete food item.'
            ];
            ?>

            <div class="alert alert-danger alert-dismissible fade show">
                <?= e($errors[$_GET['error']] ?? 'Something went wrong.') ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>

        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">

            <h5 class="fw-bold mb-0">
                All Food Items

                <span class="badge bg-secondary">
                    <?= $totalItems ?>
                </span>
            </h5>

            <!-- Search Form -->
            <form method="GET" class="d-flex gap-2">

                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Search food or category..."
                    value="<?= e($search) ?>"
                >

                <button type="submit" class="btn btn-dark">
                    Search
                </button>

                <?php if ($search !== ''): ?>
                    <a href="index.php" class="btn btn-outline-secondary">
                        Clear
                    </a>
                <?php endif; ?>

            </form>

        </div>

        <!-- Food Items Table -->
        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>
                    <tr>
                        <th>IMAGE</th>
                        <th>FOOD NAME</th>
                        <th>CATEGORY</th>
                        <th>PRICE</th>
                        <th>AVAILABILITY</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr>

                            <!-- Food Image -->
                            <td>
                                <?php if (!empty($row['image_path'])): ?>

                                    <img
                                        src="../../<?= e($row['image_path']) ?>"
                                        alt="<?= e($row['name']) ?>"
                                        class="food-image"
                                    >

                                <?php else: ?>

                                    <div class="no-image">
                                        No Image
                                    </div>

                                <?php endif; ?>
                            </td>

                            <!-- Food Name -->
                            <td>
                                <div class="food-name">
                                    <?= e($row['name']) ?>
                                </div>

                                <small class="text-muted">
                                    <?= e($row['description'] ?? '') ?>
                                </small>
                            </td>

                            <!-- Category -->
                            <td>
                                <?= e($row['category_name']) ?>
                            </td>

                            <!-- Price -->
                            <td class="fw-semibold">
                                ₹<?= number_format((float)$row['price'], 2) ?>
                            </td>

                            <!-- Availability -->
                            <td>
                                <?php if ($row['is_available']): ?>

                                    <span class="badge bg-success">
                                        Available
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-danger">
                                        Unavailable
                                    </span>

                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td>
                                <div class="d-flex gap-2">

                                    <!-- Edit Button -->
                                    <a
                                        href="edit.php?id=<?= (int)$row['id'] ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        Edit
                                    </a>

                                    <!-- Delete Form -->
                                    <form
                                        action="delete.php"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to delete <?= e($row['name']) ?>?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$row['id'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e($_SESSION['csrf_token']) ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </div>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="text-center py-5">

                            <h6 class="text-muted">
                                No food items found.
                            </h6>

                            <p class="text-muted mb-0">
                                Try another search.
                            </p>

                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
