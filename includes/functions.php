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
 * @return array Result with status and message
 */
function isTimeSlotAvailable($doctorId, $date, $startTime, $endTime, $excludeAppointmentId = null) {
    global $conn;

    $conn = getDbConnection();
    if (!$conn) {
        return [
            'available' => false,
            'message' => 'Database connection failed'
        ];
    }

    // Get appointment duration from settings
    $appointmentDuration = getAppointmentDuration();

    // Check if the doctor works on this day
    $isDoctorWorkingDay = isDoctorWorkingDay($doctorId, $date);
    if (!$isDoctorWorkingDay['working']) {
        return [
            'available' => false,
            'message' => $isDoctorWorkingDay['message']
        ];
    }

    // Check if the time is within doctor's working hours
    $isWithinWorkingHours = isWithinDoctorWorkingHours($doctorId, $date, $startTime, $endTime);
    if (!$isWithinWorkingHours['within_hours']) {
        return [
            'available' => false,
            'message' => $isWithinWorkingHours['message']
        ];
    }

    // Check for conflicts with existing appointments
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

    if ($count > 0) {
        return [
            'available' => false,
            'message' => 'This time slot is already booked. Please select another time.'
        ];
    }

    return [
        'available' => true,
        'message' => 'Time slot is available'
    ];
}

/**
 * Check if a doctor works on a specific day
 *
 * @param int $doctorId Doctor ID
 * @param string $date Date in Y-m-d format
 * @return array Result with working status and message
 */
function isDoctorWorkingDay($doctorId, $date) {
    global $conn;

    $conn = getDbConnection();
    if (!$conn) {
        return [
            'working' => false,
            'message' => 'Database connection failed'
        ];
    }

    // Get doctor's schedule
    $stmt = $conn->prepare("SELECT name, schedule FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $stmt->bind_result($doctorName, $scheduleJson);
    $stmt->fetch();
    $stmt->close();

    if (!$scheduleJson) {
        return [
            'working' => false,
            'message' => 'Doctor schedule not found'
        ];
    }

    $schedule = json_decode($scheduleJson, true);
    $dayOfWeek = date('N', strtotime($date)); // 1 (Monday) to 7 (Sunday)

    if (!isset($schedule[$dayOfWeek])) {
        $dayName = date('l', strtotime($date));
        return [
            'working' => false,
            'message' => "Dr. $doctorName does not work on $dayName. Please select another date."
        ];
    }

    return [
        'working' => true,
        'message' => 'Doctor works on this day'
    ];
}

/**
 * Check if a time slot is within doctor's working hours
 *
 * @param int $doctorId Doctor ID
 * @param string $date Date in Y-m-d format
 * @param string $startTime Start time in H:i format
 * @param string $endTime End time in H:i format
 * @return array Result with status and message
 */
function isWithinDoctorWorkingHours($doctorId, $date, $startTime, $endTime) {
    global $conn;

    $conn = getDbConnection();
    if (!$conn) {
        return [
            'within_hours' => false,
            'message' => 'Database connection failed'
        ];
    }

    // Get doctor's schedule
    $stmt = $conn->prepare("SELECT name, schedule FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $stmt->bind_result($doctorName, $scheduleJson);
    $stmt->fetch();
    $stmt->close();

    if (!$scheduleJson) {
        return [
            'within_hours' => false,
            'message' => 'Doctor schedule not found'
        ];
    }

    $schedule = json_decode($scheduleJson, true);
    $dayOfWeek = date('N', strtotime($date)); // 1 (Monday) to 7 (Sunday)

    if (!isset($schedule[$dayOfWeek])) {
        $dayName = date('l', strtotime($date));
        return [
            'within_hours' => false,
            'message' => "Dr. $doctorName does not work on $dayName. Please select another date."
        ];
    }

    $daySchedule = $schedule[$dayOfWeek];
    $doctorStartTime = $daySchedule['start'];
    $doctorEndTime = $daySchedule['end'];

    // Convert to timestamps for comparison
    $appointmentStart = strtotime($startTime);
    $appointmentEnd = strtotime($endTime);
    $doctorStart = strtotime($doctorStartTime);
    $doctorEnd = strtotime($doctorEndTime);

    if ($appointmentStart < $doctorStart) {
        return [
            'within_hours' => false,
            'message' => "The selected time is before Dr. $doctorName's working hours, which start at " .
                        date('g:i A', $doctorStart) . " on this day."
        ];
    }

    if ($appointmentEnd > $doctorEnd) {
        return [
            'within_hours' => false,
            'message' => "The selected time extends beyond Dr. $doctorName's working hours, which end at " .
                        date('g:i A', $doctorEnd) . " on this day."
        ];
    }

    return [
        'within_hours' => true,
        'message' => 'Time is within doctor working hours'
    ];
}

/**
 * Get appointment duration from settings
 *
 * @return int Appointment duration in minutes
 */
function getAppointmentDuration() {
    global $conn;

    $conn = getDbConnection();
    if (!$conn) {
        return APPOINTMENT_DURATION; // Return default from config
    }

    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'appointment_duration'");
    $stmt->execute();
    $stmt->bind_result($duration);
    $found = $stmt->fetch();
    $stmt->close();

    if ($found && $duration) {
        return (int)$duration;
    }

    return APPOINTMENT_DURATION; // Return default from config
}

/**
 * Get available time slots for a doctor on a specific date
 *
 * @param int $doctorId Doctor ID
 * @param string $date Date in Y-m-d format
 * @return array Available time slots with error message if applicable
 */
function getAvailableTimeSlots($doctorId, $date) {
    global $conn;

    $result = [
        'slots' => [],
        'error' => null
    ];

    $conn = getDbConnection();
    if (!$conn) {
        $result['error'] = 'Database connection failed';
        return $result;
    }

    // Check if doctor works on this day
    $isDoctorWorkingDay = isDoctorWorkingDay($doctorId, $date);
    if (!$isDoctorWorkingDay['working']) {
        $result['error'] = $isDoctorWorkingDay['message'];
        return $result;
    }

    // Get doctor's schedule
    $stmt = $conn->prepare("SELECT schedule FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $stmt->bind_result($scheduleJson);
    $stmt->fetch();
    $stmt->close();

    $schedule = json_decode($scheduleJson, true);
    $dayOfWeek = date('N', strtotime($date)); // 1 (Monday) to 7 (Sunday)
    $daySchedule = $schedule[$dayOfWeek];

    // Get appointment duration from settings
    $appointmentDuration = getAppointmentDuration();

    // Generate time slots
    $startHour = isset($daySchedule['start']) ? $daySchedule['start'] : CLINIC_START_TIME;
    $endHour = isset($daySchedule['end']) ? $daySchedule['end'] : CLINIC_END_TIME;

    $currentTime = strtotime($startHour);
    $endTime = strtotime($endHour);

    while ($currentTime < $endTime) {
        $slotStart = date('H:i:s', $currentTime);
        $slotEnd = date('H:i:s', $currentTime + ($appointmentDuration * 60));

        // Check if slot is available
        $availability = isTimeSlotAvailable($doctorId, $date, $slotStart, $slotEnd);

        if ($availability['available']) {
            $result['slots'][] = [
                'start' => $slotStart,
                'end' => $slotEnd,
                'display' => date('g:i A', $currentTime)
            ];
        }

        // Move to next slot
        $currentTime += ($appointmentDuration * 60);
    }

    if (empty($result['slots']) && !$result['error']) {
        $result['error'] = 'No available time slots for the selected date. Please choose another date.';
    }

    return $result;
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
