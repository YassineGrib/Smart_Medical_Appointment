<?php
/**
 * Get Available Time Slots API
 *
 * Returns available time slots for a doctor on a specific date
 */

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Require login to access this API
requireLogin();

// Set content type to JSON
header('Content-Type: application/json');

// Get parameters
$doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';
$appointmentId = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;

// Validate parameters
if ($doctorId <= 0 || empty($date)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters',
        'slots' => []
    ]);
    exit;
}

// Get available time slots
$availableSlots = getAvailableTimeSlots($doctorId, $date);

// If there's an error, return it
if ($availableSlots['error']) {
    echo json_encode([
        'success' => false,
        'message' => $availableSlots['error'],
        'slots' => []
    ]);
    exit;
}

// Format slots for display
$formattedSlots = [];
foreach ($availableSlots['slots'] as $slot) {
    $formattedSlots[] = [
        'start' => $slot['start'],
        'display' => $slot['display']
    ];
}

// Return JSON response
echo json_encode([
    'success' => true,
    'message' => 'Available time slots retrieved successfully',
    'slots' => $formattedSlots
]);
