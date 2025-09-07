/* Modern UI JavaScript Functionality */

// Global variables
let isOnline = navigator.onLine;
let sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeModernUI();
    setupOfflineDetection();
    setupFormEnhancements();
    setupSidebarToggle();
    setupPrintFunctionality();
});

// Initialize Modern UI Components
function initializeModernUI() {
    // Apply saved sidebar state
    if (sidebarCollapsed) {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            sidebar.classList.add('collapsed');
        }
    }

    // Add fade-in animation to all cards
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });

    // Initialize stat cards with counting animation
    animateStatCards();
}

// Sidebar Toggle Functionality
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebar) {
        sidebar.classList.toggle('collapsed');
        sidebarCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', sidebarCollapsed);
        
        // Trigger resize event for any charts or responsive components
        setTimeout(() => {
            window.dispatchEvent(new Event('resize'));
        }, 300);
    }
}

// Setup sidebar toggle functionality
function setupSidebarToggle() {
    // Desktop hamburger menu (inside sidebar)
    const hamburgerMenu = document.querySelector('.hamburger-menu');
    if (hamburgerMenu) {
        hamburgerMenu.addEventListener('click', toggleSidebar);
    }

    // Note: Mobile hamburger button (.hamburger-header) is handled by sidebar.js
    // to avoid duplicate event listeners and ensure proper overlay functionality
}

// Animate stat cards with counting effect
function animateStatCards() {
    const statValues = document.querySelectorAll('.stat-value[data-stat]');
    
    statValues.forEach(element => {
        const finalValue = parseFloat(element.textContent.replace(/[₹,]/g, '')) || 0;
        const isMonetary = element.textContent.includes('₹');
        
        animateCounter(element, 0, finalValue, 1000, isMonetary);
    });
}

// Counter animation function
function animateCounter(element, start, end, duration, isMonetary = false) {
    const startTime = performance.now();
    
    function updateCounter(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        // Easing function for smooth animation
        const easeOutCubic = 1 - Math.pow(1 - progress, 3);
        const current = start + (end - start) * easeOutCubic;
        
        if (isMonetary) {
            element.textContent = '₹' + new Intl.NumberFormat('en-IN').format(Math.floor(current));
        } else {
            element.textContent = Math.floor(current);
        }
        
        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        }
    }
    
    requestAnimationFrame(updateCounter);
}

// Offline Detection
function setupOfflineDetection() {
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);
    
    // Check initial state
    if (!navigator.onLine) {
        handleOffline();
    }
}

function handleOffline() {
    isOnline = false;
    showOfflineOverlay();
}

function handleOnline() {
    isOnline = true;
    hideOfflineOverlay();
    showNotification('Connection restored!', 'success');
}

function showOfflineOverlay() {
    // Remove existing overlay if present
    const existingOverlay = document.querySelector('.offline-overlay');
    if (existingOverlay) {
        existingOverlay.remove();
    }

    const overlay = document.createElement('div');
    overlay.className = 'offline-overlay';
    overlay.innerHTML = `
        <div class="offline-content">
            <div class="offline-icon">📡</div>
            <h3>Connection Lost</h3>
            <p>You're currently offline. Some features may not work properly.</p>
            <button class="btn btn-primary" onclick="checkConnection()">
                <i class="bi bi-arrow-clockwise"></i> Try Again
            </button>
        </div>
    `;
    
    document.body.appendChild(overlay);
}

function hideOfflineOverlay() {
    const overlay = document.querySelector('.offline-overlay');
    if (overlay) {
        overlay.style.animation = 'fadeOut 0.3s ease-in';
        setTimeout(() => overlay.remove(), 300);
    }
}

function checkConnection() {
    if (navigator.onLine) {
        handleOnline();
    } else {
        showNotification('Still offline. Please check your connection.', 'warning');
    }
}

