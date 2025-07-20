// Elite Motors - Bootstrap Enhanced JavaScript

// Initialize AOS (Animate On Scroll)
document.addEventListener('DOMContentLoaded', function() {
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true,
        offset: 100
    });
    
    // Initialize all functionality
    initCounterAnimation();
    initCarFiltering();
    initContactForm();
    initScrollEffects();
    initButtonInteractions();
    initFavoriteSystem();
});

// Counter Animation for Statistics
function initCounterAnimation() {
    const counters = document.querySelectorAll('.counter');
    let hasAnimated = false;

    const animateCounters = () => {
        if (hasAnimated) return;
        
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            const duration = 2000; // 2 seconds
            const startTime = performance.now();
            
            const updateCounter = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                // Easing function for smooth animation
                const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                const current = Math.floor(target * easeOutQuart);
                
                // Format number with commas
                counter.textContent = current.toLocaleString();
                
                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                }
            };
            
            requestAnimationFrame(updateCounter);
        });
        
        hasAnimated = true;
    };

    // Intersection Observer for counter animation
    const observerOptions = {
        threshold: 0.5,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounters();
            }
        });
    }, observerOptions);

    // Observe the stats section
    const statsSection = document.querySelector('.py-5.bg-light');
    if (statsSection) {
        observer.observe(statsSection);
    }
}

// Car Filtering System
function initCarFiltering() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const carItems = document.querySelectorAll('.car-item');

    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            
            // Update active button
            filterButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.classList.add('btn-outline-primary');
                btn.classList.remove('btn-primary');
            });
            
            this.classList.add('active');
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary');
            
            // Filter cars with animation
            carItems.forEach((item, index) => {
                const category = item.getAttribute('data-category');
                
                if (filter === 'all' || category === filter) {
                    item.style.display = 'block';
                    item.style.opacity = '0';
                    item.style.transform = 'translateY(20px)';
                    
                    setTimeout(() => {
                        item.style.transition = 'all 0.5s ease';
                        item.style.opacity = '1';
                        item.style.transform = 'translateY(0)';
                    }, index * 100);
                } else {
                    item.style.transition = 'all 0.3s ease';
                    item.style.opacity = '0';
                    item.style.transform = 'translateY(-20px)';
                    
                    setTimeout(() => {
                        item.style.display = 'none';
                    }, 300);
                }
            });
            
            // Track filter event
            trackEvent('Filter', 'Click', filter);
        });
    });
}

// Favorite System
function initFavoriteSystem() {
    const favoriteButtons = document.querySelectorAll('.btn-favorite');
    
    favoriteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const icon = this.querySelector('i');
            const carName = this.closest('.card').querySelector('.card-title').textContent;
            
            if (icon.classList.contains('far')) {
                // Add to favorites
                icon.classList.remove('far');
                icon.classList.add('fas');
                this.style.color = '#dc3545';
                
                // Animation effect
                this.style.transform = 'scale(1.3)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 200);
                
                showNotification(`${carName} added to favorites!`, 'success');
                trackEvent('Favorite', 'Add', carName);
            } else {
                // Remove from favorites
                icon.classList.remove('fas');
                icon.classList.add('far');
                this.style.color = '#6c757d';
                
                showNotification(`${carName} removed from favorites.`, 'info');
                trackEvent('Favorite', 'Remove', carName);
            }
        });
    });
}

// Contact Form Handling
function initContactForm() {
    const contactForm = document.getElementById('contactForm');
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            // Validate form
            if (validateForm(data)) {
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalHTML = submitBtn.innerHTML;
                
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
                submitBtn.disabled = true;
                submitBtn.classList.add('loading');
                
                // Simulate form submission (replace with actual AJAX call)
                setTimeout(() => {
                    showNotification('Message sent successfully! We will contact you soon.', 'success');
                    this.reset();
                    submitBtn.innerHTML = originalHTML;
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('loading');
                    trackEvent('Contact', 'Submit', 'Success');
                }, 2000);
            }
        });
    }
}

// Form Validation
function validateForm(data) {
    let isValid = true;
    const errors = [];

    // Required fields validation
    if (!data.name || data.name.trim().length < 2) {
        errors.push('Name is required and must be at least 2 characters');
        isValid = false;
    }

    if (!data.email || !isValidEmail(data.email)) {
        errors.push('Please enter a valid email address');
        isValid = false;
    }

    if (!data.message || data.message.trim().length < 10) {
        errors.push('Message is required and must be at least 10 characters');
        isValid = false;
    }

    if (!isValid) {
        showNotification(errors.join('\n'), 'danger');
    }

    return isValid;
}

// Email validation helper
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Bootstrap Toast Notification System
function showNotification(message, type = 'info') {
    // Remove existing toasts
    const existingToasts = document.querySelectorAll('.toast');
    existingToasts.forEach(toast => {
        const bsToast = bootstrap.Toast.getInstance(toast);
        if (bsToast) bsToast.hide();
    });

    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1090';
        document.body.appendChild(toastContainer);
    }

    // Create toast element
    const toastId = 'toast-' + Date.now();
    const iconClass = getNotificationIcon(type);
    const bgClass = type === 'danger' ? 'bg-danger' : type === 'success' ? 'bg-success' : type === 'warning' ? 'bg-warning' : 'bg-info';
    
    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${iconClass} me-2"></i>
                    ${message.replace(/\n/g, '<br>')}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    // Initialize and show toast
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 5000
    });
    
    toast.show();
    
    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}

// Get notification icon based on type
function getNotificationIcon(type) {
    switch (type) {
        case 'success': return 'check-circle';
        case 'danger': return 'exclamation-circle';
        case 'warning': return 'exclamation-triangle';
        default: return 'info-circle';
    }
}

