/**
 * Fix for Bootstrap dropdowns
 * This ensures dropdowns work correctly across all pages
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    
    if (typeof bootstrap !== 'undefined') {
        dropdownElementList.forEach(function(dropdownToggleEl) {
            new bootstrap.Dropdown(dropdownToggleEl);
        });
    } else {
        // If Bootstrap JS isn't loaded yet, wait and try again
        setTimeout(function() {
            if (typeof bootstrap !== 'undefined') {
                dropdownElementList.forEach(function(dropdownToggleEl) {
                    new bootstrap.Dropdown(dropdownToggleEl);
                });
            } else {
                console.error('Bootstrap JS not loaded. Dropdowns may not work properly.');
                
                // Load Bootstrap JS as fallback
                var script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js';
                script.onload = function() {
                    dropdownElementList.forEach(function(dropdownToggleEl) {
                        new bootstrap.Dropdown(dropdownToggleEl);
                    });
                };
                document.body.appendChild(script);
            }
        }, 500);
    }
    
    // Add click event listeners to ensure dropdowns toggle properly
    document.querySelectorAll('.dropdown-toggle').forEach(function(element) {
        element.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle dropdown manually if Bootstrap JS isn't working
            var dropdownMenu = this.nextElementSibling;
            if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                var isShown = dropdownMenu.classList.contains('show');
                
                // Close all other dropdowns
                document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                    menu.classList.remove('show');
                });
                
                // Toggle current dropdown
                if (!isShown) {
                    dropdownMenu.classList.add('show');
                }
            }
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                menu.classList.remove('show');
            });
        }
    });
});
