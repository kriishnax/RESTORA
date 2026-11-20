<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

// Fetch all categories with menu item count
$sql = "SELECT
            c.id,
            c.name,
            c.description,
            c.created_at,
            COUNT(m.id) AS item_count
        FROM categories c
        LEFT JOIN menu_items m
            ON c.id = m.category_id
        GROUP BY c.id
        ORDER BY c.id DESC";

$result = $conn->query($sql);

$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

// Page layout variables
$pageTitle = "Restora | Categories";
$pageHeading = "Categories";
$pageSubtitle = "Manage menu categories";

include_once "../../includes/header.php";
include_once "../../includes/sidebar.php";
include_once "../../includes/navbar.php";
?>

<?php if (!empty($msg)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $msg ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($err)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $err ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="content-card">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">
            <i class="bi bi-tags me-2"></i>
            All Categories
        </h5>

        <a href="add.php" class="btn btn-restora">
            <i class="bi bi-plus-lg me-1"></i>
            Add Category
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">

            <thead>
                <tr>
                    <th>#</th>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th>Menu Items</th>
                    <th>Created Date</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php $i = 1; ?>
                <?php while ($row = $result->fetch_assoc()): ?>

                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="fw-semibold"><?= $row['name'] ?></td>
                        <td><?= !empty($row['description']) ? $row['description'] : '-' ?></td>
                        <td>
                            <span class="badge text-bg-light border">
                                <?= $row['item_count'] ?> items
                            </span>
                        </td>
                        <td>
                            <?= date("d M Y", strtotime($row['created_at'])) ?>
                        </td>
                        <td>
                            <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-pencil"></i> Edit
                            </a>

                            <a
                                href="delete.php?id=<?= $row['id'] ?>"
                                class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Are you sure you want to delete this category?');"
                            >
                                <i class="bi bi-trash"></i> Delete
                            </a>
                        </td>
                    </tr>

                <?php endwhile; ?>
            <?php else: ?>

                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        No categories found. Click "Add Category" to create one.
                    </td>
                </tr>

            <?php endif; ?>
            </tbody>

        </table>
    </div>

</div>

<?php include_once "../../includes/footer.php"; ?>