// Enhanced JavaScript features for School Management System with Bootstrap 5
// This file extends main.js with modern Bootstrap integration

// Global configuration enhancement
window.SMS = window.SMS || {};
Object.assign(window.SMS, {
    baseUrl: window.location.origin + '/school_management_system',
    ajaxTimeout: 30000,
    debug: false,
    csrf_token: null
});

// Initialize enhanced features when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeEnhancedFeatures();
});

function initializeEnhancedFeatures() {
    // Get CSRF token if available
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) {
        window.SMS.csrf_token = csrfMeta.content;
    }
    
    // Initialize Bootstrap components
    initializeBootstrap();
    
    // Initialize enhanced features
    initializeEnhancedSearch();
    initializeEnhancedFileUploads();
    initializeEnhancedFormValidation();
    initializeDataTables();
    initializeCharts();
    initializeToasts();
    initializeKeyboardShortcuts();
    initializeAutoSave();
    initializeMobileMenu();
}

// Bootstrap Components Initialization
function initializeBootstrap() {
    // Initialize all tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize all popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Initialize modals with custom options
    const modalElements = document.querySelectorAll('.modal[data-bs-toggle]');
    modalElements.forEach(modalEl => {
        const modal = new bootstrap.Modal(modalEl, {
            backdrop: 'static',
            keyboard: true
        });
    });
}

// Enhanced Search with highlighting
function initializeEnhancedSearch() {
    const searchInputs = document.querySelectorAll('[data-search]');
    
    searchInputs.forEach(input => {
        let searchTimeout;
        
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const spinner = this.parentElement.querySelector('.spinner-border');
            
            // Show loading spinner
            if (spinner) spinner.classList.remove('d-none');
            
            searchTimeout = setTimeout(() => {
                performSearchEnhanced(this.value, this.dataset.search);
                if (spinner) spinner.classList.add('d-none');
            }, 300);
        });
        
        // Clear search
        const clearBtn = input.parentElement.querySelector('.clear-search');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                input.value = '';
                performSearchEnhanced('', input.dataset.search);
            });
        }
    });
}

function performSearchEnhanced(query, target) {
    const targetElement = document.getElementById(target);
    if (!targetElement) return;
    
    // Enhanced table search with highlighting
    if (targetElement.tagName === 'TABLE' || targetElement.classList.contains('data-table')) {
        const rows = targetElement.querySelectorAll('tbody tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const matches = text.includes(query.toLowerCase());
            
            if (matches) {
                row.classList.remove('d-none');
                highlightText(row, query);
                visibleCount++;
            } else {
                row.classList.add('d-none');
            }
        });
        
        updateSearchResultsEnhanced(targetElement, query, visibleCount);
    }
}

function highlightText(element, query) {
    if (!query) return;
    
    // Remove existing highlights
    element.querySelectorAll('mark').forEach(mark => {
        mark.outerHTML = mark.innerHTML;
    });
    
    if (query.trim() === '') return;
    
    const walker = document.createTreeWalker(
        element,
        NodeFilter.SHOW_TEXT,
        null,
        false
    );
    
    const textNodes = [];
    let node;
    
    while (node = walker.nextNode()) {
        textNodes.push(node);
    }
    
    textNodes.forEach(textNode => {
        const text = textNode.textContent;
        const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
        
        if (regex.test(text)) {
            const highlightedHTML = text.replace(regex, '<mark>$1</mark>');
            const wrapper = document.createElement('span');
            wrapper.innerHTML = highlightedHTML;
            textNode.parentNode.replaceChild(wrapper, textNode);
        }
    });
}

