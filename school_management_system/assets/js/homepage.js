// Homepage JavaScript for enhanced interactions

document.addEventListener('DOMContentLoaded', function() {
    initializeNavbar();
    initializeScrollReveal();
    initializeCounters();
    initializeSmoothScrolling();
    initializeMobileMenu();
    initializeGalleryLightbox();
    initializeFormValidation();
});

// Navbar scroll effect
function initializeNavbar() {
    const navbar = document.querySelector('.homepage-navbar');
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 100) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
    
    // Update active nav link based on scroll position
    updateActiveNavLink();
    window.addEventListener('scroll', updateActiveNavLink);
}

function updateActiveNavLink() {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link');
    
    let currentSection = '';
    
    sections.forEach(section => {
        const rect = section.getBoundingClientRect();
        if (rect.top <= 100 && rect.bottom >= 100) {
            currentSection = section.id;
        }
    });
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === '#' + currentSection) {
            link.classList.add('active');
        }
    });
}

// Smooth scrolling for anchor links
function initializeSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            
            if (target) {
                const offsetTop = target.offsetTop - 80; // Account for fixed navbar
                
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Scroll reveal animations
function initializeScrollReveal() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
            }
        });
    }, observerOptions);
    
    // Add scroll reveal to elements
    const revealElements = document.querySelectorAll('.feature-card, .gallery-item, .news-card, .contact-item');
    revealElements.forEach(el => {
        el.classList.add('scroll-reveal');
        observer.observe(el);
    });
}

// Animated counters for statistics
function initializeCounters() {
    const counters = document.querySelectorAll('.stat-number');
    
    const observerOptions = {
        threshold: 0.5
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    counters.forEach(counter => {
        observer.observe(counter);
    });
}

function animateCounter(element) {
    const target = parseInt(element.textContent.replace(/,/g, ''));
    const duration = 2000;
    const step = target / (duration / 16);
    let current = 0;
    
    const timer = setInterval(() => {
        current += step;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        
        element.textContent = Math.floor(current).toLocaleString();
    }, 16);
}

// Mobile menu functionality
function initializeMobileMenu() {
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const navbarMenu = document.querySelector('.navbar-menu');
    
    if (mobileToggle && navbarMenu) {
        mobileToggle.addEventListener('click', function() {
            navbarMenu.style.display = navbarMenu.style.display === 'flex' ? 'none' : 'flex';
            
            // Update icon
            const icon = this.querySelector('i');
            if (icon.classList.contains('bi-list')) {
                icon.className = 'bi bi-x';
            } else {
                icon.className = 'bi bi-list';
            }
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!mobileToggle.contains(e.target) && !navbarMenu.contains(e.target)) {
                navbarMenu.style.display = 'none';
                const icon = mobileToggle.querySelector('i');
                icon.className = 'bi bi-list';
            }
        });
    }
}

// Gallery lightbox functionality
function initializeGalleryLightbox() {
    const galleryItems = document.querySelectorAll('.gallery-item');
    
    galleryItems.forEach(item => {
        item.addEventListener('click', function() {
            const img = this.querySelector('img');
            const title = this.querySelector('.gallery-overlay h4')?.textContent;
            const description = this.querySelector('.gallery-overlay p')?.textContent;
            
            openLightbox(img.src, title, description);
        });
    });
}

