<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/academic_year.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Determine the correct path for login based on current location
    $login_path = '../login.php';
    if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
        $login_path = '../login.php';
    } else {
        $login_path = 'login.php';
    }
    header('Location: ' . $login_path);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

// Get school information
try {
    $stmt = $pdo->prepare("SELECT * FROM school_info WHERE id = 1");
    $stmt->execute();
    $school_info = $stmt->fetch();
} catch (Exception $e) {
    $school_info = null;
}
?>

<header class="main-header">
    <div class="header-container">
        <div class="header-left">
            <!-- Mobile Hamburger Menu -->
            <button class="hamburger-header" id="mobileMenuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <!-- Search Bar -->
            <div class="search-container">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="search-input" id="globalSearch" placeholder="Search students, teachers, classes...">
                <div class="search-results" id="searchResults" style="display: none;"></div>
            </div>
        </div>
        
        <div class="header-right">
            <!-- Messages -->
            <div class="notification-menu">
                <a href="<?php echo $user_role === 'admin' ? 'messages.php' : '../admin/messages.php'; ?>" class="notification-btn" title="Messages">
                    <i class="bi bi-chat-dots"></i>
                    <span class="notification-badge" id="messageCount" style="display: none;">0</span>
                </a>
            </div>
            
            <!-- Notifications -->
            <div class="notification-menu">
                <button class="notification-btn" onclick="toggleNotifications()" title="Notifications">
                    <i class="bi bi-bell"></i>
                    <span class="notification-badge" id="notificationCount" style="display: none;">0</span>
                </button>
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h6>Notifications</h6>
                        <button class="btn-link" onclick="markAllNotificationsRead()">Mark all read</button>
                    </div>
                    <div class="notification-list" id="notificationList">
                        <div class="text-center p-3">
                            <small class="text-muted">No new notifications</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- User Menu -->
            <div class="user-menu">
                <div class="user-profile" onclick="toggleUserMenu()">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                        <div class="user-role"><?php echo ucfirst($user_role); ?></div>
                    </div>
                    <i class="bi bi-chevron-down"></i>
                </div>
                <div class="user-dropdown" id="userDropdown">
                    <a href="profile.php"><i class="bi bi-person"></i>My Profile</a>
                    <a href="settings.php"><i class="bi bi-gear"></i>Settings</a>
                    <div class="dropdown-divider"></div>
                    <a href="#" onclick="logout()" class="text-danger"><i class="bi bi-box-arrow-right"></i>Logout</a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
}

function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    
    // Load notifications if opening
    if (dropdown.style.display === 'block') {
        loadNotifications();
    }
}

function loadNotifications() {
    // Determine correct path based on current location
    const basePath = window.location.pathname.includes('/admin/') ? '../includes/' : 'includes/';
    fetch(basePath + 'get_notifications.php')
        .then(response => response.json())
        .then(data => {
            const notificationList = document.getElementById('notificationList');
            const notificationCount = document.getElementById('notificationCount');
            
            if (data.notifications && data.notifications.length > 0) {
                notificationCount.textContent = data.unread_count;
                notificationCount.style.display = data.unread_count > 0 ? 'inline' : 'none';
                
                notificationList.innerHTML = '';
                data.notifications.forEach(notification => {
                    const item = document.createElement('div');
                    item.className = 'notification-item';
                    item.innerHTML = `
                        <div class="notification-item-content">
                            <div class="notification-main">
                                <div class="notification-title">${notification.title}</div>
                                <div class="notification-message">${notification.message}</div>
                                <div class="notification-time">${notification.created_at}</div>
                            </div>
                            ${!notification.is_read ? '<span class="notification-new-badge">New</span>' : ''}
                        </div>
                    `;
                    notificationList.appendChild(item);
                });
            } else {
                notificationCount.style.display = 'none';
                notificationList.innerHTML = '<div class="text-center p-3"><small class="text-muted">No new notifications</small></div>';
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
        });
}

function markAllNotificationsRead() {
    // Determine correct path based on current location
    const basePath = window.location.pathname.includes('/admin/') ? '../includes/' : 'includes/';
    fetch(basePath + 'mark_notifications_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'mark_all=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications(); // Refresh notifications
        }
    })
    .catch(error => {
        console.error('Error marking notifications as read:', error);
    });
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../logout.php';
    }
}

function showLoadingSpinner() {
    // Show loading indicator for academic year change
    const selector = document.querySelector('.academic-year-select');
    if (selector) {
        selector.disabled = true;
        selector.style.opacity = '0.6';
    }
}


// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.user-menu')) {
        document.getElementById('userDropdown').style.display = 'none';
    }
    if (!e.target.closest('.notification-menu')) {
        document.getElementById('notificationDropdown').style.display = 'none';
    }
});

