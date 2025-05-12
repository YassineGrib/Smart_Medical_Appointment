<?php
/**
 * Admin Footer
 * 
 * Contains the footer HTML and scripts for admin pages
 */

// Ensure this file is included, not accessed directly
if (!defined('APP_NAME')) {
    exit('Direct access not permitted');
}
?>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.5.1/main.min.js"></script>

<!-- Custom scripts -->
<script>
    // Initialize FullCalendar if element exists
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        
        if (calendarEl) {
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: 'api/get_appointments.php',
                eventClick: function(info) {
                    window.location.href = 'appointments.php?action=view&id=' + info.event.id;
                }
            });
            
            calendar.render();
        }
    });
    
    // Enable Bootstrap tooltips
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });
    
    // Auto-hide alerts after 5 seconds
    $(document).ready(function() {
        setTimeout(function() {
            $('.alert-dismissible').alert('close');
        }, 5000);
    });
</script>

</body>
</html>
