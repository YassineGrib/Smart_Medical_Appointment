<?php
/**
 * Tracking Page
 *
 * Allows patients to track their appointments using tracking code
 */

// Include required files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Initialize variables
$trackingCode = isset($_GET['code']) ? $_GET['code'] : '';
$appointment = null;
$error = '';
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['track_appointment'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid form submission';
    } else {
        // Get tracking code from form
        $trackingCode = sanitizeInput($_POST['tracking_code']);

        // Validate tracking code
        if (empty($trackingCode)) {
            $error = 'Tracking code is required';
        }
    }
}

// Get database connection
$conn = getDbConnection();

// Get appointment details if tracking code is provided
if ($conn && !empty($trackingCode) && empty($error)) {
    $stmt = $conn->prepare("
        SELECT a.*, d.name AS doctor_name, s.name AS specialty_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        JOIN specialties s ON d.specialty_id = s.id
        WHERE a.tracking_code = ?
    ");

    $stmt->bind_param("s", $trackingCode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $appointment = $result->fetch_assoc();
        $success = true;
    } else {
        $error = 'No appointment found with the provided tracking code';
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
    <title>Track Appointment - <?php echo APP_NAME; ?></title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- Custom styles -->
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #34a853;
            --accent-color: #4285f4;
            --dark-color: #202124;
            --light-color: #f8f9fa;
        }

        body {
            background-color: #f8f9fa;
            padding-top: 20px;
            padding-bottom: 20px;
            font-family: 'Roboto', sans-serif;
        }

        /* Navbar styles */
        .navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0.8rem 1rem;
            margin-bottom: 20px;
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.3rem;
        }

        .nav-item {
            margin: 0 0.25rem;
        }

        .nav-link {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-link i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }

        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.15);
        }

        .admin-link {
            background-color: var(--secondary-color);
            color: white !important;
            border-radius: 50px;
            padding: 0.5rem 1.2rem;
            margin-left: 0.5rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .admin-link:hover {
            background-color: #2d9249;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        .tracking-container {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .tracking-form {
            max-width: 500px;
            margin: 0 auto;
        }

        .appointment-details {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 1.5rem;
            margin-top: 2rem;
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

        .status-badge {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }

        .tracking-code {
            font-size: 1.2rem;
            font-weight: bold;
            color: #007bff;
            padding: 0.5rem;
            border: 2px dashed #007bff;
            border-radius: 5px;
            display: inline-block;
            margin: 1rem 0;
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
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="booking.php">
                            <i class="fas fa-calendar-plus"></i> Book Appointment
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link" href="tracking.php">
                            <i class="fas fa-search"></i> Track Appointment
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link admin-link" href="admin/index.php">
                            <i class="fas fa-user-shield"></i> Admin Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="tracking-container">
            <h2 class="text-center mb-4">Track Your Appointment</h2>

            <?php if (!$success): ?>
                <div class="tracking-form">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                        <div class="form-group">
                            <label for="tracking_code">Enter Your Tracking Code</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                                <input type="text" class="form-control" id="tracking_code" name="tracking_code" value="<?php echo $trackingCode; ?>" placeholder="e.g. CLINIC-2023-ABCD" required>
                            </div>
                            <small class="form-text text-muted">
                                The tracking code was provided when you booked your appointment.
                            </small>
                        </div>

                        <div class="form-group text-center">
                            <button type="submit" name="track_appointment" class="btn btn-primary">Track Appointment</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="text-center mb-4">
                    <div class="tracking-code">
                        <?php echo $appointment['tracking_code']; ?>
                    </div>
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
                                <?php
                                $statusClass = '';
                                switch ($appointment['status']) {
                                    case 'pending':
                                        $statusClass = 'badge-warning';
                                        break;
                                    case 'confirmed':
                                        $statusClass = 'badge-info';
                                        break;
                                    case 'completed':
                                        $statusClass = 'badge-success';
                                        break;
                                    case 'cancelled':
                                        $statusClass = 'badge-danger';
                                        break;
                                }
                                ?>
                                <span class="badge <?php echo $statusClass; ?> status-badge"><?php echo ucfirst($appointment['status']); ?></span>
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
                    <a href="tracking.php" class="btn btn-primary">Track Another Appointment</a>
                    <a href="index.php" class="btn btn-secondary ml-2">Return to Homepage</a>
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