// Initialize header functionality
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu functionality is handled by sidebar.js
    // Just setup header-specific functionality here
    
    // Setup search functionality
    setupGlobalSearch();
    
    // Load notification count
    const basePath = window.location.pathname.includes('/admin/') ? '../includes/' : 'includes/';
    fetch(basePath + 'get_notifications.php?count_only=1')
        .then(response => response.json())
        .then(data => {
            const notificationCount = document.getElementById('notificationCount');
            if (data.unread_count > 0) {
                notificationCount.textContent = data.unread_count;
                notificationCount.style.display = 'inline';
            }
        })
        .catch(error => {
            console.error('Error loading notification count:', error);
        });
        
    // Load message count
    const messagePath = window.location.pathname.includes('/admin/') ? 'messages-api.php' : 'admin/messages-api.php';
    fetch(messagePath + '?action=unread_count')
        .then(response => response.json())
        .then(data => {
            const messageCount = document.getElementById('messageCount');
            if (data.count > 0) {
                messageCount.textContent = data.count;
                messageCount.style.display = 'inline';
            }
        })
        .catch(error => {
            console.error('Error loading message count:', error);
        });
});

// Global search functionality
function setupGlobalSearch() {
    const searchInput = document.getElementById('globalSearch');
    const searchResults = document.getElementById('searchResults');
    let searchTimeout;
    
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();
        
        if (query.length < 2) {
            hideSearchResults();
            return;
        }
        
        searchTimeout = setTimeout(() => {
            performGlobalSearch(query);
        }, 300);
    });
    
    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-container')) {
            hideSearchResults();
        }
    });
    
    // Handle Enter key
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = e.target.value.trim();
            if (query) {
                // Redirect to search results page
                window.location.href = 'students.php?search=' + encodeURIComponent(query);
            }
        }
    });
}

function performGlobalSearch(query) {
    const basePath = window.location.pathname.includes('/admin/') ? '../includes/' : 'includes/';
    
    fetch(basePath + 'search.php?q=' + encodeURIComponent(query) + '&limit=8')
        .then(response => response.json())
        .then(data => {
            displaySearchResults(data.results || []);
        })
        .catch(error => {
            console.error('Search error:', error);
            hideSearchResults();
        });
}

function displaySearchResults(results) {
    const searchResults = document.getElementById('searchResults');
    if (!searchResults) return;
    
    if (results.length === 0) {
        searchResults.innerHTML = '<div class="search-no-results">No results found</div>';
    } else {
        const resultsHTML = results.map(result => `
            <a href="${result.url}" class="search-result-item">
                <i class="bi bi-${result.icon}"></i>
                <div class="search-result-content">
                    <div class="search-result-title">${result.title}</div>
                    <div class="search-result-subtitle">${result.subtitle}</div>
                </div>
            </a>
        `).join('');
        
        searchResults.innerHTML = resultsHTML;
    }
    
    searchResults.style.display = 'block';
}

function hideSearchResults() {
    const searchResults = document.getElementById('searchResults');
    if (searchResults) {
        searchResults.style.display = 'none';
    }
}

// Auto-refresh message count every 30 seconds
setInterval(() => {
    const messagePath = window.location.pathname.includes('/admin/') ? 'messages-api.php' : 'admin/messages-api.php';
    fetch(messagePath + '?action=unread_count')
        .then(response => response.json())
        .then(data => {
            const messageCount = document.getElementById('messageCount');
            if (data.count > 0) {
                messageCount.textContent = data.count;
                messageCount.style.display = 'inline';
            } else {
                messageCount.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error refreshing message count:', error);
        });
}, 30000);
</script>

<style>
/* Main Header */
.main-header {
    position: fixed;
    top: 0;
    left: 280px;
    right: 0;
    height: var(--header-height);
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    z-index: 1050;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.header-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 100%;
    padding: 0 1.5rem;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex: 1;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* Header Brand */
.header-brand {
    display: flex;
    flex-direction: column;
    line-height: 1;
}

.brand-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary-color);
    margin: 0;
    background: linear-gradient(135deg, var(--primary-color), #1d4ed8);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.brand-subtitle {
    font-size: 0.7rem;
    color: var(--text-secondary);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 0.1rem;
}

/* Hamburger styles are now handled in modern-ui.css */

/* Search Container */
.search-container {
    position: relative;
    width: 350px;
}

.search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.8rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    background: #f9fafb;
    transition: all 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: var(--primary-color);
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    font-size: 1rem;
}

/* Search Results Dropdown */
.search-results {
    position: absolute;
    top: calc(100% + 0.5rem);
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    max-height: 400px;
    overflow-y: auto;
    z-index: 1100;
}

.search-result-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1.25rem;
    color: #374151;
    text-decoration: none;
    border-bottom: 1px solid #f3f4f6;
    transition: all 0.2s ease;
}

.search-result-item:hover {
    background: #f9fafb;
    color: #111827;
}

.search-result-item:last-child {
    border-bottom: none;
    border-radius: 0 0 0.75rem 0.75rem;
}

.search-result-item i {
    color: var(--primary-color);
    font-size: 1rem;
    min-width: 20px;
}

.search-result-content {
    flex: 1;
}

.search-result-title {
    font-weight: 600;
    color: #111827;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.search-result-subtitle {
    color: #6b7280;
    font-size: 0.75rem;
}

.search-no-results {
    padding: 1rem 1.25rem;
    text-align: center;
    color: #9ca3af;
    font-size: 0.875rem;
}

