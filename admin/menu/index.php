<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

$sql = "SELECT m.*, c.name AS category_name
        FROM menu_items m
        INNER JOIN categories c ON m.category_id = c.id
        ORDER BY m.id DESC";

$result = $conn->query($sql);

$pageTitle = "Restora | Menu Management";
$pageHeading = "Menu Management";
$pageSubtitle = "Manage restaurant food items and pricing";

include_once "../../includes/header.php";
include_once "../../includes/sidebar.php";
include_once "../../includes/navbar.php";
?>

<?php if (!empty($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $_GET['msg'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($_GET['err'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $_GET['err'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="content-card">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="bi bi-egg-fried me-2"></i>All Menu Items</h5>
        <a href="add.php" class="btn btn-restora">
            <i class="bi bi-plus-lg me-1"></i> Add Menu Item
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Image</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows > 0): $i = 1; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td>
                            <?php if ($row['image_path']): ?>
                                <img src="../../assets/images/menu/<?= $row['image_path'] ?>" width="48" height="48" class="rounded object-fit-cover border">
                            <?php else: ?>
                                <span class="badge text-bg-light border text-muted">No Image</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= $row['name'] ?></div>
                            <small class="text-muted"><?= $row['description'] ?></small>
                        </td>
                        <td><span class="badge text-bg-light border"><?= $row['category_name'] ?></span></td>
                        <td class="fw-semibold">₹<?= $row['price'] ?></td>
                        <td>
                            <span class="badge <?= $row['is_available'] ? 'text-bg-success' : 'text-bg-danger' ?>">
                                <?= $row['is_available'] ? 'Available' : 'Unavailable' ?>
                            </span>
                        </td>
                        <td class="flex">
                            <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary w-100 mb-1">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Delete this menu item?')">
                                <i class="bi bi-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No menu items found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php include_once "../../includes/footer.php"; ?>