<?php 
session_start(); // 1. Start the session at the very top

// Check if the cookies exist before assigning them, otherwise set an empty string
$email = isset($_COOKIE['email']) ? $_COOKIE['email'] : '';
$password = isset($_COOKIE['password']) ? $_COOKIE['password'] : '';
$msg="";

if($_SERVER['REQUEST_METHOD']=="POST"){
    $em=$_POST["email"];
    $pass=$_POST["password"]; // Changed variable name to avoid overwriting cookie value

    if($em=="krishnasusari@gmail.com" && $pass=="Krishna12@#"){
        // 2. Fetch the name from the cookie and save it to the session
        $_SESSION['name'] = isset($_COOKIE['name']) ? $_COOKIE['name'] : 'User'; 
        
        header("location:index.php");
        exit(); // Always use exit() after a header redirect
    }
    else{
        $msg="Incorrect Credential";
    }
}

require_once "header.php";
?>

<div class="container mt-5">
    <div class="row mx-auto">
        <div class="col-md-6">
            <form action="login.php" method="post">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" value="<?php echo htmlspecialchars($password); ?>">
                </div>
                <p class="text-danger"><?php echo $msg?></p>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>
    </div>
</div>

<?php require_once "footer.php"; ?>
