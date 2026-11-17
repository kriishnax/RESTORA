
<?php
session_start();

// If already logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: admin/dashboard/index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Restora | Restaurant Management System</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            color: #212529;
        }

        .navbar {
            background: #ffffff;
            padding: 18px 0;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .navbar-brand {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: 1px;
            color: #212529;
        }

        .hero {
            min-height: 85vh;
            display: flex;
            align-items: center;
            text-align: center;
            background: linear-gradient(
                135deg,
                #f8f9fa,
                #e9ecef
            );
            padding: 70px 20px;
        }

        .hero-content {
            max-width: 750px;
            margin: auto;
        }

        .hero h1 {
            font-size: clamp(38px, 6vw, 64px);
            font-weight: 800;
            margin-bottom: 20px;
        }

        .hero h1 span {
            color: #dc6b35;
        }

        .hero p {
            font-size: 18px;
            color: #6c757d;
            line-height: 1.7;
            margin-bottom: 30px;
        }

        .btn-login {
            background: #212529;
            color: #ffffff;
            padding: 14px 35px;
            border-radius: 7px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
        }

        .btn-login:hover {
            background: #dc6b35;
            color: #ffffff;
            transform: translateY(-2px);
        }

        .features {
            padding: 65px 20px;
            background: #ffffff;
        }

        .feature-card {
            padding: 30px 20px;
            text-align: center;
            border: 1px solid #eeeeee;
            border-radius: 12px;
            height: 100%;
            transition: 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
        }

        .feature-card i {
            font-size: 35px;
            color: #dc6b35;
            margin-bottom: 15px;
        }

        footer {
            padding: 22px;
            background: #212529;
            color: #ffffff;
            text-align: center;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="container d-flex justify-content-between align-items-center">

            <a href="index.php" class="navbar-brand text-decoration-none">
                RESTORA
            </a>

            <a href="admin/auth/login.php" class="btn btn-dark px-4">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                Login
            </a>

        </div>
    </nav>


    <!-- Hero Section -->
    <section class="hero">

        <div class="hero-content">

            <span class="badge bg-warning text-dark mb-3 px-3 py-2">
                Restaurant Management System
            </span>

            <h1>
                Manage Your Restaurant
                <span>Smarter.</span>
            </h1>

            <p>
                Restora helps restaurant staff manage food menus,
                dining tables, customer orders, kitchen operations
                and billing — all in one place.
            </p>

            <a href="admin/auth/register.php" class="btn-login">
                Get Started
                <i class="bi bi-arrow-right ms-2"></i>
            </a>

        </div>

    </section>


    <!-- Features Section -->
    <section class="features">

        <div class="container">

            <div class="text-center mb-5">
                <h2 class="fw-bold">Everything You Need</h2>

                <p class="text-muted">
                    Simplify your daily restaurant operations.
                </p>
            </div>

            <div class="row g-4">

                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="bi bi-egg-fried"></i>
                        <h5 class="fw-bold">Menu Management</h5>
                        <p class="text-muted mb-0">
                            Organize food categories, menu items and prices.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="bi bi-grid-3x3-gap"></i>
                        <h5 class="fw-bold">Table Management</h5>
                        <p class="text-muted mb-0">
                            Manage dining tables and their availability.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="bi bi-receipt"></i>
                        <h5 class="fw-bold">Orders & Billing</h5>
                        <p class="text-muted mb-0">
                            Handle dine-in orders, payments and invoices.
                        </p>
                    </div>
                </div>

            </div>

        </div>

    </section>


    <!-- Footer -->
    <footer>
        <p class="mb-0">
            &copy; <?= date("Y") ?> Restora. All Rights Reserved.
        </p>
    </footer>

</body>
</html>