/* Academic Year Container */
.academic-year-container {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.academic-year-selector {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #f8fafc;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
}

.academic-year-selector label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    white-space: nowrap;
    margin: 0;
}

.academic-year-selector select {
    padding: 0.375rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    min-width: 130px;
    background: #ffffff;
    transition: all 0.2s ease;
}

.academic-year-selector select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Notification Menu */
.notification-menu {
    position: relative;
}

.notification-btn {
    position: relative;
    padding: 0;
    background: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    color: #4b5563;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
}

.notification-btn:hover {
    background: #e5e7eb;
    color: var(--primary-color);
}

.notification-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #ef4444;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}

.notification-dropdown {
    position: absolute;
    top: calc(100% + 0.5rem);
    right: 0;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    width: 360px;
    z-index: 1000;
    display: none;
}

.notification-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 0.75rem 0.75rem 0 0;
}

.notification-header h6 {
    color: #111827;
    font-weight: 600;
    margin: 0;
}

.btn-link {
    background: none;
    border: none;
    color: var(--primary-color);
    font-size: 0.875rem;
    cursor: pointer;
    text-decoration: none;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    transition: background 0.2s;
}

.btn-link:hover {
    background: rgba(59, 130, 246, 0.1);
}

.notification-list {
    max-height: 320px;
    overflow-y: auto;
}

.notification-item {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.2s;
}

.notification-item:hover {
    background: #f9fafb;
}

.notification-item:last-child {
    border-bottom: none;
    border-radius: 0 0 0.75rem 0.75rem;
}

.notification-item-content {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
}

.notification-main {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    color: #111827;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.notification-message {
    color: #4b5563;
    font-size: 0.8rem;
    line-height: 1.4;
    margin-bottom: 0.25rem;
}

.notification-time {
    color: #9ca3af;
    font-size: 0.75rem;
}

.notification-new-badge {
    background: var(--primary-color);
    color: white;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    white-space: nowrap;
}

.text-center {
    text-align: center;
}

.p-3 {
    padding: 1rem;
}

.text-muted {
    color: #9ca3af;
}

/* User Menu */
.user-menu {
    position: relative;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem;
    cursor: pointer;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
}

.user-profile:hover {
    background: #f3f4f6;
}

.user-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--primary-color), #1d4ed8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.user-details {
    display: flex;
    flex-direction: column;
    line-height: 1;
}

.user-name {
    font-weight: 600;
    color: #111827;
    font-size: 0.9rem;
}

.user-role {
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 0.1rem;
}

.user-dropdown {
    position: absolute;
    top: calc(100% + 0.5rem);
    right: 0;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    min-width: 200px;
    z-index: 1000;
    display: none;
    overflow: hidden;
}

.user-dropdown a {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1.25rem;
    color: #374151;
    text-decoration: none;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    border-bottom: 1px solid #f3f4f6;
}

.user-dropdown a:hover {
    background: #f9fafb;
    color: #111827;
}

.user-dropdown a:last-child {
    border-bottom: none;
}

.user-dropdown a.text-danger {
    color: #dc2626;
}

.user-dropdown a.text-danger:hover {
    background: #fef2f2;
    color: #dc2626;
}

.dropdown-divider {
    height: 1px;
    background: #e5e7eb;
    margin: 0;
}

/* Responsive Design */
@media (max-width: 768px) {
    .main-header {
        left: 0;
        padding: 0 1rem;
    }
    
    .header-left {
        gap: 1rem;
    }
    
    .hamburger-header {
        display: flex;
    }
    
    .search-container {
        width: 200px;
    }
    
    .user-details {
        display: none;
    }
    
    .notification-dropdown,
    .user-dropdown {
        max-height: calc(100vh - 100px);
        overflow-y: auto;
    }
}

@media (max-width: 480px) {
    .header-container {
        padding: 0 0.75rem;
    }
    
    .header-left {
        gap: 0.75rem;
    }
    
    .header-right {
        gap: 0.5rem;
    }
    
    .search-container {
        width: 160px;
    }
    
    .search-input {
        padding: 0.5rem 0.75rem 0.5rem 2.5rem;
        font-size: 0.8rem;
    }
    
    .notification-dropdown {
        width: calc(100vw - 2rem);
        right: -1rem;
    }
    
    .user-dropdown {
        right: -1rem;
        min-width: 180px;
    }
}

@media (max-width: 375px) {
    .header-container {
        padding: 0 0.5rem;
    }
    
    .header-left {
        gap: 0.5rem;
    }
    
    .header-right {
        gap: 0.375rem;
    }
    
    .search-container {
        width: 120px;
    }
    
    .search-input {
        padding: 0.375rem 0.5rem 0.375rem 2rem;
        font-size: 0.75rem;
    }
    
    .search-icon {
        left: 0.5rem;
        font-size: 0.875rem;
    }
    
    .notification-btn {
        width: 36px;
        height: 36px;
    }
    
    .user-avatar {
        width: 32px;
        height: 32px;
        font-size: 0.875rem;
    }
    
    .notification-dropdown,
    .user-dropdown {
        width: calc(100vw - 1rem);
        right: -0.5rem;
    }
}
</style>