function openLightbox(imageSrc, title, description) {
    // Create lightbox if it doesn't exist
    let lightbox = document.getElementById('gallery-lightbox');
    
    if (!lightbox) {
        lightbox = document.createElement('div');
        lightbox.id = 'gallery-lightbox';
        lightbox.className = 'gallery-lightbox';
        lightbox.innerHTML = `
            <div class="lightbox-overlay"></div>
            <div class="lightbox-content">
                <button class="lightbox-close">&times;</button>
                <img class="lightbox-image" src="" alt="">
                <div class="lightbox-info">
                    <h3 class="lightbox-title"></h3>
                    <p class="lightbox-description"></p>
                </div>
            </div>
        `;
        document.body.appendChild(lightbox);
        
        // Add event listeners
        lightbox.querySelector('.lightbox-close').addEventListener('click', closeLightbox);
        lightbox.querySelector('.lightbox-overlay').addEventListener('click', closeLightbox);
        
        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            .gallery-lightbox {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 9999;
                display: none;
                align-items: center;
                justify-content: center;
            }
            
            .lightbox-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.9);
                backdrop-filter: blur(5px);
            }
            
            .lightbox-content {
                position: relative;
                max-width: 90%;
                max-height: 90%;
                background: white;
                border-radius: 1rem;
                overflow: hidden;
                box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            }
            
            .lightbox-close {
                position: absolute;
                top: 1rem;
                right: 1rem;
                width: 40px;
                height: 40px;
                background: rgba(0, 0, 0, 0.7);
                border: none;
                border-radius: 50%;
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
                z-index: 10;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
            }
            
            .lightbox-close:hover {
                background: rgba(0, 0, 0, 0.9);
                transform: scale(1.1);
            }
            
            .lightbox-image {
                width: 100%;
                max-height: 70vh;
                object-fit: contain;
                display: block;
            }
            
            .lightbox-info {
                padding: 2rem;
                text-align: center;
            }
            
            .lightbox-title {
                font-size: 1.5rem;
                font-weight: 600;
                color: var(--text-primary);
                margin-bottom: 1rem;
            }
            
            .lightbox-description {
                color: var(--text-secondary);
                line-height: 1.6;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Update content and show
    lightbox.querySelector('.lightbox-image').src = imageSrc;
    lightbox.querySelector('.lightbox-title').textContent = title || '';
    lightbox.querySelector('.lightbox-description').textContent = description || '';
    lightbox.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    const lightbox = document.getElementById('gallery-lightbox');
    if (lightbox) {
        lightbox.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Form validation for contact form
function initializeFormValidation() {
    const contactForm = document.querySelector('.contact-form form');
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (validateContactForm(this)) {
                submitContactForm(this);
            }
        });
    }
}

function validateContactForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    // Clear previous errors
    clearFormErrors(form);
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'This field is required');
            isValid = false;
        } else {
            // Specific validation for email
            if (field.type === 'email' && !isValidEmail(field.value)) {
                showFieldError(field, 'Please enter a valid email address');
                isValid = false;
            }
        }
    });
    
    return isValid;
}

function submitContactForm(form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';
    
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Thank you! Your message has been sent successfully.', 'success');
            form.reset();
        } else {
            showAlert(data.message || 'An error occurred. Please try again.', 'danger');
        }
    })
    .catch(error => {
        console.error('Form submission error:', error);
        showAlert('An error occurred. Please try again later.', 'danger');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// Utility functions
function clearFormErrors(form) {
    const errorElements = form.querySelectorAll('.field-error');
    errorElements.forEach(error => error.remove());
    
    const inputs = form.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.style.borderColor = '';
    });
}

function showFieldError(field, message) {
    field.style.borderColor = '#dc3545';
    
    const error = document.createElement('div');
    error.className = 'field-error';
    error.style.cssText = `
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
        display: block;
    `;
    error.textContent = message;
    
    field.parentNode.appendChild(error);
}

function isValidEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

