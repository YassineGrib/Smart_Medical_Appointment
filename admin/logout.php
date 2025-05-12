<?php
/**
 * Admin Logout
 * 
 * Handles user logout
 */

// Include required files
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Log out user
logoutUser();

// Redirect to login page (this is handled in logoutUser function)
?>
