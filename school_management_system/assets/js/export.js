/**
 * Export Functionality for School Management System
 * 
 * Handles CSV and PDF export functionality with:
 * - Loading states and user feedback
 * - Error handling and notifications
 * - Dynamic export URLs based on current page
 * - Filter parameter preservation
 */

class ExportManager {
    constructor() {
        this.loadingOverlay = null;
        this.exportButtons = [];
        this.currentPage = this.getCurrentPageType();
        this.init();
    }
    
    init() {
        this.createLoadingOverlay();
        this.setupExportButtons();
        this.bindEvents();
    }
    
    /**
     * Determine current page type for export endpoint routing
     */
    getCurrentPageType() {
        const pathname = window.location.pathname;
        const filename = pathname.split('/').pop().replace('.php', '');
        
        // Map page names to export endpoints
        const pageMap = {
            'students': 'students_export.php',
            'fees': 'fees_export.php', 
            'attendance': 'attendance_export.php',
            'reports': 'reports_export.php',
            'teachers': 'teachers_export.php',
            'classes': 'classes_export.php'
        };
        
        return pageMap[filename] || null;
    }
    
    /**
     * Create loading overlay element
     */
    createLoadingOverlay() {
        if (document.querySelector('.export-loading-overlay')) return;
        
        this.loadingOverlay = document.createElement('div');
        this.loadingOverlay.className = 'export-loading-overlay';
        this.loadingOverlay.innerHTML = `
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <p class="loading-text">Preparing export...</p>
                <button class="btn btn-secondary btn-sm cancel-export">Cancel</button>
            </div>
        `;
        
        this.loadingOverlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(4px);
        `;
        
        const loadingContent = this.loadingOverlay.querySelector('.loading-content');
        loadingContent.style.cssText = `
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            max-width: 300px;
        `;
        
        const spinner = this.loadingOverlay.querySelector('.loading-spinner');
        spinner.style.cssText = `
            width: 40px;
            height: 40px;
            border: 3px solid #e5e7eb;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem auto;
        `;
        
        // Add spinner animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
        
        document.body.appendChild(this.loadingOverlay);
        
        // Cancel button functionality
        this.loadingOverlay.querySelector('.cancel-export').addEventListener('click', () => {
            this.hideLoading();
        });
    }
    
    /**
     * Setup export buttons on the page
     */
    setupExportButtons() {
        // Look for existing export buttons
        this.exportButtons = document.querySelectorAll('.export-btn');
        
        // If no export buttons exist, try to add them automatically
        if (this.exportButtons.length === 0 && this.currentPage) {
            this.addExportButtons();
        }
    }
    
    /**
     * Add export buttons to page automatically
     */
    addExportButtons() {
        const pageHeader = document.querySelector('.page-header');
        if (!pageHeader) return;
        
        // Check if export buttons already exist
        if (pageHeader.querySelector('.export-btn-group')) return;
        
        const exportGroup = document.createElement('div');
        exportGroup.className = 'export-btn-group';
        exportGroup.style.cssText = `
            display: flex;
            gap: 0.5rem;
            margin-left: auto;
        `;
        
        exportGroup.innerHTML = `
            <button class="btn btn-outline export-btn" data-format="csv" title="Export to CSV">
                <i class="bi bi-file-earmark-spreadsheet"></i> CSV
            </button>
            <button class="btn btn-outline export-btn" data-format="pdf" title="Export to PDF">
                <i class="bi bi-file-earmark-pdf"></i> PDF
            </button>
        `;
        
        // Try to find a good place to insert the buttons
        const flexContainer = pageHeader.querySelector('.d-flex');
        
        if (flexContainer) {
            flexContainer.appendChild(exportGroup);
        } else {
            // Create a flex container if none exists
            const newFlexContainer = document.createElement('div');
            newFlexContainer.className = 'd-flex justify-between align-center';
            newFlexContainer.style.cssText = 'display: flex; justify-content: space-between; align-items: center;';
            
            // Move existing content to flex container
            const existingContent = document.createElement('div');
            while (pageHeader.firstChild) {
                existingContent.appendChild(pageHeader.firstChild);
            }
            
            newFlexContainer.appendChild(existingContent);
            newFlexContainer.appendChild(exportGroup);
            pageHeader.appendChild(newFlexContainer);
        }
        
        // Update export buttons reference
        this.exportButtons = document.querySelectorAll('.export-btn');
    }
    
    /**
     * Bind event listeners
     */
    bindEvents() {
        // Handle export button clicks
        document.addEventListener('click', (e) => {
            if (e.target.closest('.export-btn')) {
                e.preventDefault();
                const button = e.target.closest('.export-btn');
                const format = button.dataset.format;
                this.handleExport(format);
            }
        });
        
        // Handle keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl+E for CSV export
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                this.handleExport('csv');
            }
            
            // Ctrl+P for PDF export (override default print)
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                this.handleExport('pdf');
            }
        });
    }
    
    /**
     * Handle export process
     */
    async handleExport(format) {
        if (!this.currentPage) {
            this.showError('Export not available for this page');
            return;
        }
        
        if (!format || !['csv', 'pdf'].includes(format)) {
            this.showError('Invalid export format');
            return;
        }
        
        try {
            this.showLoading(`Preparing ${format.toUpperCase()} export...`);
            this.disableExportButtons();
            
            // Build export URL with current filters
            const exportUrl = this.buildExportUrl(format);
            
            // Use window.open for file download
            const exportWindow = window.open(exportUrl, '_blank');
            
            // Check if popup was blocked
            if (!exportWindow) {
                throw new Error('Popup blocked. Please allow popups for file downloads.');
            }
            
            // Hide loading after a short delay
            setTimeout(() => {
                this.hideLoading();
                this.enableExportButtons();
                this.showSuccess(`${format.toUpperCase()} export completed!`);
            }, 2000);
            
        } catch (error) {
            this.hideLoading();
            this.enableExportButtons();
            this.showError('Export failed: ' + error.message);
        }
    }
    
    /**
     * Build export URL with filters
     */
    buildExportUrl(format) {
        const baseUrl = this.currentPage;
        const urlParams = new URLSearchParams();
        
        // Add format
        urlParams.set('format', format);
        
        // Preserve current filters from URL
        const currentParams = new URLSearchParams(window.location.search);
        const preserveParams = ['class_id', 'academic_year', 'status', 'search', 'date_from', 'date_to'];
        
        preserveParams.forEach(param => {
            if (currentParams.has(param)) {
                urlParams.set(param, currentParams.get(param));
            }
        });
        
        // Preserve form filters if they exist
        const classFilter = document.querySelector('#class_filter, select[name="class_id"]');
        if (classFilter && classFilter.value) {
            urlParams.set('class_id', classFilter.value);
        }
        
        const academicYearFilter = document.querySelector('#academic_year_filter, select[name="academic_year"]');
        if (academicYearFilter && academicYearFilter.value) {
            urlParams.set('academic_year', academicYearFilter.value);
        }
        
        return baseUrl + '?' + urlParams.toString();
    }
    
    /**
     * Show loading overlay
     */
    showLoading(message = 'Preparing export...') {
        if (this.loadingOverlay) {
            this.loadingOverlay.querySelector('.loading-text').textContent = message;
            this.loadingOverlay.style.display = 'flex';
        }
    }
    
    /**
     * Hide loading overlay
     */
    hideLoading() {
        if (this.loadingOverlay) {
            this.loadingOverlay.style.display = 'none';
        }
    }
    
    /**
     * Show success message
     */
    showSuccess(message) {
        this.showNotification(message, 'success');
    }
    
    /**
     * Show error message
     */
    showError(message) {
        this.showNotification(message, 'error');
    }
    
    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.export-notification');
        existingNotifications.forEach(n => n.remove());
        
        const notification = document.createElement('div');
        notification.className = `export-notification alert alert-${type === 'success' ? 'success' : 'danger'}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            min-width: 300px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            animation: slideInRight 0.3s ease-out;
        `;
        
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                <span>${message}</span>
                <button type="button" class="btn-close" style="margin-left: auto; background: none; border: none; font-size: 1.2rem; cursor: pointer;">&times;</button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
        
        // Manual close button
        notification.querySelector('.btn-close').addEventListener('click', () => {
            notification.remove();
        });
        
        // Add slide animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    }
    
    /**
     * Enable export buttons
     */
    enableExportButtons() {
        this.exportButtons.forEach(btn => {
            btn.disabled = false;
            btn.classList.remove('loading');
        });
    }
    
    /**
     * Disable export buttons
     */
    disableExportButtons() {
        this.exportButtons.forEach(btn => {
            btn.disabled = true;
            btn.classList.add('loading');
        });
    }
}

// Initialize export manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize on pages that support export
    const supportedPages = ['students', 'fees', 'attendance', 'reports', 'teachers', 'classes'];
    const currentPage = window.location.pathname.split('/').pop().replace('.php', '');
    
    if (supportedPages.includes(currentPage)) {
        window.exportManager = new ExportManager();
        console.log('📊 Export functionality initialized for', currentPage);
    }
});

// Global export functions for backward compatibility
window.exportData = function(format) {
    if (window.exportManager) {
        window.exportManager.handleExport(format);
    }
};

window.exportCSV = function() {
    window.exportData('csv');
};

window.exportPDF = function() {
    window.exportData('pdf');
};
