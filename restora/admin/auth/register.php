
<?php
session_start();

require_once "../../config/database.php";

Only logged-in Admin can create accounts
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";

$name = "";
$username = "";
$role = "Staff";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'Staff';

    // Validate fields
    if (
        empty($name) ||
        empty($username) ||
        empty($password) ||
        empty($confirm_password)
    ) {
        $error = "All fields are required.";

    } elseif (strlen($username) < 3) {
        $error = "Username must contain at least 3 characters.";

    } elseif (strlen($password) < 8) {
        $error = "Password must contain at least 8 characters.";

    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";

    } elseif (!in_array($role, ['Admin', 'Staff'], true)) {
        $error = "Invalid account role.";

    } else {

        // Check whether username already exists
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();

        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {

            $error = "Username already exists.";

        } else {

            // Hash password before storing it
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Insert new user
            $insert_sql = "INSERT INTO users
                           (name, username, password, role)
                           VALUES (?, ?, ?, ?)";

            $insert_stmt = $conn->prepare($insert_sql);

            $insert_stmt->bind_param(
                "ssss",
                $name,
                $username,
                $hashed_password,
                $role
            );

            if ($insert_stmt->execute()) {

                $success = "Account created successfully!";

                $name = "";
                $username = "";
                $role = "Staff";

            } else {

                $error = "Unable to create account. Please try again.";

            }

            $insert_stmt->close();
        }

        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Restora | Register Staff</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f5f7;
            font-family: Arial, sans-serif;
        }

        .register-card {
            width: 100%;
            max-width: 460px;
            padding: 35px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
        }

        .brand {
            text-align: center;
            margin-bottom: 25px;
        }

        .brand h2 {
            font-weight: 700;
        }

        .form-control,
        .form-select {
            padding: 11px;
        }

        .btn-register {
            background: #212529;
            color: #fff;
            padding: 12px;
            font-weight: 600;
        }

        .btn-register:hover {
            background: #343a40;
            color: #fff;
        }
    </style>
</head>

<body>

<div class="register-card">

    <div class="brand">
        <h2>RESTORA</h2>
        <p class="text-muted">Create Admin or Staff Account</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input
                type="text"
                name="name"
                class="form-control"
                value="<?= htmlspecialchars($name) ?>"
                required
            >
        </div>

        <div class="mb-3">
            <label class="form-label">Username</label>
            <input
                type="text"
                name="username"
                class="form-control"
                value="<?= htmlspecialchars($username) ?>"
                minlength="3"
                required
            >
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input
                type="password"
                name="password"
                class="form-control"
                minlength="8"
                required
            >
        </div>

        <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input
                type="password"
                name="confirm_password"
                class="form-control"
                minlength="8"
                required
            >
        </div>

        <div class="mb-4">
            <label class="form-label">Account Role</label>
            <select name="role" class="form-select" required>
                <option value="Staff"
                    <?= $role === 'Staff' ? 'selected' : '' ?>>
                    Staff
                </option>

                <option value="Admin"
                    <?= $role === 'Admin' ? 'selected' : '' ?>>
                    Admin
                </option>
            </select>
        </div>

        <button type="submit" class="btn btn-register w-100">
            Create Account
        </button>

    </form>

    <div class="text-center mt-3">
        <a href="../dashboard/index.php"
           class="text-decoration-none">
            Back to Dashboard
        </a>
    </div>

</div>

</body>
</html>
