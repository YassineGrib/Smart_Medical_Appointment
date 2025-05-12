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
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/locales-all.min.js"></script>

<!-- Moment.js for date formatting -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

<!-- Custom scripts -->
<script>
    // Initialize FullCalendar if element exists
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');

        if (calendarEl) {
            // Default calendar options
            var calendarOptions = {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                events: 'api/get_appointments.php',
                height: 'auto',
                aspectRatio: 1.8,
                navLinks: true,
                dayMaxEvents: true,
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    meridiem: 'short'
                },
                views: {
                    timeGrid: {
                        dayMaxEventRows: 4
                    }
                },
                eventDidMount: function(info) {
                    // Add tooltips to events
                    $(info.el).tooltip({
                        title: '<div class="calendar-tooltip">' +
                               '<strong>Patient:</strong> ' + info.event.title + '<br>' +
                               '<strong>Doctor:</strong> ' + info.event.extendedProps.doctor + '<br>' +
                               '<strong>Time:</strong> ' + info.event.extendedProps.time + '<br>' +
                               '<strong>Status:</strong> ' + capitalizeFirstLetter(info.event.extendedProps.status) +
                               '</div>',
                        placement: 'top',
                        trigger: 'hover',
                        container: 'body',
                        html: true
                    });
                },
                eventClick: function(info) {
                    // Show modal with appointment details
                    $('#modal-patient').text(info.event.title);
                    $('#modal-doctor').text(info.event.extendedProps.doctor);
                    $('#modal-specialty').text(info.event.extendedProps.specialty);
                    $('#modal-date').text(moment(info.event.start).format('dddd, MMMM D, YYYY'));
                    $('#modal-time').text(info.event.extendedProps.time);

                    // Set status with appropriate badge
                    var statusClass = '';
                    switch (info.event.extendedProps.status) {
                        case 'pending':
                            statusClass = 'badge-warning';
                            break;
                        case 'confirmed':
                            statusClass = 'badge-info';
                            break;
                        case 'completed':
                            statusClass = 'badge-success';
                            break;
                        case 'cancelled':
                            statusClass = 'badge-danger';
                            break;
                    }

                    $('#modal-status').html('<span class="badge ' + statusClass + '">' +
                        capitalizeFirstLetter(info.event.extendedProps.status) + '</span>');

                    // Set links
                    $('#modal-view-link').attr('href', 'appointments.php?action=view&id=' + info.event.id);
                    $('#modal-edit-link').attr('href', 'appointments.php?action=edit&id=' + info.event.id);

                    // Show modal
                    $('#appointmentModal').modal('show');
                },
                loading: function(isLoading) {
                    if (isLoading) {
                        // Add loading indicator
                        $('#calendar').append('<div class="calendar-loading"><i class="fas fa-spinner fa-spin"></i> Loading appointments...</div>');
                    } else {
                        // Remove loading indicator
                        $('.calendar-loading').remove();
                    }
                }
            };

            // Initialize calendar
            var calendar = new FullCalendar.Calendar(calendarEl, calendarOptions);
            calendar.render();

            // Handle calendar filters
            $('#apply-calendar-filter').on('click', function() {
                var doctorId = $('#doctor-filter').val();
                var specialtyId = $('#specialty-filter').val();

                // Update events source with filters
                calendar.getEventSources().forEach(function(source) {
                    source.remove();
                });

                calendar.addEventSource({
                    url: 'api/get_appointments.php',
                    extraParams: {
                        doctor_id: doctorId,
                        specialty_id: specialtyId
                    }
                });

                // Refresh calendar
                calendar.refetchEvents();

                // Close dropdown
                $('.dropdown-menu').removeClass('show');
            });

            // Make calendar responsive
            window.addEventListener('resize', function() {
                calendar.updateSize();
            });
        }
    });

    // Helper function to capitalize first letter
    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    // Initialize Bootstrap tooltips
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
