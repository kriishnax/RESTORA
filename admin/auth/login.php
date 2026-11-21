<?php
session_start();
require_once "../../config/database.php";

if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/index.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username == "" || $password == "") {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, username, password, role FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];

                header("Location: ../dashboard/index.php");
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restora | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            font-family: Arial, sans-serif;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 32px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }
        .btn-login {
            background: #6366f1;
            color: #ffffff;
            font-weight: 600;
            padding: 10px;
            border: none;
        }
        .btn-login:hover {
            background: #4f46e5;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="text-center mb-4">
            <h3 class="fw-bold mb-1">RESTORA</h3>
            <p class="text-muted small mb-0">Admin & Staff Login</p>
        </div>

        <?php if ($error != ""): ?>
            <div class="alert alert-danger py-2"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Enter username" required>
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>

            <button type="submit" class="btn btn-login w-100">Login</button>
        </form>

        <div class="text-center mt-3">
            <a href="../../index.php" class="text-muted small text-decoration-none">Back to Home</a>
        </div>
    </div>

</body>
</html>