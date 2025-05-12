<?php
/**
 * Get Appointments API
 *
 * Returns appointments in FullCalendar format
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

// Get database connection
$conn = getDbConnection();
if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Get filter parameters
$doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
$specialtyId = isset($_GET['specialty_id']) ? (int)$_GET['specialty_id'] : 0;
$startDate = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-1 month'));
$endDate = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d', strtotime('+1 month'));

// Build query
$query = "
    SELECT 
        a.id, 
        a.patient_name, 
        a.appointment_date, 
        a.start_time, 
        a.end_time, 
        a.status, 
        d.name AS doctor_name,
        s.name AS specialty_name
    FROM 
        appointments a
    JOIN 
        doctors d ON a.doctor_id = d.id
    JOIN 
        specialties s ON d.specialty_id = s.id
    WHERE 
        a.appointment_date BETWEEN ? AND ?
";

$params = [$startDate, $endDate];
$types = "ss";

// Add doctor filter if specified
if ($doctorId > 0) {
    $query .= " AND a.doctor_id = ?";
    $params[] = $doctorId;
    $types .= "i";
}

// Add specialty filter if specified
if ($specialtyId > 0) {
    $query .= " AND d.specialty_id = ?";
    $params[] = $specialtyId;
    $types .= "i";
}

// Order by date and time
$query .= " ORDER BY a.appointment_date, a.start_time";

// Prepare and execute query
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Format appointments for FullCalendar
$events = [];
while ($row = $result->fetch_assoc()) {
    // Define color based on status
    $color = '#6c757d'; // Default gray
    $textColor = '#ffffff'; // White text
    
    switch ($row['status']) {
        case 'pending':
            $color = '#f6c23e'; // Warning yellow
            $textColor = '#212529'; // Dark text for better contrast
            break;
        case 'confirmed':
            $color = '#36b9cc'; // Info blue
            break;
        case 'completed':
            $color = '#1cc88a'; // Success green
            break;
        case 'cancelled':
            $color = '#e74a3b'; // Danger red
            break;
    }
    
    // Format start and end datetime
    $start = $row['appointment_date'] . 'T' . $row['start_time'];
    $end = $row['appointment_date'] . 'T' . $row['end_time'];
    
    // Create event object
    $events[] = [
        'id' => $row['id'],
        'title' => $row['patient_name'],
        'start' => $start,
        'end' => $end,
        'backgroundColor' => $color,
        'borderColor' => $color,
        'textColor' => $textColor,
        'extendedProps' => [
            'doctor' => $row['doctor_name'],
            'specialty' => $row['specialty_name'],
            'status' => $row['status'],
            'patient' => $row['patient_name'],
            'time' => formatTime($row['start_time']) . ' - ' . formatTime($row['end_time'])
        ]
    ];
}

// Close statement
$stmt->close();

// Return JSON response
echo json_encode($events);
