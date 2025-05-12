<?php
/**
 * Admin Login Page
 * 
 * Handles admin authentication
 */

// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Redirect to dashboard if already logged in
if (isLoggedIn()) {
    redirect(APP_URL . '/admin/dashboard.php');
}

// Initialize variables
$username = '';
$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid form submission';
    } else {
        // Get form data
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password']; // Don't sanitize password
        
        // Validate form data
        if (empty($username) || empty($password)) {
            $error = 'Username and password are required';
        } else {
            // Attempt to authenticate user
            if (authenticateUser($username, $password)) {
                // Redirect to dashboard on successful login
                redirect(APP_URL . '/admin/dashboard.php');
            } else {
                $error = 'Invalid username or password';
            }
        }
    }
}

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 2rem;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .login-logo i {
            font-size: 3rem;
            color: #007bff;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .btn-login {
            background-color: #007bff;
            border-color: #007bff;
            font-weight: bold;
            padding: 0.6rem 1rem;
        }
        .alert {
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-logo">
                <i class="fas fa-hospital-user"></i>
                <h2><?php echo APP_NAME; ?></h2>
                <p class="text-muted">Admin Panel</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php
            // Display flash message if any
            $flashMessage = getFlashMessage();
            if ($flashMessage) {
                $alertClass = 'alert-info';
                if ($flashMessage['type'] === 'error') {
                    $alertClass = 'alert-danger';
                } elseif ($flashMessage['type'] === 'success') {
                    $alertClass = 'alert-success';
                } elseif ($flashMessage['type'] === 'warning') {
                    $alertClass = 'alert-warning';
                }
                
                echo '<div class="alert ' . $alertClass . '" role="alert">' . $flashMessage['message'] . '</div>';
            }
            ?>
            
            <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                        </div>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo $username; ?>" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        </div>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block btn-login">Log In</button>
                </div>
                
                <div class="text-center">
                    <a href="<?php echo APP_URL; ?>" class="text-decoration-none">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Homepage
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
