<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Restora | Restaurant Management System</title>

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <!-- Custom Style CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>

    <!-- Top Navbar -->
    <nav class="navbar main-navbar">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                RESTORA
            </a>

            <?php if ($isLoggedIn): ?>
                <a href="admin/dashboard/index.php" class="btn btn-restora btn-sm">
                    <i class="bi bi-grid me-1"></i> Dashboard
                </a>
            <?php else: ?>
                <a href="admin/auth/login.php" class="btn btn-restora btn-sm">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Login
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">

            <div class="row justify-content-center mb-5">
                <div class="col-lg-8">
                    <div class="hero-card text-center">

                        <span class="hero-badge">
                            Dine-In Restaurant Management
                        </span>

                        <h1 class="hero-title">
                            Welcome to RESTORA
                        </h1>

                        <p class="hero-text">
                            A simple and reliable restaurant management system for handling menu items, table occupancy, dine-in orders, kitchen tracking, billing, and daily sales reports.
                        </p>

                        <?php if ($isLoggedIn): ?>
                            <a href="admin/dashboard/index.php" class="btn-restora">
                                <i class="bi bi-speedometer2 me-2"></i>
                                Open Dashboard
                            </a>
                        <?php else: ?>
                            <a href="admin/auth/login.php" class="btn-restora">
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                Admin & Staff Login
                            </a>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <!-- Key Highlights -->
            <div class="row g-4">

                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="bi bi-grid-3x3-gap"></i>
                        </div>
                        <h5 class="fw-bold">Table & Order Control</h5>
                        <p class="text-muted mb-0">
                            Track available and occupied tables in real time and create dine-in orders quickly.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="bi bi-egg-fried"></i>
                        </div>
                        <h5 class="fw-bold">Menu & Kitchen Flow</h5>
                        <p class="text-muted mb-0">
                            Manage food categories, item availability, and live kitchen preparation statuses.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <h5 class="fw-bold">Billing & Reports</h5>
                        <p class="text-muted mb-0">
                            Generate clean customer invoices in INR (₹), record payments, and review daily sales.
                        </p>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            &copy; <?= date("Y") ?> Restora — Restaurant Management System
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>