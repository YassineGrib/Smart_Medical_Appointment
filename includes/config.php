<?php
/**
 * Configuration Settings
 * 
 * Contains global configuration settings for the application
 */

// Application settings
define('APP_NAME', 'Smart Medical Appointment');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/Smart_Medical_Appointment');
define('ADMIN_EMAIL', 'admin@example.com');

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Error reporting settings
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Time zone settings
date_default_timezone_set('UTC');

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['application/pdf', 'image/jpeg', 'image/png']);
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');

// Appointment settings
define('APPOINTMENT_DURATION', 30); // minutes
define('CLINIC_START_TIME', '09:00'); // 24-hour format
define('CLINIC_END_TIME', '17:00'); // 24-hour format
define('CLINIC_DAYS', [1, 2, 3, 4, 5]); // Monday to Friday (0 = Sunday)

// Tracking code format
define('TRACKING_CODE_PREFIX', 'CLINIC');

/**
 * Load configuration from database
 * 
 * This function will be implemented later to allow admin to change
 * configuration settings through the admin interface
 */
function loadConfigFromDatabase() {
    // This will be implemented later
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
