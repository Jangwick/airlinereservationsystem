// Main JavaScript file for SkyWay Airlines Reservation System

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Remove alert messages after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    if (alerts.length > 0) {
        setTimeout(function() {
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    }

    // Animate counting for statistics (if present on page)
    const statCounters = document.querySelectorAll('.stat-counter');
    if (statCounters.length > 0) {
        statCounters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            const duration = 2000; // ms
            const step = Math.ceil(target / (duration / 16)); // 16ms is approx one frame at 60fps
            let current = 0;
            
            const updateCounter = () => {
                current += step;
                if (current >= target) {
                    counter.textContent = target.toLocaleString();
                } else {
                    counter.textContent = current.toLocaleString();
                    requestAnimationFrame(updateCounter);
                }
            };
            
            updateCounter();
        });
    }

    // Flight search form validation
    const flightSearchForm = document.querySelector('form[action="flights/search.php"]');
    if (flightSearchForm) {
        flightSearchForm.addEventListener('submit', function(event) {
            const departureCity = document.getElementById('departure_city');
            const arrivalCity = document.getElementById('arrival_city');
            const departureDate = document.getElementById('departure_date');
            const returnDate = document.getElementById('return_date');
            const tripType = document.querySelector('input[name="trip_type"]:checked');
            
            let isValid = true;
            
            // Reset any previous error messages
            document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            
            // Check if departure and arrival cities are different
            if (departureCity.value === arrivalCity.value) {
                isValid = false;
                departureCity.classList.add('is-invalid');
                arrivalCity.classList.add('is-invalid');
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                errorDiv.textContent = 'Departure and arrival cities cannot be the same';
                arrivalCity.parentNode.appendChild(errorDiv);
            }
            
            // Check if departure date is in the past
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const depDate = new Date(departureDate.value);
            
            if (depDate < today) {
                isValid = false;
                departureDate.classList.add('is-invalid');
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                errorDiv.textContent = 'Departure date cannot be in the past';
                departureDate.parentNode.appendChild(errorDiv);
            }
            
            // Check if return date is before departure date for round trips
            if (tripType.value === 'round_trip') {
                const retDate = new Date(returnDate.value);
                
                if (retDate < depDate) {
                    isValid = false;
                    returnDate.classList.add('is-invalid');
                    
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    errorDiv.textContent = 'Return date must be after departure date';
                    returnDate.parentNode.appendChild(errorDiv);
                }
            }
            
            if (!isValid) {
                event.preventDefault();
            }
        });
    }

    // Add to calendar button functionality
    const addToCalendarBtns = document.querySelectorAll('.add-to-calendar');
    if (addToCalendarBtns.length > 0) {
        addToCalendarBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                
                const flightData = {
                    title: this.getAttribute('data-title'),
                    start: this.getAttribute('data-start'),
                    end: this.getAttribute('data-end'),
                    location: this.getAttribute('data-location'),
                    description: this.getAttribute('data-description')
                };
                
                // Google Calendar link
                const googleCalendarUrl = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(flightData.title)}&dates=${formatDateForGoogle(flightData.start)}/${formatDateForGoogle(flightData.end)}&details=${encodeURIComponent(flightData.description)}&location=${encodeURIComponent(flightData.location)}&sf=true&output=xml`;
                
                window.open(googleCalendarUrl);
            });
        });
        
        function formatDateForGoogle(dateString) {
            const date = new Date(dateString);
            return date.toISOString().replace(/-|:|\.\d+/g, '');
        }
    }

    // Print ticket functionality
    const printTicketBtn = document.querySelector('.print-ticket');
    if (printTicketBtn) {
        printTicketBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.print();
        });
    }

    // Mobile navigation enhancements
    const windowWidth = window.innerWidth;
    if (windowWidth < 992) { // Mobile view
        const dropdown = document.querySelectorAll('.navbar .dropdown');
        dropdown.forEach(item => {
            item.addEventListener('click', function(e) {
                const dropdownMenu = this.querySelector('.dropdown-menu');
                if (!dropdownMenu.classList.contains('show')) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropdownMenu.classList.add('show');
                    this.classList.add('show');
                }
            });
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.navbar .dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                    menu.parentElement.classList.remove('show');
                });
            }
        });
    }
});
