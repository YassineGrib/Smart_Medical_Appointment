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
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #34a853;
            --accent-color: #4285f4;
            --dark-color: #202124;
            --light-color: #f8f9fa;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }

        body {
            font-family: 'Roboto', sans-serif;
            color: #333;
        }

        /* Hero section */
        .hero {
            position: relative;
            background: linear-gradient(135deg, rgba(26, 115, 232, 0.8), rgba(66, 133, 244, 0.8)), url('assets/img/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 0;
            margin-bottom: 3rem;
            overflow: hidden;
            height: 600px;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            padding: 100px 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            animation: fadeInDown 1s ease-out;
        }

        .hero p {
            font-size: 1.4rem;
            margin-bottom: 2.5rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            animation: fadeInUp 1s ease-out;
        }

        .hero-cta {
            animation: fadeIn 1.5s ease-out;
        }

        .hero-btn {
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .hero-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .hero-btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .hero-btn-primary:hover {
            background-color: #2d9249;
            border-color: #2d9249;
        }

        .hero-btn-outline {
            background-color: transparent;
            border: 2px solid white;
        }

        .hero-btn-outline:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .hero-image-container {
            position: absolute;
            right: -100px;
            bottom: -50px;
            width: 600px;
            height: 600px;
            z-index: 1;
            animation: slideInRight 1s ease-out;
            display: none;
        }

        .hero-image {
            width: 100%;
            height: auto;
        }

        /* Features */
        .feature-box {
            padding: 2.5rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            height: 100%;
        }

        .feature-box:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .feature-box:hover .feature-icon {
            transform: scale(1.1);
            color: var(--secondary-color);
        }

        .feature-box h4 {
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark-color);
        }

        /* Footer */
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 3rem 0 2rem;
            margin-top: 3rem;
        }

        footer h5 {
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--light-color);
        }

        footer a {
            color: rgba(255, 255, 255, 0.8);
            transition: all 0.2s ease;
        }

        footer a:hover {
            color: var(--primary-color);
            text-decoration: none;
        }

        footer address p {
            margin-bottom: 0.5rem;
        }

        /* Booking form */
        .booking-form {
            background-color: white;
            padding: 2.5rem;
            border-radius: 10px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transform: translateY(-80px);
            margin-bottom: -50px;
            position: relative;
            z-index: 10;
        }

        .booking-form h3 {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
        }

        .booking-form label {
            font-weight: 500;
            color: #555;
        }

        .booking-form .form-control {
            height: calc(2.5em + 0.75rem + 2px);
            border-radius: 5px;
        }

        .booking-form .btn {
            padding: 0.6rem 2rem;
            font-weight: 600;
            border-radius: 5px;
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .booking-form .btn:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }

        /* Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Responsive styles */
        @media (min-width: 992px) {
            .hero-image-container {
                display: block;
            }

            .hero-content {
                text-align: left;
                padding-left: 2rem;
            }

            .hero p {
                margin-left: 0;
            }
        }

        @media (max-width: 991.98px) {
            .hero {
                height: 500px;
            }

            .hero h1 {
                font-size: 2.8rem;
            }

            .hero p {
                font-size: 1.2rem;
            }

            .booking-form {
                transform: translateY(-60px);
                margin-bottom: -30px;
            }
        }

        @media (max-width: 767.98px) {
            .hero {
                height: 450px;
            }

            .hero h1 {
                font-size: 2.2rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .hero-btn {
                padding: 10px 20px;
                font-size: 1rem;
            }

            .booking-form {
                transform: translateY(-40px);
                margin-bottom: -20px;
                padding: 1.5rem;
            }
        }
    </style>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
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
        <div class="container">
            <div class="row">
                <div class="col-lg-7">
                    <div class="hero-content">
                        <h1>Your Health, Our Priority</h1>
                        <p>Experience seamless healthcare scheduling with our Smart Medical Appointment system. Book appointments with top specialists in just a few clicks — no registration required.</p>
                        <div class="hero-cta">
                            <a href="booking.php" class="btn hero-btn hero-btn-primary">
                                <i class="fas fa-calendar-check mr-2"></i>Book Appointment
                            </a>
                            <a href="tracking.php" class="btn hero-btn hero-btn-outline ml-3">
                                <i class="fas fa-search mr-2"></i>Track Appointment
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="hero-image-container">
                        <img src="assets/img/doctor-illustration.png" alt="Doctor with patient" class="hero-image">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Booking Form -->
    <section class="container mb-5">
        <div class="row">
            <div class="col-lg-10 offset-lg-1">
                <div class="booking-form">
                    <h3 class="text-center mb-4">
                        <i class="fas fa-calendar-plus text-primary mr-2"></i>
                        Quick Appointment Booking
                    </h3>
                    <form action="booking.php" method="get">
                        <div class="form-row">
                            <div class="form-group col-md-5">
                                <label for="specialty">
                                    <i class="fas fa-stethoscope text-primary mr-2"></i>
                                    Medical Specialty
                                </label>
                                <select class="form-control" id="specialty" name="specialty" required>
                                    <option value="">Select Specialty</option>
                                    <?php foreach ($specialties as $specialty): ?>
                                        <option value="<?php echo $specialty['id']; ?>"><?php echo $specialty['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-5">
                                <label for="date">
                                    <i class="fas fa-calendar-day text-primary mr-2"></i>
                                    Preferred Date
                                </label>
                                <input type="date" class="form-control" id="date" name="date" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-arrow-right mr-2"></i>Continue
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="container py-5">
        <div class="text-center mb-5">
            <h2 class="mb-3">Why Choose Our Service</h2>
            <p class="lead text-muted mx-auto" style="max-width: 700px;">Our Smart Medical Appointment system offers a seamless experience with these key benefits</p>
        </div>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="feature-box text-center">
                    <div class="feature-icon">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <h4>No Registration Required</h4>
                    <p>Book appointments quickly without creating an account. Just provide your basic information and you're all set to schedule your visit.</p>
                    <a href="booking.php" class="btn btn-sm btn-outline-primary mt-3">Book Now <i class="fas fa-arrow-right ml-1"></i></a>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-box text-center">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h4>Easy Appointment Tracking</h4>
                    <p>Track your appointment status anytime using the unique reference code provided after booking. Stay updated on any changes.</p>
                    <a href="tracking.php" class="btn btn-sm btn-outline-primary mt-3">Track Appointment <i class="fas fa-arrow-right ml-1"></i></a>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-box text-center">
                    <div class="feature-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <h4>Expert Medical Specialists</h4>
                    <p>Choose from our wide range of specialized doctors with years of experience in their respective medical fields.</p>
                    <a href="booking.php" class="btn btn-sm btn-outline-primary mt-3">Find Specialists <i class="fas fa-arrow-right ml-1"></i></a>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-4 mb-4">
                <div class="feature-box text-center">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h4>Save Time</h4>
                    <p>No more waiting on phone calls or standing in queues. Book your appointment online in less than 2 minutes from anywhere.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-box text-center">
                    <div class="feature-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h4>Appointment Reminders</h4>
                    <p>Receive timely reminders about your upcoming appointments so you never miss an important consultation.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-box text-center">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4>Secure & Private</h4>
                    <p>Your personal and medical information is always protected with our secure booking system and privacy protocols.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-5" style="background-color: #f8f9fa;">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="mb-3">What Our Patients Say</h2>
                <p class="lead text-muted mx-auto" style="max-width: 700px;">Read testimonials from patients who have used our Smart Medical Appointment system</p>
            </div>

            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex mb-3">
                                <div class="text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                            <p class="card-text mb-3">"The booking process was incredibly simple and fast. I was able to find a specialist and book an appointment in less than 5 minutes. Highly recommended!"</p>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px;">
                                    <span style="font-size: 1.2rem;">JD</span>
                                </div>
                                <div>
                                    <h6 class="mb-0">John Doe</h6>
                                    <small class="text-muted">Patient</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex mb-3">
                                <div class="text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                            <p class="card-text mb-3">"I love the tracking feature! I received timely reminders about my appointment, and rescheduling was so easy when I needed to change the date."</p>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px;">
                                    <span style="font-size: 1.2rem;">JS</span>
                                </div>
                                <div>
                                    <h6 class="mb-0">Jane Smith</h6>
                                    <small class="text-muted">Patient</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex mb-3">
                                <div class="text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                            </div>
                            <p class="card-text mb-3">"As someone who hates creating accounts for everything, I appreciate that I could book an appointment without registration. The process was smooth and efficient."</p>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px;">
                                    <span style="font-size: 1.2rem;">RJ</span>
                                </div>
                                <div>
                                    <h6 class="mb-0">Robert Johnson</h6>
                                    <small class="text-muted">Patient</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5" style="background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); color: white;">
        <div class="container text-center">
            <h2 class="mb-4">Ready to Book Your Appointment?</h2>
            <p class="lead mb-4">Experience healthcare scheduling made simple with our Smart Medical Appointment system.</p>
            <a href="booking.php" class="btn btn-lg btn-light">
                <i class="fas fa-calendar-check mr-2"></i>Book Your Appointment Now
            </a>
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
