<?php
/**
 * Booking Page
 * 
 * Handles the appointment booking process
 */

// Include required files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Initialize variables
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$specialtyId = isset($_GET['specialty']) ? (int)$_GET['specialty'] : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$doctorId = isset($_GET['doctor']) ? (int)$_GET['doctor'] : 0;
$timeSlot = isset($_GET['time']) ? $_GET['time'] : '';
$errors = [];

// Get database connection
$conn = getDbConnection();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid form submission';
    } else {
        // Get form data
        $patientName = sanitizeInput($_POST['patient_name']);
        $patientPhone = sanitizeInput($_POST['patient_phone']);
        $patientEmail = sanitizeInput($_POST['patient_email']);
        $doctorId = (int)$_POST['doctor_id'];
        $appointmentDate = $_POST['appointment_date'];
        $startTime = $_POST['start_time'];
        $notes = sanitizeInput($_POST['notes']);
        
        // Calculate end time (30 minutes after start time)
        $endTime = date('H:i:s', strtotime($startTime . ' + ' . APPOINTMENT_DURATION . ' minutes'));
        
        // Validate form data
        if (empty($patientName)) {
            $errors[] = 'Patient name is required';
        }
        
        if (empty($patientPhone) || !isValidPhone($patientPhone)) {
            $errors[] = 'Valid phone number is required';
        }
        
        if (empty($patientEmail) || !isValidEmail($patientEmail)) {
            $errors[] = 'Valid email address is required';
        }
        
        if ($doctorId <= 0) {
            $errors[] = 'Doctor selection is required';
        }
        
        if (empty($appointmentDate) || strtotime($appointmentDate) < strtotime(date('Y-m-d'))) {
            $errors[] = 'Valid appointment date is required';
        }
        
        if (empty($startTime)) {
            $errors[] = 'Appointment time is required';
        }
        
        // Check if time slot is available
        if (empty($errors) && !isTimeSlotAvailable($doctorId, $appointmentDate, $startTime, $endTime)) {
            $errors[] = 'Selected time slot is no longer available. Please choose another time.';
        }
        
        // Handle file upload if provided
        $documentPath = '';
        if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
            $fileType = $_FILES['document']['type'];
            $fileSize = $_FILES['document']['size'];
            $fileName = $_FILES['document']['name'];
            $fileTmpName = $_FILES['document']['tmp_name'];
            
            // Validate file type and size
            if (!in_array($fileType, ALLOWED_FILE_TYPES)) {
                $errors[] = 'Invalid file type. Allowed types: PDF, JPEG, PNG';
            }
            
            if ($fileSize > MAX_FILE_SIZE) {
                $errors[] = 'File size exceeds the maximum limit of ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB';
            }
            
            if (empty($errors)) {
                // Generate unique filename
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = uniqid('doc_') . '.' . $fileExtension;
                $uploadPath = UPLOAD_DIR . $newFileName;
                
                // Move uploaded file
                if (move_uploaded_file($fileTmpName, $uploadPath)) {
                    $documentPath = $newFileName;
                } else {
                    $errors[] = 'Failed to upload file';
                }
            }
        }
        
        // Save appointment if no errors
        if (empty($errors)) {
            // Generate tracking code
            $trackingCode = generateTrackingCode();
            
            // Insert appointment into database
            $stmt = $conn->prepare("
                INSERT INTO appointments (
                    tracking_code, patient_name, patient_phone, patient_email, 
                    doctor_id, appointment_date, start_time, end_time, 
                    status, notes, documents
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)
            ");
            
            $stmt->bind_param(
                "ssssissss", 
                $trackingCode, $patientName, $patientPhone, $patientEmail, 
                $doctorId, $appointmentDate, $startTime, $endTime, 
                $notes, $documentPath
            );
            
            if ($stmt->execute()) {
                $appointmentId = $stmt->insert_id;
                $stmt->close();
                
                // Redirect to confirmation page
                redirect("confirmation.php?id=$appointmentId&code=$trackingCode");
            } else {
                $errors[] = 'Failed to book appointment: ' . $stmt->error;
                $stmt->close();
            }
        }
    }
}

// Get specialties for dropdown
$specialties = [];
if ($conn) {
    $stmt = $conn->prepare("SELECT id, name FROM specialties ORDER BY name");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $specialties[] = $row;
    }
    
    $stmt->close();
}

