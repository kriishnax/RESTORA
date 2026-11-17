
<?php

require_once "config/database.php";

$name = "Restora Admin";
$username = "admin";
$password = "Admin@123";

// Check if username already exists
$check = $conn->prepare(
    "SELECT id FROM users WHERE username = ?"
);

$check->bind_param("s", $username);
$check->execute();

$result = $check->get_result();

if ($result->num_rows > 0) {
    die("Admin username already exists. No account created.");
}

$check->close();

// Hash password
$hashed_password = password_hash(
    $password,
    PASSWORD_DEFAULT
);

// Insert admin account
$role = "Admin";

$stmt = $conn->prepare(
    "INSERT INTO users (name, username, password, role)
     VALUES (?, ?, ?, ?)"
);

$stmt->bind_param(
    "ssss",
    $name,
    $username,
    $hashed_password,
    $role
);

if ($stmt->execute()) {
    echo "Admin account created successfully!<br>";
    echo "Username: admin<br>";
    echo "Password: Admin@123<br>";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();

?>
