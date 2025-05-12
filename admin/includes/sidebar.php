<?php
/**
 * Admin Sidebar
 * 
 * Contains the sidebar navigation for admin pages
 */

// Ensure this file is included, not accessed directly
if (!defined('APP_NAME')) {
    exit('Direct access not permitted');
}

// Get current page
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="sidebar-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'appointments.php' ? 'active' : ''; ?>" href="appointments.php">
                    <i class="fas fa-calendar-alt"></i>
                    Appointments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'doctors.php' ? 'active' : ''; ?>" href="doctors.php">
                    <i class="fas fa-user-md"></i>
                    Doctors
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'specialties.php' ? 'active' : ''; ?>" href="specialties.php">
                    <i class="fas fa-stethoscope"></i>
                    Specialties
                </a>
            </li>
        </ul>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Administration</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <?php if (hasRole('admin')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users"></i>
                    Users
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</nav>
