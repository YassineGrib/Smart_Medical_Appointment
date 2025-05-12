# Smart Medical Appointment System

![Smart Medical Appointment System](https://github.com/user-attachments/assets/d7d8b487-9d93-4820-8e32-2e53acf2c60b)

A comprehensive web-based medical appointment booking system that allows patients to book appointments without registration while providing administrators with robust management capabilities.

## 🏥 Overview

The Smart Medical Appointment System is designed to streamline the process of scheduling medical appointments for healthcare facilities. It provides an intuitive interface for patients to book appointments with their preferred doctors based on specialty and availability, while giving administrators powerful tools to manage appointments, doctors, and clinic settings.

## ✨ Key Features

### For Patients
- **No-Registration Booking**: Patients can book appointments without creating an account
- **Specialty-Based Doctor Selection**: Find doctors by their medical specialty
- **Real-Time Availability**: See available time slots based on doctor's schedule
- **Appointment Tracking**: Track appointment status using a unique tracking code
- **Document Upload**: Attach medical documents during the booking process
- **Email Confirmations**: Receive booking confirmations and updates via email

### For Administrators
- **Comprehensive Dashboard**: Overview of appointments, doctors, and statistics
- **Interactive Calendar**: Color-coded appointment calendar with filtering options
- **Appointment Management**: Create, view, update, and cancel appointments
- **Doctor Management**: Add and manage doctors with their specialties and schedules
- **Specialty Management**: Organize medical specialties offered by the clinic
- **User Management**: Control access to the admin panel with role-based permissions
- **System Settings**: Configure clinic details, working hours, and appointment duration

### Advanced Booking Validation
- **Schedule Validation**: Ensures appointments can only be booked on days when doctors are working
- **Working Hours Validation**: Restricts bookings to within doctors' working hours
- **Conflict Detection**: Prevents double-booking of doctors
- **Dynamic Time Slots**: Calculates appointment slots based on configurable duration
- **Clear Error Messages**: Provides specific feedback when booking constraints are violated

## 🛠️ Technologies Used

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 4
- **Libraries**: jQuery, Font Awesome, FullCalendar
- **Security**: CSRF protection, input validation, prepared statements

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)
- GD Library for PHP (for image processing)

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/YassineGrib/Smart_Medical_Appointment.git
   ```

2. **Configure your web server**
   - Point your web server to the project directory
   - Ensure the `assets/uploads` directory is writable by the web server

3. **Configure database connection**
   - Open `includes/db.php`
   - Update the database credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     define('DB_NAME', 'medical_appointment');
     ```

4. **Configure application settings**
   - Open `includes/config.php`
   - Update the application URL and admin email:
     ```php
     define('APP_URL', 'http://your-domain.com/Smart_Medical_Appointment');
     define('ADMIN_EMAIL', 'your-email@example.com');
     ```

5. **Run the setup script**
   - Navigate to `http://your-domain.com/Smart_Medical_Appointment/setup.php`
   - Click the "Run Setup" button to create the database, tables, and sample data

6. **Access the application**
   - Frontend: `http://your-domain.com/Smart_Medical_Appointment/`
   - Admin Panel: `http://your-domain.com/Smart_Medical_Appointment/admin/`
   - Default admin credentials:
     - Username: `admin`
     - Password: `admin123`

## 📱 Usage

### For Patients

1. **Book an Appointment**
   - Visit the homepage and click "Book Appointment"
   - Select a medical specialty
   - Choose a doctor from the available specialists
   - Select a date and available time slot
   - Fill in your personal details and submit

2. **Track an Appointment**
   - Visit the "Track Appointment" page
   - Enter your tracking code and email
   - View your appointment details and status

### For Administrators

1. **Manage Appointments**
   - View all appointments in list or calendar view
   - Filter appointments by doctor, specialty, date, or status
   - Create, edit, or cancel appointments
   - Update appointment status (confirm, complete, cancel)

2. **Manage Doctors**
   - Add new doctors with their specialties
   - Set working days and hours for each doctor
   - Edit or deactivate existing doctors

3. **Manage Specialties**
   - Add, edit, or remove medical specialties
   - View doctors associated with each specialty

4. **System Settings**
   - Configure clinic information (name, address, contact details)
   - Set default appointment duration
   - Define clinic working hours and days

## 🔒 Security Features

- CSRF protection for all forms
- Input validation and sanitization
- Prepared statements for database queries
- Password hashing for admin accounts
- Session security measures
- File upload validation and restrictions

## 📸 Screenshots

### Patient Interface
![Booking Page](https://github.com/user-attachments/assets/d7d8b487-9d93-4820-8e32-2e53acf2c60b)

### Admin Interface
![image](https://github.com/user-attachments/assets/dfafbb4d-f3d3-4a6a-93c7-a8c986b42710)

![image](https://github.com/user-attachments/assets/38f8be58-ffae-47df-bf8a-e78a22616c24)

## 🔄 Validation Features

The appointment booking system includes sophisticated validation to ensure proper scheduling:

1. **Doctor Schedule Validation**
   - Checks if the selected doctor works on the chosen day
   - Provides clear error messages when attempting to book on non-working days

2. **Working Hours Validation**
   - Ensures appointments are only booked during the doctor's working hours
   - Prevents bookings outside clinic operating hours

3. **Conflict Detection**
   - Prevents double-booking of doctors at overlapping times
   - Checks for conflicts during both creation and updates

4. **Time Slot Generation**
   - Dynamically generates available time slots based on:
     - Doctor's schedule
     - Existing appointments
     - Configured appointment duration
   - Only displays valid, available time slots to users

5. **Error Messaging**
   - Provides specific, user-friendly error messages explaining why a slot is unavailable
   - Guides users toward valid booking options

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📜 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 👏 Acknowledgments

- [Bootstrap](https://getbootstrap.com/) - Frontend framework
- [Font Awesome](https://fontawesome.com/) - Icons
- [FullCalendar](https://fullcalendar.io/) - Calendar interface
- [jQuery](https://jquery.com/) - JavaScript library
- Medical icons and images from various free resources

---

Developed with ❤️ by [Yassine Grib](https://github.com/YassineGrib)
