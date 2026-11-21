<?php
if (!isset($pageHeading)) {
    $pageHeading = "Dashboard";
}

if (!isset($pageSubtitle)) {
    $pageSubtitle = "Restaurant management";
}

$userName = $_SESSION['user_name'] ?? 'Admin';
$userRole = $_SESSION['user_role'] ?? 'Staff';
?>

<!-- Main Content -->
<main class="main-content">

    <!-- Topbar -->
    <div class="topbar">

        <div class="d-flex align-items-center gap-3">

            <button class="menu-toggle" id="menuToggle">
                <i class="bi bi-list"></i>
            </button>

            <div>
                <h5><?= $pageHeading ?></h5>
                <small class="text-muted">
                    <?= $pageSubtitle ?>
                </small>
            </div>

        </div>

        <div class="text-end">
            <strong>
                <?= $userName ?>
            </strong>

            <div>
                <small class="text-muted">
                    <?= $userRole ?>
                </small>
            </div>
        </div>

    </div>