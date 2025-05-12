<?php
/**
 * Homepage
 * 
 * Main landing page for patients
 */

// Include required files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get database connection
$conn = getDbConnection();

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

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Book Your Medical Appointment</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <!-- Custom styles -->
    <style>
        /* Hero section */
        .hero {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/img/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            margin-bottom: 2rem;
        }
        
        .hero h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        
        /* Features */
        .feature-box {
            padding: 2rem;
            border-radius: 5px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }
        
        .feature-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            color: #007bff;
            margin-bottom: 1rem;
        }
        
        /* Footer */
        footer {
            background-color: #343a40;
            color: white;
            padding: 2rem 0;
            margin-top: 2rem;
        }
        
        footer a {
            color: rgba(255, 255, 255, 0.8);
        }
        
        footer a:hover {
            color: white;
            text-decoration: none;
        }
        
        /* Booking form */
        .booking-form {
            background-color: white;
            padding: 2rem;
            border-radius: 5px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
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
                    <li class="nav-item active">
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
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="container text-center">
            <h1>Book Your Medical Appointment Online</h1>
            <p>Quick, easy, and secure appointment booking without registration</p>
            <a href="booking.php" class="btn btn-primary btn-lg">Book an Appointment</a>
            <a href="tracking.php" class="btn btn-outline-light btn-lg ml-2">Track Your Appointment</a>
        </div>
    </section>
    
    <!-- Quick Booking Form -->
    <section class="container mb-5">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="booking-form">
                    <h3 class="text-center mb-4">Quick Appointment Booking</h3>
                    <form action="booking.php" method="get">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="specialty">Medical Specialty</label>
                                <select class="form-control" id="specialty" name="specialty" required>
                                    <option value="">Select Specialty</option>
                                    <?php foreach ($specialties as $specialty): ?>
                                        <option value="<?php echo $specialty['id']; ?>"><?php echo $specialty['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="date">Preferred Date</label>
                                <input type="date" class="form-control" id="date" name="date" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">Continue to Booking</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="container">
        <h2 class="text-center mb-5">Why Choose Our Service</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="feature-box text-center">
                    <div class="feature-icon">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <h4>No Registration Required</h4>
                    <p>Book appointments quickly without creating an account. Just provide your basic information.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box text-center">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h4>Easy Tracking</h4>
                    <p>Track your appointment status using the unique reference code provided after booking.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box text-center">
                    <div class="feature-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <h4>Expert Doctors</h4>
                    <p>Choose from our wide range of specialized doctors with years of experience in their fields.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>About Us</h5>
                    <p>Smart Medical Appointment is a modern solution for booking medical appointments online without the hassle of registration.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="booking.php">Book Appointment</a></li>
                        <li><a href="tracking.php">Track Appointment</a></li>
                        <li><a href="admin/index.php">Admin Login</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <address>
                        <p><i class="fas fa-map-marker-alt mr-2"></i> 123 Health Street, Medical City</p>
                        <p><i class="fas fa-phone mr-2"></i> +1 (555) 123-4567</p>
                        <p><i class="fas fa-envelope mr-2"></i> contact@smartmedical.example.com</p>
                    </address>
                </div>
            </div>
            <div class="text-center mt-4">
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
