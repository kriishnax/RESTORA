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

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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
    <nav class="navbar navbar-expand-md main-navbar sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <span class="brand-dot"></span>
                RESTORA
            </a>

            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <i class="bi bi-list fs-4"></i>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav mx-auto mb-2 mb-md-0 gap-md-3">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Modules</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#workflow">How It Works</a>
                    </li>
                </ul>

                <div class="d-flex">
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
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="hero-card text-center">

                        <span class="hero-badge">
                            </i> Dine-In Restaurant POS & Management
                        </span>

                        <h1 class="hero-title">
                            Run Your Restaurant Floor & Kitchen Without the Chaos
                        </h1>

                        <p class="hero-text mx-auto">
                            Restora connects your dining tables, kitchen preparation queue, menu catalog, and billing counter in one fast, lightweight system built for smooth daily operations.
                        </p>

                        <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
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
                            <a href="#workflow" class="btn-restora-outline">
                                <i class="bi bi-diagram-3 me-2"></i>
                                See Workflow
                            </a>
                        </div>

                        <!-- Quick Highlights Strip -->
                        <div class="hero-metrics pt-4 mt-2">
                            <div class="row g-3 justify-content-center">
                                <div class="col-4 col-sm-3">
                                    <div class="metric-value">Live</div>
                                    <div class="metric-label">Table Status</div>
                                </div>
                                <div class="col-4 col-sm-3 border-start border-end">
                                    <div class="metric-value">INR (₹)</div>
                                    <div class="metric-label">Instant Invoices</div>
                                </div>
                                <div class="col-4 col-sm-3">
                                    <div class="metric-value">2 Roles</div>
                                    <div class="metric-label">Admin & Staff</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Modules Section -->
    <section id="features" class="section-padding">
        <div class="container">
            <div class="text-center mb-5">
                <span class="section-tag">Core Modules</span>
                <h2 class="section-title">Everything Needed for Daily Service</h2>
                <p class="section-subtitle mx-auto">
                    Purpose-built tools to take a guest from seating to checkout with zero friction.
                </p>
            </div>

            <div class="row g-4">

                <div class="col-md-6 col-lg-4">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="bi bi-grid-3x3-gap"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Table & Floor Control</h5>
                        <p class="text-muted mb-0">
                            Monitor available and occupied tables at a glance, check seating capacity, and assign guests immediately.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="bi bi-journal-text"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Menu & Category Setup</h5>
                        <p class="text-muted mb-0">
                            Organize dishes by food categories, update prices in ₹, and toggle item availability in real time.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="bi bi-bag-plus"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Fast Dine-In Ordering</h5>
                        <p class="text-muted mb-0">
                            Create table-linked orders with customer details, adjust item quantities, and add new items mid-meal.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="bi bi-fire"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Live Kitchen Tracking</h5>
                        <p class="text-muted mb-0">
                            Track order progress from pending and preparing to ready and served so kitchen and floor stay synced.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Billing & Multi-Mode Pay</h5>
                        <p class="text-muted mb-0">
                            Generate itemized customer bills in INR (₹) and settle payments via Cash, UPI, or Card while freeing the table.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="bi bi-bar-chart-line"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Daily Sales & Reports</h5>
                        <p class="text-muted mb-0">
                            Review completed orders, daily revenue summaries, and payment breakdowns from the admin dashboard.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- How It Works / Workflow Section -->
    <section id="workflow" class="section-padding section-alt">
        <div class="container">
            <div class="text-center mb-5">
                <span class="section-tag">Service Flow</span>
                <h2 class="section-title">How Restora Streamlines Dine-In</h2>
                <p class="section-subtitle mx-auto">
                    Four simple steps from the moment guests arrive until the table is ready for the next party.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-sm-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">01</div>
                        <h6 class="fw-bold mb-2">Assign a Table</h6>
                        <p class="text-muted small mb-0">
                            Pick an open table from the floor view and mark it occupied for incoming guests.
                        </p>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">02</div>
                        <h6 class="fw-bold mb-2">Place Dine-In Order</h6>
                        <p class="text-muted small mb-0">
                            Select dishes from active categories, enter customer details, and submit to the kitchen.
                        </p>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">03</div>
                        <h6 class="fw-bold mb-2">Track Preparation</h6>
                        <p class="text-muted small mb-0">
                            Update order statuses as dishes move from kitchen preparation to table service.
                        </p>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">04</div>
                        <h6 class="fw-bold mb-2">Generate Bill & Close</h6>
                        <p class="text-muted small mb-0">
                            Print or view the final invoice, record Cash/UPI/Card payment, and reset the table.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Role Access Section -->
    <!-- <section id="roles" class="section-padding">
        <div class="container">
            <div class="text-center mb-5">
                <span class="section-tag">Access Control</span>
                <h2 class="section-title">Built for Admins & Floor Staff</h2>
                <p class="section-subtitle mx-auto">
                    Focused workspaces tailored to what each team member needs during service.
                </p>
            </div>

            <div class="row g-4 justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="role-card">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="feature-icon mb-0">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0">Restaurant Admin</h5>
                                <small class="text-muted">Full system configuration & analytics</small>
                            </div>
                        </div>
                        <ul class="role-list mb-0">
                            <li><i class="bi bi-check2-circle"></i> Manage food categories, dishes & pricing</li>
                            <li><i class="bi bi-check2-circle"></i> Configure dining tables & seating capacities</li>
                            <li><i class="bi bi-check2-circle"></i> Add or manage staff accounts and roles</li>
                            <li><i class="bi bi-check2-circle"></i> View daily revenue & completed order history</li>
                        </ul>
                    </div>
                </div>

                <div class="col-md-6 col-lg-5">
                    <div class="role-card">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="feature-icon mb-0">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0">Staff & Cashier</h5>
                                <small class="text-muted">Fast front-of-house & billing operations</small>
                            </div>
                        </div>
                        <ul class="role-list mb-0">
                            <li><i class="bi bi-check2-circle"></i> Check live table availability & seat guests</li>
                            <li><i class="bi bi-check2-circle"></i> Create and update active dine-in orders</li>
                            <li><i class="bi bi-check2-circle"></i> Update kitchen & serving order statuses</li>
                            <li><i class="bi bi-check2-circle"></i> Generate bills & record Cash, UPI, or Card payments</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section> -->

    <!-- Bottom CTA Banner -->
    <!-- <section class="pb-5">
        <div class="container">
            <div class="cta-banner text-center">
                <h3 class="fw-bold mb-2">Ready to Manage Today's Service?</h3>
                <p class="text-muted mb-4">
                    Sign in with your administrator or staff credentials to access the live workspace.
                </p>
                <?php if ($isLoggedIn): ?>
                    <a href="admin/dashboard/index.php" class="btn-restora">
                        <i class="bi bi-speedometer2 me-2"></i> Go to Dashboard
                    </a>
                <?php else: ?>
                    <a href="admin/auth/login.php" class="btn-restora">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Proceed to Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section> -->

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <div class="fw-semibold text-dark">
                    <span class="brand-dot me-1"></span> RESTORA
                </div>
                <div>
                    &copy; <?= date("Y") ?> Restora — Dine-In Restaurant Management System
                </div>
                <div class="d-flex gap-3">
                    <a href="#features" class="footer-link">Modules</a>
                    <a href="#workflow" class="footer-link">Workflow</a>
                    <a href="admin/auth/login.php" class="footer-link">Login</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>