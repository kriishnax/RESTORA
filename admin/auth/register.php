<?php
session_start();
require_once "../../config/database.php";

if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/index.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'staff';

    if ($name == "" || $username == "" || $password == "") {
        $error = "Please fill in all required fields.";
    } elseif (strlen($password) < 4) {
        $error = "Password must be at least 4 characters.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $check->bind_param("s", $username);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = "Username already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $username, $hashed, $role);

            if ($stmt->execute()) {
                header("Location: login.php?msg=Account created. Please login.");
                exit();
            }
            $error = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restora | Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: #f8fafc;
            font-family: Arial, sans-serif;
        }
        .register-card {
            width: 100%;
            max-width: 420px;
            padding: 32px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }
        .btn-register {
            background: #6366f1;
            color: #ffffff;
            font-weight: 600;
            padding: 10px;
            border: none;
        }
        .btn-register:hover {
            background: #4f46e5;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <div class="register-card">
        <div class="text-center mb-4">
            <h3 class="fw-bold mb-1">RESTORA</h3>
            <p class="text-muted small mb-0">Create Staff or Admin Account</p>
        </div>

        <?php if ($error != ""): ?>
            <div class="alert alert-danger py-2"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" placeholder="Enter full name" value="<?= $_POST['name'] ?? '' ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Choose a username" value="<?= $_POST['username'] ?? '' ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select">
                    <option value="staff" <?= (($_POST['role'] ?? '') === 'staff') ? 'selected' : '' ?>>Staff</option>
                    <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Create password" required>
            </div>

            <button type="submit" class="btn btn-register w-100">Register</button>
        </form>

        <div class="text-center mt-3 small">
            <span class="text-muted">Already have an account?</span>
            <a href="login.php" class="text-decoration-none fw-semibold" style="color: #6366f1;">Login</a>
        </div>
    </div>

</body>
</html>