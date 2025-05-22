<script>
/**
 * Flight Actions JavaScript Support
 * Provides enhanced functionality for flight status updates and modal interactions
 */

// Function to show status update modal
function updateStatus(flightId, currentStatus = '') {
    console.log('Opening status modal for flight ID:', flightId);
    
    // Set the flight ID in the hidden input
    document.getElementById('flightId').value = flightId;
    
    // Pre-select current status if it's provided
    if (currentStatus) {
        document.getElementById('flightStatus').value = currentStatus;
        // Trigger the change event to show/hide reason field
        $('#flightStatus').trigger('change');
    }
    
    // Create and show modal
    const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
    statusModal.show();
}

// Toggle visibility of delay reason field based on selected status
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('flightStatus');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            const delayReasonGroup = document.getElementById('delayReasonGroup');
            if (delayReasonGroup) {
                if (this.value === 'delayed' || this.value === 'cancelled') {
                    delayReasonGroup.style.display = 'block';
                    document.getElementById('delayReason').required = true;
                } else {
                    delayReasonGroup.style.display = 'none';
                    document.getElementById('delayReason').required = false;
                }
            }
        });
    }
    
    // Initialize status display
    if (statusSelect) {
        statusSelect.dispatchEvent(new Event('change'));
    }
});

// This function can be included at the bottom of manage_flights.php to ensure the JavaScript executes after the DOM is loaded
</script>