function escapeRegex(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function updateSearchResultsEnhanced(table, query, visibleCount) {
    let resultInfo = table.parentElement.querySelector('.search-results');
    
    if (!resultInfo) {
        resultInfo = document.createElement('div');
        resultInfo.className = 'search-results alert alert-info mt-2';
        table.parentElement.insertBefore(resultInfo, table);
    }
    
    if (query) {
        resultInfo.innerHTML = `<i class="bi bi-search"></i> Found ${visibleCount} results for "<strong>${query}</strong>"`;
        resultInfo.classList.remove('d-none');
    } else {
        resultInfo.classList.add('d-none');
    }
}

// Enhanced File Uploads with Drag & Drop
function initializeEnhancedFileUploads() {
    const fileInputs = document.querySelectorAll('input[type="file"]:not(.enhanced)');
    
    fileInputs.forEach(input => {
        input.classList.add('enhanced');
        
        // Create drag & drop zone if not already wrapped
        if (!input.closest('.file-upload-wrapper')) {
            createDropZone(input);
        }
        
        input.addEventListener('change', function() {
            handleEnhancedFileUpload(this);
        });
    });
}

function createDropZone(input) {
    const wrapper = document.createElement('div');
    wrapper.className = 'file-upload-wrapper border-2 border-dashed rounded p-4 text-center';
    wrapper.innerHTML = `
        <i class="bi bi-cloud-upload fs-1 text-muted"></i>
        <p class="mb-2">Drop files here or click to browse</p>
        <small class="text-muted">Supported formats: PDF, JPG, PNG, DOC, DOCX</small>
    `;
    
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);
    
    // Drag & drop events
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        wrapper.addEventListener(eventName, preventDefaults, false);
    });
    
    ['dragenter', 'dragover'].forEach(eventName => {
        wrapper.addEventListener(eventName, () => wrapper.classList.add('border-primary', 'bg-light'), false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        wrapper.addEventListener(eventName, () => wrapper.classList.remove('border-primary', 'bg-light'), false);
    });
    
    wrapper.addEventListener('drop', function(e) {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            input.files = files;
            handleEnhancedFileUpload(input);
        }
    });
    
    wrapper.addEventListener('click', () => input.click());
}

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

function handleEnhancedFileUpload(input) {
    const files = input.files;
    if (files.length === 0) return;
    
    const file = files[0];
    
    // Validate file
    if (!validateFileClient(file)) {
        return;
    }
    
    // Show file preview
    const wrapper = input.closest('.file-upload-wrapper');
    if (wrapper) {
        showFilePreview(wrapper, file);
    }
}

function validateFileClient(file) {
    const allowedTypes = [
        'application/pdf', 
        'image/jpeg', 
        'image/png', 
        'image/jpg', 
        'application/msword', 
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    const maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!allowedTypes.includes(file.type)) {
        showToast('Invalid file type. Please upload PDF, JPG, PNG, DOC, or DOCX files.', 'danger');
        return false;
    }
    
    if (file.size > maxSize) {
        showToast('File size exceeds 5MB limit.', 'danger');
        return false;
    }
    
    return true;
}

function showFilePreview(wrapper, file) {
    const preview = wrapper.querySelector('.file-preview') || document.createElement('div');
    preview.className = 'file-preview mt-3 p-3 bg-light rounded';
    
    const icon = getFileIcon(file.type);
    preview.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="${icon} fs-2 me-3"></i>
            <div class="flex-grow-1">
                <div class="fw-semibold">${file.name}</div>
                <small class="text-muted">${formatFileSize(file.size)}</small>
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeFile(this)">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    
    if (!wrapper.querySelector('.file-preview')) {
        wrapper.appendChild(preview);
    }
}

function getFileIcon(mimeType) {
    const iconMap = {
        'application/pdf': 'bi bi-file-earmark-pdf text-danger',
        'image/jpeg': 'bi bi-file-earmark-image text-success',
        'image/png': 'bi bi-file-earmark-image text-success',
        'image/jpg': 'bi bi-file-earmark-image text-success',
        'application/msword': 'bi bi-file-earmark-word text-primary',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'bi bi-file-earmark-word text-primary'
    };
    
    return iconMap[mimeType] || 'bi bi-file-earmark text-secondary';
}

function removeFile(button) {
    const wrapper = button.closest('.file-upload-wrapper');
    const input = wrapper.querySelector('input[type="file"]');
    const preview = wrapper.querySelector('.file-preview');
    
    input.value = '';
    if (preview) preview.remove();
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Enhanced Form Validation with Bootstrap
function initializeEnhancedFormValidation() {
    const forms = document.querySelectorAll('form[data-validate], .needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity() || !validateFormCustom(this)) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            form.classList.add('was-validated');
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', () => validateInputCustom(input));
            input.addEventListener('input', () => clearValidation(input));
        });
    });
}

