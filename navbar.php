<?php 
// Start the session securely if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}
?>

<nav class="navbar flex justify-item-evenly navbar-expand-lg navbar-dark bg-dark mx-5 mt-3 fixed-top rounded-pill px-3">
  <div class="container-fluid">
    <div class="start">
        <a class="navbar-brand" href="#">Restora</a>
    </div>
    <div class="end pe-4">

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <a class="nav-link active" aria-current="page" href="#">Home</a>
            </li>
            <li class="nav-item">
              <a class="nav-link active" href="#">Menu</a>
            </li>
            <li class="nav-item">
              <a class="nav-link active" href="#">About</a>
            </li>
            <li class="nav-item">
              <a class="nav-link active" href="#">My Orders</a>
            </li>
          </ul>
          
          <div class="d-flex align-items-center">
            <?php if (isset($_SESSION['name'])): ?>
                <?php 
                // Get the first letter of the user's name and capitalize it
                $first_letter = strtoupper(substr($_SESSION['name'], 0, 1)); 
                ?>
                <!-- Dropdown menu displaying the profile initial -->
                <div class="dropdown mx-4">
                  <a class="btn btn-light rounded-circle d-inline-flex align-items-center justify-content-center fw-bold  text-dark" 
                     href="#" 
                     role="button" 
                     id="userMenu" 
                     data-bs-toggle="dropdown" 
                     aria-expanded="false" 
                     style="width: 40px; height: 40px; text-decoration: none;">
                     <?php echo $first_letter; ?>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end mt-2" aria-labelledby="userMenu">
                    <li><span class="dropdown-item-text fw-bold text-secondary">Hello, <?php echo htmlspecialchars($_SESSION['name']); ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                    <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
                  </ul>
                </div>
            <?php else: ?>
                <!-- Default login link when guest -->
                <a href="signup.php" class="btn btn-outline-light mx-4">Login</a>
            <?php endif; ?>
          </div>
        </div>
      
    </div>
  </div>
</nav>
