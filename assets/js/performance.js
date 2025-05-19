/**
 * SkyWay Airlines - Performance Optimization
 * This file contains performance enhancements for faster page loading
 */

// Lazy loading for images
document.addEventListener('DOMContentLoaded', function() {
    // Initialize lazy loading for images
    const lazyImages = [].slice.call(document.querySelectorAll('img.lazy'));
    
    if ('IntersectionObserver' in window) {
        let lazyImageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    let lazyImage = entry.target;
                    lazyImage.src = lazyImage.dataset.src;
                    if (lazyImage.dataset.srcset) {
                        lazyImage.srcset = lazyImage.dataset.srcset;
                    }
                    lazyImage.classList.remove('lazy');
                    lazyImageObserver.unobserve(lazyImage);
                }
            });
        });

        lazyImages.forEach(function(lazyImage) {
            lazyImageObserver.observe(lazyImage);
        });
    } else {
        // Fallback for browsers that don't support IntersectionObserver
        let active = false;

        const lazyLoad = function() {
            if (active === false) {
                active = true;

                setTimeout(function() {
                    lazyImages.forEach(function(lazyImage) {
                        if ((lazyImage.getBoundingClientRect().top <= window.innerHeight && lazyImage.getBoundingClientRect().bottom >= 0) && getComputedStyle(lazyImage).display !== 'none') {
                            lazyImage.src = lazyImage.dataset.src;
                            if (lazyImage.dataset.srcset) {
                                lazyImage.srcset = lazyImage.dataset.srcset;
                            }
                            lazyImage.classList.remove('lazy');

                            lazyImages = lazyImages.filter(function(image) {
                                return image !== lazyImage;
                            });

                            if (lazyImages.length === 0) {
                                document.removeEventListener('scroll', lazyLoad);
                                window.removeEventListener('resize', lazyLoad);
                                window.removeEventListener('orientationChange', lazyLoad);
                            }
                        }
                    });

                    active = false;
                }, 200);
            }
        };

        document.addEventListener('scroll', lazyLoad);
        window.addEventListener('resize', lazyLoad);
        window.addEventListener('orientationChange', lazyLoad);
        lazyLoad();
    }
});

// Prefetch pages on hover
document.addEventListener('DOMContentLoaded', function() {
    const linkPrefetch = document.querySelectorAll('a:not([href^="#"]):not([href^="javascript"]):not([href^="mailto"]):not([href^="tel"])');
    
    linkPrefetch.forEach(link => {
        link.addEventListener('mouseenter', function() {
            const url = this.href;
            
            if (!url || url.startsWith('#') || url.includes('logout.php')) {
                return;
            }
            
            // Create a prefetch link
            const prefetchLink = document.createElement('link');
            prefetchLink.href = url;
            prefetchLink.rel = 'prefetch';
            document.head.appendChild(prefetchLink);
        });
    });
});

// Defer non-critical CSS and JavaScript
function deferResource(resourceType, url, callback) {
    const element = document.createElement(resourceType);
    
    if (resourceType === 'link') {
        element.href = url;
        element.rel = 'stylesheet';
    } else {
        element.src = url;
        element.async = true;
    }
    
    if (typeof callback === 'function') {
        element.onload = callback;
    }
    
    document.body.appendChild(element);
}

// Initialize navigation transitions
document.addEventListener('DOMContentLoaded', function() {
    // Smooth page transitions
    document.querySelectorAll('a:not([href^="#"]):not([href^="javascript"]):not([href^="mailto"]):not([href^="tel"])').forEach(link => {
        link.addEventListener('click', function(e) {
            // Skip for external links or links with target="_blank"
            if (this.hostname !== window.location.hostname || this.target === '_blank') {
                return;
            }
            
            e.preventDefault();
            
            // Add transition class to body
            document.body.classList.add('page-transition');
            
            // Navigate after a small delay to allow the transition
            setTimeout(() => {
                window.location.href = this.href;
            }, 300);
        });
    });
    
    // Remove transition class when page has loaded
    window.addEventListener('pageshow', function() {
        document.body.classList.remove('page-transition');
    });
});

// Defer non-critical scripts
function deferScript(url) {
    const script = document.createElement('script');
    script.src = url;
    script.defer = true;
    document.body.appendChild(script);
}

// Add preconnect for external domains to improve loading times
function addPreconnect(url) {
    const link = document.createElement('link');
    link.rel = 'preconnect';
    link.href = url;
    link.crossOrigin = 'anonymous';
    document.head.appendChild(link);
}

// Debounce function to limit how often a function can run
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