// Form Enhancement Functions
function setupFormEnhancements() {
    // Add loading states to all forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', handleFormSubmit);
    });

    // Add success/error animations to form controls
    const formControls = document.querySelectorAll('.form-control');
    formControls.forEach(control => {
        control.addEventListener('blur', validateField);
    });
}

function handleFormSubmit(e) {
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
    
    if (submitBtn && !form.classList.contains('no-loading')) {
        // Add loading state
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
        
        // Add form loading animation
        form.classList.add('form-loading');
        
        // Show loading overlay for AJAX forms
        if (form.classList.contains('ajax-form')) {
            e.preventDefault();
            handleAjaxFormSubmit(form);
        }
    }
}

function handleAjaxFormSubmit(form) {
    const formData = new FormData(form);
    const action = form.action || window.location.href;
    
    fetch(action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        handleFormResponse(form, data);
    })
    .catch(error => {
        console.error('Form submission error:', error);
        handleFormResponse(form, { success: false, message: 'An error occurred. Please try again.' });
    });
}

function handleFormResponse(form, data) {
    const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
    
    // Remove loading states
    if (submitBtn) {
        submitBtn.classList.remove('loading');
        submitBtn.disabled = false;
    }
    form.classList.remove('form-loading');
    
    if (data.success) {
        // Success animation
        form.classList.add('form-success');
        showNotification(data.message || 'Operation completed successfully!', 'success');
        
        // Reset form if specified
        if (data.reset_form) {
            form.reset();
        }
        
        // Redirect if specified
        if (data.redirect) {
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1500);
        }
    } else {
        // Error animation
        form.classList.add('form-error');
        showNotification(data.message || 'An error occurred. Please try again.', 'error');
    }
    
    // Remove animation classes after animation completes
    setTimeout(() => {
        form.classList.remove('form-success', 'form-error');
    }, 600);
}

function validateField(e) {
    const field = e.target;
    const value = field.value.trim();
    
    // Remove existing validation classes
    field.classList.remove('success', 'error');
    
    // Basic validation
    if (field.hasAttribute('required') && !value) {
        field.classList.add('error');
        return false;
    }
    
    // Email validation
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            field.classList.add('error');
            return false;
        }
    }
    
    // Phone validation
    if (field.type === 'tel' && value) {
        const phoneRegex = /^[0-9]{10}$/;
        if (!phoneRegex.test(value.replace(/\s/g, ''))) {
            field.classList.add('error');
            return false;
        }
    }
    
    // If validation passes
    if (value) {
        field.classList.add('success');
    }
    
    return true;
}

// Notification System
function showNotification(message, type = 'info', duration = 5000) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.toast-notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = `toast-notification toast-${type}`;
    notification.innerHTML = `
        <div class="toast-content">
            <div class="toast-icon">
                ${getNotificationIcon(type)}
            </div>
            <div class="toast-message">${message}</div>
            <button class="toast-close" onclick="this.parentElement.parentElement.remove()">
                <i class="bi bi-x"></i>
            </button>
        </div>
    `;
    
    // Add toast styles if not present
    if (!document.querySelector('#toast-styles')) {
        const toastStyles = document.createElement('style');
        toastStyles.id = 'toast-styles';
        toastStyles.textContent = `
            .toast-notification {
                position: fixed;
                top: 2rem;
                right: 2rem;
                z-index: 9999;
                min-width: 300px;
                background: white;
                border-radius: 0.75rem;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                animation: slideInFromRight 0.3s ease-out;
            }
            
            @keyframes slideInFromRight {
                from { opacity: 0; transform: translateX(100%); }
                to { opacity: 1; transform: translateX(0); }
            }
            
            .toast-content {
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 1rem 1.5rem;
            }
            
            .toast-icon {
                font-size: 1.5rem;
                flex-shrink: 0;
            }
            
            .toast-message {
                flex: 1;
                font-weight: 500;
            }
            
            .toast-close {
                background: none;
                border: none;
                font-size: 1.2rem;
                cursor: pointer;
                color: #6b7280;
                padding: 0.25rem;
                border-radius: 0.25rem;
                transition: all 0.2s ease;
            }
            
            .toast-close:hover {
                background: #f3f4f6;
                color: #374151;
            }
            
            .toast-success {
                border-left: 4px solid #10b981;
            }
            
            .toast-error {
                border-left: 4px solid #ef4444;
            }
            
            .toast-warning {
                border-left: 4px solid #f59e0b;
            }
            
            .toast-info {
                border-left: 4px solid #3b82f6;
            }
        `;
        document.head.appendChild(toastStyles);
    }
    
    document.body.appendChild(notification);
    
    // Auto remove after duration
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOutToRight 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        }
    }, duration);
}