// Get doctors for selected specialty
$doctors = [];
if ($conn && $specialtyId > 0) {
    $stmt = $conn->prepare("SELECT id, name FROM doctors WHERE specialty_id = ? ORDER BY name");
    $stmt->bind_param("i", $specialtyId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
    
    $stmt->close();
}

// Get available time slots for selected doctor and date
$timeSlots = [];
if ($conn && $doctorId > 0 && !empty($date)) {
    // Get doctor's schedule
    $stmt = $conn->prepare("SELECT schedule FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $stmt->bind_result($scheduleJson);
    $stmt->fetch();
    $stmt->close();
    
    $schedule = json_decode($scheduleJson, true);
    $dayOfWeek = date('N', strtotime($date)); // 1 (Monday) to 7 (Sunday)
    
    // Check if doctor works on this day
    if (isset($schedule[$dayOfWeek])) {
        $daySchedule = $schedule[$dayOfWeek];
        $startHour = isset($daySchedule['start']) ? $daySchedule['start'] : CLINIC_START_TIME;
        $endHour = isset($daySchedule['end']) ? $daySchedule['end'] : CLINIC_END_TIME;
        
        // Generate time slots
        $currentTime = strtotime($startHour);
        $endTime = strtotime($endHour);
        
        while ($currentTime < $endTime) {
            $slotStart = date('H:i:s', $currentTime);
            $slotEnd = date('H:i:s', $currentTime + (APPOINTMENT_DURATION * 60));
            
            // Check if slot is available
            if (isTimeSlotAvailable($doctorId, $date, $slotStart, $slotEnd)) {
                $timeSlots[] = [
                    'start' => $slotStart,
                    'end' => $slotEnd,
                    'display' => date('g:i A', $currentTime)
                ];
            }
            
            // Move to next slot
            $currentTime += (APPOINTMENT_DURATION * 60);
        }
    }
}

// Get doctor details if selected
$doctorDetails = null;
if ($conn && $doctorId > 0) {
    $stmt = $conn->prepare("
        SELECT d.id, d.name, d.phone, d.email, s.name AS specialty
        FROM doctors d
        JOIN specialties s ON d.specialty_id = s.id
        WHERE d.id = ?
    ");
    
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $doctorDetails = $row;
    }
    
    $stmt->close();
}

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <!-- Custom styles -->
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
            padding-bottom: 20px;
        }
        
        .booking-container {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .booking-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .booking-steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #e9ecef;
            z-index: 1;
        }
        
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            position: relative;
            z-index: 2;
        }
        
        .step.active {
            background-color: #007bff;
            color: white;
        }
        
        .step.completed {
            background-color: #28a745;
            color: white;
        }
        
        .step-label {
            position: absolute;
            top: 35px;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            font-size: 0.8rem;
        }
        
        .time-slot {
            display: inline-block;
            margin: 5px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .time-slot:hover {
            background-color: #f8f9fa;
        }
        
        .time-slot.selected {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .doctor-card {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .doctor-card:hover {
            background-color: #f8f9fa;
        }
        
        .doctor-card.selected {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-hospital-user mr-2"></i>
                <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link" href="booking.php">Book Appointment</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tracking.php">Track Appointment</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/index.php">Admin Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="booking-container">
            <h2 class="text-center mb-4">Book Your Appointment</h2>
            
            <!-- Booking Steps -->
            <div class="booking-steps">
                <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                    1
                    <span class="step-label">Select Specialty</span>
                </div>
                <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                    2
                    <span class="step-label">Choose Doctor</span>
                </div>
                <div class="step <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>">
                    3
                    <span class="step-label">Select Date & Time</span>
                </div>
                <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>">
                    4
                    <span class="step-label">Patient Details</span>
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Step 1: Select Specialty -->
            <?php if ($step === 1): ?>
                <form action="booking.php" method="get">
                    <input type="hidden" name="step" value="2">
                    
                    <div class="form-group">
                        <label for="specialty">Select Medical Specialty</label>
                        <select class="form-control" id="specialty" name="specialty" required>
                            <option value="">-- Select Specialty --</option>
                            <?php foreach ($specialties as $specialty): ?>
                                <option value="<?php echo $specialty['id']; ?>" <?php echo $specialtyId == $specialty['id'] ? 'selected' : ''; ?>>
                                    <?php echo $specialty['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Next: Choose Doctor</button>
                        <a href="index.php" class="btn btn-link">Cancel</a>
                    </div>
                </form>
            
            <!-- Step 2: Choose Doctor -->
            <?php elseif ($step === 2): ?>
                <?php if (empty($doctors)): ?>
                    <div class="alert alert-info">
                        No doctors available for the selected specialty. Please choose another specialty.
                    </div>
                    <a href="booking.php?step=1" class="btn btn-primary">Back to Specialties</a>
                <?php else: ?>
                    <form action="booking.php" method="get">
                        <input type="hidden" name="step" value="3">
                        <input type="hidden" name="specialty" value="<?php echo $specialtyId; ?>">
                        
                        <div class="form-group">
                            <label>Select a Doctor</label>
                            <?php foreach ($doctors as $doctor): ?>
                                <div class="doctor-card <?php echo $doctorId == $doctor['id'] ? 'selected' : ''; ?>" onclick="selectDoctor(<?php echo $doctor['id']; ?>)">
                                    <div class="row">
                                        <div class="col-md-2 text-center">
                                            <i class="fas fa-user-md fa-3x text-primary"></i>
                                        </div>
                                        <div class="col-md-10">
                                            <h5><?php echo $doctor['name']; ?></h5>
                                            <p class="mb-0">Specialty: <?php echo $specialties[$specialtyId - 1]['name']; ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <input type="hidden" id="doctor" name="doctor" value="<?php echo $doctorId; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" id="next-btn" disabled>Next: Select Date & Time</button>
                            <a href="booking.php?step=1&specialty=<?php echo $specialtyId; ?>" class="btn btn-link">Back</a>
                        </div>
                    </form>
                <?php endif; ?>
            
            <!-- Step 3: Select Date & Time -->
            <?php elseif ($step === 3): ?>
                <form action="booking.php" method="get">
                    <input type="hidden" name="step" value="4">
                    <input type="hidden" name="specialty" value="<?php echo $specialtyId; ?>">
                    <input type="hidden" name="doctor" value="<?php echo $doctorId; ?>">
                    
                    <div class="form-group">
                        <label for="date">Select Date</label>
                        <input type="date" class="form-control" id="date" name="date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo $date; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Select Time</label>
                        <div class="time-slots">
                            <?php if (empty($timeSlots)): ?>
                                <div class="alert alert-info">
                                    No available time slots for the selected date. Please choose another date.
                                </div>
                            <?php else: ?>
                                <?php foreach ($timeSlots as $slot): ?>
                                    <div class="time-slot <?php echo $timeSlot === $slot['start'] ? 'selected' : ''; ?>" 
                                         onclick="selectTimeSlot('<?php echo $slot['start']; ?>')">
                                        <?php echo $slot['display']; ?>
                                    </div>
                                <?php endforeach; ?>
                                <input type="hidden" id="time" name="time" value="<?php echo $timeSlot; ?>" required>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" id="time-next-btn" <?php echo empty($timeSlots) ? 'disabled' : ''; ?>>
                            Next: Patient Details
                        </button>
                        <a href="booking.php?step=2&specialty=<?php echo $specialtyId; ?>&doctor=<?php echo $doctorId; ?>" class="btn btn-link">Back</a>
                    </div>
                </form>
            
            <!-- Step 4: Patient Details -->
            <?php elseif ($step === 4): ?>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Appointment Details</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($doctorDetails): ?>
                                    <p><strong>Doctor:</strong> <?php echo $doctorDetails['name']; ?></p>
                                    <p><strong>Specialty:</strong> <?php echo $doctorDetails['specialty']; ?></p>
                                    <p><strong>Date:</strong> <?php echo formatDate($date); ?></p>
                                    <p><strong>Time:</strong> <?php echo formatTime($timeSlot); ?></p>
                                <?php else: ?>
                                    <p class="text-danger">Invalid doctor selection</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <form action="booking.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="doctor_id" value="<?php echo $doctorId; ?>">
                    <input type="hidden" name="appointment_date" value="<?php echo $date; ?>">
                    <input type="hidden" name="start_time" value="<?php echo $timeSlot; ?>">
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="patient_name">Full Name</label>
                            <input type="text" class="form-control" id="patient_name" name="patient_name" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="patient_phone">Phone Number</label>
                            <input type="tel" class="form-control" id="patient_phone" name="patient_phone" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="patient_email">Email Address</label>
                        <input type="email" class="form-control" id="patient_email" name="patient_email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="document">Upload Document (Optional)</label>
                        <input type="file" class="form-control-file" id="document" name="document">
                        <small class="form-text text-muted">
                            Allowed file types: PDF, JPEG, PNG. Maximum size: <?php echo MAX_FILE_SIZE / 1024 / 1024; ?>MB
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="book_appointment" class="btn btn-success">Book Appointment</button>
                        <a href="booking.php?step=3&specialty=<?php echo $specialtyId; ?>&doctor=<?php echo $doctorId; ?>&date=<?php echo $date; ?>" class="btn btn-link">Back</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        // Doctor selection
        function selectDoctor(doctorId) {
            document.getElementById('doctor').value = doctorId;
            document.getElementById('next-btn').disabled = false;
            
            // Update UI
            document.querySelectorAll('.doctor-card').forEach(function(card) {
                card.classList.remove('selected');
            });
            
            event.currentTarget.classList.add('selected');
        }
        
        // Time slot selection
        function selectTimeSlot(time) {
            document.getElementById('time').value = time;
            document.getElementById('time-next-btn').disabled = false;
            
            // Update UI
            document.querySelectorAll('.time-slot').forEach(function(slot) {
                slot.classList.remove('selected');
            });
            
            event.currentTarget.classList.add('selected');
        }
        
        // Date change handler
        document.addEventListener('DOMContentLoaded', function() {
            var dateInput = document.getElementById('date');
            if (dateInput) {
                dateInput.addEventListener('change', function() {
                    var form = this.form;
                    form.submit();
                });
            }
        });
    </script>
</body>
</html>