function validateFormCustom(form) {
    let isValid = true;
    const customValidators = form.querySelectorAll('[data-validate]');
    
    customValidators.forEach(input => {
        const validators = input.dataset.validate.split('|');
        
        validators.forEach(validator => {
            const [rule, param] = validator.split(':');
            
            if (!applyValidationRule(input, rule, param)) {
                isValid = false;
            }
        });
    });
    
    return isValid;
}

function applyValidationRule(input, rule, param) {
    const value = input.value.trim();
    
    switch (rule) {
        case 'required':
            return validateRequired(input, value);
        case 'email':
            return validateEmailRule(input, value);
        case 'phone':
            return validatePhoneRule(input, value);
        case 'min':
            return validateMinLength(input, value, parseInt(param));
        case 'max':
            return validateMaxLength(input, value, parseInt(param));
        case 'numeric':
            return validateNumeric(input, value);
        case 'date':
            return validateDateRule(input, value);
        default:
            return true;
    }
}

function validateRequired(input, value) {
    if (!value) {
        setInputError(input, 'This field is required.');
        return false;
    }
    return true;
}

function validateEmailRule(input, value) {
    if (value && !isValidEmail(value)) {
        setInputError(input, 'Please enter a valid email address.');
        return false;
    }
    return true;
}

function validatePhoneRule(input, value) {
    if (value && !isValidPhone(value)) {
        setInputError(input, 'Please enter a valid phone number.');
        return false;
    }
    return true;
}

function validateMinLength(input, value, min) {
    if (value && value.length < min) {
        setInputError(input, `Must be at least ${min} characters long.`);
        return false;
    }
    return true;
}

function validateMaxLength(input, value, max) {
    if (value && value.length > max) {
        setInputError(input, `Must not exceed ${max} characters.`);
        return false;
    }
    return true;
}

function validateNumeric(input, value) {
    if (value && isNaN(value)) {
        setInputError(input, 'Please enter a valid number.');
        return false;
    }
    return true;
}

function validateDateRule(input, value) {
    if (value && !isValidDate(value)) {
        setInputError(input, 'Please enter a valid date.');
        return false;
    }
    return true;
}

function isValidDate(date) {
    return !isNaN(Date.parse(date));
}

function setInputError(input, message) {
    input.classList.add('is-invalid');
    
    let feedback = input.parentElement.querySelector('.invalid-feedback');
    if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        input.parentElement.appendChild(feedback);
    }
    
    feedback.textContent = message;
}

function clearValidation(input) {
    input.classList.remove('is-invalid', 'is-valid');
    const feedback = input.parentElement.querySelector('.invalid-feedback');
    if (feedback) feedback.remove();
}