function showAlert(message, type = 'info') {
    // Create alert element
    const alert = document.createElement('div');
    alert.className = `homepage-alert alert-${type}`;
    alert.innerHTML = `
        <div class="alert-content">
            <i class="bi ${getAlertIcon(type)}"></i>
            <span>${message}</span>
            <button class="alert-close">&times;</button>
        </div>
    `;
    
    // Add styles
    alert.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        background: ${getAlertColor(type)};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        transform: translateX(100%);
        transition: all 0.3s ease;
    `;
    
    document.body.appendChild(alert);
    
    // Animate in
    setTimeout(() => {
        alert.style.transform = 'translateX(0)';
    }, 100);
    
    // Add close functionality
    alert.querySelector('.alert-close').addEventListener('click', () => {
        removeAlert(alert);
    });
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        removeAlert(alert);
    }, 5000);
}

function removeAlert(alert) {
    alert.style.transform = 'translateX(100%)';
    setTimeout(() => {
        if (alert.parentNode) {
            alert.parentNode.removeChild(alert);
        }
    }, 300);
}

function getAlertIcon(type) {
    switch(type) {
        case 'success': return 'bi-check-circle';
        case 'danger': return 'bi-exclamation-triangle';
        case 'warning': return 'bi-exclamation-circle';
        default: return 'bi-info-circle';
    }
}

function getAlertColor(type) {
    switch(type) {
        case 'success': return '#28a745';
        case 'danger': return '#dc3545';
        case 'warning': return '#ffc107';
        default: return '#17a2b8';
    }
}

// Gallery lightbox enhancements
function initializeGalleryLightbox() {
    // Add keyboard navigation
    document.addEventListener('keydown', function(e) {
        const lightbox = document.getElementById('gallery-lightbox');
        if (lightbox && lightbox.style.display === 'flex') {
            if (e.key === 'Escape') {
                closeLightbox();
            }
        }
    });
}

// Mobile menu functionality
function initializeMobileMenu() {
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    
    if (mobileToggle) {
        // Create mobile menu overlay
        const mobileMenu = document.createElement('div');
        mobileMenu.className = 'mobile-menu-overlay';
        mobileMenu.innerHTML = `
            <div class="mobile-menu-content">
                <div class="mobile-menu-header">
                    <h3>Menu</h3>
                    <button class="mobile-menu-close">&times;</button>
                </div>
                <nav class="mobile-nav">
                    <a href="#home" class="mobile-nav-link">Home</a>
                    <a href="#about" class="mobile-nav-link">About</a>
                    <a href="#gallery" class="mobile-nav-link">Gallery</a>
                    <a href="#contact" class="mobile-nav-link">Contact</a>
                    <a href="admissions.php" class="mobile-nav-link">Admissions</a>
                    <a href="login.php" class="mobile-nav-link btn btn-primary">Login</a>
                </nav>
            </div>
        `;
        
        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            .mobile-menu-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 9999;
                display: none;
            }
            
            .mobile-menu-content {
                position: absolute;
                top: 0;
                right: 0;
                width: 280px;
                height: 100%;
                background: white;
                transform: translateX(100%);
                transition: transform 0.3s ease;
            }
            
            .mobile-menu-overlay.active .mobile-menu-content {
                transform: translateX(0);
            }
            
            .mobile-menu-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 1.5rem;
                border-bottom: 1px solid #e2e8f0;
            }
            
            .mobile-menu-close {
                background: none;
                border: none;
                font-size: 1.5rem;
                cursor: pointer;
                color: var(--text-primary);
            }
            
            .mobile-nav {
                padding: 2rem 1.5rem;
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }
            
            .mobile-nav-link {
                color: var(--text-primary);
                text-decoration: none;
                padding: 0.75rem 0;
                border-bottom: 1px solid #f1f5f9;
                font-weight: 500;
                transition: all 0.3s ease;
            }
            
            .mobile-nav-link:hover {
                color: var(--primary-color);
                transform: translateX(10px);
            }
        `;
        document.head.appendChild(style);
        document.body.appendChild(mobileMenu);
        
        // Toggle functionality
        mobileToggle.addEventListener('click', function() {
            mobileMenu.style.display = 'block';
            setTimeout(() => {
                mobileMenu.classList.add('active');
            }, 10);
        });
        
        // Close functionality
        function closeMobileMenu() {
            mobileMenu.classList.remove('active');
            setTimeout(() => {
                mobileMenu.style.display = 'none';
            }, 300);
        }
        
        mobileMenu.querySelector('.mobile-menu-close').addEventListener('click', closeMobileMenu);
        mobileMenu.addEventListener('click', function(e) {
            if (e.target === mobileMenu) {
                closeMobileMenu();
            }
        });
        
        // Close when clicking nav links
        mobileMenu.querySelectorAll('.mobile-nav-link').forEach(link => {
            link.addEventListener('click', closeMobileMenu);
        });
    }
}