function getNotificationIcon(type) {
    const icons = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    return icons[type] || icons.info;
}

// Print Functionality
function setupPrintFunctionality() {
    // Print functionality is now handled manually per page to avoid duplicates
    // Individual pages should implement their own print buttons as needed
    console.log('Print functionality initialized');
}

function printTable(tableContainer) {
    const table = tableContainer.querySelector('table');
    if (!table) return;
    
    const card = tableContainer.closest('.card');
    const title = card?.querySelector('.card-title')?.textContent || 'Report';
    
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>${title} - A.S.ACADEMY</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 0; 
                    padding: 20px; 
                }
                .print-header {
                    text-align: center;
                    border-bottom: 2px solid #000;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                .print-header h1 {
                    margin: 0;
                    font-size: 24px;
                    color: #000;
                }
                .print-header p {
                    margin: 5px 0 0 0;
                    font-size: 14px;
                    color: #666;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-top: 20px;
                }
                th, td { 
                    border: 1px solid #000; 
                    padding: 8px; 
                    text-align: left; 
                }
                th { 
                    background: #f5f5f5; 
                    font-weight: bold; 
                }
                .print-footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 12px;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h1>A.S.ACADEMY HIGHER SECONDARY SCHOOL</h1>
                <p>LAHAR ROAD UMARI BHIND MADHYA PRADESH (477331)</p>
                <h3>${title}</h3>
            </div>
            ${table.outerHTML}
            <div class="print-footer">
                <p>Generated on: ${new Date().toLocaleString()}</p>
                <p>© A.S.Academy Higher Secondary School</p>
            </div>
        </body>
        </html>
    `;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
}

// Enhanced form submission with loading states
function submitFormWithLoading(form, successCallback, errorCallback) {
    const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
    const originalText = submitBtn?.textContent;
    
    // Show loading state
    if (submitBtn) {
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
    }
    
    form.classList.add('form-loading');
    
    const formData = new FormData(form);
    const action = form.action || window.location.href;
    
    return fetch(action, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        // Remove loading state
        if (submitBtn) {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }
        form.classList.remove('form-loading');
        
        if (data.success) {
            form.classList.add('form-success');
            showNotification(data.message || 'Operation completed successfully!', 'success');
            
            if (successCallback) {
                successCallback(data);
            }
            
            // Reset form if specified
            if (data.reset_form !== false) {
                setTimeout(() => form.reset(), 1000);
            }
        } else {
            throw new Error(data.message || 'Operation failed');
        }
        
        // Remove success animation
        setTimeout(() => {
            form.classList.remove('form-success');
        }, 600);
        
        return data;
    })
    .catch(error => {
        console.error('Form submission error:', error);
        
        // Remove loading state
        if (submitBtn) {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }
        form.classList.remove('form-loading');
        
        // Show error animation
        form.classList.add('form-error');
        showNotification(error.message || 'An error occurred. Please try again.', 'error');
        
        if (errorCallback) {
            errorCallback(error);
        }
        
        // Remove error animation
        setTimeout(() => {
            form.classList.remove('form-error');
        }, 600);
        
        throw error;
    });
}

// Utility Functions
function showLoadingOverlay() {
    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay';
    overlay.innerHTML = `
        <div class="loading-spinner"></div>
    `;
    overlay.id = 'global-loading';
    document.body.appendChild(overlay);
}

function hideLoadingOverlay() {
    const overlay = document.getElementById('global-loading');
    if (overlay) {
        overlay.remove();
    }
}

// Search Enhancement
function setupEnhancedSearch() {
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    performSearch(query);
                }, 300);
            }
        });
    }
}

function performSearch(query) {
    // This would connect to a search API endpoint
    console.log('Searching for:', query);
    // Implementation would depend on backend search functionality
}

// Refresh dashboard stats
function refreshDashboardStats() {
    showLoadingOverlay();
    
    fetch(window.location.href + '?refresh_stats=1')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update stat values with animation
            Object.keys(data.stats).forEach(key => {
                const element = document.querySelector(`[data-stat="${key}"]`);
                if (element) {
                    const newValue = data.stats[key];
                    const isMonetary = element.textContent.includes('₹');
                    animateCounter(element, 0, newValue, 800, isMonetary);
                }
            });
            
            showNotification('Dashboard updated successfully!', 'success');
        }
    })
    .catch(error => {
        console.error('Error refreshing stats:', error);
        showNotification('Failed to refresh dashboard', 'error');
    })
    .finally(() => {
        hideLoadingOverlay();
    });
}

// Print specific elements
function printElement(elementId, title = 'Print') {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>${title} - A.S.ACADEMY</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 0; 
                    padding: 20px; 
                }
                .print-header {
                    text-align: center;
                    border-bottom: 2px solid #000;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                }
                th, td { 
                    border: 1px solid #000; 
                    padding: 8px; 
                    text-align: left; 
                }
                th { 
                    background: #f5f5f5; 
                    font-weight: bold; 
                }
                .no-print { 
                    display: none !important; 
                }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h1>A.S.ACADEMY HIGHER SECONDARY SCHOOL</h1>
                <p>LAHAR ROAD UMARI BHIND MADHYA PRADESH (477331)</p>
                <h3>${title}</h3>
            </div>
            ${element.innerHTML}
            <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #666;">
                <p>Generated on: ${new Date().toLocaleString()}</p>
            </div>
        </body>
        </html>
    `;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
}

