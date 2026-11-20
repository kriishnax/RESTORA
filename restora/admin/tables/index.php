<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

// Fetch all restaurant tables
$sql = "SELECT id, table_number, capacity, status, created_at
        FROM restaurant_tables
        ORDER BY table_number ASC";

$result = $conn->query($sql);

// Quick floor occupancy counts
$totalTables = 0;
$availableTables = 0;
$occupiedTables = 0;

$tables = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tables[] = $row;
        $totalTables++;

        if ($row['status'] === 'Available') {
            $availableTables++;
        } elseif ($row['status'] === 'Occupied') {
            $occupiedTables++;
        }
    }
}

$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

// Page layout variables
$pageTitle = "Restora | Table Management";
$pageHeading = "Table Management";
$pageSubtitle = "Manage restaurant dining tables and occupancy status";

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

<!-- Table Summary Cards -->
<div class="row g-4 mb-4">

    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-grid-3x3-gap"></i>
            </div>
            <h3><?= $totalTables ?></h3>
            <p>Total Tables</p>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-check-circle"></i>
            </div>
            <h3><?= $availableTables ?></h3>
            <p>Available Tables</p>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="bi bi-people"></i>
            </div>
            <h3><?= $occupiedTables ?></h3>
            <p>Occupied Tables</p>
        </div>
    </div>

</div>

<!-- Tables List -->
<div class="content-card">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">
            <i class="bi bi-grid-3x3-gap me-2"></i>
            All Dining Tables
        </h5>

        <a href="add.php" class="btn btn-restora">
            <i class="bi bi-plus-lg me-1"></i>
            Add Table
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">

            <thead>
                <tr>
                    <th>#</th>
                    <th>Table Number</th>
                    <th>Seating Capacity</th>
                    <th>Status</th>
                    <th>Created Date</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php if (count($tables) > 0): ?>
                <?php $i = 1; ?>
                <?php foreach ($tables as $row): ?>

                    <?php
                    $statusClass = match ($row['status']) {
                        'Available' => 'success',
                        'Occupied' => 'danger',
                        'Reserved' => 'warning',
                        default => 'secondary'
                    };
                    ?>

                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="fw-semibold"><?= $row['table_number'] ?></td>
                        <td><?= $row['capacity'] ?> Persons</td>
                        <td>
                            <span class="badge text-bg-<?= $statusClass ?>">
                                <?= $row['status'] ?>
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
                                onclick="return confirm('Are you sure you want to delete this table?');"
                            >
                                <i class="bi bi-trash"></i> Delete
                            </a>
                        </td>
                    </tr>

                <?php endforeach; ?>
            <?php else: ?>

                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        No dining tables found. Click "Add Table" to create one.
                    </td>
                </tr>

            <?php endif; ?>
            </tbody>

        </table>
    </div>

</div>

<?php include_once "../../includes/footer.php"; ?>