// Parallax effect for hero background
window.addEventListener('scroll', function() {
    const scrolled = window.pageYOffset;
    const rate = scrolled * -0.5;
    const heroBackground = document.querySelector('.hero-background');
    
    if (heroBackground) {
        heroBackground.style.transform = `translateY(${rate}px)`;
    }
});

// Loading states for images
function initializeImageLoading() {
    const images = document.querySelectorAll('img');
    
    images.forEach(img => {
        if (!img.complete) {
            img.classList.add('loading');
            
            img.addEventListener('load', function() {
                this.classList.remove('loading');
            });
            
            img.addEventListener('error', function() {
                this.classList.remove('loading');
                this.src = 'assets/images/placeholder.jpg'; // Fallback image
            });
        }
    });
}

// Initialize loading for images when DOM is ready
document.addEventListener('DOMContentLoaded', initializeImageLoading);

// Statistics animation on scroll
function initializeStatsAnimation() {
    const statsSection = document.querySelector('.hero-stats');
    
    if (statsSection) {
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.querySelectorAll('.stat-card').forEach((card, index) => {
                        setTimeout(() => {
                            card.style.animation = 'fadeInUp 0.6s ease forwards';
                        }, index * 100);
                    });
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        observer.observe(statsSection);
    }
}

// Initialize stats animation
document.addEventListener('DOMContentLoaded', initializeStatsAnimation);

// Typing effect for hero subtitle
function initializeTypingEffect() {
    const subtitle = document.querySelector('.hero-subtitle');
    if (subtitle && !subtitle.dataset.animated) {
        const text = subtitle.textContent;
        subtitle.textContent = '';
        subtitle.dataset.animated = 'true';
        
        let i = 0;
        const typeInterval = setInterval(() => {
            subtitle.textContent += text.charAt(i);
            i++;
            if (i >= text.length) {
                clearInterval(typeInterval);
            }
        }, 50);
    }
}

// Initialize typing effect after a delay
setTimeout(initializeTypingEffect, 1000);

// Enhanced scroll to top functionality
function addScrollToTop() {
    const scrollBtn = document.createElement('button');
    scrollBtn.className = 'scroll-to-top';
    scrollBtn.innerHTML = '<i class="bi bi-arrow-up"></i>';
    scrollBtn.style.cssText = `
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        width: 50px;
        height: 50px;
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    `;
    
    document.body.appendChild(scrollBtn);
    
    // Show/hide based on scroll
    window.addEventListener('scroll', function() {
        if (window.scrollY > 500) {
            scrollBtn.style.display = 'flex';
        } else {
            scrollBtn.style.display = 'none';
        }
    });
    
    // Scroll to top functionality
    scrollBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Hover effect
    scrollBtn.addEventListener('mouseover', function() {
        this.style.background = 'var(--primary-dark)';
        this.style.transform = 'scale(1.1)';
    });
    
    scrollBtn.addEventListener('mouseout', function() {
        this.style.background = 'var(--primary-color)';
        this.style.transform = 'scale(1)';
    });
}

// Initialize scroll to top
document.addEventListener('DOMContentLoaded', addScrollToTop);

// Preloader
function initializePreloader() {
    const preloader = document.createElement('div');
    preloader.id = 'homepage-preloader';
    preloader.innerHTML = `
        <div class="preloader-content">
            <div class="preloader-logo">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <div class="preloader-spinner"></div>
            <p>Loading...</p>
        </div>
    `;
    
    const style = document.createElement('style');
    style.textContent = `
        #homepage-preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
            transition: opacity 0.5s ease;
        }
        
        .preloader-content {
            text-align: center;
        }
        
        .preloader-logo {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }
        
        .preloader-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    `;
    document.head.appendChild(style);
    document.body.appendChild(preloader);
    
    // Hide preloader when page loads
    window.addEventListener('load', function() {
        setTimeout(() => {
            preloader.style.opacity = '0';
            setTimeout(() => {
                preloader.remove();
            }, 500);
        }, 1000);
    });
}

// Initialize preloader
initializePreloader();
