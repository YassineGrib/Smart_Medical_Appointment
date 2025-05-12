<?php
/**
 * Confirmation Page
 * 
 * Displays appointment confirmation details
 */

// Include required files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get appointment ID and tracking code from URL
$appointmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$trackingCode = isset($_GET['code']) ? $_GET['code'] : '';

// Initialize variables
$appointment = null;
$error = '';

// Get database connection
$conn = getDbConnection();

// Get appointment details
if ($conn && $appointmentId > 0 && !empty($trackingCode)) {
    $stmt = $conn->prepare("
        SELECT a.*, d.name AS doctor_name, s.name AS specialty_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        JOIN specialties s ON d.specialty_id = s.id
        WHERE a.id = ? AND a.tracking_code = ?
    ");
    
    $stmt->bind_param("is", $appointmentId, $trackingCode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $appointment = $result->fetch_assoc();
    } else {
        $error = 'Appointment not found';
    }
    
    $stmt->close();
} else {
    $error = 'Invalid appointment information';
}

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Confirmation - <?php echo APP_NAME; ?></title>
    
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
        
        .confirmation-container {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .confirmation-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .confirmation-header i {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
        
        .tracking-code {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
            padding: 0.5rem;
            border: 2px dashed #007bff;
            border-radius: 5px;
            display: inline-block;
            margin: 1rem 0;
        }
        
        .appointment-details {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .appointment-details h4 {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .detail-row {
            margin-bottom: 0.5rem;
        }
        
        .detail-label {
            font-weight: bold;
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
                    <li class="nav-item">
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
        <div class="confirmation-container">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <h4 class="alert-heading">Error</h4>
                    <p><?php echo $error; ?></p>
                    <hr>
                    <p class="mb-0">Please try again or contact support for assistance.</p>
                    <div class="mt-3">
                        <a href="index.php" class="btn btn-primary">Return to Homepage</a>
                    </div>
                </div>
            <?php elseif ($appointment): ?>
                <div class="confirmation-header">
                    <i class="fas fa-check-circle"></i>
                    <h2>Appointment Confirmed!</h2>
                    <p class="lead">Your appointment has been successfully booked.</p>
                    <div class="tracking-code">
                        <?php echo $appointment['tracking_code']; ?>
                    </div>
                    <p class="text-muted">Please save this tracking code to check your appointment status later.</p>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="appointment-details">
                            <h4>Appointment Details</h4>
                            <div class="detail-row">
                                <span class="detail-label">Doctor:</span>
                                <span><?php echo $appointment['doctor_name']; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Specialty:</span>
                                <span><?php echo $appointment['specialty_name']; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Date:</span>
                                <span><?php echo formatDate($appointment['appointment_date']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Time:</span>
                                <span><?php echo formatTime($appointment['start_time']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Status:</span>
                                <span class="badge badge-warning">Pending</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="appointment-details">
                            <h4>Patient Information</h4>
                            <div class="detail-row">
                                <span class="detail-label">Name:</span>
                                <span><?php echo $appointment['patient_name']; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Phone:</span>
                                <span><?php echo $appointment['patient_phone']; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Email:</span>
                                <span><?php echo $appointment['patient_email']; ?></span>
                            </div>
                            <?php if (!empty($appointment['notes'])): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Notes:</span>
                                    <span><?php echo $appointment['notes']; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p>You will receive a confirmation email shortly with these details.</p>
                    <div class="btn-group">
                        <a href="index.php" class="btn btn-primary">Return to Homepage</a>
                        <a href="tracking.php?code=<?php echo $appointment['tracking_code']; ?>" class="btn btn-info">Track Appointment</a>
                    </div>
                </div>
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
</body>
</html>
