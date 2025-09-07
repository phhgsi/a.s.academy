<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'School Management System'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>/assets/css/custom.css">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="<?php echo $school_info['school_name'] ?? 'School Management System'; ?> - Administrative Portal">
</head>
<body class="bg-light">
    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="loading-overlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="wrapper d-flex">
        <!-- Sidebar -->
        <nav class="sidebar bg-dark text-white d-flex flex-column position-fixed h-100" id="sidebar">
            <!-- Logo Section -->
            <div class="sidebar-header p-3 border-bottom border-secondary">
                <div class="d-flex align-items-center">
                    <?php if (!empty($school_info['logo'])): ?>
                        <img src="<?php echo getBaseUrl(); ?>/uploads/images/<?php echo $school_info['logo']; ?>" 
                             alt="School Logo" class="me-2" style="width: 40px; height: 40px; object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-primary rounded me-2 d-flex align-items-center justify-content-center" 
                             style="width: 40px; height: 40px;">
                            <i class="bi bi-building text-white"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($school_info['school_name'] ?? 'SMS'); ?></h6>
                        <small class="text-muted"><?php echo ucfirst($user_role); ?> Panel</small>
                    </div>
                </div>
                <button class="btn btn-link text-white d-md-none position-absolute top-0 end-0 me-2 mt-2" 
                        type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" id="sidebarToggle">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Navigation Menu -->
            <div class="sidebar-menu flex-grow-1 p-3">
                <ul class="nav nav-pills flex-column">
                    <?php foreach ($menu_items as $item): ?>
                        <?php 
                        $isActive = (basename($_SERVER['PHP_SELF']) == basename($item['url']));
                        $currentUrl = $_SERVER['REQUEST_URI'];
                        $itemUrl = '/' . ltrim($item['url'], '/');
                        ?>
                        <li class="nav-item mb-1">
                            <a href="<?php echo getBaseUrl() . '/' . $item['url']; ?>" 
                               class="nav-link text-white <?php echo $isActive ? 'active bg-primary' : ''; ?> d-flex align-items-center">
                                <i class="<?php echo $item['icon']; ?> me-3"></i>
                                <?php echo htmlspecialchars($item['title']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- User Info -->
            <div class="sidebar-footer p-3 border-top border-secondary">
                <div class="d-flex align-items-center mb-2">
                    <div class="bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center" 
                         style="width: 35px; height: 35px;">
                        <span class="text-white fw-bold"><?php echo strtoupper(substr($user_name, 0, 1)); ?></span>
                    </div>
                    <div class="flex-grow-1">
                        <small class="text-white-50">Logged in as:</small>
                        <div class="text-white fw-semibold small"><?php echo htmlspecialchars($user_name); ?></div>
                    </div>
                </div>
                <a href="<?php echo getBaseUrl(); ?>/logout.php" class="btn btn-outline-light btn-sm w-100">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content flex-grow-1">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
                <div class="container-fluid">
                    <button class="btn btn-outline-dark d-md-none me-2" type="button" 
                            data-bs-toggle="offcanvas" data-bs-target="#sidebar">
                        <i class="bi bi-list"></i>
                    </button>
                    
                    <div class="navbar-brand mb-0 h1 d-flex align-items-center">
                        <span class="text-muted me-2"><?php echo $page_title ?? 'Dashboard'; ?></span>
                    </div>

                    <div class="navbar-nav ms-auto d-flex flex-row">
                        <!-- Notifications -->
                        <div class="nav-item dropdown me-2">
                            <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-bell fs-5"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                      id="notificationCount" style="font-size: 0.6rem;">0</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end" style="width: 300px;">
                                <h6 class="dropdown-header">Notifications</h6>
                                <div id="notificationList">
                                    <div class="dropdown-item text-muted text-center">No new notifications</div>
                                </div>
                            </div>
                        </div>

                        <!-- User Profile -->
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                                <div class="bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                     style="width: 32px; height: 32px;">
                                    <span class="text-white fw-bold small"><?php echo strtoupper(substr($user_name, 0, 1)); ?></span>
                                </div>
                                <span class="d-none d-sm-block"><?php echo htmlspecialchars($user_name); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header"><?php echo ucfirst($user_role); ?> Account</h6></li>
                                <li><a class="dropdown-item" href="<?php echo getBaseUrl(); ?>/<?php echo $user_role; ?>/profile.php">
                                    <i class="bi bi-person me-2"></i>My Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo getBaseUrl(); ?>/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Flash Messages -->
            <?php if (hasFlash()): ?>
                <div class="container-fluid mt-3">
                    <?php foreach (getFlash() as $type => $message): ?>
                        <div class="alert alert-<?php echo $type === 'error' ? 'danger' : $type; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Page Content -->
            <div class="container-fluid p-4">
                <?php echo $content; ?>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo getBaseUrl(); ?>/assets/js/app.js"></script>

    <script>
    // Initialize notifications
    document.addEventListener('DOMContentLoaded', function() {
        loadNotifications();
        
        // Auto-refresh notifications every 30 seconds
        setInterval(loadNotifications, 30000);
        
        // Hide loading spinner
        setTimeout(() => {
            document.getElementById('loadingSpinner').style.display = 'none';
        }, 500);
        
        // Initialize DataTables if present
        if (typeof $.fn.dataTable !== 'undefined') {
            $('.data-table').DataTable({
                responsive: true,
                pageLength: 25,
                language: {
                    search: "Search records:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        }
    });

    function loadNotifications() {
        fetch('<?php echo getBaseUrl(); ?>/includes/notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationUI(data.notifications, data.unread_count);
                }
            })
            .catch(error => console.error('Error loading notifications:', error));
    }

    function updateNotificationUI(notifications, unreadCount) {
        const countBadge = document.getElementById('notificationCount');
        const notificationList = document.getElementById('notificationList');
        
        countBadge.textContent = unreadCount;
        countBadge.style.display = unreadCount > 0 ? 'block' : 'none';
        
        if (notifications.length === 0) {
            notificationList.innerHTML = '<div class="dropdown-item text-muted text-center">No new notifications</div>';
        } else {
            let html = '';
            notifications.slice(0, 5).forEach(notification => {
                html += `
                    <div class="dropdown-item ${!notification.is_read ? 'bg-light' : ''}" 
                         onclick="markNotificationRead(${notification.id})">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <div class="fw-semibold small">${notification.message}</div>
                                <small class="text-muted">${new Date(notification.created_at).toLocaleDateString()}</small>
                            </div>
                            ${!notification.is_read ? '<div class="badge bg-primary">New</div>' : ''}
                        </div>
                    </div>
                `;
            });
            
            if (notifications.length > 5) {
                html += '<div class="dropdown-divider"></div>';
                html += '<a class="dropdown-item text-center text-primary" href="notifications.php">View All Notifications</a>';
            }
            
            notificationList.innerHTML = html;
        }
    }

    function markNotificationRead(notificationId) {
        fetch('<?php echo getBaseUrl(); ?>/includes/notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=mark_read&id=' + notificationId
        }).then(() => loadNotifications());
    }
    </script>

    <?php if (isset($additional_js)): ?>
        <?php echo $additional_js; ?>
    <?php endif; ?>
</body>
</html>
