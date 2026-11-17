
<?php
session_start();

require_once "../../config/database.php";

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/index.php");
    exit();
}

$error = "";

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {

        $error = "Please enter both username and password.";

    } else {

        // Find user using prepared statement
        $sql = "SELECT id, name, username, password, role
                FROM users
                WHERE username = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param("s", $username);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {

                $user = $result->fetch_assoc();

                // Verify hashed password
                if (password_verify($password, $user['password'])) {

                    // Regenerate session ID after login
                    session_regenerate_id(true);

                    // Store user information in session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];

                    $stmt->close();

                    header("Location: ../dashboard/index.php");
                    exit();

                } else {

                    $error = "Invalid username or password.";

                }

            } else {

                $error = "Invalid username or password.";

            }

            $stmt->close();

        } else {

            $error = "Something went wrong. Please try again.";

        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Restora | Admin Login</title>

    <!-- Bootstrap 5 CSS -->
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

        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 35px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
        }

        .brand {
            text-align: center;
            margin-bottom: 28px;
        }

        .brand h2 {
            font-weight: 700;
            color: #212529;
        }

        .brand p {
            color: #777;
            margin-bottom: 0;
        }

        .form-control {
            padding: 12px;
        }

        .btn-login {
            padding: 12px;
            font-weight: 600;
            background: #212529;
            color: #ffffff;
            border: none;
        }

        .btn-login:hover {
            background: #343a40;
            color: #ffffff;
        }
    </style>
</head>

<body>

    <div class="login-card">

        <div class="brand">
            <h2>RESTORA</h2>
            <p>Restaurant Management System</p>
            <p class="mt-2">Admin & Staff Login</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="mb-3">
                <label class="form-label">Username</label>

                <input
                    type="text"
                    name="username"
                    class="form-control"
                    placeholder="Enter username"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>

                <input
                    type="password"
                    name="password"
                    class="form-control"
                    placeholder="Enter password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="btn btn-login w-100">
                Login
            </button>

        </form>

        <p class="text-center text-muted mt-4 mb-0">
            &copy; <?= date("Y") ?> Restora
        </p>

    </div>

</body>
</html>
