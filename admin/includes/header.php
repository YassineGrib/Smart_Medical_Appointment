<?php
/**
 * Admin Header
 * 
 * Contains the header HTML for admin pages
 */

// Ensure this file is included, not accessed directly
if (!defined('APP_NAME')) {
    exit('Direct access not permitted');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo APP_NAME; ?> Admin</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <!-- FullCalendar CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.5.1/main.min.css">
    
    <!-- Custom styles -->
    <style>
        body {
            font-size: .875rem;
        }
        
        .feather {
            width: 16px;
            height: 16px;
            vertical-align: text-bottom;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }
        
        @media (max-width: 767.98px) {
            .sidebar {
                top: 5rem;
            }
        }
        
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .sidebar .nav-link {
            font-weight: 500;
            color: #333;
        }
        
        .sidebar .nav-link .feather {
            margin-right: 4px;
            color: #727272;
        }
        
        .sidebar .nav-link.active {
            color: #007bff;
        }
        
        .sidebar .nav-link:hover .feather,
        .sidebar .nav-link.active .feather {
            color: inherit;
        }
        
        .sidebar-heading {
            font-size: .75rem;
            text-transform: uppercase;
        }
        
        /* Navbar */
        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
            font-size: 1rem;
            background-color: rgba(0, 0, 0, .25);
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .25);
        }
        
        .navbar .navbar-toggler {
            top: .25rem;
            right: 1rem;
        }
        
        .navbar .form-control {
            padding: .75rem 1rem;
            border-width: 0;
            border-radius: 0;
        }
        
        .form-control-dark {
            color: #fff;
            background-color: rgba(255, 255, 255, .1);
            border-color: rgba(255, 255, 255, .1);
        }
        
        .form-control-dark:focus {
            border-color: transparent;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, .25);
        }
        
        /* Cards */
        .border-left-primary {
            border-left: 4px solid #4e73df;
        }
        
        .border-left-success {
            border-left: 4px solid #1cc88a;
        }
        
        .border-left-info {
            border-left: 4px solid #36b9cc;
        }
        
        .border-left-warning {
            border-left: 4px solid #f6c23e;
        }
        
        .border-left-danger {
            border-left: 4px solid #e74a3b;
        }
        
        /* Calendar */
        #calendar {
            height: 400px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="dashboard.php">
            <?php echo APP_NAME; ?>
        </a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-toggle="collapse" data-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <ul class="navbar-nav px-3 ml-auto">
            <li class="nav-item text-nowrap d-flex align-items-center">
                <span class="text-white mr-3">Welcome, <?php echo $_SESSION['username']; ?></span>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Sign out
                </a>
            </li>
        </ul>
    </nav>
    
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
        
        echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show m-3" role="alert">';
        echo $flashMessage['message'];
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
        echo '<span aria-hidden="true">&times;</span>';
        echo '</button>';
        echo '</div>';
    }
    ?>
