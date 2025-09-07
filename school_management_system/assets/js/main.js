// Main JavaScript functions for School Management System

// Global variables
let sidebar = null;
let searchTimeout = null;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeSidebar();
    initializeSearch();
    initializeModals();
    initializeFileUploads();
    initializeDatePickers();
    initializeFormValidation();
});

// Sidebar functionality
function initializeSidebar() {
    sidebar = document.querySelector('.sidebar');
    const menuToggle = document.querySelector('.menu-toggle');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', toggleSidebar);
    }
    
    // Close sidebar on mobile when clicking outside
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 1024) {
            if (!sidebar.contains(e.target) && !e.target.closest('.menu-toggle')) {
                closeSidebar();
            }
        }
    });
}

function toggleSidebar() {
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
}

function closeSidebar() {
    if (sidebar) {
        sidebar.classList.remove('open');
    }
}

// Search functionality
function initializeSearch() {
    const searchInput = document.querySelector('.search-input');
    
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(e.target.value);
            }, 300);
        });
    }
}

function performSearch(query) {
    if (query.length < 2) return;
    
    // Implementation depends on current page context
    const currentPage = window.location.pathname;
    
    if (currentPage.includes('students')) {
        searchStudents(query);
    } else if (currentPage.includes('teachers')) {
        searchTeachers(query);
    } else if (currentPage.includes('fees')) {
        searchFees(query);
    }
}

// Search functions for different modules
function searchStudents(query) {
    fetch(`search.php?type=students&q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => updateSearchResults(data))
        .catch(error => console.error('Search error:', error));
}

function searchTeachers(query) {
    fetch(`search.php?type=teachers&q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => updateSearchResults(data))
        .catch(error => console.error('Search error:', error));
}

