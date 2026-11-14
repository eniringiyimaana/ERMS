<?php
// index.php
session_start();

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Display login error if exists
$loginError = '';
if (isset($_SESSION['login_error'])) {
    $loginError = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

// Check for timeout
$timeoutMessage = '';
if (isset($_GET['timeout'])) {
    $timeoutMessage = 'Your session has expired due to inactivity. Please login again.';
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="MOWT Electronic Records Management System - Secure Login">
  <meta name="keywords" content="MOWT, ERMS, Records Management, Uganda Government">
  <title>MOWT Electronic Records Management System</title>

  <!-- Security Headers -->
  <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://use.fontawesome.com https://fonts.googleapis.com; img-src 'self' data:; font-src 'self' https://use.fontawesome.com https://fonts.gstatic.com; connect-src 'self'">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
  <!-- Custom Styles -->
  <style>
    :root {
      --primary-color: #f28c38;
      --primary-hover: #e67e22;
      --secondary-color: #2c3e50;
      --light-color: #f8f9fa;
      --dark-color: #343a40;
    }
    
    body {
      margin: 0;
      padding: 0;
      font-family: 'Roboto', sans-serif;
      background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url("img/mowt_background.jpg") no-repeat center center fixed;
      background-size: cover;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .navbar {
      background: linear-gradient(90deg, var(--primary-color), var(--primary-hover));
      padding: 1rem 2rem;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .navbar-brand {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 1rem;
      color: #fff;
      font-weight: 700;
      font-size: 1.25rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin: 0 auto;
    }

    .navbar-brand img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      border: 2px solid #fff;
      transition: transform 0.3s ease;
    }

    .navbar-brand img:hover {
      transform: scale(1.05);
    }

    .navbar-brand .subtitle {
      font-size: 0.9rem;
      color: #d4edda;
      font-weight: 400;
    }

    .login-container {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 2rem;
    }

    .login-card {
      width: 100%;
      max-width: 450px;
      background: rgba(255, 255, 255, 0.95);
      border-radius: 15px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
      overflow: hidden;
      animation: fadeIn 0.5s ease-in-out;
      backdrop-filter: blur(5px);
    }

    .login-card .card-body {
      padding: 2.5rem;
    }

    .login-card .card-title {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--secondary-color);
      margin-bottom: 1.5rem;
      text-align: center;
    }

    .form-floating {
      margin-bottom: 1.5rem;
      position: relative;
    }

    .form-floating .form-control {
      border-radius: 8px;
      border: 1px solid #ced4da;
      padding-left: 3rem;
      transition: border-color 0.3s ease, box-shadow 0.3s ease;
      height: calc(3.5rem + 2px);
    }

    .form-floating > label {
      left: 3rem;
    }

    .form-floating .form-control:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 8px rgba(242, 140, 56, 0.3);
    }

    .form-floating i {
      position: absolute;
      top: 50%;
      left: 1rem;
      transform: translateY(-50%);
      color: #6c757d;
      z-index: 5;
    }

    .btn-login {
      background: var(--primary-color);
      color: white;
      border: none;
      border-radius: 8px;
      padding: 0.75rem;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      transition: background 0.3s ease, transform 0.2s ease;
      width: 100%;
    }

    .btn-login:hover {
      background: var(--primary-hover);
      transform: translateY(-2px);
    }

    .alert-danger {
      border-radius: 8px;
      margin-bottom: 1.5rem;
    }

    .password-toggle {
      position: absolute;
      right: 1rem;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #6c757d;
      z-index: 5;
    }

    footer {
      background: rgba(0, 0, 0, 0.8);
      color: #fff;
      padding: 1.5rem;
      text-align: center;
      font-size: 0.9rem;
      border-radius: 10px;
      max-width: 600px;
      margin: 1rem auto;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    footer p {
      margin: 0;
      line-height: 1.6;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @media (max-width: 576px) {
      .login-card {
        margin: 1rem;
      }

      .login-card .card-body {
        padding: 1.5rem;
      }

      .navbar-brand img {
        width: 60px;
        height: 60px;
      }

      .navbar-brand span {
        font-size: 0.9rem;
      }

      .navbar-brand .subtitle {
        font-size: 0.8rem;
      }
    }

    /* Accessibility improvements */
    .sr-only {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border-width: 0;
    }

    a:focus, button:focus, input:focus {
      outline: 3px solid var(--primary-color);
      outline-offset: 2px;
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark" aria-label="Main navigation">
    <div class="container-fluid d-flex justify-content-center">
      <a class="navbar-brand" href="index.php">
        <img src="logo.png" alt="Ministry of Works and Transport Logo" class="rounded-circle">
        <div class="d-flex flex-column text-center">
          <span>Ministry of Works and Transport</span>
          <span class="subtitle">Electronic Records Management System</span>
        </div>
      </a>
    </div>
  </nav>

  <!-- Login Container -->
  <main class="login-container">
    <div class="login-card">
      <div class="card-body">
        <h1 class="card-title">User Login</h1>
        
        <?php if (!empty($loginError)): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($loginError); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($timeoutMessage)): ?>
          <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($timeoutMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <form action="login.php" method="POST" autocomplete="on">
          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
          
          <div class="form-floating mb-4">
            <i class="fas fa-envelope" aria-hidden="true"></i>
            <input type="email" class="form-control" id="userEmail" name="admin_user" placeholder="Email address" required
                   aria-describedby="emailHelp" autocomplete="email">
            <label for="userEmail">Email address</label>
            <div id="emailHelp" class="form-text sr-only">Enter your registered email address</div>
          </div>
          
          <div class="form-floating mb-4">
            <i class="fas fa-lock" aria-hidden="true"></i>
            <input type="password" class="form-control" id="userPassword" name="admin_password" placeholder="Password" required
                   aria-describedby="passwordHelp" autocomplete="current-password">
            <label for="userPassword">Password</label>
            <span class="password-toggle" id="togglePassword" role="button" aria-label="Show password">
              <i class="fas fa-eye" aria-hidden="true"></i>
            </span>
            <div id="passwordHelp" class="form-text sr-only">Enter your password</div>
          </div>
          
          <button class="btn btn-login" name="adminlog" type="submit">
            <span class="d-flex align-items-center justify-content-center">
              <i class="fas fa-sign-in-alt me-2" aria-hidden="true"></i>
              Login
            </span>
          </button>
          
          <div class="mt-3 text-center">
            
          </div>
        </form>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer>
    <p>© <?php echo date('Y'); ?> Ministry of Works and Transport — All Rights Reserved.<br>
      Designed and Developed with Excellence for Government e-Records Modernization.</p>
  </footer>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // Password visibility toggle
    document.getElementById('togglePassword').addEventListener('click', function() {
      const passwordInput = document.getElementById('userPassword');
      const icon = this.querySelector('i');
      
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
        this.setAttribute('aria-label', 'Hide password');
      } else {
        passwordInput.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
        this.setAttribute('aria-label', 'Show password');
      }
    });

    // Focus on email field when page loads
    document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('userEmail').focus();
    });

    // Form submission handler
    document.querySelector('form').addEventListener('submit', function() {
      const submitButton = document.querySelector('button[type="submit"]');
      submitButton.disabled = true;
      submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging in...';
    });
  </script>
</body>

</html>