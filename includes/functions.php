<?php
/**
 * Helper Functions
 *
 * Contains utility functions used throughout the application
 */

/**
 * Sanitize user input
 *
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email address
 *
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number
 *
 * @param string $phone Phone number to validate
 * @return bool True if valid, false otherwise
 */
function isValidPhone($phone) {
    // Basic phone validation - can be customized based on region
    return preg_match('/^[0-9\+\-\(\) ]{8,20}$/', $phone);
}

/**
 * Generate a unique tracking code for appointments
 *
 * @return string Tracking code in format CLINIC-YYYY-XXXX
 */
function generateTrackingCode() {
    $prefix = TRACKING_CODE_PREFIX;
    $year = date('Y');
    $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));

    return $prefix . '-' . $year . '-' . $random;
}

/**
 * Format date for display
 *
 * @param string $date Date in Y-m-d format
 * @return string Formatted date
 */
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

/**
 * Format time for display
 *
 * @param string $time Time in H:i:s format
 * @return string Formatted time
 */
function formatTime($time) {
    return date('g:i A', strtotime($time));
}

/**
 * Check if a time slot is available for a doctor
 *
 * @param int $doctorId Doctor ID
 * @param string $date Date in Y-m-d format
 * @param string $startTime Start time in H:i format
 * @param string $endTime End time in H:i format
 * @param int|null $excludeAppointmentId Optional appointment ID to exclude from check (for updates)
 * @return bool True if available, false otherwise
 */
function isTimeSlotAvailable($doctorId, $date, $startTime, $endTime, $excludeAppointmentId = null) {
    global $conn;

    $conn = getDbConnection();
    if (!$conn) {
        return false;
    }

    $query = "
        SELECT COUNT(*) FROM appointments
        WHERE doctor_id = ?
        AND appointment_date = ?
        AND status != 'cancelled'
        AND (
            (start_time <= ? AND end_time > ?) OR
            (start_time < ? AND end_time >= ?) OR
            (start_time >= ? AND end_time <= ?)
        )
    ";

    // If we're updating an existing appointment, exclude it from the check
    if ($excludeAppointmentId) {
        $query .= " AND id != ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssssssi", $doctorId, $date, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime, $excludeAppointmentId);
    } else {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssssss", $doctorId, $date, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime);
    }

    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return $count === 0;
}

/**
 * Generate CSRF token
 *
 * @return string CSRF token
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 *
 * @param string $token Token to verify
 * @return bool True if valid, false otherwise
 */
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Redirect to a URL
 *
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Display flash message
 *
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message content
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get flash message and clear it
 *
 * @return array|null Flash message or null if none
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}