function validateInputCustom(input) {
    const value = input.value.trim();
    
    // Clear previous validation
    clearValidation(input);
    
    // Apply custom validation if present
    if (input.dataset.validate) {
        const validators = input.dataset.validate.split('|');
        let isValid = true;
        
        validators.forEach(validator => {
            const [rule, param] = validator.split(':');
            if (!applyValidationRule(input, rule, param)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    return true;
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
    if (typeof Chart !== 'undefined') {
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
}

// Toast notifications
function initializeToasts() {
    // Initialize any existing toasts
    const toastElements = document.querySelectorAll('.toast');
    toastElements.forEach(toastEl => {
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    });
}

function showToast(message, type = 'info', duration = 3000) {
    const toastContainer = getToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-${getToastIcon(type)} me-2"></i>${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast, { delay: duration });
    bsToast.show();
    
    // Remove from DOM after hiding
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

function getToastContainer() {
    let container = document.querySelector('#toast-container');
    
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    
    return container;
}

function getToastIcon(type) {
    const iconMap = {
        'success': 'check-circle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle',
        'primary': 'info-circle'
    };
    
    return iconMap[type] || 'info-circle';
}

// Mobile Menu Enhancement
function initializeMobileMenu() {
    const sidebarToggle = document.querySelector('#sidebarToggle, .menu-toggle');
    const sidebar = document.querySelector('#sidebar, .sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !sidebarToggle.contains(e.target) && 
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
    }
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
            if (openModal && typeof bootstrap !== 'undefined') {
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

// Enhanced AJAX with CSRF protection
function makeRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        timeout: window.SMS.ajaxTimeout
    };
    
    const config = { ...defaultOptions, ...options };
    
    // Add CSRF token for POST requests
    if (config.method === 'POST' && window.SMS.csrf_token) {
        if (config.body instanceof FormData) {
            config.body.append('csrf_token', window.SMS.csrf_token);
        } else {
            config.body = (config.body || '') + '&csrf_token=' + window.SMS.csrf_token;
        }
    }
    
    return fetch(url, config)
        .then(async response => {
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            } else {
                return response.text();
            }
        })
        .catch(error => {
            console.error('Request failed:', error);
            showToast('Request failed. Please try again.', 'danger');
            throw error;
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

// Confirmation dialogs with Bootstrap
function confirmAction(message, callback) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>${message}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmBtn">Confirm</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    const bsModal = new bootstrap.Modal(modal);
    
    modal.querySelector('#confirmBtn').addEventListener('click', () => {
        callback();
        bsModal.hide();
    });
    
    modal.addEventListener('hidden.bs.modal', () => {
        modal.remove();
    });
    
    bsModal.show();
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

// Enhanced date formatting
function formatDateEnhanced(date, format = 'DD/MM/YYYY') {
    if (!date) return '';
    
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    
    switch (format) {
        case 'DD/MM/YYYY':
            return `${day}/${month}/${year}`;
        case 'MM/DD/YYYY':
            return `${month}/${day}/${year}`;
        case 'YYYY-MM-DD':
            return `${year}-${month}-${day}`;
        default:
            return d.toLocaleDateString('en-IN');
    }
}

// Enhanced currency formatting
function formatCurrencyEnhanced(amount, currency = '₹') {
    return currency + parseFloat(amount).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Enhanced print functionality
function printSectionEnhanced(elementId, title = 'Print') {
    const element = document.getElementById(elementId);
    if (!element) {
        showToast('Print section not found.', 'danger');
        return;
    }
    
    const printWindow = window.open('', '_blank');
    const schoolInfo = document.querySelector('.sidebar-header h6')?.textContent || 'School Management System';
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
            <head>
                <title>${title} - ${schoolInfo}</title>
                <meta charset="UTF-8">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .no-print { display: none !important; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; }
                    th { background-color: #f8f9fa; }
                    .print-header { text-align: center; margin-bottom: 30px; }
                    .print-date { text-align: right; margin-bottom: 20px; }
                    @media print {
                        body { margin: 0; }
                        .btn, .no-print { display: none !important; }
                    }
                </style>
            </head>
            <body>
                <div class="print-header">
                    <h2>${schoolInfo}</h2>
                    <h4>${title}</h4>
                </div>
                <div class="print-date">
                    <small>Generated on: ${new Date().toLocaleDateString('en-IN')} at ${new Date().toLocaleTimeString('en-IN')}</small>
                </div>
                ${element.innerHTML}
                <div class="print-footer mt-4">
                    <hr>
                    <small class="text-muted">This is a computer-generated document. No signature required.</small>
                </div>
            </body>
        </html>
    `);
    
    printWindow.document.close();
    
    // Wait for content to load, then print
    printWindow.onload = function() {
        printWindow.focus();
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 500);
    };
}

// Export enhanced functions to global scope
Object.assign(window, {
    showToast,
    makeRequest,
    formatDateEnhanced,
    formatCurrencyEnhanced,
    showLoading,
    hideLoading,
    confirmAction,
    serializeForm,
    populateForm,
    printSectionEnhanced,
    debounce
});

// Debug helper
function debug(message, data = null) {
    if (window.SMS.debug) {
        console.log('[SMS Enhanced Debug]', message, data);
    }
}

// Global error handler
window.addEventListener('error', function(e) {
    if (window.SMS.debug) {
        console.error('Global error:', e.error);
    }
});

console.log('Enhanced School Management System JavaScript loaded successfully');
