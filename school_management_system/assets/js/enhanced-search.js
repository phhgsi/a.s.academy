/**
 * Enhanced Search Functionality
 * 
 * Features:
 * - Real-time search with debouncing
 * - Autocomplete suggestions
 * - Advanced filtering
 * - Search history
 * - Keyboard navigation
 * - Mobile-optimized interface
 */

class EnhancedSearch {
    constructor(options = {}) {
        this.searchInput = document.getElementById(options.inputId || 'searchInput');
        this.searchResults = document.getElementById(options.resultsId || 'searchResults');
        this.searchFilters = document.getElementById(options.filtersId || 'searchFilters');
        this.searchType = options.defaultType || 'all';
        this.debounceDelay = options.debounceDelay || 300;
        this.minQueryLength = options.minQueryLength || 2;
        
        this.currentQuery = '';
        this.searchTimeout = null;
        this.currentFocus = -1;
        this.searchHistory = this.loadSearchHistory();
        this.isSearching = false;
        
        this.init();
    }
    
    init() {
        if (!this.searchInput) return;
        
        this.createSearchInterface();
        this.bindEvents();
        this.loadRecentSearches();
    }
    
    createSearchInterface() {
        // Create search container if it doesn't exist
        if (!this.searchResults) {
            this.searchResults = document.createElement('div');
            this.searchResults.id = 'searchResults';
            this.searchResults.className = 'search-results-container';
            this.searchInput.parentNode.appendChild(this.searchResults);
        }
        
        // Add search icon and clear button
        const searchWrapper = this.searchInput.parentNode;
        if (!searchWrapper.querySelector('.search-icon')) {
            searchWrapper.style.position = 'relative';
            
            const searchIcon = document.createElement('i');
            searchIcon.className = 'bi bi-search search-icon';
            searchWrapper.appendChild(searchIcon);
            
            const clearButton = document.createElement('button');
            clearButton.className = 'search-clear-btn';
            clearButton.innerHTML = '<i class="bi bi-x"></i>';
            clearButton.style.display = 'none';
            searchWrapper.appendChild(clearButton);
            
            clearButton.addEventListener('click', () => this.clearSearch());
        }
        
        // Add loading indicator
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'search-loading';
        loadingIndicator.innerHTML = '<i class="bi bi-spinner loading-spin"></i>';
        loadingIndicator.style.display = 'none';
        this.searchInput.parentNode.appendChild(loadingIndicator);
        
        this.loadingIndicator = loadingIndicator;
    }
    
    bindEvents() {
        // Search input events
        this.searchInput.addEventListener('input', (e) => {
            this.handleSearchInput(e.target.value);
        });
        
        this.searchInput.addEventListener('focus', () => {
            this.showSearchInterface();
        });
        
        this.searchInput.addEventListener('blur', (e) => {
            // Delay hiding to allow clicking on results
            setTimeout(() => {
                if (!this.searchResults.contains(document.activeElement)) {
                    this.hideSearchInterface();
                }
            }, 150);
        });
        
        // Keyboard navigation
        this.searchInput.addEventListener('keydown', (e) => {
            this.handleKeyNavigation(e);
        });
        
        // Search type filter if exists
        const typeSelector = document.getElementById('searchType');
        if (typeSelector) {
            typeSelector.addEventListener('change', (e) => {
                this.searchType = e.target.value;
                if (this.currentQuery.length >= this.minQueryLength) {
                    this.performSearch(this.currentQuery);
                }
            });
        }
        
        // Clear button
        const clearButton = document.querySelector('.search-clear-btn');
        if (clearButton) {
            clearButton.addEventListener('click', () => this.clearSearch());
        }
        
        // Click outside to close
        document.addEventListener('click', (e) => {
            if (!this.searchInput.parentNode.contains(e.target)) {
                this.hideSearchInterface();
            }
        });
    }
    
    handleSearchInput(value) {
        this.currentQuery = value.trim();
        
        // Show/hide clear button
        const clearButton = document.querySelector('.search-clear-btn');
        if (clearButton) {
            clearButton.style.display = this.currentQuery ? 'block' : 'none';
        }
        
        // Clear previous timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        if (this.currentQuery.length === 0) {
            this.hideSearchInterface();
            return;
        }
        