function searchFees(query) {
    fetch(`search.php?type=fees&q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => updateSearchResults(data))
        .catch(error => console.error('Search error:', error));
}

function updateSearchResults(results) {
    const tbody = document.querySelector('.data-table tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (results.length === 0) {
        tbody.innerHTML = '<tr><td colspan="100%" class="text-center">No results found</td></tr>';
        return;
    }
    
    results.forEach(item => {
        const row = createTableRow(item);
        tbody.appendChild(row);
    });
}

// Modal functionality
function initializeModals() {
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        const closeBtn = modal.querySelector('.close-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => closeModal(modal));
        }
        
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal(modal);
            }
        });
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modal) {
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// File upload functionality
function initializeFileUploads() {
    const fileInputs = document.querySelectorAll('.file-upload-input');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', handleFileUpload);
    });
}

function handleFileUpload(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    const preview = e.target.closest('.form-group').querySelector('.photo-preview');
    
    if (file.type.startsWith('image/') && preview) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
    
    // Validate file size (5MB limit)
    if (file.size > 5 * 1024 * 1024) {
        showAlert('File size should not exceed 5MB', 'danger');
        e.target.value = '';
        return;
    }
}

// Date picker initialization
function initializeDatePickers() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    
    dateInputs.forEach(input => {
        if (!input.value) {
            input.value = new Date().toISOString().split('T')[0];
        }
    });
}

// Form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', validateForm);
    });
}

function validateForm(e) {
    const form = e.target;
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.style.borderColor = 'var(--danger-color)';
            showFieldError(field, 'This field is required');
        } else {
            field.style.borderColor = 'var(--border-color)';
            removeFieldError(field);
        }
    });
    
    // Email validation
    const emailFields = form.querySelectorAll('input[type="email"]');
    emailFields.forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            isValid = false;
            field.style.borderColor = 'var(--danger-color)';
            showFieldError(field, 'Please enter a valid email address');
        }
    });
    
    // Phone number validation
    const phoneFields = form.querySelectorAll('input[name*="mobile"], input[name*="phone"]');
    phoneFields.forEach(field => {
        if (field.value && !isValidPhone(field.value)) {
            isValid = false;
            field.style.borderColor = 'var(--danger-color)';
            showFieldError(field, 'Please enter a valid phone number');
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        showAlert('Please fill in all required fields correctly', 'danger');
    }
}

function showFieldError(field, message) {
    removeFieldError(field);
    const error = document.createElement('div');
    error.className = 'field-error';
    error.style.color = 'var(--danger-color)';
    error.style.fontSize = '0.875rem';
    error.style.marginTop = '0.25rem';
    error.textContent = message;
    field.parentNode.appendChild(error);
}

function removeFieldError(field) {
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

// Utility functions
function isValidEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

function isValidPhone(phone) {
    const regex = /^[+]?[\d\s\-()]{10,15}$/;
    return regex.test(phone);
}

function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const container = document.querySelector('.content-wrapper');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR'
    }).format(amount);
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-IN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

// Delete confirmation
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Print functionality
function printReport() {
    window.print();
}

// Export functionality
function exportToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = Array.from(cols).map(col => {
            return '"' + col.textContent.replace(/"/g, '""') + '"';
        });
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    downloadFile(csvContent, filename + '.csv', 'text/csv');
}

function downloadFile(content, filename, contentType) {
    const blob = new Blob([content], { type: contentType });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.click();
    window.URL.revokeObjectURL(url);
}

// Photo capture functionality
function initializePhotoCapture() {
    const captureBtn = document.querySelector('.capture-photo-btn');
    if (captureBtn) {
        captureBtn.addEventListener('click', capturePhoto);
    }
}

function capturePhoto() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showAlert('Camera not supported in this browser', 'warning');
        return;
    }
    
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(stream => {
            showCameraModal(stream);
        })
        .catch(error => {
            console.error('Camera error:', error);
            showAlert('Unable to access camera', 'danger');
        });
}

function showCameraModal(stream) {
    // Create camera modal (to be implemented based on specific needs)
    console.log('Camera stream available', stream);
}

// AJAX form submission
function submitForm(formId, successCallback) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const formData = new FormData(form);
    const submitBtn = form.querySelector('[type="submit"]');
    
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner"></span> Processing...';
    }
    
    fetch(form.action || window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message || 'Operation completed successfully', 'success');
            if (successCallback) successCallback(data);
        } else {
            showAlert(data.message || 'An error occurred', 'danger');
        }
    })
    .catch(error => {
        console.error('Form submission error:', error);
        showAlert('An error occurred while processing your request', 'danger');
    })
    .finally(() => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'Submit';
        }
    });
}

// Auto-generate receipt/voucher numbers
function generateReceiptNumber() {
    const today = new Date();
    const year = today.getFullYear().toString().substr(-2);
    const month = (today.getMonth() + 1).toString().padStart(2, '0');
    const day = today.getDate().toString().padStart(2, '0');
    const time = Date.now().toString().substr(-4);
    
    return `RCP${year}${month}${day}${time}`;
}

function generateVoucherNumber() {
    const today = new Date();
    const year = today.getFullYear().toString().substr(-2);
    const month = (today.getMonth() + 1).toString().padStart(2, '0');
    const day = today.getDate().toString().padStart(2, '0');
    const time = Date.now().toString().substr(-4);
    
    return `VCH${year}${month}${day}${time}`;
}

// Auto-fill functionality
function fillReceiptNumber() {
    const receiptField = document.querySelector('input[name="receipt_no"]');
    if (receiptField && !receiptField.value) {
        receiptField.value = generateReceiptNumber();
    }
}

function fillVoucherNumber() {
    const voucherField = document.querySelector('input[name="voucher_no"]');
    if (voucherField && !voucherField.value) {
        voucherField.value = generateVoucherNumber();
    }
}

// Dynamic class-based student filtering
function filterStudentsByClass() {
    const classSelect = document.querySelector('select[name="class_id"]');
    const studentSelect = document.querySelector('select[name="student_id"]');
    
    if (classSelect && studentSelect) {
        classSelect.addEventListener('change', function() {
            const classId = this.value;
            
            if (classId) {
                fetch(`get_students.php?class_id=${classId}`)
                    .then(response => response.json())
                    .then(students => {
                        studentSelect.innerHTML = '<option value="">Select Student</option>';
                        students.forEach(student => {
                            const option = document.createElement('option');
                            option.value = student.id;
                            option.textContent = `${student.admission_no} - ${student.first_name} ${student.last_name}`;
                            studentSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error fetching students:', error));
            } else {
                studentSelect.innerHTML = '<option value="">Select Class First</option>';
            }
        });
    }
}

// Dynamic village-based student filtering
function filterStudentsByVillage() {
    const villageSelect = document.querySelector('select[name="village"]');
    const studentSelect = document.querySelector('select[name="student_id"]');
    
    if (villageSelect && studentSelect) {
        villageSelect.addEventListener('change', function() {
            const village = this.value;
            
            if (village) {
                fetch(`get_students.php?village=${encodeURIComponent(village)}`)
                    .then(response => response.json())
                    .then(students => {
                        studentSelect.innerHTML = '<option value="">Select Student</option>';
                        students.forEach(student => {
                            const option = document.createElement('option');
                            option.value = student.id;
                            option.textContent = `${student.admission_no} - ${student.first_name} ${student.last_name} (Class: ${student.class_name})`;
                            studentSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error fetching students:', error));
            } else {
                studentSelect.innerHTML = '<option value="">Select Village First</option>';
            }
        });
    }
}

// Table row creation helper
function createTableRow(data) {
    const row = document.createElement('tr');
    // Implementation depends on data structure
    return row;
}

// Logout functionality
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
    }
}

// Dashboard stats refresh
function refreshDashboardStats() {
    fetch('get_dashboard_stats.php')
        .then(response => response.json())
        .then(data => {
            updateDashboardCards(data);
        })
        .catch(error => console.error('Error refreshing stats:', error));
}

function updateDashboardCards(stats) {
    Object.keys(stats).forEach(key => {
        const element = document.querySelector(`[data-stat="${key}"]`);
        if (element) {
            element.textContent = stats[key];
        }
    });
}

// Print specific sections
function printSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (section) {
        const printContent = section.outerHTML;
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Print</title>
                    <link rel="stylesheet" href="assets/css/style.css">
                    <style>
                        body { margin: 20px; }
                        .sidebar, .header, .btn { display: none !important; }
                        .main-content { margin-left: 0 !important; padding-top: 0 !important; }
                    </style>
                </head>
                <body>${printContent}</body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }
}

// Calculate age from date of birth
function calculateAge(dob) {
    const today = new Date();
    const birthDate = new Date(dob);
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    
    return age;
}

// Auto-update age field when DOB changes
function updateAgeField() {
    const dobField = document.querySelector('input[name="date_of_birth"]');
    const ageField = document.querySelector('input[name="age"]');
    
    if (dobField && ageField) {
        dobField.addEventListener('change', function() {
            if (this.value) {
                ageField.value = calculateAge(this.value);
            }
        });
    }
}

// DataTables Enhancement
function initializeDataTables() {
    if (typeof $.fn.DataTable !== 'undefined') {
        $('.data-table:not(.dt-initialized)').each(function() {
            const table = $(this);
            
            table.DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                language: {
                    search: "<i class='bi bi-search'></i>",
                    searchPlaceholder: "Search records...",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "No entries available",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    zeroRecords: "No matching records found",
                    paginate: {
                        first: "<i class='bi bi-chevron-double-left'></i>",
                        last: "<i class='bi bi-chevron-double-right'></i>",
                        next: "<i class='bi bi-chevron-right'></i>",
                        previous: "<i class='bi bi-chevron-left'></i>"
                    }
                },
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                drawCallback: function() {
                    // Re-initialize Bootstrap components for new content
                    initializeBootstrap();
                }
            });
            
            table.addClass('dt-initialized');
        });
    }
}

// Chart initialization
function initializeCharts() {
    // Initialize any Chart.js charts
    const chartElements = document.querySelectorAll('.chart-canvas');
    
    chartElements.forEach(canvas => {
        const type = canvas.dataset.chartType || 'line';
        const data = JSON.parse(canvas.dataset.chartData || '{}');
        const options = JSON.parse(canvas.dataset.chartOptions || '{}');
        
        new Chart(canvas, {
            type: type,
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                ...options
            }
        });
    });
}

// Auto-save functionality
function initializeAutoSave() {
    const autoSaveForms = document.querySelectorAll('[data-auto-save]');
    
    autoSaveForms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('input', debounce(() => {
                autoSaveForm(form);
            }, 2000));
        });
    });
}

function autoSaveForm(form) {
    const formData = new FormData(form);
    formData.append('auto_save', '1');
    
    fetch(form.action || window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Auto-saved', 'success', 1000);
        }
    })
    .catch(() => {
        // Silently fail for auto-save
    });
}

// Keyboard Shortcuts
function initializeKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl+S for save
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            const saveButton = document.querySelector('button[type="submit"], .btn-save');
            if (saveButton && !saveButton.disabled) {
                saveButton.click();
            }
        }
        
        // Escape to close modals
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                bootstrap.Modal.getInstance(openModal)?.hide();
            }
        }
        
        // Ctrl+F for search
        if (e.ctrlKey && e.key === 'f') {
            const searchInput = document.querySelector('.search-input, [data-search]');
            if (searchInput) {
                e.preventDefault();
                searchInput.focus();
            }
        }
    });
}

// Enhanced Loading states
function showLoading(button) {
    if (button) {
        button.disabled = true;
        button.classList.add('loading');
        button.dataset.originalHtml = button.innerHTML;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
    }
}

function hideLoading(button) {
    if (button) {
        button.disabled = false;
        button.classList.remove('loading');
        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
            delete button.dataset.originalHtml;
        }
    }
}

// Form helpers
function serializeForm(form) {
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        if (data[key]) {
            // Handle multiple values (like checkboxes)
            if (Array.isArray(data[key])) {
                data[key].push(value);
            } else {
                data[key] = [data[key], value];
            }
        } else {
            data[key] = value;
        }
    }
    
    return data;
}

function populateForm(form, data) {
    Object.keys(data).forEach(key => {
        const elements = form.querySelectorAll(`[name="${key}"]`);
        
        elements.forEach(element => {
            if (element.type === 'checkbox') {
                element.checked = Array.isArray(data[key]) ? 
                    data[key].includes(element.value) : 
                    Boolean(data[key]);
            } else if (element.type === 'radio') {
                element.checked = element.value === data[key];
            } else {
                element.value = data[key];
            }
        });
    });
}

// Enhanced AJAX form submission
function submitForm(formId, successCallback) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const formData = new FormData(form);
    const submitBtn = form.querySelector('[type="submit"]');
    
    showLoading(submitBtn);
    
    makeRequest(form.action || window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Operation completed successfully', 'success');
            if (successCallback) successCallback(data);
        } else {
            showToast(data.message || 'An error occurred', 'danger');
        }
    })
    .catch(error => {
        console.error('Form submission error:', error);
        showToast('An error occurred while processing your request', 'danger');
    })
    .finally(() => {
        hideLoading(submitBtn);
    });
}

// Utility Functions
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

// Export global functions
window.SMS = SMS;
window.showToast = showToast;
window.makeRequest = makeRequest;
window.formatDate = formatDate;
window.formatCurrency = formatCurrency;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.confirmAction = confirmAction;
window.serializeForm = serializeForm;
window.populateForm = populateForm;
window.showAlert = showAlert;
window.openModal = openModal;
window.closeModal = closeModal;

// Debug helper
function debug(message, data = null) {
    if (SMS.debug) {
        console.log('[SMS Debug]', message, data);
    }
}

// Global error handler
window.addEventListener('error', function(e) {
    if (SMS.debug) {
        console.error('Global error:', e.error);
    }
});

// Service worker registration (for PWA capabilities)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/school_management_system/sw.js')
            .then(function(registration) {
                console.log('SW registered: ', registration);
            })
            .catch(function(registrationError) {
                console.log('SW registration failed: ', registrationError);
            });
    });
}
