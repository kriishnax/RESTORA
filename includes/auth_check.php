
<?php

// Start session if it hasn't already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check whether user is logged in
if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit();
}

?>