// Print dropdown menu functionality
function togglePrintMenu(studentId) {
    const menu = document.getElementById(`print-menu-${studentId}`);
    const allMenus = document.querySelectorAll('.print-menu');
    
    // Close all other menus
    allMenus.forEach(m => {
        if (m !== menu) {
            m.style.display = 'none';
        }
    });
    
    // Toggle current menu
    if (menu.style.display === 'none' || !menu.style.display) {
        menu.style.display = 'block';
    } else {
        menu.style.display = 'none';
    }
}

// Close print menus when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown')) {
        const allMenus = document.querySelectorAll('.print-menu');
        allMenus.forEach(menu => {
            menu.style.display = 'none';
        });
    }
});

// Global print report function
function printReport() {
    // Find the main table on the page and print it
    const table = document.querySelector('.table-container table, .data-table, table');
    if (table) {
        const tableContainer = table.closest('.table-container') || table.parentElement;
        printTable(tableContainer);
    } else {
        // If no table found, print the whole page content
        window.print();
    }
}

// Global print section function
function printSection(elementId) {
    printElement(elementId, 'Report');
}

// Export functions for global use (excluding sidebar functions handled by sidebar.js)
window.printElement = printElement;
window.printTable = printTable;
window.printReport = printReport;
window.printSection = printSection;
window.showNotification = showNotification;
window.submitFormWithLoading = submitFormWithLoading;
window.refreshDashboardStats = refreshDashboardStats;
window.showLoadingOverlay = showLoadingOverlay;
window.hideLoadingOverlay = hideLoadingOverlay;
window.togglePrintMenu = togglePrintMenu;
