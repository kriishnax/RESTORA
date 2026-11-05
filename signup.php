<?php 
if($_SERVER['REQUEST_METHOD']=="POST"){
    $name=$_POST['name'];
    $email=$_POST['email'];
    $password=$_POST['password'];
    
   setcookie("name", $name, time() + (86400 * 30), "/"); // Added expiry and path parameters
   setcookie("email", $email, time() + (86400 * 30), "/");
   setcookie("password", $password, time() + (86400 * 30), "/");
   header("location:login.php");
   exit();
}
require_once"header.php"
?>


<div class="container mt-5">
    <div class="row mx-auto">
        <div class="col-md-6">
            <form action="signup.php" method="post">
                <div class="mb-3">
                    
                    <label for="" class="form-label">Name</label>
                    <input type="text" class="form-control" name="name">
                </div>
                <div class="mb-3">
                    <label for="" class="form-label">Email</label>
                    <input type="email" class="form-control" name="email">
                </div>
                <div class="mb-3">
                <label for="" class="form-label">Password</label>
                <input type="password" class="form-control" name="password">
            </div>
                <button type="submit" class="btn btn-primary">Submit</button> <br><br>
                <p>Already have and account? <a href="login.php">Login</a></p>
            </form>
        </div>
    </div>

</div>


<?php require_once"footer.php"?>