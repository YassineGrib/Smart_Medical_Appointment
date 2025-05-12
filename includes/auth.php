<?php
/**
 * Authentication Functions
 * 
 * Handles user authentication and authorization
 */

/**
 * Authenticate admin user
 * 
 * @param string $username Username
 * @param string $password Password
 * @return bool True if authenticated, false otherwise
 */
function authenticateUser($username, $password) {
    $conn = getDbConnection();
    if (!$conn) {
        return false;
    }
    
    $stmt = $conn->prepare("SELECT id, username, password, role FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['is_logged_in'] = true;
            
            // Update last login time
            $updateStmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $user['id']);
            $updateStmt->execute();
            $updateStmt->close();
            
            return true;
        }
    }
    
    $stmt->close();
    return false;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
}

/**
 * Check if user has specific role
 * 
 * @param string $role Role to check
 * @return bool True if user has role, false otherwise
 */
function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    return $_SESSION['role'] === $role;
}

/**
 * Check if user has permission to access a resource
 * 
 * @param string $permission Permission to check
 * @return bool True if user has permission, false otherwise
 */
function hasPermission($permission) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Admin role has all permissions
    if ($_SESSION['role'] === 'admin') {
        return true;
    }
    
    // Check specific permissions based on role
    $rolePermissions = [
        'doctor' => ['view_appointments', 'update_appointment', 'view_patients'],
        'receptionist' => ['view_appointments', 'create_appointment', 'update_appointment', 'view_patients', 'create_patient'],
        'staff' => ['view_appointments', 'view_patients']
    ];
    
    if (isset($rolePermissions[$_SESSION['role']])) {
        return in_array($permission, $rolePermissions[$_SESSION['role']]);
    }
    
    return false;
}

/**
 * Require authentication to access a page
 * 
 * Redirects to login page if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Please log in to access this page');
        redirect(APP_URL . '/admin/index.php');
    }
}

/**
 * Require specific role to access a page
 * 
 * @param string $role Required role
 */
function requireRole($role) {
    requireLogin();
    
    if (!hasRole($role)) {
        setFlashMessage('error', 'You do not have permission to access this page');
        redirect(APP_URL . '/admin/dashboard.php');
    }
}

/**
 * Require specific permission to access a page
 * 
 * @param string $permission Required permission
 */
function requirePermission($permission) {
    requireLogin();
    
    if (!hasPermission($permission)) {
        setFlashMessage('error', 'You do not have permission to access this page');
        redirect(APP_URL . '/admin/dashboard.php');
    }
}

/**
 * Log out user
 */
function logoutUser() {
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    redirect(APP_URL . '/admin/index.php');
}
