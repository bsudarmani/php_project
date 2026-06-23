// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');

    if (hamburger) {
        hamburger.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            hamburger.classList.toggle('active');
        });
    }

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        if (hamburger && !hamburger.contains(event.target) && !navMenu.contains(event.target)) {
            navMenu.classList.remove('active');
            hamburger.classList.remove('active');
        }
    });

    // Initialize tooltips
    initializeTooltips();

    // Handle quantity inputs
    handleQuantityInputs();

    // Handle form validation
    handleFormValidation();
});

// Initialize tooltips
function initializeTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    
    tooltips.forEach(el => {
        el.addEventListener('mouseenter', showTooltip);
        el.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const tooltipText = e.target.getAttribute('data-tooltip');
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = tooltipText;
    tooltip.style.cssText = `
        position: absolute;
        background: #333;
        color: white;
        padding: 5px 10px;
        border-radius: 5px;
        font-size: 12px;
        z-index: 1000;
    `;
    
    const rect = e.target.getBoundingClientRect();
    tooltip.style.top = rect.top - 30 + 'px';
    tooltip.style.left = rect.left + 'px';
    
    document.body.appendChild(tooltip);
    e.target.tooltip = tooltip;
}

function hideTooltip(e) {
    if (e.target.tooltip) {
        e.target.tooltip.remove();
    }
}

// Handle quantity inputs
function handleQuantityInputs() {
    const quantityInputs = document.querySelectorAll('.quantity-input');
    
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const min = parseFloat(this.min) || 0.5;
            const max = parseFloat(this.max) || 1000;
            let value = parseFloat(this.value);
            
            if (isNaN(value) || value < min) {
                this.value = min;
            } else if (value > max) {
                this.value = max;
            }
        });
    });
}

// Handle form validation
function handleFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            showError(input, 'This field is required');
            isValid = false;
        } else {
            clearError(input);
        }
        
        // Email validation
        if (input.type === 'email' && input.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(input.value)) {
                showError(input, 'Please enter a valid email address');
                isValid = false;
            }
        }
        
        // Phone validation
        if (input.type === 'tel' && input.value) {
            const phoneRegex = /^[0-9+\-\s]{10,15}$/;
            if (!phoneRegex.test(input.value)) {
                showError(input, 'Please enter a valid phone number');
                isValid = false;
            }
        }
    });
    
    return isValid;
}

function showError(input, message) {
    const formGroup = input.closest('.form-group');
    if (!formGroup) return;
    
    let error = formGroup.querySelector('.error-message');
    if (!error) {
        error = document.createElement('span');
        error.className = 'error-message';
        error.style.cssText = 'color: #ff4757; font-size: 12px; margin-top: 5px; display: block;';
        formGroup.appendChild(error);
    }
    
    error.textContent = message;
    input.style.borderColor = '#ff4757';
}

function clearError(input) {
    const formGroup = input.closest('.form-group');
    if (!formGroup) return;
    
    const error = formGroup.querySelector('.error-message');
    if (error) {
        error.remove();
    }
    input.style.borderColor = '#ecf0f1';
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func.apply(this, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add active class to current nav item
document.addEventListener('DOMContentLoaded', function() {
    const currentLocation = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const linkPath = link.getAttribute('href');
        if (currentLocation.includes(linkPath) && linkPath !== 'index.php') {
            link.classList.add('active');
        } else if (currentLocation.endsWith('index.php') && linkPath.includes('index.php')) {
            link.classList.add('active');
        }
    });
});