// Scroll Effects
function initScrollEffects() {
    const navbar = document.querySelector('.navbar');
    
    window.addEventListener('scroll', throttle(() => {
        if (window.scrollY > 100) {
            navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.1)';
        } else {
            navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            navbar.style.boxShadow = 'none';
        }
    }, 16));

    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);
            
            if (targetSection) {
                const navbarHeight = navbar.offsetHeight;
                const targetPosition = targetSection.offsetTop - navbarHeight - 20;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
                
                // Close mobile menu if open
                const navbarToggler = document.querySelector('.navbar-toggler');
                const navbarCollapse = document.querySelector('.navbar-collapse.show');
                
                if (navbarToggler && !navbarToggler.classList.contains('collapsed')) {
                    navbarToggler.click();
                }
            }
        });
    });
}

// Button Interactions
function initButtonInteractions() {
    // Hero buttons
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('click', function(e) {
            const buttonText = this.textContent.toLowerCase().trim();
            
            if (buttonText.includes('view collection')) {
                const carsSection = document.getElementById('cars');
                if (carsSection) {
                    carsSection.scrollIntoView({ behavior: 'smooth' });
                }
                trackEvent('Button', 'Click', 'View Collection');
            } else if (buttonText.includes('watch video')) {
                showNotification('Video feature coming soon!', 'info');
                trackEvent('Button', 'Click', 'Watch Video');
            } else if (buttonText.includes('view all cars')) {
                showNotification('Redirecting to full inventory...', 'info');
                trackEvent('Button', 'Click', 'View All Cars');
            }
        });
    });

    // Add hover effects to cards
    document.querySelectorAll('.car-card, .service-card, .stat-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

// Performance Optimization - Throttle function
function throttle(func, wait) {
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

// Analytics Event Tracking
function trackEvent(category, action, label) {
    // Integration with analytics service (Google Analytics, etc.)
    if (typeof gtag !== 'undefined') {
        gtag('event', action, {
            event_category: category,
            event_label: label
        });
    }
    console.log('Event tracked:', { category, action, label });
}

// Keyboard Navigation Support
document.addEventListener('keydown', function(e) {
    // Escape key to close modals/dropdowns
    if (e.key === 'Escape') {
        // Close any open Bootstrap modals
        const openModals = document.querySelectorAll('.modal.show');
        openModals.forEach(modal => {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) bsModal.hide();
        });
        
        // Close mobile menu
        const navbarToggler = document.querySelector('.navbar-toggler');
        const navbarCollapse = document.querySelector('.navbar-collapse.show');
        
        if (navbarCollapse && navbarToggler) {
            navbarToggler.click();
        }
    }
});

// Image Lazy Loading Enhancement
function initLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));
}

// Initialize lazy loading if needed
if (document.querySelectorAll('img[data-src]').length > 0) {
    initLazyLoading();
}

// Service Worker Registration (for PWA capabilities)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js')
            .then(function(registration) {
                console.log('ServiceWorker registration successful');
            })
            .catch(function(err) {
                console.log('ServiceWorker registration failed');
            });
    });
}

// Dark Mode Toggle (if needed)
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
}

// Check for saved dark mode preference
if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
}

// Preloader (if needed)
window.addEventListener('load', function() {
    const preloader = document.querySelector('.preloader');
    if (preloader) {
        preloader.style.opacity = '0';
        setTimeout(() => {
            preloader.style.display = 'none';
        }, 500);
    }
});

// Initialize Bootstrap tooltips and popovers
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

// Form Enhancement
document.querySelectorAll('.form-control, .form-select').forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.classList.add('focused');
    });
    
    input.addEventListener('blur', function() {
        if (!this.value) {
            this.parentElement.classList.remove('focused');
        }
    });
});

// Add loading states to buttons
document.querySelectorAll('.btn').forEach(button => {
    button.addEventListener('click', function() {
        if (this.type === 'submit') return; // Skip submit buttons
        
        const originalHTML = this.innerHTML;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
        this.disabled = true;
        
        setTimeout(() => {
            this.innerHTML = originalHTML;
            this.disabled = false;
        }, 1000);
    });
});

// Custom right-click context menu (optional)
document.addEventListener('contextmenu', function(e) {
    // You can customize this or remove it
    // e.preventDefault();
});

// Print page functionality
function printPage() {
    window.print();
}

// Share functionality
async function shareContent() {
    if (navigator.share) {
        try {
            await navigator.share({
                title: 'Elite Motors - Premium Car Showroom',
                text: 'Check out this amazing car showroom!',
                url: window.location.href
            });
        } catch (err) {
            console.log('Error sharing:', err);
        }
    } else {
        // Fallback to copy URL
        navigator.clipboard.writeText(window.location.href);
        showNotification('URL copied to clipboard!', 'success');
    }
}

// Back to top functionality
function addBackToTop() {
    const backToTop = document.createElement('button');
    backToTop.innerHTML = '<i class="fas fa-arrow-up"></i>';
    backToTop.className = 'btn btn-primary rounded-circle position-fixed';
    backToTop.style.cssText = `
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        display: none;
        z-index: 1000;
        transition: all 0.3s ease;
    `;
    
    document.body.appendChild(backToTop);
    
    window.addEventListener('scroll', throttle(() => {
        if (window.scrollY > 300) {
            backToTop.style.display = 'block';
        } else {
            backToTop.style.display = 'none';
        }
    }, 100));
    
    backToTop.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

// Initialize back to top button
addBackToTop();

console.log('Elite Motors website loaded successfully! 🚗✨');