        if (this.currentQuery.length < this.minQueryLength) {
            this.showNoResults('Type at least 2 characters to search');
            return;
        }
        
        // Show loading
        this.showLoading();
        
        // Debounced search
        this.searchTimeout = setTimeout(() => {
            this.performSearch(this.currentQuery);
        }, this.debounceDelay);
    }
    
    async performSearch(query) {
        if (this.isSearching) return;
        
        this.isSearching = true;
        this.showLoading();
        
        try {
            // Build search URL with filters
            const filters = this.getActiveFilters();
            const searchParams = new URLSearchParams({
                q: query,
                type: this.searchType,
                ...filters
            });
            
            const response = await fetch(`/includes/search_api.php?${searchParams}`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.error) {
                this.showError(data.error);
            } else {
                this.displayResults(data);
                this.addToSearchHistory(query);
            }
            
        } catch (error) {
            console.error('Search error:', error);
            this.showError('Search failed. Please try again.');
        } finally {
            this.isSearching = false;
            this.hideLoading();
        }
    }
    
    async getSuggestions(query) {
        try {
            const response = await fetch(`/includes/search_api.php?q=${encodeURIComponent(query)}&type=${this.searchType}&suggestions=1`);
            const data = await response.json();
            return data.suggestions || [];
        } catch (error) {
            console.error('Suggestions error:', error);
            return [];
        }
    }
    
    displayResults(data) {
        if (!data.results || data.results.length === 0) {
            this.showNoResults('No results found');
            return;
        }
        
        let html = `
            <div class="search-results-header">
                <div class="search-results-count">
                    ${data.total} result${data.total !== 1 ? 's' : ''} found
                </div>
                ${data.breakdown ? this.renderBreakdown(data.breakdown) : ''}
            </div>
            <div class="search-results-list">
        `;
        
        data.results.forEach((result, index) => {
            html += this.renderSearchResult(result, index);
        });
        
        html += '</div>';
        
        if (data.total > data.results.length) {
            html += `
                <div class="search-results-footer">
                    <button class="btn btn-outline view-all-results" data-query="${this.currentQuery}" data-type="${this.searchType}">
                        View all ${data.total} results
                    </button>
                </div>
            `;
        }
        
        this.searchResults.innerHTML = html;
        this.searchResults.style.display = 'block';
        
        // Bind result click events
        this.bindResultEvents();
    }
    
    renderSearchResult(result, index) {
        const typeIcon = this.getTypeIcon(result.type);
        const typeColor = this.getTypeColor(result.type);
        
        return `
            <div class="search-result-item" data-index="${index}" data-url="${result.url}">
                <div class="search-result-icon" style="background: ${typeColor};">
                    <i class="bi bi-${typeIcon}"></i>
                </div>
                <div class="search-result-content">
                    <div class="search-result-title">${this.highlightQuery(result.title)}</div>
                    <div class="search-result-subtitle">${this.highlightQuery(result.subtitle)}</div>
                    <div class="search-result-meta">
                        <span class="result-type">${result.type.replace('_', ' ')}</span>
                        ${result.meta && result.meta.academic_year ? `<span class="result-year">${result.meta.academic_year}</span>` : ''}
                    </div>
                </div>
                ${result.image ? `<div class="search-result-image"><img src="${result.image}" alt=""></div>` : ''}
                <div class="search-result-action">
                    <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        `;
    }
    
    renderBreakdown(breakdown) {
        let html = '<div class="search-breakdown">';
        
        Object.entries(breakdown).forEach(([type, count]) => {
            if (count > 0) {
                html += `
                    <span class="breakdown-item" data-type="${type}">
                        ${type}: ${count}
                    </span>
                `;
            }
        });
        
        html += '</div>';
        return html;
    }
    
    highlightQuery(text) {
        if (!this.currentQuery || !text) return text;
        
        const regex = new RegExp(`(${this.escapeRegex(this.currentQuery)})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }
    
    escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    
    getTypeIcon(type) {
        const icons = {
            'student': 'person-fill',
            'teacher': 'person-workspace',
            'fee_payment': 'receipt',
            'attendance': 'calendar-check',
            'event': 'calendar-event',
            'book': 'book',
            'assignment': 'clipboard'
        };
        return icons[type] || 'search';
    }
    
    getTypeColor(type) {
        const colors = {
            'student': '#3b82f6',
            'teacher': '#10b981',
            'fee_payment': '#f59e0b',
            'attendance': '#8b5cf6',
            'event': '#ef4444',
            'book': '#06b6d4',
            'assignment': '#84cc16'
        };
        return colors[type] || '#6b7280';
    }
    
    bindResultEvents() {
        const resultItems = this.searchResults.querySelectorAll('.search-result-item');
        
        resultItems.forEach((item, index) => {
            item.addEventListener('click', () => {
                const url = item.dataset.url;
                if (url) {
                    window.location.href = url;
                }
            });
            
            item.addEventListener('mouseenter', () => {
                this.setFocusedResult(index);
            });
        });
        
        // View all results button
        const viewAllBtn = this.searchResults.querySelector('.view-all-results');
        if (viewAllBtn) {
            viewAllBtn.addEventListener('click', () => {
                const query = viewAllBtn.dataset.query;
                const type = viewAllBtn.dataset.type;
                window.location.href = `/admin/search.php?q=${encodeURIComponent(query)}&type=${type}`;
            });
        }
        
        // Breakdown filter buttons
        const breakdownItems = this.searchResults.querySelectorAll('.breakdown-item');
        breakdownItems.forEach(item => {
            item.addEventListener('click', () => {
                const type = item.dataset.type;
                this.searchType = type;
                this.performSearch(this.currentQuery);
            });
        });
    }
    
    handleKeyNavigation(e) {
        const resultItems = this.searchResults.querySelectorAll('.search-result-item');
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.currentFocus = Math.min(this.currentFocus + 1, resultItems.length - 1);
                this.setFocusedResult(this.currentFocus);
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                this.currentFocus = Math.max(this.currentFocus - 1, -1);
                this.setFocusedResult(this.currentFocus);
                break;
                
            case 'Enter':
                e.preventDefault();
                if (this.currentFocus >= 0 && resultItems[this.currentFocus]) {
                    resultItems[this.currentFocus].click();
                }
                break;
                
            case 'Escape':
                this.hideSearchInterface();
                this.searchInput.blur();
                break;
        }
    }
    
    setFocusedResult(index) {
        const resultItems = this.searchResults.querySelectorAll('.search-result-item');
        
        // Remove previous focus
        resultItems.forEach(item => item.classList.remove('focused'));
        
        // Set new focus
        if (index >= 0 && index < resultItems.length) {
            resultItems[index].classList.add('focused');
            this.currentFocus = index;
        } else {
            this.currentFocus = -1;
        }
    }
    
    showSearchInterface() {
        this.searchResults.style.display = 'block';
        
        if (this.currentQuery.length === 0) {
            this.showRecentSearches();
        }
    }
    
    hideSearchInterface() {
        this.searchResults.style.display = 'none';
        this.currentFocus = -1;
    }
    
    showLoading() {
        if (this.loadingIndicator) {
            this.loadingIndicator.style.display = 'block';
        }
        
        this.searchResults.innerHTML = `
            <div class="search-loading-state">
                <i class="bi bi-search loading-pulse"></i>
                <span>Searching...</span>
            </div>
        `;
        this.searchResults.style.display = 'block';
    }
    
    hideLoading() {
        if (this.loadingIndicator) {
            this.loadingIndicator.style.display = 'none';
        }
    }
    
    showNoResults(message = 'No results found') {
        this.searchResults.innerHTML = `
            <div class="search-no-results">
                <i class="bi bi-inbox"></i>
                <span>${message}</span>
                ${this.currentQuery.length >= this.minQueryLength ? '<button class="btn btn-sm btn-outline" onclick="enhancedSearch.showAdvancedSearch()">Try advanced search</button>' : ''}
            </div>
        `;
        this.searchResults.style.display = 'block';
    }
    
    showError(message) {
        this.searchResults.innerHTML = `
            <div class="search-error">
                <i class="bi bi-exclamation-triangle"></i>
                <span>${message}</span>
                <button class="btn btn-sm btn-outline" onclick="enhancedSearch.performSearch('${this.currentQuery}')">Retry</button>
            </div>
        `;
        this.searchResults.style.display = 'block';
    }
    
    showRecentSearches() {
        if (this.searchHistory.length === 0) {
            this.searchResults.innerHTML = `
                <div class="search-suggestions">
                    <div class="suggestions-header">Recent searches</div>
                    <div class="no-recent-searches">No recent searches</div>
                </div>
            `;
        } else {
            let html = `
                <div class="search-suggestions">
                    <div class="suggestions-header">
                        Recent searches
                        <button class="clear-history-btn" onclick="enhancedSearch.clearSearchHistory()">Clear</button>
                    </div>
                    <div class="suggestions-list">
            `;
            
            this.searchHistory.slice(0, 5).forEach(search => {
                html += `
                    <div class="suggestion-item" data-query="${search.query}" data-type="${search.type}">
                        <i class="bi bi-clock-history"></i>
                        <span class="suggestion-text">${search.query}</span>
                        <span class="suggestion-type">${search.type}</span>
                    </div>
                `;
            });
            
            html += '</div></div>';
            this.searchResults.innerHTML = html;
        }
        
        this.searchResults.style.display = 'block';
        this.bindSuggestionEvents();
    }
    
    bindSuggestionEvents() {
        const suggestionItems = this.searchResults.querySelectorAll('.suggestion-item');
        suggestionItems.forEach(item => {
            item.addEventListener('click', () => {
                const query = item.dataset.query;
                const type = item.dataset.type;
                
                this.searchInput.value = query;
                this.searchType = type;
                this.performSearch(query);
            });
        });
    }
    
    getActiveFilters() {
        const filters = {};
        
        // Get filters from form if exists
        if (this.searchFilters) {
            const filterInputs = this.searchFilters.querySelectorAll('input, select');
            filterInputs.forEach(input => {
                if (input.value && input.name) {
                    filters[input.name] = input.value;
                }
            });
        }
        
        return filters;
    }
    
    addToSearchHistory(query) {
        // Remove duplicate if exists
        this.searchHistory = this.searchHistory.filter(item => 
            !(item.query === query && item.type === this.searchType)
        );
        
        // Add to beginning
        this.searchHistory.unshift({
            query: query,
            type: this.searchType,
            timestamp: Date.now()
        });
        
        // Keep only last 20 searches
        this.searchHistory = this.searchHistory.slice(0, 20);
        
        // Save to localStorage
        this.saveSearchHistory();
    }
    
    loadSearchHistory() {
        try {
            const history = localStorage.getItem('schoolSearchHistory');
            return history ? JSON.parse(history) : [];
        } catch (error) {
            return [];
        }
    }
    
    saveSearchHistory() {
        try {
            localStorage.setItem('schoolSearchHistory', JSON.stringify(this.searchHistory));
        } catch (error) {
            console.error('Failed to save search history:', error);
        }
    }
    
    clearSearchHistory() {
        this.searchHistory = [];
        this.saveSearchHistory();
        if (this.currentQuery.length === 0) {
            this.showRecentSearches();
        }
    }
    
    clearSearch() {
        this.searchInput.value = '';
        this.currentQuery = '';
        this.hideSearchInterface();
        
        const clearButton = document.querySelector('.search-clear-btn');
        if (clearButton) {
            clearButton.style.display = 'none';
        }
    }
    
    loadRecentSearches() {
        // Clean old searches (older than 30 days)
        const thirtyDaysAgo = Date.now() - (30 * 24 * 60 * 60 * 1000);
        this.searchHistory = this.searchHistory.filter(item => item.timestamp > thirtyDaysAgo);
        this.saveSearchHistory();
    }
    
    showAdvancedSearch() {
        // Create advanced search modal or redirect to advanced search page
        window.location.href = '/admin/advanced_search.php';
    }
}

// Search widget for header
class HeaderSearch extends EnhancedSearch {
    constructor() {
        super({
            inputId: 'headerSearchInput',
            resultsId: 'headerSearchResults',
            defaultType: 'all',
            debounceDelay: 250
        });
    }
    
    createSearchInterface() {
        super.createSearchInterface();
        
        // Add specific styling for header search
        this.searchResults.classList.add('header-search-results');
    }
    
    displayResults(data) {
        super.displayResults(data);
        
        // Position results below search input
        const inputRect = this.searchInput.getBoundingClientRect();
        this.searchResults.style.top = `${inputRect.bottom + window.scrollY + 5}px`;
        this.searchResults.style.left = `${inputRect.left + window.scrollX}px`;
        this.searchResults.style.width = `${Math.max(inputRect.width, 400)}px`;
    }
}

// Quick search functionality for specific pages
class QuickSearch {
    constructor(tableId, searchInputId, columns = []) {
        this.table = document.getElementById(tableId);
        this.searchInput = document.getElementById(searchInputId);
        this.searchColumns = columns;
        this.originalRows = null;
        
        if (this.table && this.searchInput) {
            this.init();
        }
    }
    
    init() {
        // Store original table rows
        this.originalRows = Array.from(this.table.querySelectorAll('tbody tr'));
        
        // Bind search input
        this.searchInput.addEventListener('input', (e) => {
            this.filterTable(e.target.value);
        });
        
        // Add clear button
        const clearBtn = document.createElement('button');
        clearBtn.className = 'quick-search-clear';
        clearBtn.innerHTML = '<i class="bi bi-x"></i>';
        clearBtn.addEventListener('click', () => {
            this.searchInput.value = '';
            this.filterTable('');
        });
        
        this.searchInput.parentNode.appendChild(clearBtn);
    }
    
    filterTable(query) {
        const tbody = this.table.querySelector('tbody');
        
        if (!query.trim()) {
            // Show all rows
            tbody.innerHTML = '';
            this.originalRows.forEach(row => tbody.appendChild(row));
            return;
        }
        
        const filteredRows = this.originalRows.filter(row => {
            const cells = Array.from(row.cells);
            
            if (this.searchColumns.length > 0) {
                // Search only specified columns
                return this.searchColumns.some(columnIndex => {
                    if (cells[columnIndex]) {
                        return cells[columnIndex].textContent.toLowerCase().includes(query.toLowerCase());
                    }
                    return false;
                });
            } else {
                // Search all columns
                return cells.some(cell => 
                    cell.textContent.toLowerCase().includes(query.toLowerCase())
                );
            }
        });
        
        // Update table
        tbody.innerHTML = '';
        filteredRows.forEach(row => tbody.appendChild(row));
        
        // Show no results message if needed
        if (filteredRows.length === 0) {
            const noResultsRow = document.createElement('tr');
            noResultsRow.innerHTML = `
                <td colspan="${this.originalRows[0]?.cells.length || 1}" class="text-center text-muted py-4">
                    <i class="bi bi-search" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                    No results found for "${query}"
                </td>
            `;
            tbody.appendChild(noResultsRow);
        }
    }
}

// Auto-complete functionality
class SearchAutoComplete {
    constructor(inputElement, options = {}) {
        this.input = inputElement;
        this.apiUrl = options.apiUrl || '/includes/search_api.php';
        this.minLength = options.minLength || 2;
        this.delay = options.delay || 300;
        this.maxSuggestions = options.maxSuggestions || 5;
        
        this.suggestions = [];
        this.currentIndex = -1;
        this.timeout = null;
        
        this.init();
    }
    
    init() {
        // Create suggestions container
        this.suggestionsContainer = document.createElement('div');
        this.suggestionsContainer.className = 'autocomplete-suggestions';
        this.input.parentNode.appendChild(this.suggestionsContainer);
        
        // Bind events
        this.input.addEventListener('input', (e) => this.handleInput(e));
        this.input.addEventListener('keydown', (e) => this.handleKeydown(e));
        this.input.addEventListener('blur', () => this.hideSuggestions());
    }
    
    async handleInput(e) {
        const value = e.target.value.trim();
        
        if (this.timeout) {
            clearTimeout(this.timeout);
        }
        
        if (value.length < this.minLength) {
            this.hideSuggestions();
            return;
        }
        
        this.timeout = setTimeout(async () => {
            await this.fetchSuggestions(value);
        }, this.delay);
    }
    
    async fetchSuggestions(query) {
        try {
            const response = await fetch(`${this.apiUrl}?q=${encodeURIComponent(query)}&suggestions=1`);
            const data = await response.json();
            
            this.suggestions = data.suggestions || [];
            this.showSuggestions();
            
        } catch (error) {
            console.error('Autocomplete error:', error);
            this.hideSuggestions();
        }
    }
    
    showSuggestions() {
        if (this.suggestions.length === 0) {
            this.hideSuggestions();
            return;
        }
        
        let html = '';
        this.suggestions.slice(0, this.maxSuggestions).forEach((suggestion, index) => {
            html += `
                <div class="autocomplete-suggestion" data-index="${index}" data-value="${suggestion.suggestion}">
                    <i class="bi bi-${this.getTypeIcon(suggestion.type)}"></i>
                    <span class="suggestion-text">${suggestion.suggestion}</span>
                    <span class="suggestion-type">${suggestion.type}</span>
                </div>
            `;
        });
        
        this.suggestionsContainer.innerHTML = html;
        this.suggestionsContainer.style.display = 'block';
        
        // Bind click events
        this.suggestionsContainer.querySelectorAll('.autocomplete-suggestion').forEach(item => {
            item.addEventListener('mousedown', (e) => {
                e.preventDefault(); // Prevent blur
                this.selectSuggestion(item.dataset.value);
            });
        });
    }
    
    hideSuggestions() {
        setTimeout(() => {
            this.suggestionsContainer.style.display = 'none';
            this.currentIndex = -1;
        }, 150);
    }
    
    handleKeydown(e) {
        const suggestions = this.suggestionsContainer.querySelectorAll('.autocomplete-suggestion');
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.currentIndex = Math.min(this.currentIndex + 1, suggestions.length - 1);
                this.highlightSuggestion();
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                this.currentIndex = Math.max(this.currentIndex - 1, -1);
                this.highlightSuggestion();
                break;
                
            case 'Enter':
                if (this.currentIndex >= 0 && suggestions[this.currentIndex]) {
                    e.preventDefault();
                    this.selectSuggestion(suggestions[this.currentIndex].dataset.value);
                }
                break;
                
            case 'Escape':
                this.hideSuggestions();
                break;
        }
    }
    
    highlightSuggestion() {
        const suggestions = this.suggestionsContainer.querySelectorAll('.autocomplete-suggestion');
        
        suggestions.forEach((item, index) => {
            if (index === this.currentIndex) {
                item.classList.add('highlighted');
            } else {
                item.classList.remove('highlighted');
            }
        });
    }
    
    selectSuggestion(value) {
        this.input.value = value;
        this.hideSuggestions();
        
        // Trigger search if parent search system exists
        if (window.enhancedSearch) {
            window.enhancedSearch.performSearch(value);
        }
    }
    
    getTypeIcon(type) {
        const icons = {
            'student': 'person-fill',
            'teacher': 'person-workspace',
            'fee_payment': 'receipt',
            'event': 'calendar-event'
        };
        return icons[type] || 'search';
    }
}

// Global search initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize main search
    const mainSearchInput = document.getElementById('searchInput');
    if (mainSearchInput) {
        window.enhancedSearch = new EnhancedSearch();
    }
    
    // Initialize header search
    const headerSearchInput = document.getElementById('headerSearchInput');
    if (headerSearchInput) {
        window.headerSearch = new HeaderSearch();
    }
    
    // Initialize autocomplete for search inputs
    document.querySelectorAll('.search-autocomplete').forEach(input => {
        new SearchAutoComplete(input);
    });
    
    // Initialize quick search for tables
    document.querySelectorAll('[data-quick-search]').forEach(table => {
        const searchInputId = table.dataset.quickSearch;
        const searchColumns = table.dataset.searchColumns ? 
            table.dataset.searchColumns.split(',').map(Number) : [];
        
        new QuickSearch(table.id, searchInputId, searchColumns);
    });
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { EnhancedSearch, HeaderSearch, QuickSearch, SearchAutoComplete };
}
