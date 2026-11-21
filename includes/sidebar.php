<?php
$currentPath = $_SERVER['PHP_SELF'] ?? '';
$userRole = $_SESSION['user_role'] ?? 'Staff';
?>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        RESTORA
    </div>

    <div class="sidebar-section">Main</div>

    <a href="../dashboard/index.php" class="<?= str_contains($currentPath, '/dashboard/') ? 'active' : '' ?>">
        <i class="bi bi-grid"></i>
        Dashboard
    </a>

    <div class="sidebar-section">Restaurant Management</div>

    <a href="../categories/index.php" class="<?= str_contains($currentPath, '/categories/') ? 'active' : '' ?>">
        <i class="bi bi-tags"></i>
        Categories
    </a>

    <a href="../menu/index.php" class="<?= str_contains($currentPath, '/menu/') ? 'active' : '' ?>">
        <i class="bi bi-egg-fried"></i>
        Menu Management
    </a>

    <a href="../tables/index.php" class="<?= str_contains($currentPath, '/tables/') ? 'active' : '' ?>">
        <i class="bi bi-grid-3x3-gap"></i>
        Tables
    </a>

    <a href="../orders/index.php" class="<?= str_contains($currentPath, '/orders/') ? 'active' : '' ?>">
        <i class="bi bi-receipt"></i>
        Orders
    </a>

    <div class="sidebar-section">Finance</div>

    <a href="../billing/index.php" class="<?= str_contains($currentPath, '/billing/') ? 'active' : '' ?>">
        <i class="bi bi-credit-card"></i>
        Billing
    </a>
<!-- 
    <a href="../reports/index.php" class="<?= str_contains($currentPath, '/reports/') ? 'active' : '' ?>">
        <i class="bi bi-bar-chart"></i>
        Reports
    </a> -->

    <div class="sidebar-section">Account</div>

    <?php if ($userRole === 'Admin'): ?>
        <a href="../auth/register.php" class="<?= str_contains($currentPath, '/auth/register.php') ? 'active' : '' ?>">
            <i class="bi bi-person-plus"></i>
            Add Staff
        </a>
    <?php endif; ?>

    <a href="../auth/logout.php">
        <i class="bi bi-box-arrow-right"></i>
        Logout
    </a>

</aside>